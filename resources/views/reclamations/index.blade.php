@extends('layouts.app')

@section('content')
<div class="container-fluid"> {{-- Changed from container to container-fluid --}}
    <h2>Liste des Réclamations</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Recherche par référence ou message..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="statut_envoi" class="form-select">
                    <option value="">-- Tous les statuts --</option>
                    <option value="envoyé" {{ request('statut_envoi') == 'envoyé' ? 'selected' : '' }}>Envoyé</option>
                    <option value="non_envoyé" {{ request('statut_envoi') == 'non_envoyé' ? 'selected' : '' }}>Non envoyé</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="objet" class="form-select">
                    <option value="">-- Tous les objets --</option>
                    @foreach(['ANNULATION','DOCUMENT','ELIGIBILITE','INFORMATION','MAJ','PAIEMENT','RESTITUTION'] as $obj)
                        <option value="{{ $obj }}" {{ request('objet') == $obj ? 'selected' : '' }}>{{ $obj }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_reclamation" class="form-control" value="{{ request('date_reclamation') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Filtrer</button>
            </div>
            <div class="col-md-1">
                @if($reclamations->where('statut_envoi', 'non_envoyé')->count() > 0)
                <form action="{{ route('reclamations.retry-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning w-100"
                        onclick="return confirm('Êtes-vous sûr de vouloir renvoyer toutes les réclamations non envoyées ?')">
                         Tout
                    </button>
                </form>
                @endif
            </div>
        </div>
    </form>

    {{-- Added wrapper for horizontal scrolling --}}
    <div class="table-responsive-custom">
        <table class="table table-bordered table-hover table-striped wide-table">
            <thead class="table-dark">
                <tr>
                    <th>Réf. Externe</th>
                    <th>Réf. Demande</th>
                    <th>Réf. Réclamation API</th>
                    <th>Identifiant Notaire</th>
                    <th>Nom & Prénom</th>
                    <th>Ville</th>
                    <th>Canal</th>
                    <th>Objet</th>
                    <th>Message</th>
                    <th>Date Réclamation</th>
                    <th>Statut Envoi</th>
                    <th>Statut Traitement</th>
                    <th>Retour API</th>
                    <th>Remarque MATNUHPV</th>
                    <th>Pièce Jointe</th>
                    <th>Réponses</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reclamations as $rec)
                <tr>
                    <td>{{ $rec->reference_externe_rec }}</td>
                    <td>{{ $rec->reference_demande }}</td>
                    <td>{{ $rec->reference_reclamation ?? '-' }}</td>
                    <td>{{ $rec->identifiant_notaire ?? '-' }}</td>
                    <td>{{ $rec->nom_prenom ?? '-' }}</td>
                    <td>{{ $rec->ville ?? '-' }}</td>
                    <td>{{ $rec->canal }}</td>
                    <td>{{ $rec->objet }}</td>
                    <td>{{ Str::limit($rec->message, 50) }}</td>
                    <td>{{ \Carbon\Carbon::parse($rec->date_reclamation)->format('d/m/Y') }}</td>
                    <td>
                        @if($rec->statut_envoi === 'envoyé')
                            <span class="badge bg-success">Envoyé</span>
                        @else
                            <span class="badge bg-danger">Non envoyé</span>
                        @endif
                    </td>
                    <td>{{ $rec->statut_traitement ?? '-' }}</td>
                    <td>
                        <small>
                            @if($rec->api_message_retour)
                                {{ Str::limit($rec->api_message_retour, 30) }}
                            @else
                                -
                            @endif
                        </small>
                    </td>
                    <td>{{ $rec->remarque_matnuhpv ?? '-' }}</td>
                    <td>
                        @if ($rec->piece_jointe_path)
                            <a href="{{ asset('storage/' . $rec->piece_jointe_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $responses = $rec->responses;
                        @endphp

                        @if ($responses && $responses->count() > 0)
                            <div class="dropdown">
                                <button 
        class="btn btn-sm btn-outline-info dropdown-toggle" 
        type="button" 
        data-bs-toggle="dropdown" 
        aria-expanded="false"
        data-bs-auto-close="outside"
        title="Afficher les réponses"
                                >
                                    Voir les réponses ({{ $responses->count() }})
                                </button>

                                <div class="dropdown-menu p-2" style="min-width: 400px; max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>État</th>
                                                <th>Type</th>
                                                <th>Message</th>
                                                <th>Supprimer</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($responses as $res)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($res->date_reponse)->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $res->etat }}</td>
                                                    <td>{{ $res->type_operation }}</td>
                                                    <td>{{ Str::limit($res->reponse, 40) }}</td>
                                                    <td>
                                                        <form action="{{ route('reclamation-responses.destroy', $res->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réponse ?');" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <span class="text-muted">Aucune réponse</span>
                        @endif

                        <!-- Fetch responses button -->
                        <form action="{{ route('reclamations.fetch-responses', $rec->id) }}" method="POST" class="d-inline mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Récupérer les réponses">
                                 Réponses
                            </button>
                        </form>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('reclamations.edit', $rec->id) }}" class="btn btn-info btn-sm" title="Modifier">✏️</a>

                        @if($rec->statut_envoi === 'non_envoyé')
                            <form action="{{ route('reclamations.retry', $rec->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-warning btn-sm" title="Renvoyer">↻</button>
                            </form>
                        @endif

                        <form action="{{ route('reclamations.destroy', $rec->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette réclamation ?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Supprimer">🗑</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="17" class="text-center text-muted">Aucune réclamation trouvée.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $reclamations->withQueryString()->links() }}
    </div>

    <hr>

    <div class="my-4">
        <h4>Importer des réclamations depuis un fichier Excel</h4>
        <form action="{{ route('reclamations.import') }}" method="POST" enctype="multipart/form-data" class="row g-2">
            @csrf
            <div class="col-md-4">
                <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100" type="submit">Importer Excel</button>
            </div>
            <div class="col-md-5">
                <p class="text-muted mb-0">Le fichier doit être au format Excel (.xlsx) ou CSV (.csv).</p>
            </div>
        </form>
    </div>

    <div class="my-4">
        <h4>Exporter les réclamations</h4>
        <form action="{{ route('reclamations.export') }}" method="GET" class="d-inline">
            @foreach(request()->query() as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="btn btn-outline-success">
                 Exporter en Excel
            </button>
        </form>
    </div>
</div>
@endsection