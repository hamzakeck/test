<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReclamationResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'reclamation_id',
        'api_id',
        'date_reponse',
        'etat',
        'reponse',
        'type_operation',
    ];

    // Relationship
    public function reclamation()
    {
        return $this->belongsTo(Reclamation::class);
    }

    //  Casting
    protected $casts = [
        'date_reponse' => 'datetime',
    ];
} //