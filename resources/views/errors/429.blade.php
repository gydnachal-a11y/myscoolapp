@extends('layouts.admin')

@section('page_title', 'Trop de requêtes')
@section('page_subtitle', 'Veuillez patienter quelques instants')

@section('content')
@php
    $webUser = auth('web')->user();
    $homeRoute = $webUser ? 'admin.redirect' : 'home';

    // Récupère Retry-After (secondes) depuis les headers de l'exception
    $retryAfter = null;
    if (method_exists($exception, 'getHeaders')) {
        $headers = $exception->getHeaders();
        $retryAfter = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;
    }

    // Formate un délai lisible
    $retryText = null;
    if ($retryAfter !== null && $retryAfter > 0) {
        if ($retryAfter < 60) {
            $retryText = "{$retryAfter} seconde" . ($retryAfter > 1 ? 's' : '');
        } elseif ($retryAfter < 3600) {
            $minutes = (int) ceil($retryAfter / 60);
            $retryText = "{$minutes} minute" . ($minutes > 1 ? 's' : '');
        } else {
            $hours = (int) ceil($retryAfter / 3600);
            $retryText = "{$hours} heure" . ($hours > 1 ? 's' : '');
        }
    }
@endphp

<div class="error-page error-page-429"
     x-data="rateLimitCountdown({{ $retryAfter ?? 0 }})"
     x-init="start()">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HERO --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-hero">
        <div class="error-icon-wrapper" aria-hidden="true">
            <i class="fa-solid fa-gauge-high"></i>
        </div>

        <span class="error-code">Erreur 429</span>

        <h1 class="error-title">Trop de requêtes</h1>

        <p class="error-message">
            Vous avez envoyé trop de requêtes en peu de temps.
            @if($retryText)
                Veuillez patienter <strong>{{ $retryText }}</strong> avant de réessayer.
            @else
                Veuillez patienter quelques instants avant de réessayer.
            @endif
        </p>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- COMPTEUR (si Retry-After disponible) --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @if($retryAfter !== null && $retryAfter > 0)
        <div class="countdown-card" x-show="remaining > 0" x-cloak>
            <div class="countdown-label">
                <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                <span>Nouvel essai possible dans</span>
            </div>
            <div class="countdown-timer">
                <span class="countdown-value" x-text="formatted"></span>
            </div>
            <div class="countdown-progress">
                <div class="countdown-bar"
                     :style="`width: ${progress}%`"></div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- ACTIONS --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-actions">
        <button type="button"
                class="error-btn error-btn-primary"
                :disabled="remaining > 0"
                @click="retry()">
            <i class="fa-solid"
               :class="remaining > 0 ? 'fa-hourglass-half' : 'fa-rotate-right'"
               aria-hidden="true"></i>
            <span x-text="remaining > 0 ? 'Patientez…' : 'Réessayer'"></span>
        </button>

        @if(Route::has($homeRoute))
            <a href="{{ route($homeRoute) }}" class="error-btn error-btn-ghost">
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                <span>Retour à l'accueil</span>
            </a>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- AIDE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-help">

        <div class="help-header">
            <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
            <h2>Pourquoi cette limite ?</h2>
        </div>

        <ul class="help-list">
            <li>
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <div>
                    <strong>Protection anti-abus</strong>
                    <span>Cette limite protège le serveur contre les attaques et les surcharges.</span>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-hand" aria-hidden="true"></i>
                <div>
                    <strong>Clics répétés</strong>
                    <span>Vous avez peut-être cliqué plusieurs fois sur un bouton ou rafraîchi la page trop vite.</span>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-robot" aria-hidden="true"></i>
                <div>
                    <strong>Script automatisé</strong>
                    <span>Un onglet ou une extension peut envoyer des requêtes en arrière-plan.</span>
                </div>
            </li>
        </ul>

        <div class="help-footer">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <p>
                Patientez quelques instants puis rechargez la page manuellement.
                Évitez de cliquer plusieurs fois sur les boutons.
            </p>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FOOTER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-footer">
        <p>
            Code d'erreur <code>429</code> · Too Many Requests
        </p>
    </div>

</div>
@endsection

@push('styles')
@include('errors._shared-styles')
<style>
    /* ════════════════════════════════════════════════════════
       SPÉCIFIQUE 429 — Palette ambrée
       ════════════════════════════════════════════════════════ */
    .error-page-429 .error-icon-wrapper {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        box-shadow:
            0 0 0 8px rgba(254, 243, 199, 0.5),
            0 12px 32px rgba(217, 119, 6, 0.18);
    }
    .error-page-429 .error-icon-wrapper i {
        color: #d97706;
    }
    .error-page-429 .error-code {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }
    .error-page-429 .help-list li > i {
        background: #fffbeb;
        color: #d97706;
    }
    .error-page-429 .help-footer {
        border-left-color: #d97706;
    }
    .error-page-429 .help-footer i {
        color: #d97706;
    }

    /* ════════════════════════════════════════════════════════
       COMPTEUR
       ════════════════════════════════════════════════════════ */
    .countdown-card {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 1px solid #fde68a;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(217, 119, 6, 0.08);
    }

    .countdown-label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #92400e;
        margin-bottom: 0.75rem;
    }
    .countdown-label i { font-size: 0.9rem; }

    .countdown-timer {
        display: flex;
        justify-content: center;
        align-items: baseline;
        margin-bottom: 1rem;
    }

    .countdown-value {
        font-size: 3rem;
        font-weight: 800;
        color: #b45309;
        letter-spacing: -1.5px;
        font-variant-numeric: tabular-nums;
        line-height: 1;
        transition: color 0.3s;
    }

    .countdown-progress {
        height: 6px;
        background: rgba(217, 119, 6, 0.15);
        border-radius: 9999px;
        overflow: hidden;
    }

    .countdown-bar {
        height: 100%;
        background: linear-gradient(90deg, #f59e0b, #d97706);
        border-radius: 9999px;
        transition: width 1s linear;
    }

    @media (max-width: 640px) {
        .countdown-card { padding: 1.15rem; }
        .countdown-value { font-size: 2.25rem; }
        .countdown-label { font-size: 0.78rem; }
    }
</style>
@endpush

@push('scripts')
<script>
    /**
     * ✅ Compteur de décompte pour la page 429.
     * Désactive le bouton "Réessayer" tant que le délai n'est pas écoulé.
     */
    function rateLimitCountdown(initialSeconds) {
        return {
            total: initialSeconds,
            remaining: initialSeconds,

            get progress() {
                if (this.total === 0) return 100;
                return ((this.total - this.remaining) / this.total) * 100;
            },

            get formatted() {
                const s = this.remaining;
                if (s <= 0) return '0s';

                if (s < 60) {
                    return `${s}s`;
                }

                const m = Math.floor(s / 60);
                const sec = s % 60;
                return `${m}:${String(sec).padStart(2, '0')}`;
            },

            start() {
                if (this.total <= 0) return;

                const tick = () => {
                    if (this.remaining > 0) {
                        this.remaining--;
                        setTimeout(tick, 1000);
                    }
                };

                // Démarrer immédiatement, pas d'attente initiale
                tick();
            },

            retry() {
                if (this.remaining > 0) return;
                window.location.reload();
            },
        };
    }
</script>
@endpush