@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-6">

    <!-- Title -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Vue globale des réclamations</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Total -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Réclamations</p>
            <p class="text-3xl font-semibold text-gray-900 dark:text-white mt-1">{{ $total }}</p>
        </div>

        <!-- Traitées -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Réclamations Traitées</p>
            <p class="text-3xl font-semibold text-green-600 dark:text-green-400 mt-1">{{ $treated }}</p>
        </div>

        <!-- En attente -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Réclamations en Attente</p>
            <p class="text-3xl font-semibold text-yellow-600 dark:text-yellow-400 mt-1">{{ $pending }}</p>
        </div>
    </div>

    <!-- Section Récentes -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">5 dernières réclamations</h2>
        
        @forelse($recentReclamations as $rec)
            <div class="py-3 border-b last:border-b-0 border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $rec->reference_demande }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($rec->date_reclamation)->format('d/m/Y') }} – {{ Str::limit($rec->message, 60) }}</p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-full 
                    {{ $rec->statut_envoi === 'envoyé' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ ucfirst($rec->statut_envoi) }}
                </span>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune réclamation récente.</p>
        @endforelse
    </div>
</div>
@endsection
