<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Location;
use App\Models\Partner;
use App\Models\TipologiaIngresso;
use App\Models\CategoriaLocation;
use App\Models\TipologiaLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed basic data for tests
        $this->seedTestData();
    }

    protected function seedTestData()
    {
        // Create partners
        Partner::create([
            'idPartner' => 5,
            'nomePartner' => 'Partner Gestore',
            'emailPartner' => 'gestore@example.com',
            'attivoPartner' => 1
        ]);

        Partner::create([
            'idPartner' => 3,
            'nomePartner' => 'Partner Test',
            'emailPartner' => 'test@example.com',
            'attivoPartner' => 1
        ]);

        // Create categories and types
        $categoria = CategoriaLocation::create([
            'nomeCatLocation' => 'Ristoranti'
        ]);

        $tipologia = TipologiaLocation::create([
            'nomeTipLocation' => 'Cenone'
        ]);

        // Create location
        $location = Location::create([
            'nomeLocation' => 'Location Test Roma',
            'indirizzoLocation' => 'Via Roma 1',
            'telefonoLocation' => '06123456',
            'attivaLocation' => 1
        ]);

        // Associate category and type
        $location->categorie()->attach($categoria->idCatLocation);
        $location->tipologie()->attach($tipologia->idTipLocation);

        // Create active ticket
        $ingresso = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto Standard',
            'prezzoIngresso' => 50.00,
            'giacenzaBiglietti' => 100,
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(30),
            'orarioIngresso' => '20:00:00'
        ]);

        // Associate ticket with partner
        $ingresso->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 100
        ]);
    }

    /** @test */
    public function test_homepage_loads_successfully()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('ACQUISTA ONLINE');
    }

    /** @test */
    public function test_partner_tracking_from_url()
    {
        $response = $this->get('/?idPartner=3');

        $response->assertStatus(200);
        $this->assertEquals(3, session('idPartner'));
    }

    /** @test */
    public function test_default_partner_when_not_specified()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertEquals(5, session('idPartner'));
    }

    /** @test */
    public function test_locations_filtered_by_partner()
    {
        // Create a location NOT associated with partner 3
        $location2 = Location::create([
            'nomeLocation' => 'Location Non Associata',
            'attivaLocation' => 1
        ]);

        $ingresso2 = TipologiaIngresso::create([
            'idLocation' => $location2->idLocation,
            'nomeIngresso' => 'Biglietto',
            'prezzoIngresso' => 30.00,
            'giacenzaBiglietti' => 50,
            'ingressoAttivo' => 1,
            'dataIngresso' => now()->addDays(30)
        ]);

        // Associate only with partner 5, not 3
        $ingresso2->partners()->attach(5, [
            'idlocation' => $location2->idLocation,
            'quantitaIngressi' => 50
        ]);

        // Request with partner 3
        $response = $this->get('/?idPartner=3');

        // Should NOT see "Location Non Associata" because it's not linked to partner 3
        $response->assertDontSee('Location Non Associata');
    }

    /** @test */
    public function test_search_filters_locations()
    {
        $response = $this->get('/?search=Roma');

        $response->assertStatus(200);
        $response->assertSee('Location Test Roma');
    }

    /** @test */
    public function test_category_filter_works()
    {
        $categoria = CategoriaLocation::first();
        
        $response = $this->get('/?categoria=' . $categoria->idCatLocation);

        $response->assertStatus(200);
        $response->assertSee('Location Test Roma');
    }

    /** @test */
    public function test_iframe_mode_works()
    {
        $response = $this->get('/?iframe=1');

        $response->assertStatus(200);
        // In iframe mode, should not show full header
        $response->assertDontSee('Metodi di pagamento');
    }

    /** @test */
    public function test_only_active_locations_shown()
    {
        // Create inactive location
        $inactiveLocation = Location::create([
            'nomeLocation' => 'Location Disattivata',
            'attivaLocation' => 0
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Location Disattivata');
    }

    /** @test */
    public function test_only_locations_with_active_tickets_shown()
    {
        // Create location with inactive ticket
        $location = Location::create([
            'nomeLocation' => 'Location Senza Biglietti Attivi',
            'attivaLocation' => 1
        ]);

        $ingresso = TipologiaIngresso::create([
            'idLocation' => $location->idLocation,
            'nomeIngresso' => 'Biglietto Disattivato',
            'prezzoIngresso' => 40.00,
            'giacenzaBiglietti' => 50,
            'ingressoAttivo' => 0, // INACTIVE
            'dataIngresso' => now()->addDays(30)
        ]);

        $ingresso->partners()->attach(5, [
            'idlocation' => $location->idLocation,
            'quantitaIngressi' => 50
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Location Senza Biglietti Attivi');
    }
}
