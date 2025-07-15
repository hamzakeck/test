<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Reclamation;
use App\Models\ReclamationResponse;

class FetchReclamationResponses extends Command
{
    protected $signature = 'reclamations:fetch-responses';
    protected $description = 'Fetch reclamation responses from API and update database';
    
    const API_URL = 'https://reclamation.free.beeceptor.com'; // Replace with your actual API URL

    public function handle()
    {
        $this->info('Starting automatic reclamation response fetch...');
        
        // Get ALL reclamations
        $reclamations = Reclamation::all();

        $totalUpdated = 0;
        $totalResponses = 0;
        $errors = 0;

        foreach ($reclamations as $reclamation) {
            try {
                $result = $this->fetchReclamationResponse($reclamation);
                if ($result['success']) {
                    $totalUpdated++;
                    $totalResponses += $result['new_responses'];
                    $this->info("Updated reclamation {$reclamation->id}: {$result['new_responses']} new responses");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error processing reclamation {$reclamation->id}: " . $e->getMessage());
                Log::error("Automatic reclamation fetch error for ID {$reclamation->id}: " . $e->getMessage());
            }
        }

        $this->info("Fetch completed: {$totalUpdated} reclamations updated, {$totalResponses} new responses, {$errors} errors");
        
        Log::info("Automatic reclamation fetch completed", [
            'reclamations_updated' => $totalUpdated,
            'new_responses' => $totalResponses,
            'errors' => $errors,
            'total_processed' => $reclamations->count()
        ]);
    }

    private function fetchReclamationResponse($reclamation)
    {
        try {
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
                return ['success' => false, 'new_responses' => 0];
            }

            $matched = collect($data)->firstWhere('referenceDemande', $reclamation->reference_demande);
            
            if (!$matched) {
                return ['success' => false, 'new_responses' => 0];
            }

            $etat = strtoupper($matched['etat'] ?? '');
            $reclamation->update([
                'statut_traitement' => $etat === 'TREATED' ? 'Traité' : 'En cours de traitement',
                'api_full_response' => json_encode($matched, JSON_UNESCAPED_UNICODE),
            ]);

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

            return ['success' => true, 'new_responses' => $newResponses];

        } catch (\Exception $e) {
            Log::error("Error fetching reclamation response for ID {$reclamation->id}: " . $e->getMessage());
            return ['success' => false, 'new_responses' => 0];
        }
    }
}