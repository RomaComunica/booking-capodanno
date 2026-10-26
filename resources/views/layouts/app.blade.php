<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('meta_title', config('app.name', 'Capodanno Roma'))</title>
    
    <meta name="description" content="@yield('meta_description', 'Prenota il tuo evento di Capodanno con ' . config('app.name'))">
    
    <!-- Open Graph Data -->
    <meta property="og:title" content="@yield('meta_title', config('app.name', 'Capodanno Roma'))" />
    <meta property="og:description" content="@yield('meta_description', 'Prenota il tuo evento di Capodanno con ' . config('app.name'))" />
    @if(trim($__env->yieldContent('og_image')))
        <meta property="og:image" content="@yield('og_image')" />
    @endif
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />

    <!-- Favicon (Carica 'favicon.ico' o 'favicon.png' in public/) -->
    <link rel="icon" type="image/png" href="{{ asset('img/favicon-rc.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Alpine.js for interactive UI components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    
    <!-- Google Analytics -->
    @php
        $gaId = \App\Models\Setting::getVal('google_analytics_id', '');
    @endphp
    @if($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ $gaId }}');
    </script>
    @endif

    <!-- Meta / Facebook Pixel -->
    @php
        $fbPixelId = \App\Models\Setting::getVal('facebook_pixel_id', '');
    @endphp
    @if($fbPixelId)
    <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '{{ $fbPixelId }}');
      fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
      src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"
    /></noscript>
    @endif
</head>
<body class="{{ (isset($iframe) && $iframe) || request()->get('iframe') == '1' ? 'iframe-body' : '' }}">
    <script>
        if (window.self !== window.top) {
            document.body.classList.add('iframe-body');
        }
    </script>
    @if(!isset($iframe) || !$iframe)
    <header class="site-header">
        <div class="container header-container">
            <a href="{{ url('/') }}" class="brand-logo" style="display: flex; align-items: center; gap: 10px;">
                <span class="brand-logo-container" style="background-color: #ffffff !important; display: flex; align-items: center; justify-content: center;">
                    <img src="{{ asset('img/logo-rc-scuro.png') }}" alt="{{ config('app.name', 'Capodanno Roma') }}" class="brand-logo-img" style="height: 45px; max-height: 45px; width: auto; max-width: 100%; object-fit: contain; display: block;" onerror="this.onerror=null; this.parentElement.style.display='none';">
                </span>
            </a>
            <nav class="nav-links">
                <a href="{{ route('cart.index') }}" class="nav-item cart-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="hidden-mobile">Carrello</span>
                </a>
                
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/admin') }}" class="nav-item">Admin</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-item">Log in</a>
                    @endauth
                @endif
            </nav>
        </div>
    </header>
    @endif

    <main class="main-content">
        @if(session('success') || session('error') || $errors->any())
        <div class="container" style="margin-bottom: 1rem;">
            @if(session('success'))
                <div class="alert alert-success animate-fade-in" role="alert">
                    <strong>Successo!</strong>&nbsp;{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error animate-fade-in" role="alert" style="background-color: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                    <strong>Errore!</strong>&nbsp;{{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error animate-fade-in" role="alert">
                    <strong>Errore!</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        @endif

        @yield('content')
    </main>

    @if(!isset($iframe) || !$iframe)
    <footer class="site-footer">
        <div class="container">
            &copy; {{ date('Y') }} {{ config('app.name', 'Capodanno Roma') }}. All rights reserved.
        </div>
    </footer>
    @endif
    
    @stack('scripts')
</body>
</html>
