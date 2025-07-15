<?php

namespace App\Exports;

use App\Models\Reclamation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReclamationsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $filters;
    protected $columns;

    public function __construct($filters = [], $columns = [])
    {
        $this->filters = $filters;
        $this->columns = $columns;
    }

    public function collection()
    {
        $query = Reclamation::with('responses'); // eager load responses

        if (!empty($this->filters['statut_envoi'])) {
            $query->where('statut_envoi', $this->filters['statut_envoi']);
        }

        if (!empty($this->filters['objet'])) {
            $query->where('objet', $this->filters['objet']);
        }

        if (!empty($this->filters['date_reclamation'])) {
            $query->whereDate('date_reclamation', $this->filters['date_reclamation']);
        }

        $records = $query->get();

        return $records->map(function ($item) {
            // Prepare bullet-pointed responses text
            $responsesText = $item->responses->map(function ($response) {
                // Use your actual response message column here (e.g. 'reponse' or 'api_message_retour')
                return '• ' . ($response->reponse ?? $response->api_message_retour ?? '-');
            })->implode("\n");

            $map = [
                'source_requete' => $item->source_requete,
                'date_reclamation' => optional(\Carbon\Carbon::make($item->date_reclamation))->format('d/m/Y'),
                'reference_demande' => $item->reference_demande,
                'cnie' => $item->cnie,
                'nom_prenom' => $item->nom_prenom,
                'ville' => $item->ville,
                'canal' => $item->canal,
                'objet' => $item->objet,
                'message' => $item->message,
                'remarque_matnuhpv' => $item->remarque_matnuhpv,
                'response' => $responsesText,  // <-- add the responses as bullet points
            ];

            return collect($map)->only($this->columns ?: array_keys($map));
        });
    }

    public function headings(): array
    {
        $fullMap = [
            'source_requete' => 'Source de la requête',
            'date_reclamation' => 'Date',
            'reference_demande' => 'Réf dossier/ Ref demande',
            'cnie' => 'CNIE',
            'nom_prenom' => 'Nom et prénom',
            'ville' => 'Ville',
            'canal' => 'Canal de réclamation',
            'objet' => 'Objets des réclamations',
            'message' => 'Message',
            'remarque_matnuhpv' => 'Remarque matnuhpv',
            'response' => 'Réponses',  // add heading for responses
        ];

        return $this->columns
            ? collect($this->columns)->map(fn($c) => $fullMap[$c])->toArray()
            : array_values($fullMap);
    }
}