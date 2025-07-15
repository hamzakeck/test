@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Nouvelle Réclamation</h2>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('reclamations.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="source_requete" class="form-label">Source de la requête *</label>
            <input type="text" id="source_requete" name="source_requete" class="form-control" value="{{ old('source_requete') }}" required>
        </div>

        <div class="mb-3">
            <label for="date_reclamation" class="form-label">Date *</label>
            <input type="date" id="date_reclamation" name="date_reclamation" class="form-control" value="{{ old('date_reclamation', now()->toDateString()) }}" required>
        </div>

        <div class="mb-3">
            <label for="reference_demande" class="form-label">Référence dossier / demande *</label>
            <input type="text" id="reference_demande" name="reference_demande" class="form-control" value="{{ old('reference_demande') }}" required>
        </div>

        <div class="mb-3">
            <label for="cnie" class="form-label">CNIE</label>
            <input type="text" id="cnie" name="cnie" class="form-control" value="{{ old('cnie') }}">
        </div>

        <div class="mb-3">
            <label for="nom_prenom" class="form-label">Nom et prénom</label>
            <input type="text" id="nom_prenom" name="nom_prenom" class="form-control" value="{{ old('nom_prenom') }}">
        </div>

        <div class="mb-3">
            <label for="ville" class="form-label">Ville</label>
            <input type="text" id="ville" name="ville" class="form-control" value="{{ old('ville') }}">
        </div>

        <div class="mb-3">
            <label for="canal" class="form-label">Canal de réclamation *</label>
            <select id="canal" name="canal" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                <option value="ECRITE" {{ old('canal') == 'ECRITE' ? 'selected' : '' }}>Écrite</option>
                <option value="PHYSIQUE" {{ old('canal') == 'PHYSIQUE' ? 'selected' : '' }}>Physique</option>
                <option value="TELEPHONE" {{ old('canal') == 'TELEPHONE' ? 'selected' : '' }}>Par téléphone</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="objet" class="form-label">Objet de la réclamation *</label>
            <select id="objet" name="objet" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                @foreach(['ANNULATION','DOCUMENT','ELIGIBILITE','INFORMATION','MAJ','PAIEMENT','RESTITUTION'] as $obj)
                    <option value="{{ $obj }}" {{ old('objet') == $obj ? 'selected' : '' }}>{{ ucfirst(strtolower($obj)) }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="message" class="form-label">Message *</label>
            <textarea id="message" name="message" class="form-control" rows="4" required>{{ old('message') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="document" class="form-label">Joindre une pièce justificative (PDF, image)</label>
            <input type="file" id="document" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
        </div>

        <div class="mb-3">
            <label for="remarque_matnuhpv" class="form-label">Remarque MATNUHPV (interne)</label>
            <textarea id="remarque_matnuhpv" name="remarque_matnuhpv" class="form-control" rows="2">{{ old('remarque_matnuhpv') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Soumettre la réclamation</button>
    </form>
</div>
@endsection
