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
    Schema::create('reclamation_responses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('reclamation_id')->constrained()->onDelete('cascade');
    $table->string('api_id');
    $table->dateTime('date_reponse');
    $table->string('etat');             
    $table->text('reponse');
    $table->string('type_operation');   
    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamation_responses');
    }
};