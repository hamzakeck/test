@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Éditer la Réclamation</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('reclamations.update', $rec->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Référence Externe --}}
        <div class="mb-3">
            <label for="reference_externe_rec" class="form-label">Référence Externe *</label>
            <input type="text" class="form-control" id="reference_externe_rec" name="reference_externe_rec" value="{{ old('reference_externe_rec', $rec->reference_externe_rec) }}" required>
        </div>

        {{-- Identifiant Notaire --}}
        <div class="mb-3">
            <label for="identifiant_notaire" class="form-label">Identifiant Notaire</label>
            <input type="text" class="form-control" id="identifiant_notaire" name="identifiant_notaire" value="{{ old('identifiant_notaire', $rec->identifiant_notaire) }}">
        </div>

        {{-- Référence Demande --}}
        <div class="mb-3">
            <label for="reference_demande" class="form-label">Référence Demande *</label>
            <input type="text" class="form-control" id="reference_demande" name="reference_demande" value="{{ old('reference_demande', $rec->reference_demande) }}" required>
        </div>

        {{-- Objet --}}
        <div class="mb-3">
            <label for="objet" class="form-label">Objet *</label>
            <select class="form-select" id="objet" name="objet" required>
                <option value="">Sélectionner</option>
                @foreach(['ANNULATION','DOCUMENT','ELIGIBILITE','INFORMATION','MAJ','PAIEMENT','RESTITUTION'] as $obj)
                    <option value="{{ $obj }}" @if(old('objet', $rec->objet) == $obj) selected @endif>{{ $obj }}</option>
                @endforeach
            </select>
        </div>

        {{-- Message --}}
        <div class="mb-3">
            <label for="message" class="form-label">Message *</label>
            <textarea class="form-control" id="message" name="message" rows="4" required>{{ old('message', $rec->message) }}</textarea>
        </div>

        {{-- Pièce jointe --}}
        <div class="mb-3">
            <label for="document" class="form-label">Pièce jointe (laisser vide pour ne pas modifier)</label>
            <input type="file" class="form-control" id="document" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            @if($rec->piece_jointe_path)
                <small class="form-text text-muted">Fichier actuel : <a href="{{ asset('storage/' . $rec->piece_jointe_path) }}" target="_blank">Voir</a></small>
            @endif
        </div>

        {{-- Remarque MATNUHPV --}}
        <div class="mb-3">
            <label for="remarque_matnuhpv" class="form-label">Remarque MATNUHPV</label>
            <textarea class="form-control" id="remarque_matnuhpv" name="remarque_matnuhpv" rows="2">{{ old('remarque_matnuhpv', $rec->remarque_matnuhpv) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>

    {{-- Retry button if not sent --}}
    @if($rec->statut_envoi === 'non_envoyé')
        <form action="{{ route('reclamations.retry', $rec->id) }}" method="POST" class="mt-3">
            @csrf
            <button class="btn btn-warning">
                ↻ Réessayer l'envoi
            </button>
        </form>
    @endif
</div>
@endsection
