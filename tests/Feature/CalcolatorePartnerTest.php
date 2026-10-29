<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Ordine;
use App\Models\RigaOrdine;
use App\Models\TipologiaIngresso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Pages\CalcolatorePartner;

class CalcolatorePartnerTest extends TestCase
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
            'attivoPartner' => 1,
            'tipoPartner' => Partner::TIPO_AGENZIA,
        ]);

        $this->location = Location::create([
            'idLocation' => 1,
            'nomeLocation' => 'Location Test',
            'indirizzoLocation' => 'Via Test 1',
            'attivaLocation' => 1
        ]);
    }

    /** @test */
    public function test_calcolatore_partner_page_can_be_rendered()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        Livewire::test(CalcolatorePartner::class)
            ->assertStatus(200);
    }

    /** @test */
    public function test_calcolatore_partner_calculation_and_filtering()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        // Create ticket type with partner commission (tipoProvvigionePartner = 1, valoreProvvigionePartner = 10%)
        $ingresso = TipologiaIngresso::create([
            'idLocation' => $this->location->idLocation,
            'idIngresso' => 201,
            'nomeIngresso' => 'Ingresso VIP',
            'prezzoIngresso' => 100.00,
            'ingressoAttivo' => 1,
            'tipoProvvigionePartner' => 1,
            'valoreProvvigionePartner' => 10.00,
        ]);

        // Create completed order for partner
        $order = Ordine::create([
            'numeroOrdine' => 20001,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2, // Completato
            'totaleOrdine' => 200.00,
            'dataOrdine' => now(),
        ]);

        RigaOrdine::create([
            'numeroOrdine' => $order->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 100.00,
            'nomeIngressoOrdine' => 'Ingresso VIP',
            'totaleRiga' => 200.00,
        ]);

        // Run calculation
        Livewire::test(CalcolatorePartner::class)
            ->set('data.idPartner', $this->partner->idPartner)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertSet('risultati.partner', 'Partner Test')
            ->assertSet('risultati.totaleQuantita', 2)
            ->assertSet('risultati.totaleVenduto', 200.00)
            ->assertSet('risultati.totaleProvvigione', 20.00) // 10% of 200 = 20
            ->assertHasNoErrors();
    }

    /** @test */
    public function test_calcolatore_partner_affiliato_usa_provvigione_ingresso()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $this->partner->update(['tipoPartner' => Partner::TIPO_AFFILIATO]);
        $this->creaOrdineConProvvigionePercentuale(20002);

        Livewire::test(CalcolatorePartner::class)
            ->set('data.idPartner', $this->partner->idPartner)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertSet('risultati.totaleVenduto', 200.00)
            ->assertSet('risultati.totaleProvvigione', 20.00);
    }

    /** @test */
    public function test_calcolatore_partner_diretto_non_calcola_provvigione()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $this->partner->update(['tipoPartner' => Partner::TIPO_DIRETTO]);
        $this->creaOrdineConProvvigionePercentuale(20003);

        Livewire::test(CalcolatorePartner::class)
            ->set('data.idPartner', $this->partner->idPartner)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertSet('risultati.totaleVenduto', 200.00)
            ->assertSet('risultati.totaleProvvigione', 0)
            ->assertSet('risultati.totaleDaPagareResiduo', 0);
    }

    /** @test */
    public function test_calcolatore_partner_legacy_tipo_zero_trattato_come_diretto()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $this->partner->update(['tipoPartner' => 0]);
        $this->creaOrdineConProvvigionePercentuale(20004);

        Livewire::test(CalcolatorePartner::class)
            ->set('data.idPartner', $this->partner->idPartner)
            ->set('data.data_da', now()->subDays(1)->format('Y-m-d'))
            ->set('data.data_a', now()->addDays(1)->format('Y-m-d'))
            ->call('calcola')
            ->assertSet('risultati.totaleProvvigione', 0);
    }

    private function creaOrdineConProvvigionePercentuale(int $numeroOrdine): void
    {
        $ingresso = TipologiaIngresso::create([
            'idLocation' => $this->location->idLocation,
            'idIngresso' => $numeroOrdine,
            'nomeIngresso' => 'Ingresso VIP ' . $numeroOrdine,
            'prezzoIngresso' => 100.00,
            'ingressoAttivo' => 1,
            'tipoProvvigionePartner' => 1,
            'valoreProvvigionePartner' => 10.00,
        ]);

        $order = Ordine::create([
            'numeroOrdine' => $numeroOrdine,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 200.00,
            'dataOrdine' => now(),
        ]);

        RigaOrdine::create([
            'numeroOrdine' => $order->numeroOrdine,
            'idIngressoOrdine' => $ingresso->idIngressoCont,
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 100.00,
            'nomeIngressoOrdine' => $ingresso->nomeIngresso,
            'totaleRiga' => 200.00,
        ]);
    }
}
