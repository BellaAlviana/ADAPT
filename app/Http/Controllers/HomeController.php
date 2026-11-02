<?php

namespace App\Http\Controllers;

use App\Models\DiagramGeneration;
use App\Models\DiagramAudit;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $totalGenerations = DiagramGeneration::where('status', 'success')->count();
        $totalAudits = DiagramAudit::where('status', 'success')->count();

        return view('home', compact('totalGenerations', 'totalAudits'));
    }

    public function history()
    {
        $generations = DiagramGeneration::latest()->paginate(10, ['*'], 'gen_page');
        $audits = DiagramAudit::latest()->paginate(10, ['*'], 'audit_page');

        return view('history', compact('generations', 'audits'));
    }
}
