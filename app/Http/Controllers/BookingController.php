<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\TipologiaIngresso;

class BookingController extends Controller
{
    public function index()
    {
        $locations = Location::query()->orderBy('idLocation', 'desc')->limit(50)->get();
        return view('booking.index', compact('locations'));
    }

    public function ingressi($id)
    {
        $ingressi = TipologiaIngresso::where('idLocation', $id)
            ->where('ingressoAttivo', 1)
            ->orderBy('dataIngresso')
            ->get();

        return response()->json([
            'data' => $ingressi,
        ]);
    }
}
