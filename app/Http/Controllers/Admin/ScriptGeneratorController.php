<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Partner;
use Illuminate\Http\Request;

class ScriptGeneratorController extends Controller
{
    public function index()
    {
        $locations = Location::where('attivaLocation', true)
            ->orderBy('nomeLocation')
            ->get();
        
        $partners = Partner::where('attivoPartner', true)
            ->orderBy('nomePartner')
            ->get();

        return view('admin.script-generator', compact('locations', 'partners'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:link,button,iframe,new_window',
            'partner_id' => 'required|exists:partner,idPartner',
            'location_id' => 'nullable|exists:location,idLocation',
        ]);

        $partnerId = $request->partner_id;
        $locationId = $request->location_id ?? 0;
        $type = $request->type;

        $baseUrl = config('app.url');
        $bookingUrl = "{$baseUrl}?partner={$partnerId}&location={$locationId}";

        $scripts = [
            'link' => $this->generateLink($bookingUrl),
            'button' => $this->generateButton($bookingUrl),
            'iframe' => $this->generateIframe($bookingUrl),
            'new_window' => $this->generateNewWindow($bookingUrl),
        ];

        return response()->json([
            'script' => $scripts[$type],
            'preview_url' => $bookingUrl,
        ]);
    }

    private function generateLink($url)
    {
        return <<<HTML
<!-- Link Prenotazione -->
<a href="{$url}" target="_blank" rel="noopener">
    Prenota ora
</a>
HTML;
    }

    private function generateButton($url)
    {
        return <<<HTML
<!-- Pulsante Prenotazione -->
<a href="{$url}" target="_blank" rel="noopener" style="display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
    Prenota ora
</a>
HTML;
    }

    private function generateIframe($url)
    {
        return <<<HTML
<!-- Iframe Prenotazione -->
<iframe src="{$url}" width="100%" height="800" frameborder="0" style="border: none; max-width: 1200px;">
    Il tuo browser non supporta gli iframe. <a href="{$url}" target="_blank">Clicca qui per prenotare</a>
</iframe>
HTML;
    }

    private function generateNewWindow($url)
    {
        return <<<HTML
<!-- Link con Nuova Finestra -->
<a href="#" onclick="window.open('{$url}', 'booking', 'width=1200,height=800,scrollbars=yes,resizable=yes'); return false;">
    Prenota ora
</a>
HTML;
    }
}
