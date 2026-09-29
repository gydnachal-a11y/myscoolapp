@extends('layouts.admin')

@section('page_title', 'Erreur serveur')
@section('page_subtitle', 'Une erreur est survenue')

@section('content')
@php
    $webUser = auth('web')->user();
    $homeRoute = $webUser ? 'admin.redirect' : 'home';

    // ⚠️ En production, on n'expose JAMAIS le message technique
    $isDebug = config('app.debug');
    $technicalMessage = $exception->getMessage() ?: 'Erreur inconnue.';

    $publicMessage = "Une erreur inattendue est survenue de notre côté. "
        . "Nos équipes ont été notifiées et travaillent à la résolution.";
@endphp

<div class="error-page error-page-500">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HERO --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-hero">
        <div class="error-icon-wrapper" aria-hidden="true">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <span class="error-code">Erreur 500</span>

        <h1 class="error-title">Erreur serveur</h1>

        <p class="error-message">{{ $publicMessage }}</p>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- ACTIONS --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-actions">
        <button type="button"
                class="error-btn error-btn-primary"
                onclick="window.location.reload()">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
            <span>Réessayer</span>
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
            <h2>Que faire maintenant ?</h2>
        </div>

        <ul class="help-list">
            <li>
                <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                <div>
                    <strong>Rechargez la page</strong>
                    <span>L'erreur peut être temporaire. Attendez quelques secondes puis réessayez.</span>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                <div>
                    <strong>Réessayez plus tard</strong>
                    <span>Si le problème persiste, il peut s'agir d'une maintenance ou d'une panne temporaire.</span>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                <div>
                    <strong>Contactez le support</strong>
                    <span>Si l'erreur se reproduit systématiquement, signalez-la avec l'heure exacte.</span>
                </div>
            </li>
        </ul>

        <div class="help-footer">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <p>
                @if($webUser)
                    L'incident a été automatiquement enregistré. Si vous étiez en train
                    d'enregistrer des données, vérifiez qu'elles ont bien été sauvegardées.
                @else
                    Aucune action de votre part n'est requise. L'équipe technique a été alertée.
                @endif
            </p>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- DEBUG (visible uniquement en local) --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @if($isDebug)
        <div class="error-debug">
            <div class="debug-header">
                <i class="fa-solid fa-bug" aria-hidden="true"></i>
                <strong>Mode debug — informations techniques</strong>
            </div>
            <div class="debug-body">
                <p><strong>Message :</strong> {{ $technicalMessage }}</p>
                <p><strong>Fichier :</strong> <code>{{ $exception->getFile() }}:{{ $exception->getLine() }}</code></p>
                @if(method_exists($exception, 'getTraceAsString'))
                    <details>
                        <summary>Stack trace</summary>
                        <pre>{{ $exception->getTraceAsString() }}</pre>
                    </details>
                @endif
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FOOTER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-footer">
        <p>
            Code d'erreur <code>500</code> · Internal Server Error
        </p>
    </div>

</div>
@endsection

@push('styles')
@include('errors._shared-styles')
<style>
    /* ════════════════════════════════════════════════════════
       SPÉCIFIQUE 500 — Palette violette
       ════════════════════════════════════════════════════════ */
    .error-page-500 .error-icon-wrapper {
        background: linear-gradient(135deg, #ede9fe, #ddd6fe);
        box-shadow:
            0 0 0 8px rgba(237, 233, 254, 0.5),
            0 12px 32px rgba(124, 58, 237, 0.18);
    }
    .error-page-500 .error-icon-wrapper i {
        color: #7c3aed;
    }
    .error-page-500 .error-code {
        background: #f5f3ff;
        color: #6d28d9;
        border-color: #ddd6fe;
    }
    .error-page-500 .help-list li > i {
        background: #f5f3ff;
        color: #7c3aed;
    }
    .error-page-500 .help-footer {
        border-left-color: #7c3aed;
    }
    .error-page-500 .help-footer i {
        color: #7c3aed;
    }

    /* ════════════════════════════════════════════════════════
       Bloc DEBUG
       ════════════════════════════════════════════════════════ */
    .error-debug {
        background: #0f172a;
        border: 1px solid #1e293b;
        border-radius: 12px;
        margin-bottom: 2rem;
        overflow: hidden;
        text-align: left;
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.78rem;
    }

    .debug-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: #1e293b;
        color: #fbbf24;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .debug-header i { font-size: 0.85rem; }

    .debug-body {
        padding: 1rem;
        color: #cbd5e1;
        line-height: 1.6;
    }
    .debug-body p {
        margin: 0 0 0.6rem;
        word-break: break-word;
    }
    .debug-body p:last-child { margin-bottom: 0; }
    .debug-body strong { color: #f1f5f9; }
    .debug-body code {
        display: inline-block;
        padding: 0.1rem 0.35rem;
        background: #334155;
        color: #fbbf24;
        border-radius: 4px;
        font-size: 0.75rem;
    }
    .debug-body details {
        margin-top: 0.6rem;
    }
    .debug-body summary {
        cursor: pointer;
        color: #fbbf24;
        font-weight: 600;
        padding: 0.4rem 0;
        user-select: none;
    }
    .debug-body summary:hover { color: #fde047; }
    .debug-body pre {
        margin: 0.5rem 0 0;
        padding: 0.85rem;
        background: #020617;
        border-radius: 6px;
        overflow-x: auto;
        color: #94a3b8;
        font-size: 0.72rem;
        line-height: 1.5;
        max-height: 400px;
        white-space: pre;
    }

    @media (max-width: 640px) {
        .error-debug { font-size: 0.72rem; }
        .debug-body pre { font-size: 0.68rem; max-height: 300px; }
    }
</style>
@endpush