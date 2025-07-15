<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Reclamation extends Model
{
       use HasFactory;

 protected $fillable = [
        'source_requete',
        'date_reclamation',
        'reference_demande',
        'cnie',
        'nom_prenom',
        'ville',
        'canal',
        'objet',
        'message',
        'statut_envoi',
        'statut_traitement',
        'piece_jointe_path',
        'reference_externe_rec',
        'identifiant_notaire',
        'reference_reclamation',
        'api_code_retour',
        'api_message_retour',
        'remarque_matnuhpv',
    ];

    // Relation
    public function responses()
    {
        return $this->hasMany(ReclamationResponse::class);
    }

    // Date casting
    protected $casts = [
        'date_reclamation' => 'date',
    ];

    // latest response
    public function latestResponse()
    {
        return $this->responses()->latest()->first();
    }
}