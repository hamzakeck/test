<?php

namespace App\Http\Controllers;

use App\Models\ReclamationResponse;
use Illuminate\Http\Request;

class ReclamationResponseController extends Controller
{
    public function destroy($id)
    {
        $response = ReclamationResponse::findOrFail($id);

        try {
            $response->delete();
            return redirect()->back()->with('success', 'Réponse supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}