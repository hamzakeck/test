@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Éditer la Réclamation</h4>
                </div>
                <div class="card-body">
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

                        {{-- Section 1: Informations de base --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Informations de base</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="source_requete" class="form-label">Source de la requête <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="source_requete" name="source_requete" value="{{ old('source_requete', $rec->source_requete) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_reclamation" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_reclamation" name="date_reclamation" value="{{ old('date_reclamation', $rec->date_reclamation ? $rec->date_reclamation->format('Y-m-d') : '') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reference_externe_rec" class="form-label">Référence Externe <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="reference_externe_rec" name="reference_externe_rec" value="{{ old('reference_externe_rec', $rec->reference_externe_rec) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reference_demande" class="form-label">Réf dossier / Ref demande <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="reference_demande" name="reference_demande" value="{{ old('reference_demande', $rec->reference_demande) }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Informations du demandeur --}}
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Informations du demandeur</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nom_prenom" class="form-label">Nom et prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom_prenom" name="nom_prenom" value="{{ old('nom_prenom', $rec->nom_prenom) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cnie" class="form-label">CNIE</label>
                                    <input type="text" class="form-control" id="cnie" name="cnie" value="{{ old('cnie', $rec->cnie) }}" maxlength="12" placeholder="Ex: A123456">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville" value="{{ old('ville', $rec->ville) }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="identifiant_notaire" class="form-label">Identifiant Notaire</label>
                                    <input type="text" class="form-control" id="identifiant_notaire" name="identifiant_notaire" value="{{ old('identifiant_notaire', $rec->identifiant_notaire) }}">
                                </div>
                            </div>
                        </div>

                        {{-- Section 3: Détails de la réclamation --}}
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Détails de la réclamation</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="canal_reclamation" class="form-label">Canal de réclamation <span class="text-danger">*</span></label>
                                    <select class="form-select" id="canal_reclamation" name="canal_reclamation" required>
                                        <option value="">Sélectionner</option>
                                        @foreach(['écrite', 'physique', 'par téléphone', 'web'] as $canal)
                                            <option value="{{ $canal }}" @if(old('canal_reclamation', $rec->canal_reclamation) == $canal) selected @endif>{{ $canal }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="objet" class="form-label">Objet des réclamations <span class="text-danger">*</span></label>
                                    <select class="form-select" id="objet" name="objet" required>
                                        <option value="">Sélectionner</option>
                                        @foreach(['ANNULATION','DOCUMENT','ELIGIBILITE','INFORMATION','MAJ','PAIEMENT','RESTITUTION'] as $obj)
                                            <option value="{{ $obj }}" @if(old('objet', $rec->objet) == $obj) selected @endif>{{ $obj }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Décrivez votre réclamation...">{{ old('message', $rec->message) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="remarque_matnuhpv" class="form-label">Remarque MATNUHPV</label>
                                    <textarea class="form-control" id="remarque_matnuhpv" name="remarque_matnuhpv" rows="2" placeholder="Remarques internes...">{{ old('remarque_matnuhpv', $rec->remarque_matnuhpv) }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Section 4: Pièce jointe --}}
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Pièce jointe</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="document" class="form-label">Pièce jointe</label>
                                    <input type="file" class="form-control" id="document" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <div class="form-text">Formats acceptés: PDF, DOC, DOCX, JPG, JPEG, PNG (Max: 2MB)</div>
                                    @if($rec->piece_jointe_path)
                                        <div class="mt-2">
                                            <small class="text-muted">Fichier actuel : </small>
                                            <a href="{{ asset('storage/' . $rec->piece_jointe_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Voir le fichier
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Boutons d'action --}}
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('reclamations.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Retour
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Retry button if not sent --}}
                    @if($rec->statut_envoi === 'non_envoyé')
                        <div class="mt-4 pt-3 border-top">
                            <form action="{{ route('reclamations.retry', $rec->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-warning">
                                    <i class="bi bi-arrow-repeat"></i> Réessayer l'envoi
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection