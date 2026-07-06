<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Ordine;
use App\Models\Voucher;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

class TicketValidationTest extends TestCase
{
    use RefreshDatabase;

    private $location;
    private $partner;
    private $order;
    private $voucher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    private function seedTestData()
    {
        $this->partner = Partner::create([
            'idPartner' => 1,
            'nomePartner' => 'Partner Test',
            'emailPartner' => 'partner@test.com',
            'attivoPartner' => 1
        ]);

        $this->location = Location::create([
            'idLocation' => 1,
            'nomeLocation' => 'Location Test',
            'indirizzoLocation' => 'Via delle Teste 1',
            'attivaLocation' => 1
        ]);

        $this->order = Ordine::create([
            'numeroOrdine' => 10001,
            'idLocationOrdine' => $this->location->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2, // Completato
            'totaleOrdine' => 100.00,
            'nomeClienteOrdine' => 'John',
            'cognomeClienteOrdine' => 'Doe',
            'emailClienteOrdine' => 'john@example.com',
            'telefonoClienteOrdine' => '123456789'
        ]);

        $this->voucher = Voucher::create([
            'numeroVoucher' => 20001,
            'numeroOrdineVoucher' => $this->order->numeroOrdine,
            'idLocationVoucher' => $this->location->idLocation,
            'nomeVoucher' => 'John',
            'cognomeVoucher' => 'Doe',
            'emailVoucher' => 'john@example.com',
            'telefonoVoucher' => '123456789',
            'utilizzato' => false,
            'data_utilizzo' => null
        ]);
    }

    /** @test */
    public function test_validation_route_requires_signature()
    {
        // Access route directly without a signature
        $response = $this->get(route('voucher.validate', [
            'numeroOrdine' => $this->order->numeroOrdine,
            'voucher' => $this->voucher->numeroVoucher
        ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_customer_scan_shows_valid_status_without_action_button()
    {
        // Generate signed route
        $url = URL::signedRoute('voucher.validate', [
            'numeroOrdine' => $this->order->numeroOrdine,
            'voucher' => $this->voucher->numeroVoucher
        ]);

        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('voucher.validate');
        $response->assertSee('Biglietto Valido');
        $response->assertSee('Presenta il QR code originale allo staff');
        // Staff action button should NOT be visible
        $response->assertDontSee('Registra Ingresso');
        $response->assertSee('Sei dello staff? Abilita questo dispositivo');
    }

    /** @test */
    public function test_staff_login_page_loads()
    {
        $response = $this->get(route('staff.login', ['redirect' => 'http://localhost/test-redirect']));

        $response->assertStatus(200);
        $response->assertViewIs('voucher.staff_login');
        $response->assertSee('Area Controllo Staff');
    }

    /** @test */
    public function test_staff_login_with_incorrect_password_fails()
    {
        $response = $this->from(route('staff.login'))->post(route('staff.process-login'), [
            'password' => 'wrongpassword',
            'redirect_url' => 'http://localhost/test'
        ]);

        $response->assertRedirect(route('staff.login'));
        $response->assertSessionHasErrors('password');
        $this->assertNull(request()->cookie('staff_device'));
    }

    /** @test */
    public function test_staff_login_with_correct_password_sets_cookie()
    {
        $response = $this->post(route('staff.process-login'), [
            'password' => 'staff2026',
            'redirect_url' => 'http://localhost/test-redirect'
        ]);

        $response->assertRedirect('http://localhost/test-redirect');
        $response->assertCookie('staff_device', 'authenticated_staff_member');
    }

    /** @test */
    public function test_staff_view_shows_checkin_button()
    {
        $url = URL::signedRoute('voucher.validate', [
            'numeroOrdine' => $this->order->numeroOrdine,
            'voucher' => $this->voucher->numeroVoucher
        ]);

        $response = $this->withCookie('staff_device', 'authenticated_staff_member')->get($url);

        $response->assertStatus(200);
        $response->assertSee('Dispositivo Staff');
        $response->assertSee('Registra Ingresso');
        $response->assertDontSee('Sei dello staff? Abilita questo dispositivo');
    }

    /** @test */
    public function test_staff_can_confirm_checkin()
    {
        $response = $this->withCookie('staff_device', 'authenticated_staff_member')
            ->post(route('voucher.confirm-checkin', [
                'numeroOrdine' => $this->order->numeroOrdine,
                'numeroVoucher' => $this->voucher->numeroVoucher
            ]));

        $response->assertRedirect();
        
        // Refresh voucher and assert check-in fields
        $this->voucher->refresh();
        $this->assertTrue($this->voucher->utilizzato);
        $this->assertNotNull($this->voucher->data_utilizzo);
    }

    /** @test */
    public function test_non_staff_cannot_confirm_checkin()
    {
        $response = $this->post(route('voucher.confirm-checkin', [
            'numeroOrdine' => $this->order->numeroOrdine,
            'numeroVoucher' => $this->voucher->numeroVoucher
        ]));

        $response->assertStatus(403);
        
        $this->voucher->refresh();
        $this->assertFalse($this->voucher->utilizzato);
    }

    /** @test */
    public function test_already_used_ticket_shows_used_status()
    {
        // Mark voucher as used
        $this->voucher->update([
            'utilizzato' => true,
            'data_utilizzo' => now()
        ]);

        $url = URL::signedRoute('voucher.validate', [
            'numeroOrdine' => $this->order->numeroOrdine,
            'voucher' => $this->voucher->numeroVoucher
        ]);

        $response = $this->withCookie('staff_device', 'authenticated_staff_member')->get($url);

        $response->assertStatus(200);
        $response->assertSee('Già Utilizzato');
        $response->assertDontSee('Registra Ingresso');
    }

    /** @test */
    public function test_unpaid_order_shows_warning()
    {
        // Change order to pending
        $this->order->update(['statoOrdine' => 0]);

        $url = URL::signedRoute('voucher.validate', [
            'numeroOrdine' => $this->order->numeroOrdine,
            'voucher' => $this->voucher->numeroVoucher
        ]);

        $response = $this->withCookie('staff_device', 'authenticated_staff_member')->get($url);

        $response->assertStatus(200);
        $response->assertSee('Pagamento In Attesa');
        $response->assertDontSee('Registra Ingresso');
    }
}
