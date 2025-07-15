<?php

namespace App\Http\Controllers;

use App\Models\Reclamation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
  public function index()
{
    $total = Reclamation::count();
    $treated = Reclamation::where('statut_envoi', 'envoyé')->count();
    $pending = Reclamation::where('statut_envoi', 'non_envoyé')->count();
    $recentReclamations = Reclamation::latest()->take(5)->get();

    return view('dashboard', compact('total', 'treated', 'pending', 'recentReclamations'));
}

}