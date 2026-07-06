<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Ordine;
use App\Models\Voucher;
use App\Models\RigaOrdine;
use App\Models\TipologiaIngresso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Pages\CalcolatoreLocation;

class CalcolatoreLocationTest extends TestCase
{
    use RefreshDatabase;

    private $location;
    private $partner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->partner = Partner::create([
            'idPartner' => 1,
            'nomePartner' => 'Partner Test',
            'emailPartner' => 'partner@test.com',
            'attivoPartner' => 1
        ]);

        $this->location = Location::create([
            'idLocation' => 1,
            'nomeLocation' => 'Location Test',
            'indirizzoLocation' => 'Via Test 1',
            'attivaLocation' => 1
        ]);
    }

    /** @test */
    public function test_calcolatore_page_can_be_rendered()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        Livewire::test(CalcolatoreLocation::class)
            ->assertStatus(200);
    }

    /** @test */
    public function test_calcolatore_calculation_and_note_extraction()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        // Create ticket type with 20% percentage commission (tipoProvvigione = 1, valoreProvvigione = 20)
        $ingresso = TipologiaIngresso::create([
            'idLocation' => $this->location->idLocation,
            'idIngresso' => 101,
            'nomeIngresso' => 'Ingresso Cena',
            'prezzoIngresso' => 100.00,
            'ingressoAttivo' => 1,
            'tipoProvvigione' => 1,
            'valoreProvvigione' => 20.00,
        ]);

        // Create paid order
        $order = Ordine::create([
            'numeroOrdine' => 10001,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2, // Completato
            'totaleOrdine' => 200.00,
            'dataOrdine' => now(),
            'noteOrdine' => 'Tavolo Rossi per favore'
        ]);

        $riga = RigaOrdine::create([
            'numeroOrdine' => $order->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 100.00,
            'nomeIngressoOrdine' => 'Ingresso Cena',
            'totaleRiga' => 200.00,
        ]);

        // Run calculation
        Livewire::test(CalcolatoreLocation::class)
            ->set('data.idLocation', $this->location->idLocation)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertSet('risultati.totaleVenduto', 200.00)
            ->assertSet('risultati.totaleDare', 160.00) // 200 - 20% commission (40) = 160
            ->assertSet('tavoli.0', 'Rossi') // Extracted note group Rossi
            ->assertHasNoErrors();
    }

    /** @test */
    public function test_calcolatore_tavolo_persistence()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $ingresso = TipologiaIngresso::create([
            'idLocation' => $this->location->idLocation,
            'idIngresso' => 102,
            'nomeIngresso' => 'Ingresso Tavolo',
            'prezzoIngresso' => 150.00,
            'ingressoAttivo' => 1,
            'tipoProvvigione' => 1,
            'valoreProvvigione' => 10.00,
        ]);

        // Create paid order with predefined tavolo value 'Bianchi'
        $order = Ordine::create([
            'numeroOrdine' => 10002,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 150.00,
            'dataOrdine' => now(),
            'noteOrdine' => 'Tavolo Rossi per favore', // note says Rossi, but DB already has Bianchi
            'tavolo' => 'Bianchi',
        ]);

        $riga = RigaOrdine::create([
            'numeroOrdine' => $order->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 1,
            'prezzoIngressoOrdine' => 150.00,
            'nomeIngressoOrdine' => 'Ingresso Tavolo',
            'totaleRiga' => 150.00,
        ]);

        // Run calculation and check that DB value takes precedence
        $testable = Livewire::test(CalcolatoreLocation::class)
            ->set('data.idLocation', $this->location->idLocation)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertSet('tavoli.0', 'Bianchi'); // Takes Bianchi (from DB) instead of Rossi (from note)

        // Simulate user updating the table to 'Verdi' via UI
        $testable->set('tavoli.0', 'Verdi');

        // Check that it was persisted to the database
        $this->assertEquals('Verdi', Ordine::where('numeroOrdine', 10002)->value('tavolo'));
    }

    /** @test */
    public function test_calcolatore_tavolo_by_voucher_number_linking()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $ingresso = TipologiaIngresso::create([
            'idLocation' => $this->location->idLocation,
            'idIngresso' => 103,
            'nomeIngresso' => 'Ingresso standard',
            'prezzoIngresso' => 50.00,
            'ingressoAttivo' => 1,
            'tipoProvvigione' => 1,
            'valoreProvvigione' => 10.00,
        ]);

        // Order A: placed by Mario Rossi
        $orderA = Ordine::create([
            'numeroOrdine' => 10003,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 50.00,
            'dataOrdine' => now(),
            'nomeClienteOrdine' => 'Mario',
            'cognomeClienteOrdine' => 'Rossi',
            'noteOrdine' => 'Tavolo Rossi per favore',
        ]);

        $rigaA = RigaOrdine::create([
            'numeroOrdine' => $orderA->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 1,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso standard',
            'totaleRiga' => 50.00,
        ]);

        // Order B: references Order A
        $orderB = Ordine::create([
            'numeroOrdine' => 10004,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 50.00,
            'dataOrdine' => now(),
            'nomeClienteOrdine' => 'Luigi',
            'cognomeClienteOrdine' => 'Verdi',
            'noteOrdine' => 'Vogliamo stare con voucher 10003',
        ]);

        $rigaB = RigaOrdine::create([
            'numeroOrdine' => $orderB->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 1,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso standard',
            'totaleRiga' => 50.00,
        ]);

        // Run calculation
        Livewire::test(CalcolatoreLocation::class)
            ->set('data.idLocation', $this->location->idLocation)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertSet('tavoli.0', 'Rossi') // Order A pre-fills Rossi (by surname default)
            ->assertSet('tavoli.1', 'Rossi') // Order B pre-fills Rossi (by linking to Order A)
            ->assertHasNoErrors();
    }

    /** @test */
    public function test_calcolatore_tavolo_recursion_cycle_prevention()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $ingresso = TipologiaIngresso::create([
            'idLocation' => $this->location->idLocation,
            'idIngresso' => 104,
            'nomeIngresso' => 'Ingresso standard',
            'prezzoIngresso' => 50.00,
            'ingressoAttivo' => 1,
            'tipoProvvigione' => 1,
            'valoreProvvigione' => 10.00,
        ]);

        // Order A: note references Order B (10006)
        $orderA = Ordine::create([
            'numeroOrdine' => 10005,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 50.00,
            'dataOrdine' => now(),
            'nomeClienteOrdine' => 'Mario',
            'cognomeClienteOrdine' => 'Rossi',
            'noteOrdine' => 'Vogliamo stare vicino a voucher 10006',
        ]);

        RigaOrdine::create([
            'numeroOrdine' => $orderA->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 1,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso standard',
            'totaleRiga' => 50.00,
        ]);

        // Order B: note references Order A (10005) - This creates a cycle!
        $orderB = Ordine::create([
            'numeroOrdine' => 10006,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 50.00,
            'dataOrdine' => now(),
            'nomeClienteOrdine' => 'Luigi',
            'cognomeClienteOrdine' => 'Verdi',
            'noteOrdine' => 'Vogliamo stare vicino a voucher 10005',
        ]);

        RigaOrdine::create([
            'numeroOrdine' => $orderB->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 1,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso standard',
            'totaleRiga' => 50.00,
        ]);

        // Run calculation. Without recursion cycle prevention, this would time out or crash.
        Livewire::test(CalcolatoreLocation::class)
            ->set('data.idLocation', $this->location->idLocation)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertHasNoErrors();
    }
}
