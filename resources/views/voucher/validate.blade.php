<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convalida Voucher #{{ $voucher->numeroVoucher }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0b0f19 0%, #111827 100%);
            color: #f3f4f6;
            min-height: 100vh;
            padding: 16px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .wrapper {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Status cards styling */
        .status-card {
            border-radius: 24px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        /* State: VALID */
        .status-valid {
            background: linear-gradient(135deg, #065f46 0%, #064e3b 100%);
            border: 1px solid rgba(52, 211, 153, 0.2);
        }
        .status-valid::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        /* State: USED */
        .status-used {
            background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
            border: 1px solid rgba(248, 113, 113, 0.2);
        }
        
        /* State: UNPAID/CANCELLED */
        .status-warning {
            background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
            border: 1px solid rgba(251, 191, 36, 0.2);
        }

        .status-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
        }
        
        .status-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .status-subtitle {
            font-size: 14px;
            color: #d1d5db;
            line-height: 1.4;
        }
        
        /* Info boxes */
        .info-card {
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .info-section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 12px;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 6px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.5;
        }
        .detail-row:last-child {
            margin-bottom: 0;
        }
        
        .detail-label {
            color: #9ca3af;
        }
        
        .detail-value {
            font-weight: 500;
            color: #f3f4f6;
            text-align: right;
        }
        
        /* Tickets List */
        .ticket-item {
            background: rgba(17, 24, 39, 0.5);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ticket-item:last-child {
            margin-bottom: 0;
        }
        
        .ticket-info {
            flex-grow: 1;
            padding-right: 12px;
        }
        
        .ticket-name {
            font-size: 14px;
            font-weight: 600;
            color: #f3f4f6;
        }
        
        .ticket-date {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 2px;
        }
        
        .ticket-qty {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 14px;
            font-weight: 700;
            color: #cbd5e1;
            white-space: nowrap;
        }

        /* Action Buttons & Staff panel */
        .action-card {
            background: rgba(17, 24, 39, 0.8);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 25px rgba(99, 102, 241, 0.08);
            margin-bottom: 20px;
        }
        
        .btn-checkin {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 16px;
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
            filter: brightness(1.1);
        }
        .btn-checkin:active {
            transform: translateY(0);
        }
        
        .staff-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .text-muted {
            font-size: 13px;
            color: #9ca3af;
            line-height: 1.5;
        }
        
        .staff-link {
            color: #818cf8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .staff-link:hover {
            color: #a5b4fc;
            text-decoration: underline;
        }
        
        .footer-logo {
            text-align: center;
            margin-top: 30px;
            opacity: 0.5;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        
        <!-- SUCCESS BANNER FROM POST CHECKIN -->
        @if (session('success'))
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @php
            $isUsed = $voucher->utilizzato;
            $isPaid = $order->statoOrdine == 2;
            $isCancelled = $order->statoOrdine == 1;
        @endphp

        <!-- STATUS CARDS -->
        @if ($isUsed)
            <!-- GIÀ UTILIZZATO -->
            <div class="status-card status-used">
                <div class="status-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
                <div class="status-title">Già Utilizzato</div>
                <div class="status-subtitle">
                    Ingresso già registrato il <strong>{{ \Carbon\Carbon::parse($voucher->data_utilizzo)->format('d/m/Y \a\l\l\e H:i:s') }}</strong>.
                </div>
            </div>
        @elseif (!$isPaid)
            <!-- ANNULLATO / DA COMPLETARE -->
            <div class="status-card status-warning">
                <div class="status-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div class="status-title">
                    {{ $isCancelled ? 'Voucher Annullato' : 'Pagamento In Attesa' }}
                </div>
                <div class="status-subtitle">
                    {{ $isCancelled ? 'Questo ordine risulta annullato e non è valido per l\'ingresso.' : 'L\'ordine associato non risulta ancora saldato/completato.' }}
                </div>
            </div>
        @else
            <!-- BIGLIETTO VALIDO -->
            <div class="status-card status-valid">
                <div class="status-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="status-title">Biglietto Valido</div>
                <div class="status-subtitle">
                    Pronto per la convalida all'ingresso.
                </div>
            </div>
        @endif

        <!-- DETAILS CARD -->
        <div class="info-card">
            <div class="info-section-title">Dettagli Voucher</div>
            <div class="detail-row">
                <span class="detail-label">Codice Voucher</span>
                <span class="detail-value" style="color: #60a5fa; font-weight: 700;">#{{ $voucher->numeroVoucher }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Intestatario</span>
                <span class="detail-value">{{ $voucher->nomeVoucher }} {{ $voucher->cognomeVoucher }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Location</span>
                <span class="detail-value" style="font-weight: 600;">{{ $location->nomeLocation }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Stato Pagamento</span>
                <span class="detail-value">
                    @if ($isPaid)
                        <span style="color: #34d399; font-weight: 600;">✓ Confermato (Saldato)</span>
                    @elseif ($isCancelled)
                        <span style="color: #f87171; font-weight: 600;">✗ Annullato</span>
                    @else
                        <span style="color: #fbbf24; font-weight: 600;">⚠ In attesa</span>
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Numero Ordine</span>
                <span class="detail-value">#{{ $order->numeroOrdine }}</span>
            </div>
        </div>

        <!-- TICKET ITEMS -->
        <div class="info-card">
            <div class="info-section-title">Biglietti / Servizi Inclusi</div>
            @foreach($orderLines as $line)
                <div class="ticket-item">
                    <div class="ticket-info">
                        <div class="ticket-name">{{ $line->nomeIngressoOrdine }}</div>
                        <div class="ticket-date">
                            @if($line->ingressoSenzaDataOrdine)
                                {{ $line->etichettaIngressoOrdine }} {{ $line->orarioIngressoOrdine ? '- Ore ' . \Carbon\Carbon::parse($line->orarioIngressoOrdine)->format('H:i') : '' }}
                            @else
                                {{ $line->dataIngressoOrdine ? \Carbon\Carbon::parse($line->dataIngressoOrdine)->format('d/m/Y') : '' }} {{ $line->orarioIngressoOrdine ? '- Ore ' . \Carbon\Carbon::parse($line->orarioIngressoOrdine)->format('H:i') : '' }}
                            @endif
                        </div>
                    </div>
                    <div class="ticket-qty">
                        x{{ $line->quantitaIngressoOrdine }}
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ACTIONS CARD (Depends on Staff / Customer status) -->
        @if ($isStaff)
            <div class="action-card">
                <div class="staff-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Dispositivo Staff</span>
                </div>
                
                @if (!$isUsed && $isPaid)
                    <form action="{{ route('voucher.confirm-checkin', ['numeroOrdine' => $order->numeroOrdine, 'numeroVoucher' => $voucher->numeroVoucher]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-checkin">Registra Ingresso</button>
                    </form>
                    <p class="text-muted" style="margin-top: 12px;">
                        Tocca per confermare il passaggio all'ingresso e invalidare il QR Code.
                    </p>
                @elseif ($isUsed)
                    <p class="text-muted" style="color: #fca5a5;">
                        Biglietto già utilizzato. Non è possibile effettuare un secondo check-in.
                    </p>
                @elseif ($isCancelled)
                    <p class="text-muted" style="color: #f87171;">
                        Ordine annullato. Non abilitato al check-in.
                    </p>
                @else
                    <p class="text-muted" style="color: #fcd34d;">
                        Pagamento non completato. Non abilitato al check-in.
                    </p>
                @endif
            </div>
        @else
            <!-- CUSTOMER PREVIEW -->
            <div class="action-card" style="border-color: rgba(255,255,255,0.05); background: rgba(31, 41, 55, 0.4);">
                <p class="text-muted">
                    Questo schermo serve solo come verifica del biglietto.<br>
                    Presenta il QR code originale allo staff all'ingresso per consentire la convalida del voucher.
                </p>
                <div style="margin-top: 20px; font-size: 13px;">
                    <a href="{{ route('staff.login', ['redirect' => request()->fullUrl()]) }}" class="staff-link">
                        Sei dello staff? Abilita questo dispositivo
                    </a>
                </div>
            </div>
        @endif

        <div class="footer-logo">
            <img src="{{ asset('img/logo-rc-scuro.png') }}" alt="Roma Comunica" style="max-height: 30px; margin-bottom: 5px; filter: brightness(2) contrast(0.5);"><br>
            Booking Online Eventi
        </div>

    </div>
</body>
</html>
