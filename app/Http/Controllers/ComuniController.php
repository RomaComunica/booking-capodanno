<?php

namespace App\Http\Controllers;

use App\Models\ComuneItaliano;
use Illuminate\Http\Request;

class ComuniController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->query('q');
        
        if (empty($search) || strlen($search) < 2) {
            return response()->json([]);
        }
        
        // Cerca comuni che iniziano con le lettere digitate
        $comuni = ComuneItaliano::select('idComune', 'nomeComune', 'siglaProvincia')
            ->where('nomeComune', 'like', $search . '%')
            ->orderBy('nomeComune', 'asc')
            ->limit(15)
            ->get();
            
        return response()->json($comuni);
    }
}
