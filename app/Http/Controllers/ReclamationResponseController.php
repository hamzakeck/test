<?php

namespace App\Http\Controllers;

use App\Models\ReclamationResponse;
use Illuminate\Http\Request;

class ReclamationResponseController extends Controller
{
    /**
     * Supprime une réponse de réclamation spécifique.
     *
     * @param  int  $id  L'identifiant de la réponse à supprimer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Rechercher la réponse via son ID ou échouer avec une exception 404 si elle n'existe pas
        $response = ReclamationResponse::findOrFail($id);

        try {
            // Supprimer la réponse de la base de données
            $response->delete();

            // Rediriger avec un message de succès
            return redirect()->back()->with('success', 'Réponse supprimée avec succès.');
        } catch (\Exception $e) {
            // En cas d'erreur lors de la suppression, rediriger avec un message d'erreur
            return redirect()->back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}