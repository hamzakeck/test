<?php

namespace App\Http\Controllers;

use App\Models\Reclamation;
use App\Models\ReclamationResponse;
use App\Exports\ReclamationsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

/**
 * ReclamationController - Contrôleur pour gérer le système de réclamations
 * 
 * Ce contrôleur s'occupe de toutes les opérations CRUD (Create, Read, Update, Delete) 
 * pour les réclamations, l'intégration avec des APIs externes, la gestion des fichiers
 * et les fonctionnalités d'importation/exportation Excel.
 */
class ReclamationController extends Controller
{
    // Constantes pour la configuration de l'API externe
    // Ces valeurs ne changent jamais pendant l'exécution du programme
    private const API_URL = 'https://reclamation.free.beeceptor.com'; // important!!!!!!!!!!!!!!!!: URL de l'API externe, remplacer par l'URL réelle de l'API !!!!!!!!!!!!!!!
    private const API_TIMEOUT = 30; // Délai d'attente de reponses en secondes pour les requêtes API

    /**
     * Afficher la liste paginée des réclamations avec filtres optionnels
     * 
     * But : Montrer toutes les réclamations dans un tableau avec capacités de recherche et filtrage
     * Objectif final : Permettre aux utilisateurs de naviguer, chercher et filtrer les réclamations
     */
    public function index(Request $request)
    {
        //  construire la requête de base de données pour le modèle Reclamation
        // query() crée un "Query Builder" qui nous permet de construire notre requête SQL étape par étape (Laravel's Eloquent ORM)
        $query = Reclamation::query();

        // Appliquer le filtre par statut d'envoi si fourni dans la requête
        // filled() vérifie si le champ existe ET n'est pas vide
        if ($request->filled('statut_envoi')) {
            // where() ajoute une condition WHERE à notre requête SQL
            // Cela équivaut à : WHERE statut_envoi = 'valeur_du_formulaire'
            $query->where('statut_envoi', $request->statut_envoi);
        }

        // Appliquer le filtre par objet/sujet si fourni
        if ($request->filled('objet')) {
            // Ajouter une autre condition WHERE pour l'objet de la réclamation
            $query->where('objet', $request->objet);
        }

        // Appliquer le filtre par date de réclamation si fourni
        if ($request->filled('date_reclamation')) {
            // whereDate() compare seulement la partie date, ignorant l'heure
            // Cela évite les problèmes avec les heures différentes
            $query->whereDate('date_reclamation', $request->date_reclamation);
        }

        // Appliquer la recherche générale à travers plusieurs champs
        if ($request->filled('search')) {
            // Récupérer le terme de recherche depuis la requête
            $search = $request->search;
            
            // Utiliser une closure (fonction anonyme) pour grouper les conditions OR
            // Cela crée des parenthèses dans la requête SQL : WHERE (condition1 OR condition2 OR condition3)
            $query->where(function($q) use ($search) {
                // Rechercher dans la référence externe avec LIKE pour recherche partielle
                // LIKE "%terme%" trouve le terme n'importe où dans le texte
                $q->where('reference_externe_rec', 'LIKE', "%{$search}%")
                  // OU rechercher dans l'objet
                  ->orWhere('objet', 'LIKE', "%{$search}%")
                  // OU rechercher dans le message
                  ->orWhere('message', 'LIKE', "%{$search}%");
                  //equivalent a WHERE (reference_externe_rec LIKE '%search%' OR objet LIKE '%search%' OR message LIKE '%search%')
            });
        }

        // Obtenir les résultats triés par date de création (plus récent en premier) avec pagination
        // orderBy() trie les résultats par la colonne 'created_at' en ordre décroissant
        // paginate(10) limite les résultats à 10 par page et crée les liens de pagination
        $reclamations = $query->orderBy('created_at', 'desc')->paginate(10);

        // Retourner la vue avec les données des réclamations
        // compact() crée un tableau associatif : ['reclamations' => $reclamations]
        return view('reclamations.index', compact('reclamations'));
    }

    /**
     * Afficher le formulaire de création d'une nouvelle réclamation
     * 
     * But : Afficher le formulaire de création
     * Objectif final : Permettre aux utilisateurs d'accéder au formulaire pour créer de nouvelles réclamations
     */
    public function create()
    {
        // Simplement retourner la vue du formulaire de création
        // Pas de logique complexe ici, juste afficher le formulaire vide
        return view('reclamations.create');
    }

    /**
     * Enregistrer une nouvelle réclamation dans la base de données
     * 
     * But : Traiter les données du formulaire, valider, sauvegarder et envoyer à l'API
     * Objectif final : Créer une nouvelle réclamation complète avec tous ses détails
     */
    public function store(Request $request)
    {
        // Valider les données reçues du formulaire
        // Cette méthode privée vérifie que toutes les données sont correctes
        $validated = $this->validateReclamation($request);

        // Utiliser un bloc try-catch pour gérer les erreurs possibles
        try {
            // Gérer l'upload du fichier joint
            $documentPath = null; // Initialiser à null par défaut
            
            // Vérifier si un fichier a été uploadé
            if ($request->hasFile('document')) {
                // store() sauvegarde le fichier dans storage/app/public/reclamations
                // et retourne le chemin relatif du fichier
                $documentPath = $request->file('document')->store('reclamations', 'public');
            }

            // Créer la réclamation dans la base de données
            // array_merge() combine les données validées avec les données supplémentaires
            $reclamation = Reclamation::create(array_merge($validated, [
                'piece_jointe_path' => $documentPath, // Chemin du fichier uploadé
                'reference_externe_rec' => $this->generateExternalReference(), // Référence unique générée
                'statut_envoi' => 'non_envoyé', // Statut initial
                'statut_traitement' => 'Nouvelle réclamation', // Statut de traitement initial
            ]));

            // Envoyer la réclamation à l'API externe
            $this->sendToApi($reclamation);

            // Rediriger vers la liste avec un message de succès
            // with() ajoute un message flash qui sera affiché une seule fois
            return redirect()->route('reclamations.index')
                ->with('success', 'Réclamation créée et envoyée avec succès.');

        } catch (\Exception $e) {
            // Si une erreur survient, l'enregistrer dans les logs
            Log::error('Error creating reclamation: ' . $e->getMessage());
            
            // Retourner à la page précédente avec les données saisies et un message d'erreur
            // withInput() garde les données du formulaire pour que l'utilisateur n'ait pas à tout retaper
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Afficher le formulaire d'édition d'une réclamation existante
     * 
     * But : Charger les données d'une réclamation et afficher le formulaire pré-rempli
     * Objectif final : Permettre la modification d'une réclamation existante
     */
    public function edit($id)
    {
        // Trouver la réclamation par son ID ou lancer une exception 404 si non trouvée
        $rec = Reclamation::findOrFail($id);
        // findOrFail() cherche l'enregistrement et lance une erreur 404 si pas trouvé
        // Retourner la vue d'édition avec les données de la réclamation
        return view('reclamations.edit', compact('rec'));
    }

    /**
     * Mettre à jour une réclamation existante
     * 
     * But : Traiter les modifications du formulaire et sauvegarder les changements
     * Objectif final : Modifier une réclamation existante avec les nouvelles données
     */
    public function update(Request $request, $id)
    {
        // Trouver la réclamation à modifier
        $reclamation = Reclamation::findOrFail($id);
        
        // Valider les nouvelles données
        $validated = $this->validateReclamation($request);

        try {
            // Gérer le remplacement du fichier joint si un nouveau fichier est uploadé
            if ($request->hasFile('document')) {
                // Supprimer l'ancien fichier s'il existe
                if ($reclamation->piece_jointe_path && Storage::disk('public')->exists($reclamation->piece_jointe_path)) {
                    // delete() supprime physiquement le fichier du disque
                    Storage::disk('public')->delete($reclamation->piece_jointe_path);
                }
                
                // Sauvegarder le nouveau fichier et mettre à jour le chemin
                $validated['piece_jointe_path'] = $request->file('document')->store('reclamations', 'public');
            }

            // Mettre à jour la réclamation avec les nouvelles données
            // update() modifie l'enregistrement existant dans la base de données
            $reclamation->update($validated);

            // Rediriger avec un message de succès
            return redirect()->route('reclamations.index')
                ->with('success', 'Réclamation mise à jour avec succès.');

        } catch (\Exception $e) {
            // Gérer les erreurs de mise à jour
            Log::error('Error updating reclamation: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer une réclamation
     * 
     * But : Supprimer définitivement une réclamation et son fichier associé
     * Objectif final : Nettoyer complètement une réclamation de la base de données et du stockage
     */
    public function destroy($id)
    {
        try {
            // Trouver la réclamation à supprimer
            $reclamation = Reclamation::findOrFail($id);
            
            // Supprimer le fichier associé s'il existe
            if ($reclamation->piece_jointe_path && Storage::disk('public')->exists($reclamation->piece_jointe_path)) {
                Storage::disk('public')->delete($reclamation->piece_jointe_path);
            }

            // Supprimer l'enregistrement de la base de données
            // delete() supprime définitivement l'enregistrement
            $reclamation->delete();

            // Rediriger avec un message de succès
            return redirect()->route('reclamations.index')
                ->with('success', 'Réclamation supprimée avec succès.');

        } catch (\Exception $e) {
            // Gérer les erreurs de suppression
            Log::error('Error deleting reclamation: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Renvoyer une réclamation individuelle à l'API externe
     * 
     * But : Tenter de renvoyer une réclamation qui avait échoué précédemment
     * Objectif final : Récupérer les réclamations non envoyées en les renvoyant à l'API
     */
    public function retry($id)
    {
        try {
            // Trouver la réclamation à renvoyer
            $reclamation = Reclamation::findOrFail($id);
            
            // Tenter de l'envoyer à l'API
            $this->sendToApi($reclamation);

            // Préparer le message de retour selon le résultat
            // Vérifier si l'envoi a réussi en consultant le statut mis à jour
            $message = $reclamation->statut_envoi === 'envoyé' 
                ? 'Réclamation renvoyée avec succès.'
                : 'Erreur lors du renvoi : ' . $reclamation->api_message_retour;

            // Déterminer le type de message (succès ou erreur)
            $type = $reclamation->statut_envoi === 'envoyé' ? 'success' : 'error';

            // Rediriger avec le message approprié
            return redirect()->route('reclamations.index')->with($type, $message);

        } catch (\Exception $e) {
            // Gérer les erreurs techniques
            Log::error('Error retrying reclamation: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur technique : ' . $e->getMessage());
        }
    }

    /**
     * Renvoyer toutes les réclamations non envoyées à l'API externe
     * 
     * But : Traiter en lot toutes les réclamations qui ont échoué
     * Objectif final : Récupérer massivement toutes les réclamations en échec
     */
    public function retryAll()
    {
        // Récupérer toutes les réclamations avec le statut 'non_envoyé'
        $failedReclamations = Reclamation::where('statut_envoi', 'non_envoyé')->get();

        // Vérifier s'il y a des réclamations à traiter
        if ($failedReclamations->isEmpty()) {
            return redirect()->route('reclamations.index')
                ->with('info', 'Aucune réclamation à renvoyer.');
        }

        // Initialiser les compteurs pour le rapport final
        $successCount = 0;
        $errorCount = 0;
        $errors = []; // Tableau pour stocker les messages d'erreur

        // Traiter chaque réclamation échouée
        foreach ($failedReclamations as $reclamation) {
            try {
                // Tenter d'envoyer la réclamation à l'API
                $this->sendToApi($reclamation);
                
                // Vérifier le résultat en rechargeant les données depuis la base
                // fresh() recharge l'objet avec les données les plus récentes de la base
                if ($reclamation->fresh()->statut_envoi === 'envoyé') {
                    $successCount++; // Incrémenter le compteur de succès
                } else {
                    $errorCount++; // Incrémenter le compteur d'erreurs
                    // Ajouter le message d'erreur au tableau
                    $errors[] = "#{$reclamation->id}: " . $reclamation->api_message_retour;
                }

            } catch (\Exception $e) {
                // Gérer les erreurs d'envoi
                $errorCount++;
                $errors[] = "#{$reclamation->id}: " . $e->getMessage();
                Log::error("Error retrying reclamation {$reclamation->id}: " . $e->getMessage());
            }
        }

        // Construire le message de rapport final
        $message = "Résultats: {$successCount} envoyées, {$errorCount} échouées.";
        
        // Ajouter les détails des erreurs si peu nombreuses (pour éviter un message trop long)
        if ($errorCount > 0 && count($errors) <= 3) {
            $message .= " Erreurs: " . implode('; ', $errors);
        }

        // Déterminer le type de message selon les résultats
        $type = $errorCount > 0 ? ($successCount > 0 ? 'warning' : 'error') : 'success';

        // Rediriger avec le rapport final
        return redirect()->route('reclamations.index')->with($type, $message);
    }

    /**
     * Importer des réclamations depuis un fichier Excel
     * 
     * But : Permettre l'import en masse de réclamations depuis un fichier Excel/CSV
     * Objectif final : Traiter un fichier Excel et créer automatiquement toutes les réclamations
     */
    public function import(Request $request)
    {
        // Valider le fichier uploadé
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:2048', // Fichier obligatoire, format Excel/CSV, max 2MB
        ]);

        try {
            // Obtenir le chemin physique du fichier uploadé
            $path = $request->file('file')->getRealPath();
            
            // Lire le fichier Excel et convertir en tableau
            // toArray() convertit le fichier Excel en tableau PHP
            // [0] prend la première feuille du fichier Excel
            $rows = Excel::toArray([], $path)[0];

            // Vérifier que le fichier n'est pas vide
            if (empty($rows)) {
                return redirect()->back()->with('error', 'Le fichier est vide.');
            }

            // Mapping des en-têtes du fichier Excel vers les colonnes de la base de données
            // Cela permet de faire correspondre les noms de colonnes Excel avec les champs de la base
            $headerMap = [
                'source de la requête' => 'source_requete',
                'date' => 'date_reclamation',
                'réf dossier/ ref demande' => 'reference_demande',
                'cnie' => 'cnie',
                'nom et prénom' => 'nom_prenom',
                'ville' => 'ville',
                'canal de réclamation' => 'canal',
                'objets des réclamations' => 'objet',
                'message' => 'message',
                'remarque matnuhpv' => 'remarque_matnuhpv',
                'response' => 'response',
            ];

            // Traiter la première ligne (en-têtes) du fichier Excel
            $headers = array_map(function($key) use ($headerMap) {
                // Normaliser la clé (minuscules, espaces supprimés)
                $normalizedKey = strtolower(trim($key));
                // Retourner la clé mappée ou null si pas trouvée
                return $headerMap[$normalizedKey] ?? null;
            }, $rows[0]);

            // Supprimer la ligne d'en-tête du tableau de données
            unset($rows[0]);

            // Initialiser les compteurs pour le rapport d'import
            $imported = 0;
            $errors = [];

            // Traiter chaque ligne de données
            foreach ($rows as $index => $row) {
                // Calculer le numéro de ligne réel (en tenant compte de l'en-tête)
                $rowNumber = $index + 2;

                // Ignorer les lignes vides (toutes les cellules sont vides)
                if (empty(array_filter($row))) continue;

                // Créer un tableau associatif en combinant les en-têtes avec les données
                $data = array_combine($headers, $row);
                
                // Filtrer les données pour garder seulement les champs valides
                $data = collect($data)->filter(function($value, $key) {
                    // Garder le champ si la clé n'est pas null ET si la valeur n'est pas null
                    // OU si c'est un champ qui peut être null (message, remarque)
                    return $key !== null && ($value !== null || in_array($key, ['message', 'remarque_matnuhpv']));
                })->all();

                // Valider les données de la réclamation
                $validator = Validator::make($data, [
                    'source_requete' => 'required|string|max:255',
                    'date_reclamation' => 'required|date_format:d/m/Y', // Format jour/mois/année
                    'reference_demande' => 'required|string|max:255',
                    'cnie' => 'nullable|string|max:255',
                    'nom_prenom' => 'nullable|string|max:255',
                    'ville' => 'nullable|string|max:255',
                    'canal' => 'required|string|max:255',
                    'objet' => 'required|in:ANNULATION,DOCUMENT,ELIGIBILITE,INFORMATION,MAJ,PAIEMENT,RESTITUTION',
                    'message' => 'nullable|string',
                    'remarque_matnuhpv' => 'nullable|string',
                    'response' => 'nullable|string',
                ]);

                // Si la validation échoue, ajouter l'erreur et passer à la ligne suivante
                if ($validator->fails()) {
                    $errors[] = "Ligne {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                try {
                    // Préparer les données validées pour la création
                    $reclamationData = $validator->validated();
                    
                    // Convertir la date du format d/m/Y vers Y-m-d (format base de données)
                    $reclamationData['date_reclamation'] = Carbon::createFromFormat('d/m/Y', $data['date_reclamation'])->format('Y-m-d');
                    
                    // Définir un message par défaut si vide
                    $reclamationData['message'] = $reclamationData['message'] ?? 'Importé via Excel';
                    
                    // Générer une référence externe unique
                    $reclamationData['reference_externe_rec'] = $this->generateExternalReference();
                    
                    // Définir les statuts initiaux
                    $reclamationData['statut_envoi'] = 'non_envoyé';
                    $reclamationData['statut_traitement'] = 'Nouvelle réclamation';

                    // Créer la réclamation dans la base de données
                    $reclamation = Reclamation::create($reclamationData);

                    // Traiter les réponses associées s'il y en a
                    if (!empty($data['response'])) {
                        // Diviser le texte des réponses sur les retours à la ligne et puces
                        $responses = preg_split('/[\n\r•●-]+/', $data['response']);

                        // Créer chaque réponse individuellement
                        foreach ($responses as $responseText) {
                            $responseText = trim($responseText); // Supprimer les espaces
                            
                            // Ignorer les réponses vides
                            if ($responseText !== '') {
                                ReclamationResponse::create([
                                    'reclamation_id' => $reclamation->id,
                                    'reponse' => $responseText,
                                    'etat' => 'importé',
                                    'type_operation' => 'import',
                                    'date_reponse' => now(), // Date et heure actuelles
                                ]);
                            }
                        }
                    }

                    // Envoyer la réclamation à l'API externe
                    $this->sendToApi($reclamation);
                    
                    // Incrémenter le compteur d'import réussi
                    $imported++;

                } catch (\Exception $e) {
                    // Ajouter l'erreur au rapport et continuer avec la ligne suivante
                    $errors[] = "Ligne {$rowNumber}: " . $e->getMessage();
                    Log::error("Import error on row {$rowNumber}: " . $e->getMessage());
                }
            }

            // Construire le message de rapport final
            $message = "{$imported} réclamations importées avec succès.";
            
            // Ajouter les erreurs au message (max 5 pour éviter un message trop long)
            if (!empty($errors)) {
                $message .= " Erreurs: " . implode(' | ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " (et " . (count($errors) - 5) . " autres erreurs)";
                }
            }

            // Rediriger avec le rapport d'import
            return redirect()->route('reclamations.index')
                ->with($imported > 0 ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            // Gérer les erreurs générales d'import
            Log::error('Import error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
        }
    }

    /**
     * Exporter les réclamations vers un fichier Excel
     * 
     * But : Permettre le téléchargement des réclamations dans un fichier Excel
     * Objectif final : Donner aux utilisateurs un moyen d'exporter leurs données
     */
    public function export(Request $request)
    {
        try {
            // Récupérer les colonnes sélectionnées pour l'export (ou toutes par défaut)
            $columns = $request->input('columns', []);
            
            // Récupérer tous les filtres appliqués
            $filters = $request->all();

            // Générer et télécharger le fichier Excel
            // Excel::download() crée le fichier et force le téléchargement
            return Excel::download(new ReclamationsExport($filters, $columns), 'reclamations.xlsx');

        } catch (\Exception $e) {
            // Gérer les erreurs d'export
            Log::error('Export error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export : ' . $e->getMessage());
        }
    }

    /**
     * Méthode privée pour valider les données d'une réclamation
     * 
     * But : Centraliser la validation des données pour éviter la répétition de code
     * Objectif final : Assurer que toutes les données sont correctes avant sauvegarde
     */
    private function validateReclamation(Request $request): array
    {
        // validate() vérifie les données selon les règles définies
        // Si la validation échoue, Laravel redirige automatiquement avec les erreurs
        return $request->validate([
            'source_requete' => 'required|string|max:255', // Obligatoire, texte, max 255 caractères
            'date_reclamation' => 'required|date', // Obligatoire, format date valide
            'reference_demande' => 'required|string|max:255',
            'cnie' => 'nullable|string|max:255', // Optionnel
            'nom_prenom' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'canal' => 'required|string|max:255',
            'objet' => 'required|in:ANNULATION,DOCUMENT,ELIGIBILITE,INFORMATION,MAJ,PAIEMENT,RESTITUTION', // Doit être une des valeurs listées
            'message' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Fichier optionnel, formats spécifiques, max 10MB
            'remarque_matnuhpv' => 'nullable|string',
        ]);
    }

    /**
     * Méthode privée pour générer une référence externe unique
     * 
     * But : Créer un identifiant unique pour chaque réclamation
     * Objectif final : Avoir une référence traçable pour chaque réclamation
     */
    private function generateExternalReference(): string
    {
        // Construire la référence avec :
        // - Préfixe "EXT-"
        // - Date et heure actuelle au format YmdHis (AnnéeMoisJourHeureMinuteSeconde)
        // - Nombre aléatoire entre 1000 et 9999
        return 'EXT-' . now()->format('YmdHis') . '-' . rand(1000, 9999);
    }

    /**
     * Méthode privée pour envoyer une réclamation à l'API externe
     * 
     * But : Communiquer avec l'API externe pour transmettre la réclamation
     * Objectif final : Synchroniser les réclamations avec le système externe
     */
    private function sendToApi(Reclamation $reclamation): void
    {
        try {
            // Préparer les données à envoyer en format multipart (pour les fichiers)
            $multipartData = [
                ['name' => 'referenceExterneRec', 'contents' => $reclamation->reference_externe_rec],
                ['name' => 'dateReclamation', 'contents' => $reclamation->date_reclamation],
                ['name' => 'identifiantNotaire', 'contents' => $reclamation->identifiant_notaire ?? ''],
                ['name' => 'referenceDemande', 'contents' => $reclamation->reference_demande],
                ['name' => 'objet', 'contents' => $reclamation->objet],
                ['name' => 'message', 'contents' => $reclamation->message],
            ];

            // Ajouter le document s'il existe
            if ($reclamation->piece_jointe_path && Storage::disk('public')->exists($reclamation->piece_jointe_path)) {
                // Construire le chemin physique complet du fichier
                $filePath = storage_path('app/public/' . $reclamation->piece_jointe_path);
                
                // Vérifier que le fichier existe et est lisible
                if (file_exists($filePath) && is_readable($filePath)) {
                    // Ajouter le fichier aux données multipart
                    $multipartData[] = [
                        'name' => 'document', // Nom du champ dans l'API
                        'contents' => file_get_contents($filePath), // Contenu binaire du fichier
                        'filename' => basename($reclamation->piece_jointe_path), // Nom du fichier
                    ];
                }
            }

            // Envoyer la requête HTTP POST à l'API
            $response = Http::timeout(self::API_TIMEOUT) // Définir le timeout
                ->asMultipart() // Format multipart pour les fichiers
                ->post(self::API_URL, $multipartData); // Envoyer les données

            // Vérifier si la requête a réussi (code HTTP 200-299)
            if (!$response->successful()) {
                // Lancer une exception si la requête a échoué
                throw new \Exception("API returned status: " . $response->status());
            }

            // Décoder la réponse JSON de l'API
            $responseData = $response->json();
            
            // Valider que la réponse est un tableau (format attendu)
            if (!is_array($responseData)) {
                throw new \Exception("Invalid API response format");
            }

            // Extraire les données importantes de la réponse
            $codeRetour = $responseData['codeRetour'] ?? null; // Code de retour de l'API
            $messageRetour = $responseData['messageRetour'] ?? 'No message'; // Message de l'API
            $referenceReclamation = $responseData['referenceReclamation'] ?? null; // Référence générée par l'API
            $apiReferenceDemande = $responseData['referenceDemande'] ?? null; // Référence de demande retournée

            // Déterminer si l'envoi a réussi
            // Succès si code 200 ET référence de réclamation présente
            $isSuccess = ($codeRetour === '200' || $codeRetour === 200) && $referenceReclamation;

            // Validation supplémentaire pour les réponses réussies
            if ($isSuccess && $apiReferenceDemande && $apiReferenceDemande !== $reclamation->reference_demande) {
                // Si la référence de demande ne correspond pas, considérer comme échec
                $isSuccess = false;
                $messageRetour = 'La référence de demande ne correspond pas.';
            }

            // Mettre à jour la réclamation avec les résultats de l'API
            $reclamation->update([
                'statut_envoi' => $isSuccess ? 'envoyé' : 'non_envoyé', // Statut selon le succès
                'reference_reclamation' => $referenceReclamation, // Référence de l'API
                'api_reference_demande' => $apiReferenceDemande, // Référence de demande de l'API
                'api_message_retour' => $messageRetour, // Message de l'API
                'api_full_response' => $response->body(), // Réponse complète pour debug
            ]);

            // Si l'envoi a échoué, lancer une exception
            if (!$isSuccess) {
                throw new \Exception($messageRetour);
            }

        } catch (\Exception $e) {
            // En cas d'erreur, mettre à jour la réclamation avec l'erreur
            $reclamation->update([
                'statut_envoi' => 'non_envoyé', // Marquer comme non envoyé
                'api_message_retour' => 'Erreur: ' . $e->getMessage(), // Message d'erreur
            ]);

            // Relancer l'exception pour que la méthode appelante puisse la gérer
            throw $e;
        }
    }

    /**
     * Récupérer les réponses d'une réclamation depuis l'API externe
     * 
     * But : Interroger l'API pour obtenir les réponses/mises à jour d'une réclamation
     * Objectif final : Synchroniser les réponses du système externe avec notre base de données
     */
   public function fetchReclamationResponses($id)
{
    // Récupérer la réclamation via son identifiant ou échouer si elle n'existe pas
    $reclamation = Reclamation::findOrFail($id);

    try {
        // Construire la charge utile de la requête API
        $requestBody = [
            'dateDebut' => now()->subDays(5)->format('Ymd'), // Date de début : il y a 5 jours (au format YYYYMMDD)
            'dateFin' => now()->format('Ymd'),               // Date de fin : aujourd’hui (au format YYYYMMDD)
            'table' => 'RECLAMATION',                        // Nom de la table cible dans l’API
            'idLot' => now()->format('His'),                 // ID du lot : heure actuelle (HHMMSS)
            'dateLot' => now()->format('Ymd'),               // Date du lot : aujourd’hui (YYYYMMDD)
        ];

        // Envoyer la requête POST à l’API avec un délai d’attente de 30 secondes
        $response = Http::timeout(30)->post(self::API_URL, $requestBody);

        // Vérifier si la réponse est réussie
        if (!$response->successful()) {
            throw new \Exception("API status: " . $response->status());
        }

        // Extraire les données du champ 'data' (ou tableau vide par défaut)
        $data = $response->json('data', []);

        // Vérifier que la réponse contient des données valides
        if (!is_array($data) || empty($data)) {
            return redirect()->back()->with('warning', 'Aucune donnée reçue de l’API.');
        }

        // Chercher l’élément correspondant à la référence de demande
        $matched = collect($data)->firstWhere('referenceDemande', $reclamation->reference_demande);

        // Si aucune correspondance trouvée, informer l'utilisateur
        if (!$matched) {
            return redirect()->back()->with('warning', 'Aucune réponse trouvée pour cette réclamation.');
        }

        // Récupérer et formater l’état de la réclamation
        $etat = strtoupper($matched['etat'] ?? '');

        // Mettre à jour les champs de la réclamation dans la base locale
        $reclamation->update([
            'statut_traitement' => $etat === 'TREATED' ? 'Traité' : 'En cours de traitement',
            'api_full_response' => json_encode($matched, JSON_UNESCAPED_UNICODE), // Stockage brut de la réponse API
        ]);

        // Initialiser un compteur de nouvelles réponses
        $newResponses = 0;

        // Parcourir les réponses associées à la réclamation
        foreach ($matched['reponseReclamation'] ?? [] as $resp) {
            // Vérifier si un ID est défini et si la réponse n'existe pas déjà
            if (!isset($resp['id']) || ReclamationResponse::where('api_id', $resp['id'])->exists()) {
                continue;
            }

            // Créer une nouvelle entrée dans la table des réponses
            ReclamationResponse::create([
                'reclamation_id' => $reclamation->id,
                'api_id' => $resp['id'],
                'date_reponse' => Carbon::createFromFormat('d/m/Y H:i:s', $resp['dateReponse']),
                'etat' => $resp['etat'] ?? '',
                'reponse' => $resp['reponse'] ?? '',
                'type_operation' => $resp['typeOperation'] ?? '',
            ]);

            $newResponses++;
        }

        // Rediriger avec un message de succès
        return redirect()->back()->with('success', "Réclamation mise à jour. $newResponses réponse(s) ajoutée(s).");

    } catch (\Exception $e) {
        // Enregistrer l’erreur pour le débogage
        Log::error("Erreur lors de la récupération de réponse : " . $e->getMessage());

        // Rediriger avec un message d’erreur utilisateur
        return redirect()->back()->with('error', "Erreur API : " . $e->getMessage());
    }
}
}