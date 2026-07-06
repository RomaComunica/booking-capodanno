<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Gli URI esclusi dalla verifica CSRF.
     *
     * @var array<int, string>
     */
    protected $except = [
        'pay/nexi/callback',   // niente slash iniziale va bene; anche '/pay/nexi/callback' funziona
        // 'pay/nexi/*',        // se vuoi escludere tutte le rotte Nexi
    ];
}
