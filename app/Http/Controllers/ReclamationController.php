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

class ReclamationController extends Controller
{
    private const API_URL = 'https://reclamation.free.beeceptor.com';
    private const API_TIMEOUT = 30;



    public function index(Request $request)
    {
        $query = Reclamation::query();

        // Apply filters
        if ($request->filled('statut_envoi')) {
            $query->where('statut_envoi', $request->statut_envoi);
        }

        if ($request->filled('objet')) {
            $query->where('objet', $request->objet);
        }

        if ($request->filled('date_reclamation')) {
            $query->whereDate('date_reclamation', $request->date_reclamation);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_externe_rec', 'LIKE', "%{$search}%")
                  ->orWhere('objet', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        $reclamations = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('reclamations.index', compact('reclamations'));
    }

    public function create()
    {
        return view('reclamations.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateReclamation($request);

        try {
            // Handle file upload
            $documentPath = null;
            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('reclamations', 'public');
            }

            // Create reclamation
            $reclamation = Reclamation::create(array_merge($validated, [
                'piece_jointe_path' => $documentPath,
                'reference_externe_rec' => $this->generateExternalReference(),
                'statut_envoi' => 'non_envoyé',
                'statut_traitement' => 'Nouvelle réclamation',
            ]));

            // Send to API
            $this->sendToApi($reclamation);

            return redirect()->route('reclamations.index')
                ->with('success', 'Réclamation créée et envoyée avec succès.');

        } catch (\Exception $e) {
            Log::error('Error creating reclamation: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

   public function edit($id)
{
    $rec = Reclamation::findOrFail($id);
    return view('reclamations.edit', compact('rec'));
}


    public function update(Request $request, $id)
    {
        $reclamation = Reclamation::findOrFail($id);
        $validated = $this->validateReclamation($request);

        try {
            // Handle file upload
            if ($request->hasFile('document')) {
                // Delete old file
                if ($reclamation->piece_jointe_path && Storage::disk('public')->exists($reclamation->piece_jointe_path)) {
                    Storage::disk('public')->delete($reclamation->piece_jointe_path);
                }
                $validated['piece_jointe_path'] = $request->file('document')->store('reclamations', 'public');
            }

            $reclamation->update($validated);

            return redirect()->route('reclamations.index')
                ->with('success', 'Réclamation mise à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Error updating reclamation: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $reclamation = Reclamation::findOrFail($id);
            
            // Delete file if exists
            if ($reclamation->piece_jointe_path && Storage::disk('public')->exists($reclamation->piece_jointe_path)) {
                Storage::disk('public')->delete($reclamation->piece_jointe_path);
            }

            $reclamation->delete();

            return redirect()->route('reclamations.index')
                ->with('success', 'Réclamation supprimée avec succès.');

        } catch (\Exception $e) {
            Log::error('Error deleting reclamation: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    public function retry($id)
    {
        try {
            $reclamation = Reclamation::findOrFail($id);
            $this->sendToApi($reclamation);

            $message = $reclamation->statut_envoi === 'envoyé' 
                ? 'Réclamation renvoyée avec succès.'
                : 'Erreur lors du renvoi : ' . $reclamation->api_message_retour;

            $type = $reclamation->statut_envoi === 'envoyé' ? 'success' : 'error';

            return redirect()->route('reclamations.index')->with($type, $message);

        } catch (\Exception $e) {
            Log::error('Error retrying reclamation: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur technique : ' . $e->getMessage());
        }
    }

    public function retryAll()
    {
        $failedReclamations = Reclamation::where('statut_envoi', 'non_envoyé')->get();

        if ($failedReclamations->isEmpty()) {
            return redirect()->route('reclamations.index')
                ->with('info', 'Aucune réclamation à renvoyer.');
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($failedReclamations as $reclamation) {
            try {
                $this->sendToApi($reclamation);
                
                if ($reclamation->fresh()->statut_envoi === 'envoyé') {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "#{$reclamation->id}: " . $reclamation->api_message_retour;
                }

            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "#{$reclamation->id}: " . $e->getMessage();
                Log::error("Error retrying reclamation {$reclamation->id}: " . $e->getMessage());
            }
        }

        $message = "Résultats: {$successCount} envoyées, {$errorCount} échouées.";
        if ($errorCount > 0 && count($errors) <= 3) {
            $message .= " Erreurs: " . implode('; ', $errors);
        }

        $type = $errorCount > 0 ? ($successCount > 0 ? 'warning' : 'error') : 'success';

        return redirect()->route('reclamations.index')->with($type, $message);
    }

    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,csv|max:2048',
    ]);

    try {
        $path = $request->file('file')->getRealPath();
        $rows = Excel::toArray([], $path)[0];

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Le fichier est vide.');
        }

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

        $headers = array_map(function($key) use ($headerMap) {
            $normalizedKey = strtolower(trim($key));
            return $headerMap[$normalizedKey] ?? null;
        }, $rows[0]);

        unset($rows[0]); // remove header row

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row))) continue;

            $data = array_combine($headers, $row);
            $data = collect($data)->filter(function($value, $key) {
    return $key !== null && ($value !== null || in_array($key, ['message', 'remarque_matnuhpv']));
})->all();


            // Validate reclamation fields
            $validator = Validator::make($data, [
                'source_requete' => 'required|string|max:255',
                'date_reclamation' => 'required|date_format:d/m/Y',
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

            if ($validator->fails()) {
                $errors[] = "Ligne {$rowNumber}: " . implode(', ', $validator->errors()->all());
                continue;
            }

            try {
                $reclamationData = $validator->validated();
                $reclamationData['date_reclamation'] = Carbon::createFromFormat('d/m/Y', $data['date_reclamation'])->format('Y-m-d');
                $reclamationData['message'] = $reclamationData['message'] ?? 'Importé via Excel';
                $reclamationData['reference_externe_rec'] = $this->generateExternalReference();
                $reclamationData['statut_envoi'] = 'non_envoyé';
                $reclamationData['statut_traitement'] = 'Nouvelle réclamation';

                $reclamation = Reclamation::create($reclamationData);

                // Save associated responses if any
                if (!empty($data['response'])) {
                    $responses = preg_split('/[\n\r•●-]+/', $data['response']);

                    foreach ($responses as $responseText) {
                        $responseText = trim($responseText);
                        if ($responseText !== '') {
                            ReclamationResponse::create([
                                'reclamation_id' => $reclamation->id,
                                'reponse' => $responseText,
                                'etat' => 'importé',
                                'type_operation' => 'import',
                                'date_reponse' => now(),
                            ]);
                        }
                    }
                }

                $this->sendToApi($reclamation);
                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Ligne {$rowNumber}: " . $e->getMessage();
                Log::error("Import error on row {$rowNumber}: " . $e->getMessage());
            }
        }

        $message = "{$imported} réclamations importées avec succès.";
        if (!empty($errors)) {
            $message .= " Erreurs: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " (et " . (count($errors) - 5) . " autres erreurs)";
            }
        }

        return redirect()->route('reclamations.index')
            ->with($imported > 0 ? 'success' : 'warning', $message);

    } catch (\Exception $e) {
        Log::error('Import error: ' . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
    }
}


    public function export(Request $request)
    {
        try {
            $columns = $request->input('columns', []);
            $filters = $request->all();

            return Excel::download(new ReclamationsExport($filters, $columns), 'reclamations.xlsx');

        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export : ' . $e->getMessage());
        }
    }

    /**
     * Validate reclamation data
     */
    private function validateReclamation(Request $request): array
    {
        return $request->validate([
            'source_requete' => 'required|string|max:255',
            'date_reclamation' => 'required|date',
            'reference_demande' => 'required|string|max:255',
            'cnie' => 'nullable|string|max:255',
            'nom_prenom' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'canal' => 'required|string|max:255',
            'objet' => 'required|in:ANNULATION,DOCUMENT,ELIGIBILITE,INFORMATION,MAJ,PAIEMENT,RESTITUTION',
            'message' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'remarque_matnuhpv' => 'nullable|string',
        ]);
    }

    /**
     * Generate unique external reference
     */
    private function generateExternalReference(): string
    {
        return 'EXT-' . now()->format('YmdHis') . '-' . rand(1000, 9999);
    }

    /**
     * Send reclamation to external API
     */
    private function sendToApi(Reclamation $reclamation): void
    {
        
        try {
            $multipartData = [
                ['name' => 'referenceExterneRec', 'contents' => $reclamation->reference_externe_rec],
                ['name' => 'dateReclamation', 'contents' => $reclamation->date_reclamation],
                ['name' => 'identifiantNotaire', 'contents' => $reclamation->identifiant_notaire ?? ''],
                ['name' => 'referenceDemande', 'contents' => $reclamation->reference_demande],
                ['name' => 'objet', 'contents' => $reclamation->objet],
                ['name' => 'message', 'contents' => $reclamation->message],
            ];

            // Add document if exists
            if ($reclamation->piece_jointe_path && Storage::disk('public')->exists($reclamation->piece_jointe_path)) {
                $filePath = storage_path('app/public/' . $reclamation->piece_jointe_path);
                
                if (file_exists($filePath) && is_readable($filePath)) {
                    $multipartData[] = [
                        'name' => 'document',
                        'contents' => file_get_contents($filePath),
                        'filename' => basename($reclamation->piece_jointe_path),
                    ];
                }
            }

            // Send HTTP request
            $response = Http::timeout(self::API_TIMEOUT)
                ->asMultipart()
                ->post(self::API_URL, $multipartData);

            if (!$response->successful()) {
                throw new \Exception("API returned status: " . $response->status());
            }

            $responseData = $response->json();
            
            // Validate response structure
            if (!is_array($responseData)) {
                throw new \Exception("Invalid API response format");
            }

            $codeRetour = $responseData['codeRetour'] ?? null;
            $messageRetour = $responseData['messageRetour'] ?? 'No message';
            $referenceReclamation = $responseData['referenceReclamation'] ?? null;
            $apiReferenceDemande = $responseData['referenceDemande'] ?? null;

            // Determine success
            $isSuccess = ($codeRetour === '200' || $codeRetour === 200) && $referenceReclamation;

            // Additional validation for successful responses
            if ($isSuccess && $apiReferenceDemande && $apiReferenceDemande !== $reclamation->reference_demande) {
                $isSuccess = false;
                $messageRetour = 'La référence de demande ne correspond pas.';
            }

            // Update reclamation
            $reclamation->update([
                'statut_envoi' => $isSuccess ? 'envoyé' : 'non_envoyé',
                'reference_reclamation' => $referenceReclamation,
                'api_reference_demande' => $apiReferenceDemande,
                'api_message_retour' => $messageRetour,
                'api_full_response' => $response->body(),
            ]);

            if (!$isSuccess) {
                throw new \Exception($messageRetour);
            }

        } catch (\Exception $e) {
            // Update reclamation with error
            $reclamation->update([
                'statut_envoi' => 'non_envoyé',
                'api_message_retour' => 'Erreur: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }
   public function fetchReclamationResponses($id)
{
    $reclamation = Reclamation::findOrFail($id);

    try {
        // Build request payload
        $requestBody = [
            'dateDebut' => now()->subDays(5)->format('Ymd'),
            'dateFin' => now()->format('Ymd'),
            'table' => 'RECLAMATION',
            'idLot' => now()->format('His'),
            'dateLot' => now()->format('Ymd'),
        ];

        $response = Http::timeout(30)->post(self::API_URL, $requestBody);

        if (!$response->successful()) {
            throw new \Exception("API status: " . $response->status());
        }

        $data = $response->json('data', []);

        if (!is_array($data) || empty($data)) {
            return redirect()->back()->with('warning', 'Aucune donnée reçue de l’API.');
        }

        // Match correct record based on referenceDemande
        $matched = collect($data)->firstWhere('referenceDemande', $reclamation->reference_demande);

        if (!$matched) {
            return redirect()->back()->with('warning', 'Aucune réponse trouvée pour cette réclamation.');
        }

        // Update Reclamation record
        $etat = strtoupper($matched['etat'] ?? '');
        $reclamation->update([
            'statut_traitement' => $etat === 'TREATED' ? 'Traité' : 'En cours de traitement',
            'api_full_response' => json_encode($matched, JSON_UNESCAPED_UNICODE),
        ]);

        // Create responses
        $newResponses = 0;
        foreach ($matched['reponseReclamation'] ?? [] as $resp) {
            if (!isset($resp['id']) || ReclamationResponse::where('api_id', $resp['id'])->exists()) {
                continue;
            }

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

        return redirect()->back()->with('success', "Réclamation mise à jour. $newResponses réponse(s) ajoutée(s).");

    } catch (\Exception $e) {
        Log::error("Erreur lors de la récupération de réponse: " . $e->getMessage());
        return redirect()->back()->with('error', "Erreur API : " . $e->getMessage());
    }
}

 
}