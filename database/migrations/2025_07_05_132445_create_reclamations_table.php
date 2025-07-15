<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reclamations', function (Blueprint $table) {
    $table->id();

    $table->string('source_requete');              
    $table->date('date_reclamation');              
    $table->string('reference_demande');            
    $table->string('cnie')->nullable();            
    $table->string('nom_prenom')->nullable();      
    $table->string('ville')->nullable();            
    $table->string('canal')->nullable();            
    $table->string('objet');                        
    $table->text('message');                       
    $table->string('statut_envoi')->default('non envoyé'); 
    $table->enum('statut_traitement', ['Nouvelle réclamation', 'En cours de traitement', 'En attente retour MATNUHPV','Traité'])->nullable();
    $table->string('piece_jointe_path')->nullable();
    $table->string('reference_externe_rec');        
    $table->string('identifiant_notaire')->nullable(); 

    // API return values
    $table->string('reference_reclamation')->nullable();     
    $table->string('api_code_retour')->nullable();           
    $table->text('api_message_retour')->nullable();         
    $table->json('api_full_response')->nullable();           

    // Internal follow-up       
    $table->text('remarque_matnuhpv')->nullable();   

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamations');
    }
};