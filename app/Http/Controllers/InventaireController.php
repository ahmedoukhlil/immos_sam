<?php

namespace App\Http\Controllers;

use App\Models\Inventaire;
use App\Services\RapportService;
use Illuminate\Http\Request;

class InventaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventaire $inventaire)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inventaire $inventaire)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inventaire $inventaire)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventaire $inventaire)
    {
        //
    }

    /**
     * Clôture un inventaire
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cloturer(Inventaire $inventaire)
    {
        if ($inventaire->statut === 'cloture') {
            return redirect()->back()->with('warning', 'Cet inventaire est déjà clôturé');
        }

        try {
            $inventaire->cloturer(auth()->id());
            
            return redirect()->route('inventaires.show', $inventaire)
                ->with('success', 'Inventaire clôturé avec succès');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la clôture: ' . $e->getMessage());
        }
    }

    /**
     * Exporte un inventaire en format PDF
     * 
     * @param Inventaire $inventaire
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportPDF(Inventaire $inventaire)
    {
        try {
            $service = app(RapportService::class);
            return $service->streamRapportPDF($inventaire);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Exporte un inventaire en format Excel
     * 
     * @param Inventaire $inventaire
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportExcel(Inventaire $inventaire)
    {
        // TODO: Implémenter l'export Excel de l'inventaire
        return redirect()->back()->with('info', 'Fonctionnalité en cours de développement');
    }
}

