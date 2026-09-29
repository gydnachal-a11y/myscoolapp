@extends('layouts.admin')

@section('page_title', 'Reçu')
@section('page_subtitle', 'Référence #' . $paiement->id)

@section('content')
@php
    $settings = $siteSettings ?? \App\Models\SiteSetting::getSettings();
    $fmtUsd = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $fmtFc  = fn ($v) => number_format((float) $v, 0, ',', ' ');

    $isPrincipal = ($type ?? 'principal') === 'principal';

    // Choix de la route de retour
    $routeRetour = $isPrincipal
        ? route('admin.paiements.index')
        : route('admin.paiement-frais-supplementaires.index');

    // Statut : on utilise les accessors du modèle Paiement si disponibles
    $statutKey   = $paiement->statut_key   ?? 'neutral';
    $statutLabel = $paiement->statut_label ?? ucfirst($paiement->statut ?? '—');
    $statutIcon  = $paiement->statut_icon  ?? 'fa-circle';
@endphp

<div class="page">

    {{-- HEADER --}}
    <header class="page-header no-print">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-receipt title-icon" aria-hidden="true"></i>
                @if($isPrincipal) Reçu de paiement @else Reçu de frais supplémentaire @endif
            </h1>
            <p class="page-subtitle">Référence #{{ $paiement->id }}</p>
        </div>
        <div class="header-actions">
            <a href="{{ $routeRetour }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Retour
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print" aria-hidden="true"></i> Imprimer
            </button>
        </div>
    </header>

    {{-- REÇU --}}
    <article class="recu">

        {{-- EN-TÊTE --}}
        <header class="recu-header">
            <div class="recu-brand">
                @if(!empty($settings->site_logo))
                    <img src="{{ asset('storage/' . $settings->site_logo) }}"
                         alt="{{ $settings->site_name }}" class="recu-logo">
                @else
                    <div class="recu-logo recu-logo-placeholder">
                        {{ strtoupper(substr($settings->site_name ?? 'E', 0, 1)) }}
                    </div>
                @endif
                <div class="recu-brand-info">
                    <h2 class="recu-brand-name">{{ $settings->site_name ?? config('app.name', 'Mon École') }}</h2>
                    @if(!empty($settings->site_address)) <p>{{ $settings->site_address }}</p> @endif
                    @if(!empty($settings->site_email))   <p>{{ $settings->site_email }}</p>   @endif
                    @if(!empty($settings->site_phone))   <p>{{ $settings->site_phone }}</p>   @endif
                </div>
            </div>
            <div class="recu-ref">
                <p class="recu-ref-label">
                    @if($isPrincipal) Reçu de paiement @else Reçu @endif
                </p>
                <p class="recu-ref-id">#{{ $paiement->id }}</p>
                <p class="recu-ref-date">
                    {{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}
                </p>
            </div>
        </header>

        {{-- DÉTAILS --}}
        <section class="recu-section recu-section-alt">
            @if($isPrincipal)
                <div class="recu-grid-3">
                    <div>
                        <p class="recu-label">Élève</p>
                        <p class="recu-value-lg">{{ $paiement->eleve?->nom_complet ?? '—' }}</p>
                        @if($paiement->salleClasse)
                            <p class="recu-meta">{{ $paiement->salleClasse->nom }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="recu-label">Période</p>
                        <p class="recu-value-lg">{{ $paiement->periode ?? '—' }}</p>
                        <p class="recu-meta">{{ ucfirst($paiement->type_periode ?? '—') }}</p>
                    </div>
                    <div class="recu-right">
                        <p class="recu-label">Statut</p>
                        <span class="pill pill-{{ $statutKey }}">
                            <i class="fa-solid {{ $statutIcon }}" aria-hidden="true"></i>
                            {{ $statutLabel }}
                        </span>
                    </div>
                </div>
            @else
                <div class="recu-grid-2">
                    <div>
                        <p class="recu-label">Élève</p>
                        <p class="recu-value-lg">{{ $paiement->eleve?->nom_complet ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="recu-label">Frais supplémentaire</p>
                        <p class="recu-value-lg">
                            <span class="pill pill-indigo">
                                <i class="fa-solid fa-tag" aria-hidden="true"></i>
                                {{ $paiement->fraisSupplementaire?->libelle ?? '—' }}
                            </span>
                        </p>
                    </div>
                </div>
            @endif
        </section>

        {{-- TABLEAU MONTANTS --}}
        <section class="recu-section">
            <table class="recu-table">
                <tbody>
                    @if($isPrincipal)
                        <tr>
                            <td>Montant attendu (USD)</td>
                            <td class="text-right">${{ $fmtUsd($paiement->montant_attendu_usd ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td>Montant payé (USD)</td>
                            <td class="text-right text-success">${{ $fmtUsd($paiement->montant_paye_usd ?? 0) }}</td>
                        </tr>
                        @if(!empty($paiement->montant_restant_usd))
                            <tr>
                                <td>Reste à payer (USD)</td>
                                <td class="text-right text-danger">${{ $fmtUsd($paiement->montant_restant_usd) }}</td>
                            </tr>
                        @endif
                        @if(!empty($paiement->montant_paye_fc))
                            <tr>
                                <td>Montant payé (FC)</td>
                                <td class="text-right text-muted">{{ $fmtFc($paiement->montant_paye_fc) }} FC</td>
                            </tr>
                        @endif
                    @else
                        <tr>
                            <td>Montant payé (USD)</td>
                            <td class="text-right text-success">${{ $fmtUsd($paiement->montant_paye_usd ?? 0) }}</td>
                        </tr>
                        @if(!empty($paiement->montant_paye_fc))
                            <tr>
                                <td>Montant payé (FC)</td>
                                <td class="text-right text-muted">{{ $fmtFc($paiement->montant_paye_fc) }} FC</td>
                            </tr>
                        @endif
                    @endif
                </tbody>
            </table>
        </section>

        {{-- PIED --}}
        <footer class="recu-footer">
            <div>
                <p>Merci de votre confiance.</p>
                <p class="text-muted">Document généré le {{ now()->translatedFormat('d F Y à H:i') }}</p>
            </div>
            <div class="recu-signature">
                <p class="recu-label">Signature autorisée</p>
                <div class="recu-signature-line"></div>
            </div>
        </footer>
    </article>
</div>

<style>
    .page { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }

    .page-header {
        display: flex; flex-direction: column; align-items: flex-start;
        gap: 1.25rem; margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .page-header { flex-direction: row; justify-content: space-between; align-items: center; }
    }
    .page-title {
        display: flex; align-items: center; gap: 0.6rem;
        font-size: 1.75rem; font-weight: 800; color: #0f172a;
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .title-icon { color: #6366f1; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }
    .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }

    .btn {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.7rem 1.25rem; border-radius: 12px;
        font-weight: 600; font-size: 0.9rem; text-decoration: none;
        border: none; cursor: pointer; transition: all 0.2s ease;
        white-space: nowrap;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35); }
    .btn-ghost {
        background: #fff; color: #64748b;
        border: 1.5px solid #e2e8f0;
    }
    .btn-ghost:hover { border-color: #6366f1; color: #4f46e5; background: #f8fafc; }

    /* REÇU */
    .recu {
        background: #fff; border-radius: 16px;
        border: 1px solid #e2e8f0; overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }

    .recu-header {
        display: flex; flex-wrap: wrap; gap: 1rem;
        justify-content: space-between; align-items: flex-start;
        padding: 2rem; border-bottom: 1px solid #e2e8f0;
    }
    .recu-brand { display: flex; align-items: center; gap: 1rem; }
    .recu-logo {
        width: 56px; height: 56px; border-radius: 12px;
        object-fit: cover;
    }
    .recu-logo-placeholder {
        background: linear-gradient(135deg, #4f46e5, #8b5cf6);
        color: #fff; display: flex; align-items: center;
        justify-content: center; font-size: 1.4rem; font-weight: 700;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .recu-brand-info p { font-size: 0.85rem; color: #64748b; margin: 0; }
    .recu-brand-name {
        font-size: 1.2rem; font-weight: 800;
        color: #0f172a; margin: 0 0 0.25rem;
    }
    .recu-ref { text-align: right; }
    .recu-ref-label {
        font-size: 0.75rem; font-weight: 700;
        color: #6366f1; text-transform: uppercase;
        letter-spacing: 0.5px; margin: 0;
    }
    .recu-ref-id {
        font-size: 1.4rem; font-weight: 800;
        color: #0f172a; margin: 0.15rem 0;
    }
    .recu-ref-date {
        font-size: 0.85rem; color: #64748b; margin: 0;
    }

    .recu-section { padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; }
    .recu-section-alt { background: #f8fafc; }

    .recu-grid-3 {
        display: grid; grid-template-columns: 1fr; gap: 1.5rem;
    }
    @media (min-width: 768px) { .recu-grid-3 { grid-template-columns: repeat(3, 1fr); } }

    .recu-grid-2 {
        display: grid; grid-template-columns: 1fr; gap: 1.5rem;
    }
    @media (min-width: 768px) { .recu-grid-2 { grid-template-columns: repeat(2, 1fr); } }

    .recu-right { text-align: left; }
    @media (min-width: 768px) { .recu-right { text-align: right; } }

    .recu-label {
        font-size: 0.7rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.5px;
        margin: 0 0 0.35rem;
    }
    .recu-value-lg { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .recu-meta { font-size: 0.85rem; color: #64748b; margin: 0.15rem 0 0; }

    .recu-table {
        width: 100%; border-collapse: collapse; font-size: 0.95rem;
    }
    .recu-table tbody td {
        padding: 0.7rem 0; border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .recu-table tbody tr:last-child td { border-bottom: none; }

    .text-right  { text-align: right !important; }
    .text-muted   { color: #94a3b8; }
    .text-success { color: #059669; font-weight: 700; }
    .text-danger  { color: #dc2626; font-weight: 600; }

    .pill {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.35rem 0.9rem; border-radius: 9999px;
        font-size: 0.8rem; font-weight: 700;
    }
    .pill i { font-size: 0.75rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-danger  { background: #fef2f2; color: #be123c; }
    .pill-info    { background: #eff6ff; color: #1d4ed8; }
    .pill-indigo  { background: #eef2ff; color: #4338ca; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    .recu-footer {
        display: flex; flex-wrap: wrap; gap: 1.5rem;
        justify-content: space-between; align-items: flex-end;
        padding: 1.5rem 2rem; background: #f8fafc;
        font-size: 0.85rem; color: #64748b;
    }
    .recu-footer p { margin: 0; }
    .recu-signature { text-align: right; }
    .recu-signature-line {
        width: 180px; height: 1px;
        background: #94a3b8; margin-top: 1rem;
    }

    /* PRINT */
    @media print {
        .no-print { display: none !important; }
        .page { padding: 0; max-width: none; }
        .recu { border-radius: 0; box-shadow: none; border: none; }
        .recu-section { padding: 1rem 1.25rem; }
        body { background: #fff !important; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>
@endsection