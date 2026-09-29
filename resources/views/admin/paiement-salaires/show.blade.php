@extends('layouts.admin')

@section('page_title', 'Fiche de paie')
@section('page_subtitle', $user?->name ?? 'Employé')

@section('content')
@php
    $settings = $siteSettings ?? \App\Models\SiteSetting::getSettings();
    $fmtUsd = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $fmtFc  = fn ($v) => number_format((float) $v, 0, ',', ' ');

    // Net à payer (accessor)
    $netAPayerUsd = $paiementSalaire->net_a_payer_usd;
    $netAPayerFc  = $paiementSalaire->net_a_payer_fc;
@endphp

<div class="page">

    {{-- Barre d'actions --}}
    <header class="page-header no-print">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-file-invoice-dollar title-icon" aria-hidden="true"></i>
                Fiche de paie
            </h1>
            <p class="page-subtitle">Réf. #{{ $paiementSalaire->id }} — {{ $paiementSalaire->mois_label }}</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.paiement-salaires.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Retour
            </a>
            <a href="{{ route('admin.paiement-salaires.edit', $paiementSalaire) }}" class="btn btn-primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Modifier
            </a>
            <button type="button" onclick="window.print()" class="btn btn-ghost">
                <i class="fa-solid fa-print" aria-hidden="true"></i> Imprimer
            </button>
        </div>
    </header>

    {{-- Fiche --}}
    <article class="fiche">

        {{-- EN-TÊTE --}}
        <header class="fiche-header">
            <div class="fiche-brand">
                @if(!empty($settings->site_logo))
                    <img src="{{ asset('storage/' . $settings->site_logo) }}"
                         alt="{{ $settings->site_name }}" class="fiche-logo">
                @else
                    <div class="fiche-logo fiche-logo-placeholder">
                        {{ strtoupper(substr($settings->site_name ?? 'E', 0, 1)) }}
                    </div>
                @endif
                <div class="fiche-brand-info">
                    <h2 class="fiche-brand-name">{{ $settings->site_name ?? config('app.name', 'Mon École') }}</h2>
                    @if(!empty($settings->site_address)) <p>{{ $settings->site_address }}</p> @endif
                    @if(!empty($settings->site_email))   <p>{{ $settings->site_email }}</p>   @endif
                    @if(!empty($settings->site_phone))   <p>{{ $settings->site_phone }}</p>   @endif
                </div>
            </div>
            <div class="fiche-ref">
                <p class="fiche-ref-label">Fiche de paie</p>
                <p class="fiche-ref-id">#{{ $paiementSalaire->id }}</p>
                <p class="fiche-ref-date">{{ $paiementSalaire->date_paiement_formatee }}</p>
            </div>
        </header>

        {{-- EMPLOYÉ --}}
        <section class="fiche-section fiche-section-alt">
            <div class="fiche-grid-3">
                <div>
                    <p class="fiche-label">Bénéficiaire</p>
                    <p class="fiche-value-lg">{{ $user?->name ?? 'Utilisateur supprimé' }}</p>
                    @if($user?->section) <p class="fiche-meta">{{ $user->section->nom }}</p> @endif
                </div>
                <div>
                    <p class="fiche-label">Matricule</p>
                    <p class="fiche-value">{{ $user?->matricule ?? 'Non défini' }}</p>
                    <p class="fiche-meta">{{ $user?->email }}</p>
                    <p class="fiche-meta">{{ $user?->telephone ?? '—' }}</p>
                </div>
                <div class="fiche-right">
                    <p class="fiche-label">Période</p>
                    <p class="fiche-value-lg">{{ $paiementSalaire->mois_label }}</p>
                    <p class="fiche-meta">
                        Taux : {{ number_format($tauxChange ?? 2800, 2, ',', ' ') }} FC/USD
                    </p>
                </div>
            </div>
        </section>

        {{-- TABLEAU MONTANTS --}}
        <section class="fiche-section">
            <table class="fiche-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Montant USD</th>
                        <th class="text-right">Montant FC</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Salaire brut</td>
                        <td class="text-right">{{ $fmtUsd($paiementSalaire->montant_attendu_usd) }} $</td>
                        <td class="text-right text-muted">{{ $fmtFc($paiementSalaire->montant_attendu_fc) }} FC</td>
                    </tr>

                    @if($paiementSalaire->avance_deduite_usd > 0)
                        <tr>
                            <td>Avance déduite</td>
                            <td class="text-right text-danger">-{{ $fmtUsd($paiementSalaire->avance_deduite_usd) }} $</td>
                            <td class="text-right text-danger">-{{ $fmtFc($paiementSalaire->avance_deduite_fc) }} FC</td>
                        </tr>
                    @endif

                    @if($paiementSalaire->dette_remboursee_usd > 0)
                        <tr>
                            <td>Remboursement dette</td>
                            <td class="text-right text-warning">-{{ $fmtUsd($paiementSalaire->dette_remboursee_usd) }} $</td>
                            <td class="text-right text-warning">-{{ $fmtFc($paiementSalaire->dette_remboursee_fc) }} FC</td>
                        </tr>
                    @endif

                    <tr class="row-strong">
                        <td>Net à payer</td>
                        <td class="text-right">{{ $fmtUsd($netAPayerUsd) }} $</td>
                        <td class="text-right">{{ $fmtFc($netAPayerFc) }} FC</td>
                    </tr>

                    <tr class="row-success">
                        <td>Montant payé</td>
                        <td class="text-right">{{ $fmtUsd($paiementSalaire->montant_paye_usd) }} $</td>
                        <td class="text-right">{{ $fmtFc($paiementSalaire->montant_paye_fc) }} FC</td>
                    </tr>

                    @if($paiementSalaire->montant_restant_usd > 0)
                        <tr>
                            <td>Reste à payer</td>
                            <td class="text-right text-info">{{ $fmtUsd($paiementSalaire->montant_restant_usd) }} $</td>
                            <td class="text-right text-info">{{ $fmtFc($paiementSalaire->montant_restant_fc) }} FC</td>
                        </tr>
                    @endif

                    @if($paiementSalaire->report_dette_usd > 0)
                        <tr>
                            <td>Report de dette</td>
                            <td class="text-right text-danger">{{ $fmtUsd($paiementSalaire->report_dette_usd) }} $</td>
                            <td class="text-right text-danger">{{ $fmtFc($paiementSalaire->report_dette_fc) }} FC</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </section>

        {{-- STATUT + MOTIF --}}
        <section class="fiche-section fiche-status">
            <span class="pill pill-{{ $paiementSalaire->statut_key }}">
                <i class="fa-solid {{ $paiementSalaire->statut_icon }}" aria-hidden="true"></i>
                {{ $paiementSalaire->statut_label }}
            </span>
            @if($paiementSalaire->motif_ecart)
                <div class="fiche-motif">
                    <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
                    <span><strong>Motif :</strong> {{ $paiementSalaire->motif_ecart }}</span>
                </div>
            @endif
        </section>

        {{-- REMBOURSEMENTS --}}
        @if($paiementSalaire->remboursementsAvances->count())
            <section class="fiche-section">
                <h3 class="fiche-subtitle">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    Détail des remboursements d'avances
                </h3>
                <table class="fiche-table fiche-table-sm">
                    <thead>
                        <tr>
                            <th>Avance</th>
                            <th class="text-right">Montant USD</th>
                            <th class="text-right">Montant FC</th>
                            <th class="text-center">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiementSalaire->remboursementsAvances as $r)
                            <tr>
                                <td>#{{ $r->avance_id }} {{ $r->avance?->motif ?? '' }}</td>
                                <td class="text-right">{{ $fmtUsd($r->montant_rembourse_usd) }} $</td>
                                <td class="text-right">{{ $fmtFc($r->montant_rembourse_fc) }} FC</td>
                                <td class="text-center">
                                    {{ $r->date_remboursement?->translatedFormat('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        {{-- PIED --}}
        <footer class="fiche-footer">
            <div>
                <p>Merci de votre confiance.</p>
                <p class="text-muted">Document généré le {{ now()->translatedFormat('d F Y à H:i') }}</p>
            </div>
            <div class="fiche-signature">
                <p class="fiche-label">Signature autorisée</p>
                <div class="fiche-signature-line"></div>
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

    /* FICHE */
    .fiche {
        background: #fff; border-radius: 16px;
        border: 1px solid #e2e8f0; overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }

    .fiche-header {
        display: flex; flex-wrap: wrap; gap: 1rem;
        justify-content: space-between; align-items: flex-start;
        padding: 2rem; border-bottom: 1px solid #e2e8f0;
    }
    .fiche-brand { display: flex; align-items: center; gap: 1rem; }
    .fiche-logo {
        width: 56px; height: 56px; border-radius: 12px;
        object-fit: cover;
    }
    .fiche-logo-placeholder {
        background: linear-gradient(135deg, #4f46e5, #8b5cf6);
        color: #fff; display: flex; align-items: center;
        justify-content: center; font-size: 1.4rem; font-weight: 700;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .fiche-brand-info p {
        font-size: 0.85rem; color: #64748b; margin: 0;
    }
    .fiche-brand-name {
        font-size: 1.2rem; font-weight: 800; color: #0f172a;
        margin: 0 0 0.25rem;
    }
    .fiche-ref { text-align: right; }
    .fiche-ref-label {
        font-size: 0.75rem; font-weight: 700; color: #6366f1;
        text-transform: uppercase; letter-spacing: 0.5px; margin: 0;
    }
    .fiche-ref-id {
        font-size: 1.4rem; font-weight: 800; color: #0f172a;
        margin: 0.15rem 0;
    }
    .fiche-ref-date {
        font-size: 0.85rem; color: #64748b; margin: 0;
    }

    .fiche-section { padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; }
    .fiche-section-alt { background: #f8fafc; }

    .fiche-grid-3 {
        display: grid; grid-template-columns: 1fr; gap: 1.5rem;
    }
    @media (min-width: 768px) { .fiche-grid-3 { grid-template-columns: repeat(3, 1fr); } }

    .fiche-right { text-align: left; }
    @media (min-width: 768px) { .fiche-right { text-align: right; } }

    .fiche-label {
        font-size: 0.7rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.5px;
        margin: 0 0 0.35rem;
    }
    .fiche-value    { font-weight: 600; color: #0f172a; margin: 0; }
    .fiche-value-lg { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .fiche-meta     { font-size: 0.85rem; color: #64748b; margin: 0.15rem 0 0; }

    .fiche-table {
        width: 100%; border-collapse: collapse; font-size: 0.9rem;
    }
    .fiche-table thead th {
        text-align: left; padding: 0.6rem 0;
        font-size: 0.7rem; font-weight: 700;
        color: #64748b; text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
    }
    .fiche-table tbody td {
        padding: 0.65rem 0; border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .fiche-table tbody tr:last-child td { border-bottom: none; }
    .fiche-table-sm thead th, .fiche-table-sm tbody td { padding: 0.5rem 0; font-size: 0.85rem; }

    .row-strong { font-weight: 700; color: #0f172a; background: #f8fafc; }
    .row-strong td { padding: 0.75rem 0 !important; }
    .row-success { background: #f0fdf4; font-weight: 700; color: #047857; }
    .row-success td { padding: 0.75rem 0 !important; }

    .text-right  { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-muted   { color: #94a3b8; }
    .text-danger  { color: #dc2626; }
    .text-warning { color: #d97706; }
    .text-info    { color: #2563eb; }

    .fiche-status {
        display: flex; flex-wrap: wrap; align-items: center;
        gap: 1rem; padding: 1.25rem 2rem;
    }
    .fiche-motif {
        display: flex; align-items: flex-start; gap: 0.5rem;
        font-size: 0.9rem; color: #475569;
    }
    .fiche-motif i { color: #94a3b8; margin-top: 0.15rem; }

    .pill {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.35rem 0.9rem; border-radius: 9999px;
        font-size: 0.8rem; font-weight: 700;
    }
    .pill i { font-size: 0.75rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-danger  { background: #fef2f2; color: #be123c; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    .fiche-subtitle {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.95rem; font-weight: 700;
        color: #334155; margin: 0 0 1rem;
    }
    .fiche-subtitle i { color: #6366f1; }

    .fiche-footer {
        display: flex; flex-wrap: wrap; gap: 1.5rem;
        justify-content: space-between; align-items: flex-end;
        padding: 1.5rem 2rem; background: #f8fafc;
        font-size: 0.85rem; color: #64748b;
    }
    .fiche-footer p { margin: 0; }
    .fiche-signature { text-align: right; }
    .fiche-signature-line {
        width: 180px; height: 1px; background: #94a3b8;
        margin-top: 1rem;
    }

    /* IMPRESSION */
    @media print {
        .no-print { display: none !important; }
        .page { padding: 0; max-width: none; }
        .fiche { border-radius: 0; box-shadow: none; border: none; }
        .fiche-section { padding: 1rem 1.25rem; }
        body { background: #fff !important; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>
@endsection