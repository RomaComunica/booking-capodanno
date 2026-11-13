@props(['step' => 2])

@php
    $steps = [
        1 => 'Location',
        2 => 'Biglietti',
        3 => 'Dati e Pagamento',
        4 => 'Conferma',
    ];
@endphp

<div class="booking-steps mb-2" role="navigation" aria-label="Avanzamento acquisto">
    <style>
        .booking-steps-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.65rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .booking-step-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .booking-step-badge {
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.75);
        }
        .booking-step-item.is-done {
            background: rgba(22, 163, 74, 0.12);
            border-color: rgba(22, 163, 74, 0.4);
            color: #bbf7d0;
        }
        .booking-step-item.is-done .booking-step-badge {
            background: #16A34A;
            color: #ffffff;
        }
        .booking-step-item.is-active {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.55);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }
        .booking-step-item.is-active .booking-step-badge {
            background: #ffffff;
            color: #0a0a0a;
        }
        @media (max-width: 640px) {
            .booking-steps-list {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.35rem;
            }
            .booking-step-item {
                flex-direction: column;
                text-align: center;
                padding: 0.5rem 0.35rem;
                gap: 0.3rem;
                font-size: 0.72rem;
                line-height: 1.15;
            }
            .booking-step-badge {
                width: 22px;
                height: 22px;
                font-size: 0.72rem;
            }
        }
    </style>

    <ol class="booking-steps-list">
        @foreach($steps as $num => $label)
            @php
                $stateClass = $num < $step ? 'is-done' : ($num === $step ? 'is-active' : '');
            @endphp
            <li class="booking-step-item {{ $stateClass }}">
                <span class="booking-step-badge">
                    @if($num < $step)
                        ✓
                    @else
                        {{ $num }}
                    @endif
                </span>
                <span>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</div>
