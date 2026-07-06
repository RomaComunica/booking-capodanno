<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Partner;
use App\Models\TipologiaIngresso;
use App\Models\CategoriaLocation;
use App\Models\TipologiaLocation;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        echo "Creating test data...\n";

        // Create categories
        $categoria = CategoriaLocation::firstOrCreate(
            ['idCategoriaLocation' => 1],
            ['nomeCategoriaLocation' => 'Ristoranti']
        );

        // Create tipologia
        $tipologia = TipologiaLocation::firstOrCreate(
            ['idTipologiaLocation' => 1],
            ['nomeTipologiaLocation' => 'Cenone']
        );

        // Create or get partner
        $partner = Partner::firstOrCreate(
            ['idPartner' => 5],
            [
                'nomePartner' => 'Partner Gestore',
                'emailPartner' => 'gestore@example.com',
                'attivoPartner' => 1
            ]
        );

        // Create location
        $location = Location::firstOrCreate(
            ['idLocation' => 1],
            [
                'nomeLocation' => 'Ristorante Test Capodanno',
                'indirizzoLocation' => 'Via Roma 1, Roma',
                'descrizioneLocation' => 'Cenone di Capodanno con menu gourmet',
                'attivaLocation' => 1,
                'prezzoMinimoLocation' => 50.00,
                'prezzoMassimoLocation' => 100.00
            ]
        );

        // Associate categories and tipologie
        if (!$location->categorie()->where('idCategoriaLocation', $categoria->idCategoriaLocation)->exists()) {
            $location->categorie()->attach($categoria->idCategoriaLocation);
        }

        if (!$location->tipologie()->where('idTipologiaLocation', $tipologia->idTipologiaLocation)->exists()) {
            $location->tipologie()->attach($tipologia->idTipologiaLocation);
        }

        // Create tickets
        $ticket1 = TipologiaIngresso::firstOrCreate(
            ['idIngressoCont' => 1],
            [
                'idLocation' => $location->idLocation,
                'nomeIngresso' => 'Biglietto Standard',
                'prezzoIngresso' => 50.00,
                'giacenzaBiglietti' => 100,
                'ingressoAttivo' => 1,
                'dataIngresso' => '2026-12-31',
                'orarioIngresso' => '20:00:00',
                'giorniPrimaIngresso' => 0,
                'ordine' => 1,
                'opzioneRimborso' => 1,
                'rimborsoValorePersona' => 5.00,
                'rimborsoDataScadenza' => '2026-12-30 23:59:59',
                'rimborsoTesto' => 'Rimborso completo fino al 30/12'
            ]
        );

        $ticket2 = TipologiaIngresso::firstOrCreate(
            ['idIngressoCont' => 2],
            [
                'idLocation' => $location->idLocation,
                'nomeIngresso' => 'Biglietto VIP',
                'prezzoIngresso' => 100.00,
                'giacenzaBiglietti' => 50,
                'ingressoAttivo' => 1,
                'dataIngresso' => '2026-12-31',
                'orarioIngresso' => '20:00:00',
                'giorniPrimaIngresso' => 0,
                'ordine' => 2
            ]
        );

        // Associate tickets with partner in cross_partner_ingressi
        $this->createCrossPartnerIngressi($partner->idPartner, $location->idLocation, $ticket1->idIngressoCont, 100);
        $this->createCrossPartnerIngressi($partner->idPartner, $location->idLocation, $ticket2->idIngressoCont, 50);

        echo "Test data created successfully!\n";
        echo "Location: {$location->nomeLocation}\n";
        echo "Tickets: 2\n";
        echo "Partner: {$partner->nomePartner} (ID: {$partner->idPartner})\n";
    }

    private function createCrossPartnerIngressi($partnerId, $locationId, $ticketId, $quantity)
    {
        $exists = DB::table('cross_partner_ingressi')
            ->where('idPartnerIngressi', $partnerId)
            ->where('idlocation', $locationId)
            ->where('idTipologiaIngresso', $ticketId)
            ->exists();

        if (!$exists) {
            DB::table('cross_partner_ingressi')->insert([
                'idPartnerIngressi' => $partnerId,
                'idlocation' => $locationId,
                'idTipologiaIngresso' => $ticketId,
                'quantitaIngressi' => $quantity,
                'quantitaIngressiTerra' => 0
            ]);
        }
    }
}
