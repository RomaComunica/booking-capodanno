<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Location;
use App\Models\Partner;
use App\Models\TipologiaIngresso;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocationShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    protected function seedTestData()
    {
        // Create partner
        $partner = Partner::create([
            'idPartner' => 5,
            'nomePartner' => 'Partner Gestore',
            'emailPartner' => 'gestore@example.com',
            'attivoPartner' => 1
        ]);

        // Create location
        $location = Location::create([
            'idLocation' => 1,
            'nomeLocation' => 'Location Test',
            'indirizzoLocation' => 'Via Test 1',
            'attivaLocation' => 1
        ]);

        // Create active ticket
        $ingresso = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto Standard',
            'prezzoIngresso' => 50.00,
            'giacenzaBiglietti' => 100,
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(30),
            'orarioIngresso' => '20:00:00',
            'giorniPrimaIngresso' => 0
        ]);

        // Associate with partner
        $ingresso->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 100
        ]);
    }

    /** @test */
    public function test_location_page_loads()
    {
        $response = $this->get('/location/1');

        $response->assertStatus(200);
        $response->assertSee('Location Test');
        $response->assertSee('ACQUISTA ONLINE');
    }

    /** @test */
    public function test_only_active_tickets_shown()
    {
        // Create inactive ticket
        $location = Location::first();
        $inactiveTicket = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto Disattivato',
            'prezzoIngresso' => 30.00,
            'giacenzaBiglietti' => 50,
            'ingressoAttivo' => 0, // INACTIVE
            'dataIngresso' => now()->addDays(30),
            'giorniPrimaIngresso' => 0
        ]);

        $inactiveTicket->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 50
        ]);

        $response = $this->get('/location/1');

        $response->assertStatus(200);
        $response->assertDontSee('Biglietto Disattivato');
        $response->assertSee('Biglietto Standard');
    }

    /** @test */
    public function test_only_partner_tickets_shown()
    {
        // Create another partner
        Partner::create([
            'idPartner' => 3,
            'nomePartner' => 'Partner Test',
            'emailPartner' => 'test@example.com',
            'attivoPartner' => 1
        ]);

        $location = Location::first();
        
        // Create ticket only for partner 3
        $ticket = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto Partner 3',
            'prezzoIngresso' => 40.00,
            'giacenzaBiglietti' => 50,
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(30),
            'giorniPrimaIngresso' => 0
        ]);

        $ticket->partners()->attach(3, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 50
        ]);

        // Request with partner 5 (default)
        $response = $this->get('/location/1');

        // Should NOT see ticket for partner 3
        $response->assertDontSee('Biglietto Partner 3');
        $response->assertSee('Biglietto Standard');
    }

    /** @test */
    public function test_sold_out_tickets_show_esaurito()
    {
        $location = Location::first();
        
        // Create sold out ticket
        $soldOut = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto Esaurito',
            'prezzoIngresso' => 60.00,
            'giacenzaBiglietti' => 0, // SOLD OUT
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(30),
            'giorniPrimaIngresso' => 0
        ]);

        $soldOut->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 0
        ]);

        $response = $this->get('/location/1');

        $response->assertStatus(200);
        $response->assertSee('Esaurito');
    }

    /** @test */
    public function test_refund_option_shown_when_available()
    {
        $location = Location::first();
        
        // Create ticket with refund option
        $withRefund = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto con Rimborso',
            'prezzoIngresso' => 70.00,
            'giacenzaBiglietti' => 50,
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(30),
            'giorniPrimaIngresso' => 0,
            'opzioneRimborso' => 1,
            'rimborsoValorePersona' => 5.00,
            'rimborsoDataScadenza' => now()->addDays(25),
            'rimborsoTesto' => 'Rimborso completo'
        ]);

        $withRefund->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 50
        ]);

        $response = $this->get('/location/1');

        $response->assertStatus(200);
        $response->assertSee('Opzione rimborso');
        $response->assertSee('5,00');
    }

    /** @test */
    public function test_tickets_ordered_correctly()
    {
        $location = Location::first();
        
        // Create tickets with different orders
        $ticket2 = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto B',
            'prezzoIngresso' => 30.00,
            'giacenzaBiglietti' => 50,
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(20),
            'ordine' => 2,
            'giorniPrimaIngresso' => 0
        ]);

        $ticket2->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 50
        ]);

        $response = $this->get('/location/1');

        $response->assertStatus(200);
        // Should see tickets in order
        $content = $response->getContent();
        $posStandard = strpos($content, 'Biglietto Standard');
        $posB = strpos($content, 'Biglietto B');
        
        // Standard should come before B (ordine not set = 0, which is before 2)
        $this->assertLessThan($posB, $posStandard);
    }

    /** @test */
    public function test_date_filtering_works()
    {
        $location = Location::first();
        
        // Create ticket that's too soon to book (giorniPrimaIngresso = 10, but event is in 5 days)
        $tooSoon = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto Troppo Presto',
            'prezzoIngresso' => 40.00,
            'giacenzaBiglietti' => 50,
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(5),
            'giorniPrimaIngresso' => 10, // Can only book 10+ days before
            'orarioIngresso' => '20:00:00'
        ]);

        $tooSoon->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 50
        ]);

        $response = $this->get('/location/1');

        // Should NOT see this ticket (too soon to book)
        $response->assertDontSee('Biglietto Troppo Presto');
    }

    /** @test */
    public function test_inactive_location_returns_empty_state_instead_of_404()
    {
        $location = Location::first();
        $location->attivaLocation = 0;
        $location->save();

        $response = $this->get('/location/1');

        $response->assertStatus(200);
        $response->assertSee('Disponibilità esaurita per questa location');
    }
}
