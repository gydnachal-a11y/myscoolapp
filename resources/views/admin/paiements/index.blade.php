@extends('layouts.admin')

@section('page_title', 'Paiements des élèves')
@section('page_subtitle', 'Suivi des paiements de scolarité')

@section('content')
@php
    $fmtUsd = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $fmtFc  = fn ($v) => number_format((float) $v, 0, ',', ' ');

    $statutMeta = [
        'paye'    => ['label' => 'Payé',    'icon' => 'fa-circle-check',        'class' => 'success'],
        'partiel' => ['label' => 'Partiel', 'icon' => 'fa-circle-half-stroke',  'class' => 'warning'],
        'impaye'  => ['label' => 'Impayé',  'icon' => 'fa-circle-xmark',        'class' => 'danger'],
        'surpaye' => ['label' => 'Surpayé', 'icon' => 'fa-circle-up',           'class' => 'info'],
    ];
@endphp

<div class="page" x-data="paiementsPage()" x-init="init()">

    {{-- HEADER --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-wallet title-icon"></i>
                Paiements des élèves
                <span class="count-badge">{{ $paiements->total() }}</span>
            </h1>
            <p class="page-subtitle">Suivi des paiements de scolarité — {{ $anneeActive->libelle }}</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.info-paiements.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Info Paiements</span>
            </a>
            <a href="{{ route('admin.paiements.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                <span>Nouveau paiement</span>
            </a>
        </div>
    </header>

    {{-- STATS GLOBALES --}}
    <section class="stats-grid">
        <article class="stat-card stat-success">
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-content">
                <p class="stat-label">Payés</p>
                <p class="stat-value">{{ number_format($nbPaye, 0, ',', ' ') }}</p>
            </div>
        </article>
        <article class="stat-card stat-warning">
            <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-content">
                <p class="stat-label">Partiels</p>
                <p class="stat-value">{{ number_format($nbPartiel, 0, ',', ' ') }}</p>
            </div>
        </article>
        <article class="stat-card stat-danger">
            <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            <div class="stat-content">
                <p class="stat-label">Impayés</p>
                <p class="stat-value">{{ number_format($nbImpaye, 0, ',', ' ') }}</p>
            </div>
        </article>
        <article class="stat-card stat-info">
            <div class="stat-icon"><i class="fa-solid fa-circle-up"></i></div>
            <div class="stat-content">
                <p class="stat-label">Surpayés</p>
                <p class="stat-value">{{ number_format($nbSurpaye, 0, ',', ' ') }}</p>
            </div>
        </article>
        <article class="stat-card stat-indigo stat-total">
            <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total encaissé</p>
                <p class="stat-value">
                    ${{ $fmtUsd($totalPayeUSD) }}
                    <small class="stat-unit">{{ $fmtFc($totalPayeFC) }} FC</small>
                </p>
            </div>
        </article>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- SECTION REPLIABLE : INSCRITS PAR SALLE --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if(!empty($statsParSalle))
        <section class="content-card collapsible"
                 :class="{ 'is-collapsed': !sections.salles }">
            <div class="content-header content-header-toggle"
                 @click="toggleSection('salles')"
                 role="button"
                 tabindex="0"
                 @keydown.enter.prevent="toggleSection('salles')"
                 @keydown.space.prevent="toggleSection('salles')"
                 :aria-expanded="sections.salles ? 'true' : 'false'">
                <h2 class="content-title">
                    <button type="button"
                            class="toggle-btn"
                            :class="{ 'is-open': sections.salles }"
                            aria-label="Basculer la section Inscrits par salle">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <i class="fa-solid fa-school title-icon-sm"></i>
                    Inscrits par salle
                    <span class="count-badge">{{ count($statsParSalle) }}</span>
                </h2>
                <span class="toggle-hint" x-text="sections.salles ? 'Masquer' : 'Afficher'"></span>
            </div>

            <div class="content-body" x-show="sections.salles" x-collapse>
                <div class="salle-grid">
                    @foreach($statsParSalle as $s)
                        <article class="salle-card">
                            <div class="salle-header">
                                <h3 class="salle-name">{{ $s['salle_nom'] }}</h3>
                                <span class="pill pill-indigo">{{ $s['nb_eleves'] }} élève(s)</span>
                            </div>

                            <div class="salle-rows">
                                <div class="salle-row">
                                    <span class="salle-label">Attendu</span>
                                    <span class="salle-value">${{ $fmtUsd($s['total_attendu_usd']) }}</span>
                                </div>
                                <div class="salle-row">
                                    <span class="salle-label">Payé</span>
                                    <span class="salle-value salle-value-success">${{ $fmtUsd($s['total_paye_usd']) }}</span>
                                </div>
                                <div class="salle-row">
                                    <span class="salle-label">Reste</span>
                                    <span class="salle-value salle-value-danger">${{ $fmtUsd($s['reste_a_payer']) }}</span>
                                </div>
                            </div>

                            <div class="progress-track">
                                <div class="progress-fill" style="width: {{ min(100, $s['pourcentage_paye']) }}%"></div>
                            </div>
                            <p class="salle-pct">{{ $s['pourcentage_paye'] }}% recouvré</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- SECTION REPLIABLE : STATISTIQUES PAR PÉRIODE --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if(!empty($statsMois))
        <section class="content-card collapsible"
                 :class="{ 'is-collapsed': !sections.periodes }">
            <div class="content-header content-header-toggle"
                 @click="toggleSection('periodes')"
                 role="button"
                 tabindex="0"
                 @keydown.enter.prevent="toggleSection('periodes')"
                 @keydown.space.prevent="toggleSection('periodes')"
                 :aria-expanded="sections.periodes ? 'true' : 'false'">
                <h2 class="content-title">
                    <button type="button"
                            class="toggle-btn"
                            :class="{ 'is-open': sections.periodes }"
                            aria-label="Basculer la section Statistiques par période">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <i class="fa-regular fa-calendar-plus title-icon-sm"></i>
                    Statistiques par période
                    <span class="count-badge">{{ count($statsMois) }}</span>
                </h2>
                <span class="toggle-hint" x-text="sections.periodes ? 'Masquer' : 'Afficher'"></span>
            </div>

            <div class="content-body" x-show="sections.periodes" x-collapse>
                <div class="periode-grid">
                    @foreach($statsMois as $m)
                        <article class="periode-card">
                            <p class="periode-name">{{ $m['nom'] }}</p>
                            <p class="periode-amount">${{ $fmtUsd($m['total_usd']) }}</p>
                            <p class="periode-sub">{{ $fmtFc($m['total_fc']) }} FC</p>
                            <div class="periode-meta">
                                <span><i class="fa-solid fa-receipt"></i> {{ $m['count'] }} paiement(s)</span>
                                <span><i class="fa-solid fa-chart-simple"></i> Moy. ${{ $fmtUsd($m['moyenne_usd']) }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- TABLEAU DES PAIEMENTS --}}
    <section class="content-card">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fa-solid fa-list title-icon-sm"></i>
                Paiements enregistrés
                <span class="count-badge">{{ $paiements->total() }}</span>
            </h2>
            <div class="content-meta">
                <span class="meta-item">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                    Taux : <strong>{{ number_format($tauxChange ?? 2800, 2, ',', ' ') }} FC/USD</strong>
                </span>
            </div>
        </div>

        @if($paiements->count())
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Élève</th>
                            <th scope="col">Salle</th>
                            <th scope="col">Période</th>
                            <th scope="col" class="text-right">Attendu</th>
                            <th scope="col" class="text-right">Payé</th>
                            <th scope="col" class="text-center">Statut</th>
                            <th scope="col" class="text-center">Date</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiements as $p)
                            @php
                                $percent = $p->montant_attendu_usd > 0
                                    ? min(100, round(($p->montant_paye_usd / $p->montant_attendu_usd) * 100))
                                    : 0;
                                $meta = $statutMeta[$p->statut] ?? ['label' => $p->statut, 'icon' => 'fa-circle', 'class' => 'neutral'];
                                $initiale = strtoupper(mb_substr($p->eleve?->nom ?? '?', 0, 1));
                            @endphp
                            <tr>
                                <td data-label="Élève">
                                    <div class="eleve-cell">
                                        <div class="eleve-avatar">{{ $initiale }}</div>
                                        <span class="cell-primary">
                                            {{ trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')) ?: '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Salle">{{ $p->salleClasse?->nom ?? '—' }}</td>
                                <td data-label="Période">{{ $p->periode ?? '—' }}</td>
                                <td data-label="Attendu" class="text-right">
                                    <span class="amount-usd">${{ $fmtUsd($p->montant_attendu_usd) }}</span>
                                    <span class="amount-fc">{{ $fmtFc($p->montant_attendu_fc) }} FC</span>
                                </td>
                                <td data-label="Payé" class="text-right">
                                    <span class="amount-usd amount-success">${{ $fmtUsd($p->montant_paye_usd) }}</span>
                                    <span class="amount-fc">{{ $fmtFc($p->montant_paye_fc) }} FC</span>
                                    <div class="progress-track progress-sm">
                                        <div class="progress-fill" style="width: {{ $percent }}%"></div>
                                    </div>
                                </td>
                                <td data-label="Statut" class="text-center">
                                    <span class="pill pill-{{ $meta['class'] }}">
                                        <i class="fa-solid {{ $meta['icon'] }}"></i>
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td data-label="Date" class="text-center">
                                    {{ $p->date_paiement?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td data-label="Actions" class="text-right">
                                    <div class="action-bar">
                                        <a href="{{ route('admin.paiements.edit', $p) }}"
                                           class="action-btn"
                                           title="Modifier">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ route('admin.paiements.destroy', $p) }}"
                                              method="POST"
                                              class="inline-form"
                                              onsubmit="return confirm('Supprimer définitivement ce paiement ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="action-btn action-danger" title="Supprimer">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($paiements->hasPages())
                <div class="pagination-wrapper">{{ $paiements->links() }}</div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <h3 class="empty-title">Aucun paiement enregistré</h3>
                <p class="empty-text">Commencez par enregistrer un premier paiement.</p>
                <div class="empty-actions">
                    <a href="{{ route('admin.paiements.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Nouveau paiement
                    </a>
                </div>
            </div>
        @endif
    </section>
</div>

<style>
    .page { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }

    .page-header { display: flex; flex-direction: column; align-items: flex-start; gap: 1.25rem; margin-bottom: 2rem; }
    @media (min-width: 768px) { .page-header { flex-direction: row; justify-content: space-between; align-items: center; } }
    .page-title { display: flex; align-items: center; gap: 0.6rem; font-size: 1.75rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; margin: 0 0 0.25rem; }
    .title-icon { color: #6366f1; }
    .title-icon-sm { color: #6366f1; font-size: 1rem; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }

    .count-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 26px; padding: 0 0.6rem; background: #eef2ff; color: #4f46e5; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; }
    .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }

    .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.25rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s ease; white-space: nowrap; }
    .btn-primary { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,0.25); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79,70,229,0.35); }
    .btn-ghost { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .btn-ghost:hover { border-color: #6366f1; color: #4f46e5; background: #f8fafc; }

    /* STATS */
    .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    @media (min-width: 768px)  { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(5, 1fr); } }
    .stat-total { grid-column: span 2; }
    @media (min-width: 1024px) { .stat-total { grid-column: span 1; } }

    .stat-card { display: flex; align-items: center; gap: 0.9rem; padding: 1.1rem; background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }

    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .stat-success .stat-icon { background: #ecfdf5; color: #059669; }
    .stat-warning .stat-icon { background: #fffbeb; color: #d97706; }
    .stat-danger  .stat-icon { background: #fef2f2; color: #dc2626; }
    .stat-info    .stat-icon { background: #eff6ff; color: #2563eb; }
    .stat-indigo  .stat-icon { background: #eef2ff; color: #4f46e5; }

    .stat-content { min-width: 0; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; color: #94a3b8; margin: 0 0 0.15rem; }
    .stat-value { font-size: 1.4rem; font-weight: 700; color: #0f172a; line-height: 1.15; margin: 0; }
    .stat-unit { display: block; font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin-top: 0.15rem; }

    /* CARTES */
    .content-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; overflow: hidden; }
    .content-header { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
    .content-title { display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .content-meta { font-size: 0.8rem; color: #64748b; }
    .meta-item { display: inline-flex; align-items: center; gap: 0.35rem; }
    .meta-item i { color: #94a3b8; }
    .meta-item strong { color: #0f172a; }

    /* ─── SECTIONS REPLIABLES ─── */
    .content-card.collapsible .content-header-toggle {
        cursor: pointer;
        user-select: none;
        transition: background 0.2s;
    }
    .content-card.collapsible .content-header-toggle:hover {
        background: #f8fafc;
    }
    .content-card.collapsible.is-collapsed .content-header {
        border-bottom-color: transparent;
    }

    .toggle-btn {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        background: #eef2ff;
        color: #4f46e5;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        cursor: pointer;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), background 0.2s;
        flex-shrink: 0;
        padding: 0;
    }
    .toggle-btn:hover { background: #e0e7ff; }
    .toggle-btn.is-open { transform: rotate(90deg); }

    .toggle-hint {
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* GRILLE SALLES */
    .salle-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; padding: 1.25rem; }
    @media (min-width: 640px)  { .salle-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .salle-grid { grid-template-columns: repeat(3, 1fr); } }

    .salle-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; transition: border-color 0.2s, box-shadow 0.2s; }
    .salle-card:hover { border-color: #c7d2fe; box-shadow: 0 4px 12px rgba(99,102,241,0.08); }

    .salle-header { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
    .salle-name { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0; }

    .salle-rows { display: flex; flex-direction: column; gap: 0.35rem; }
    .salle-row { display: flex; justify-content: space-between; font-size: 0.85rem; }
    .salle-label { color: #64748b; }
    .salle-value { font-weight: 600; color: #0f172a; }
    .salle-value-success { color: #059669; }
    .salle-value-danger  { color: #dc2626; }

    .progress-track { width: 100%; height: 6px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; margin-top: 0.6rem; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 9999px; transition: width 0.4s ease; }
    .progress-sm { height: 4px; max-width: 90px; margin-top: 0.35rem; margin-left: auto; }

    .salle-pct { font-size: 0.72rem; font-weight: 600; color: #4f46e5; margin: 0.35rem 0 0; text-align: right; }

    /* GRILLE PÉRIODES */
    .periode-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; padding: 1.25rem; }
    @media (min-width: 640px)  { .periode-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .periode-grid { grid-template-columns: repeat(4, 1fr); } }

    .periode-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; }
    .periode-name { font-size: 0.85rem; font-weight: 600; color: #64748b; margin: 0 0 0.5rem; }
    .periode-amount { font-size: 1.25rem; font-weight: 800; color: #4f46e5; margin: 0; letter-spacing: -0.5px; }
    .periode-sub { font-size: 0.75rem; color: #94a3b8; margin: 0 0 0.6rem; }
    .periode-meta { display: flex; flex-direction: column; gap: 0.15rem; font-size: 0.72rem; color: #64748b; }
    .periode-meta i { color: #94a3b8; margin-right: 0.25rem; }

    /* TABLEAU */
    .table-wrapper { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th { text-align: left; padding: 0.8rem 1.25rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .data-table tbody td { padding: 0.9rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .text-right  { text-align: right !important; }
    .text-center { text-align: center !important; }
    .cell-primary { font-weight: 600; color: #0f172a; }

    .eleve-cell { display: flex; align-items: center; gap: 0.6rem; }
    .eleve-avatar { width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.78rem; flex-shrink: 0; }

    .amount-usd { display: block; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .amount-success { color: #059669; }
    .amount-fc { display: block; font-size: 0.72rem; color: #94a3b8; font-variant-numeric: tabular-nums; }

    .pill { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; white-space: nowrap; }
    .pill i { font-size: 0.7rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-danger  { background: #fef2f2; color: #be123c; }
    .pill-info    { background: #eff6ff; color: #1d4ed8; }
    .pill-indigo  { background: #eef2ff; color: #4338ca; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    .action-bar { display: inline-flex; gap: 0.3rem; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 9px; background: transparent; border: none; color: #64748b; cursor: pointer; transition: all 0.18s ease; font-size: 0.9rem; text-decoration: none; }
    .action-btn:hover { background: #f1f5f9; color: #0f172a; }
    .action-btn.action-danger:hover { background: #fef2f2; color: #dc2626; }
    .inline-form { display: inline; }

    .pagination-wrapper { padding: 0.9rem 1.25rem; border-top: 1px solid #f1f5f9; }

    /* EMPTY STATE */
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1.5rem; text-align: center; gap: 0.5rem; }
    .empty-icon-wrapper { width: 80px; height: 80px; border-radius: 50%; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #cbd5e1; margin-bottom: 0.75rem; }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin: 0; }
    .empty-text { font-size: 0.9rem; color: #94a3b8; max-width: 420px; margin: 0; }
    .empty-actions { display: flex; gap: 0.6rem; margin-top: 1rem; flex-wrap: wrap; justify-content: center; }

    @media (max-width: 640px) {
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table thead { display: none; }

        .data-table tbody tr { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; margin: 1rem; padding: 0.75rem; }
        .data-table td { padding: 0.4rem 0 !important; border: none; display: flex; justify-content: space-between; align-items: center; gap: 1rem; border-bottom: 1px solid #f1f5f9; }
        .data-table td:last-child { border-bottom: none; }
        .data-table td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.3px; flex-shrink: 0; }

        .text-right, .text-center { text-align: right !important; }
        .progress-sm { max-width: 100% !important; }

        .header-actions { width: 100%; }
        .header-actions .btn { flex: 1 1 auto; justify-content: center; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>

<script>
function paiementsPage() {
    return {
        /* État des sections : true = ouvert, false = fermé */
        sections: {
            salles:   true,
            periodes: true,
        },

        /**
         * Restaure l'état sauvegardé depuis localStorage.
         */
        init() {
            try {
                const saved = localStorage.getItem('paiements_sections');
                if (saved) {
                    const parsed = JSON.parse(saved);
                    this.sections = { ...this.sections, ...parsed };
                }
            } catch (e) {
                // Ignore les erreurs localStorage (mode privé, etc.)
            }
        },

        /**
         * Bascule une section + sauvegarde dans localStorage.
         */
        toggleSection(key) {
            this.sections[key] = !this.sections[key];

            try {
                localStorage.setItem('paiements_sections', JSON.stringify(this.sections));
            } catch (e) {
                // Ignore
            }
        },
    };
}
</script>
@endsection