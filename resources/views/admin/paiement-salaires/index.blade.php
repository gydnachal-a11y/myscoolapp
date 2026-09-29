@extends('layouts.admin')

@section('page_title', 'Paiements de salaires')
@section('page_subtitle', 'Suivi des paiements mensuels effectués')

@section('content')
@php
    $fmtUsd = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $fmtFc  = fn ($v) => number_format((float) $v, 0, ',', ' ');

    $hasFilters = request()->hasAny(['user_id', 'mois_scolaire_id', 'statut', 'section_id']);
    $corbeille  = request()->boolean('corbeille');

    $routeIndex     = route('admin.paiement-salaires.index');
    $routeCorbeille = route('admin.paiement-salaires.index', ['corbeille' => 1]);
@endphp

<div class="page" x-data="paiementsSalairesPage()" x-init="init()">

    {{-- HEADER --}}
    <header class="page-header">
        <div class="page-header-main">
            <h1 class="page-title">
                <i class="fa-solid fa-money-check-dollar title-icon" aria-hidden="true"></i>
                <span class="page-title-text">
                    @if($corbeille) Corbeille des paiements @else Paiements de salaires @endif
                </span>
                <span class="count-badge">{{ $paiements->total() }}</span>
            </h1>
            <p class="page-subtitle">
                @if($corbeille)
                    Paiements supprimés — restaurables tant qu'ils n'ont pas été purgés
                @else
                    Suivi des paiements mensuels effectués
                @endif
            </p>
        </div>

        <div class="header-actions">
            @if($corbeille)
                <a href="{{ $routeIndex }}" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    <span>Retour aux paiements</span>
                </a>
            @else
                <a href="{{ route('admin.salaires.index') }}" class="btn btn-ghost">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    <span>Salaires de base</span>
                </a>
                <a href="{{ route('admin.avances.index') }}" class="btn btn-ghost">
                    <i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i>
                    <span>Avances</span>
                </a>
                <a href="{{ $routeCorbeille }}" class="btn btn-ghost" title="Voir les paiements supprimés">
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    <span>Corbeille</span>
                </a>
                <a href="{{ route('admin.paiement-salaires.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Nouveau paiement</span>
                </a>
            @endif
        </div>
    </header>

    {{-- STATS GLOBALES --}}
    @unless($corbeille)
        <section class="stats-grid">
            <article class="stat-card stat-indigo">
                <div class="stat-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Total payé (USD)</p>
                    <p class="stat-value">${{ $fmtUsd($totalPayeUSD) }}</p>
                    <p class="stat-unit">{{ $fmtFc($totalPayeFC) }} FC</p>
                </div>
            </article>

            <article class="stat-card stat-success">
                <div class="stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Payés</p>
                    <p class="stat-value">{{ number_format($nbPaye, 0, ',', ' ') }}</p>
                </div>
            </article>

            <article class="stat-card stat-warning">
                <div class="stat-icon"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Sous-payés</p>
                    <p class="stat-value">{{ number_format($nbSousPaye, 0, ',', ' ') }}</p>
                </div>
            </article>

            <article class="stat-card stat-danger">
                <div class="stat-icon"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Sur-payés</p>
                    <p class="stat-value">{{ number_format($nbSurPaye ?? 0, 0, ',', ' ') }}</p>
                </div>
            </article>
        </section>
    @endunless

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- STATS PAR MOIS — SECTION REPLIABLE --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if(!$corbeille && !empty($statsMois))
        <section class="content-card collapsible"
                 :class="{ 'is-collapsed': !sections.mois }">
            <div class="content-header content-header-toggle"
                 @click="toggleSection('mois')"
                 role="button"
                 tabindex="0"
                 @keydown.enter.prevent="toggleSection('mois')"
                 @keydown.space.prevent="toggleSection('mois')"
                 :aria-expanded="sections.mois ? 'true' : 'false'">
                <h2 class="content-title">
                    <button type="button"
                            class="toggle-btn"
                            :class="{ 'is-open': sections.mois }"
                            aria-label="Basculer la section Statistiques par mois">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <i class="fa-regular fa-calendar-plus title-icon-sm" aria-hidden="true"></i>
                    Statistiques par mois
                    <span class="count-badge">{{ count($statsMois) }}</span>
                </h2>
                <span class="toggle-hint" x-text="sections.mois ? 'Masquer' : 'Afficher'"></span>
            </div>

            <div class="content-body" x-show="sections.mois" x-collapse>
                <div class="periode-grid">
                    @foreach($statsMois as $m)
                        <article class="periode-card">
                            <p class="periode-name">{{ $m['nom'] }}</p>
                            <p class="periode-amount">${{ $fmtUsd($m['total_usd']) }}</p>
                            <p class="periode-sub">{{ $fmtFc($m['total_fc']) }} FC</p>
                            <div class="periode-meta">
                                <span><i class="fa-solid fa-receipt" aria-hidden="true"></i> {{ $m['count'] }} paiement(s)</span>
                                <span><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Moy. ${{ $fmtUsd($m['moyenne_usd']) }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FILTRES --}}
    <section class="filter-card">
        <div class="filter-header">
            <div class="filter-header-left">
                <i class="fa-solid fa-sliders filter-icon" aria-hidden="true"></i>
                <span class="filter-title">Filtres</span>
                @if($hasFilters)
                    <span class="filter-count">actifs</span>
                @endif
            </div>
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ $routeIndex }}" class="filter-form">
                @if($corbeille)
                    <input type="hidden" name="corbeille" value="1">
                @endif

                <div class="filter-grid">
                    <div class="filter-field">
                        <label for="f-user" class="filter-label">Employé</label>
                        <select name="user_id" id="f-user" class="filter-select" data-auto-submit>
                            <option value="">Tous</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="f-mois" class="filter-label">Mois</label>
                        <select name="mois_scolaire_id" id="f-mois" class="filter-select" data-auto-submit>
                            <option value="">Tous</option>
                            @foreach($mois as $m)
                                <option value="{{ $m->id }}" @selected(request('mois_scolaire_id') == $m->id)>
                                    {{ $m->nom_mois ?? $m->mois }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="f-statut" class="filter-label">Statut</label>
                        <select name="statut" id="f-statut" class="filter-select" data-auto-submit>
                            <option value="">Tous</option>
                            <option value="paye"     @selected(request('statut') === 'paye')>Payé</option>
                            <option value="souspaye" @selected(request('statut') === 'souspaye')>Sous-payé</option>
                            <option value="surpaye"  @selected(request('statut') === 'surpaye')>Sur-payé</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="f-section" class="filter-label">Section</label>
                        <select name="section_id" id="f-section" class="filter-select" data-auto-submit>
                            <option value="">Toutes</option>
                            @foreach($sections as $s)
                                <option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>{{ $s->nom }}</option>
                            @endforeach
                            <option value="sans_section" @selected(request('section_id') === 'sans_section')>Sans section</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    @if($hasFilters)
                        <a href="{{ $corbeille ? $routeCorbeille : $routeIndex }}" class="btn btn-ghost">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Réinitialiser
                        </a>
                    @endif
                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Appliquer
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- LISTE --}}
    <section class="content-card">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fa-solid fa-list title-icon-sm" aria-hidden="true"></i>
                @if($corbeille) Paiements supprimés @else Paiements enregistrés @endif
                <span class="count-badge">{{ $paiements->total() }}</span>
            </h2>
            <div class="content-meta">
                <span class="meta-item">
                    <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                    Taux : <strong>{{ number_format($tauxChange ?? 2800, 2, ',', ' ') }} FC/USD</strong>
                </span>
            </div>
        </div>

        @if($paiements->count())
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Employé</th>
                            <th scope="col">Mois</th>
                            <th scope="col" class="text-right">Attendu</th>
                            <th scope="col" class="text-right">Payé</th>
                            <th scope="col" class="text-right">Restant</th>
                            <th scope="col" class="text-center">Statut</th>
                            <th scope="col" class="text-center">
                                @if($corbeille) Supprimé le @else Date @endif
                            </th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiements as $p)
                            @php
                                $initiale  = strtoupper(substr($p->user?->name ?? '?', 0, 1));
                                $isTrashed = $p->trashed();
                                $dateLabel = $isTrashed
                                    ? ($p->deleted_at?->format('d/m/Y') ?? '—')
                                    : $p->date_paiement_formatee;
                            @endphp
                            <tr class="{{ $isTrashed ? 'row-trashed' : '' }}">
                                <td data-label="Employé" class="cell-employe">
                                    <div class="eleve-cell">
                                        <div class="eleve-avatar {{ $isTrashed ? 'avatar-muted' : '' }}">
                                            {{ $initiale }}
                                        </div>
                                        <div class="eleve-meta">
                                            <span class="cell-primary">{{ $p->user?->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Mois" class="cell-mois">
                                    <span class="month-label">{{ $p->mois_label }}</span>
                                    <span class="month-date">{{ $dateLabel }}</span>
                                </td>

                                <td data-label="Attendu" class="cell-amount text-right">
                                    <span class="amount-label">Attendu</span>
                                    <span class="amount-usd">${{ $fmtUsd($p->montant_attendu_usd) }}</span>
                                    <span class="amount-fc">{{ $fmtFc($p->montant_attendu_fc) }} FC</span>
                                </td>

                                <td data-label="Payé" class="cell-amount text-right">
                                    <span class="amount-label">Payé</span>
                                    <span class="amount-usd amount-success">${{ $fmtUsd($p->montant_paye_usd) }}</span>
                                    <span class="amount-fc">{{ $fmtFc($p->montant_paye_fc) }} FC</span>
                                    <div class="progress-track progress-sm">
                                        <div class="progress-fill" style="width: {{ $p->pourcentage_paye }}%"></div>
                                    </div>
                                </td>

                                <td data-label="Restant" class="cell-amount text-right">
                                    <span class="amount-label">Restant</span>
                                    @if($p->montant_restant_usd > 0)
                                        <span class="amount-usd amount-danger">${{ $fmtUsd($p->montant_restant_usd) }}</span>
                                        <span class="amount-fc">{{ $fmtFc($p->montant_restant_fc) }} FC</span>
                                    @else
                                        <span class="amount-usd text-muted">—</span>
                                    @endif
                                </td>

                                <td data-label="Statut" class="cell-statut text-center">
                                    <span class="pill pill-{{ $p->statut_key }}">
                                        <i class="fa-solid {{ $p->statut_icon }}" aria-hidden="true"></i>
                                        {{ $p->statut_label }}
                                    </span>
                                </td>

                                <td data-label="{{ $corbeille ? 'Supprimé le' : 'Date' }}"
                                    class="cell-date text-center">
                                    @if($corbeille)
                                        <span class="deleted-date">
                                            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                                            {{ $dateLabel }}
                                        </span>
                                    @else
                                        {{ $dateLabel }}
                                    @endif
                                </td>

                                <td data-label="Actions" class="cell-actions text-right">
                                    <div class="action-bar">
                                        @if($isTrashed)
                                            <form action="{{ route('admin.paiement-salaires.restaurer', $p->id) }}"
                                                  method="POST" class="inline-form">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                        class="action-btn action-success"
                                                        title="Restaurer">
                                                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                                    <span class="action-label">Restaurer</span>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.paiement-salaires.force-delete', $p->id) }}"
                                                  method="POST" class="inline-form"
                                                  onsubmit="return confirm('Supprimer DÉFINITIVEMENT ce paiement ? Action irréversible.')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="action-btn action-danger"
                                                        title="Supprimer définitivement">
                                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                    <span class="action-label">Supprimer</span>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.paiement-salaires.show', $p) }}"
                                               class="action-btn"
                                               title="Voir">
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                                <span class="action-label">Voir</span>
                                            </a>
                                            <a href="{{ route('admin.paiement-salaires.edit', $p) }}"
                                               class="action-btn"
                                               title="Modifier">
                                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                                <span class="action-label">Modifier</span>
                                            </a>
                                            <form action="{{ route('admin.paiement-salaires.destroy', $p) }}"
                                                  method="POST" class="inline-form"
                                                  onsubmit="return confirm('Déplacer ce paiement dans la corbeille ?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="action-btn action-danger"
                                                        title="Supprimer">
                                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                    <span class="action-label">Supprimer</span>
                                                </button>
                                            </form>
                                        @endif
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
                    <i class="fa-regular {{ $corbeille ? 'fa-trash-can' : 'fa-folder-open' }}" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">
                    @if($corbeille) Corbeille vide
                    @elseif($hasFilters) Aucun résultat
                    @else Aucun paiement enregistré @endif
                </h3>
                <p class="empty-text">
                    @if($corbeille) Aucun paiement supprimé à restaurer.
                    @elseif($hasFilters) Aucun paiement ne correspond à vos filtres.
                    @else Commencez par enregistrer un premier paiement de salaire. @endif
                </p>
                <div class="empty-actions">
                    @if($corbeille)
                        <a href="{{ $routeIndex }}" class="btn btn-primary">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Retour aux paiements
                        </a>
                    @else
                        @if($hasFilters)
                            <a href="{{ $routeIndex }}" class="btn btn-ghost">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Effacer les filtres
                            </a>
                        @endif
                        <a href="{{ route('admin.paiement-salaires.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nouveau paiement
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>

<style>
    /* ════════════════════════════════════════════════════════
       BASE
       ════════════════════════════════════════════════════════ */
    .page { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }

    .cell-mois .month-date,
    .cell-amount .amount-label { display: none; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .page-header { display: flex; flex-direction: column; align-items: flex-start; gap: 1.25rem; margin-bottom: 2rem; }
    .page-header-main { min-width: 0; width: 100%; }
    @media (min-width: 768px) {
        .page-header { flex-direction: row; justify-content: space-between; align-items: center; }
        .page-header-main { width: auto; }
    }
    .page-title { display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem; font-size: 1.75rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; margin: 0 0 0.25rem; line-height: 1.15; }
    .page-title-text { overflow: hidden; text-overflow: ellipsis; }
    .title-icon    { color: #6366f1; }
    .title-icon-sm { color: #6366f1; font-size: 1rem; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; line-height: 1.4; }

    .count-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 26px; padding: 0 0.6rem; background: #eef2ff; color: #4f46e5; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; flex-shrink: 0; }
    .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; width: 100%; }
    @media (min-width: 768px) { .header-actions { width: auto; } }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.7rem 1.25rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s ease; white-space: nowrap; text-align: center; }
    @media (max-width: 767px) { .btn { flex: 1 1 calc(50% - 0.3rem); min-width: 0; padding: 0.65rem 0.9rem; font-size: 0.85rem; } }
    @media (max-width: 420px) { .btn { flex: 1 1 100%; } }

    .btn-primary { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35); }
    .btn-secondary { background: #1e293b; color: #fff; }
    .btn-secondary:hover { background: #334155; transform: translateY(-1px); }
    .btn-ghost { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .btn-ghost:hover { border-color: #6366f1; color: #4f46e5; background: #f8fafc; }

    /* ════════════════════════════════════════════════════════
       STATS
       ════════════════════════════════════════════════════════ */
    .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card { display: flex; align-items: center; gap: 0.9rem; padding: 1.1rem; background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s; min-width: 0; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }

    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .stat-indigo  .stat-icon { background: #eef2ff; color: #4f46e5; }
    .stat-success .stat-icon { background: #ecfdf5; color: #059669; }
    .stat-warning .stat-icon { background: #fffbeb; color: #d97706; }
    .stat-danger  .stat-icon { background: #fef2f2; color: #dc2626; }

    .stat-content { min-width: 0; flex: 1; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; color: #94a3b8; margin: 0 0 0.15rem; }
    .stat-value { font-size: 1.4rem; font-weight: 700; color: #0f172a; line-height: 1.15; margin: 0; overflow: hidden; text-overflow: ellipsis; }
    .stat-unit { display: block; font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin-top: 0.15rem; }

    @media (max-width: 480px) {
        .stat-card { padding: 0.85rem; gap: 0.65rem; }
        .stat-icon { width: 36px; height: 36px; font-size: 0.95rem; }
        .stat-value { font-size: 1.1rem; }
        .stat-label { font-size: 0.65rem; }
        .stat-unit { font-size: 0.68rem; }
    }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .content-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; overflow: hidden; }
    .content-header { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
    .content-title { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; min-width: 0; }
    .content-meta { font-size: 0.8rem; color: #64748b; }
    .meta-item { display: inline-flex; align-items: center; gap: 0.35rem; }
    .meta-item i { color: #94a3b8; }
    .meta-item strong { color: #0f172a; }

    /* ─── SECTION REPLIABLE ─── */
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
        width: 26px; height: 26px; border-radius: 8px;
        background: #eef2ff; color: #4f46e5;
        border: none; display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 0.7rem; cursor: pointer;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), background 0.2s;
        flex-shrink: 0; padding: 0;
    }
    .toggle-btn:hover { background: #e0e7ff; }
    .toggle-btn.is-open { transform: rotate(90deg); }

    .toggle-hint {
        font-size: 0.75rem; font-weight: 600;
        color: #94a3b8; text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ════════════════════════════════════════════════════════
       PÉRIODE CARDS
       ════════════════════════════════════════════════════════ */
    .periode-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; padding: 1.25rem; }
    @media (min-width: 640px)  { .periode-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .periode-grid { grid-template-columns: repeat(4, 1fr); } }

    .periode-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; }
    .periode-name   { font-size: 0.85rem; font-weight: 600; color: #64748b; margin: 0 0 0.5rem; }
    .periode-amount { font-size: 1.25rem; font-weight: 800; color: #4f46e5; margin: 0; letter-spacing: -0.5px; }
    .periode-sub    { font-size: 0.75rem; color: #94a3b8; margin: 0 0 0.6rem; }
    .periode-meta   { display: flex; flex-direction: column; gap: 0.15rem; font-size: 0.72rem; color: #64748b; }
    .periode-meta i { color: #94a3b8; margin-right: 0.25rem; }

    /* ════════════════════════════════════════════════════════
       FILTRES
       ════════════════════════════════════════════════════════ */
    .filter-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; overflow: hidden; }
    .filter-header { padding: 1rem 1.25rem 0; }
    .filter-header-left { display: flex; align-items: center; gap: 0.6rem; }
    .filter-icon { color: #6366f1; }
    .filter-title { font-weight: 700; color: #0f172a; font-size: 0.95rem; }
    .filter-count { background: #eef2ff; color: #4f46e5; font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.3px; }
    .filter-body { padding: 1rem 1.25rem 1.25rem; }
    .filter-form { display: flex; flex-direction: column; gap: 1rem; }
    .filter-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
    @media (min-width: 640px)  { .filter-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .filter-grid { grid-template-columns: repeat(4, 1fr); } }

    .filter-field { display: flex; flex-direction: column; gap: 0.35rem; }
    .filter-label { font-size: 0.7rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .filter-select { width: 100%; padding: 0.65rem 2.5rem 0.65rem 0.9rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.9rem; color: #0f172a; appearance: none; cursor: pointer; outline: none; transition: all 0.2s;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 1.1rem; }
    .filter-select:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .filter-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0; flex-wrap: wrap; }
    @media (max-width: 640px) { .filter-actions { flex-direction: column-reverse; } .filter-actions .btn { width: 100%; } }

    /* ════════════════════════════════════════════════════════
       TABLEAU — Vue desktop
       ════════════════════════════════════════════════════════ */
    .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th { text-align: left; padding: 0.8rem 1.25rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .data-table tbody td { padding: 0.9rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .data-table tbody tr.row-trashed { background: #fef2f2; opacity: 0.85; }
    .data-table tbody tr.row-trashed:hover { background: #fee2e2; }
    .data-table tbody tr.row-trashed .cell-primary { text-decoration: line-through; text-decoration-thickness: 1px; text-decoration-color: #f87171; color: #64748b; }
    .data-table tbody tr.row-trashed .amount-usd,
    .data-table tbody tr.row-trashed .amount-success,
    .data-table tbody tr.row-trashed .amount-danger { color: #94a3b8; }

    .text-right  { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-muted  { color: #cbd5e1; }
    .cell-primary { font-weight: 600; color: #0f172a; }

    .eleve-cell { display: flex; align-items: center; gap: 0.6rem; }
    .eleve-avatar { width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.78rem; flex-shrink: 0; }
    .eleve-avatar.avatar-muted { background: #fee2e2; color: #dc2626; }
    .eleve-meta { min-width: 0; }

    .month-label { display: block; font-weight: 500; color: #0f172a; }
    .month-date  { display: none; }

    .amount-usd { display: block; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .amount-success { color: #059669; }
    .amount-danger  { color: #dc2626; }
    .amount-fc { display: block; font-size: 0.72rem; color: #94a3b8; font-variant-numeric: tabular-nums; }

    .progress-track { width: 100%; height: 6px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; margin-top: 0.5rem; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 9999px; transition: width 0.4s ease; }
    .progress-sm { height: 4px; max-width: 90px; margin-top: 0.35rem; margin-left: auto; }

    .pill { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; white-space: nowrap; }
    .pill i { font-size: 0.7rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-danger  { background: #fef2f2; color: #be123c; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    .deleted-date { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: #dc2626; font-weight: 600; }
    .deleted-date i { color: #f87171; }

    .action-bar { display: inline-flex; gap: 0.3rem; justify-content: flex-end; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; min-width: 34px; height: 34px; padding: 0 0.5rem; border-radius: 9px; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.18s ease; font-size: 0.9rem; text-decoration: none; font-weight: 600; }
    .action-btn:hover { background: #f1f5f9; color: #0f172a; }
    .action-btn.action-danger:hover  { background: #fef2f2; color: #dc2626; }
    .action-btn.action-success:hover { background: #ecfdf5; color: #059669; }
    .action-label { display: none; }
    .inline-form { display: inline; }

    .pagination-wrapper { padding: 0.9rem 1.25rem; border-top: 1px solid #f1f5f9; }

    /* ════════════════════════════════════════════════════════
       EMPTY STATE
       ════════════════════════════════════════════════════════ */
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1.5rem; text-align: center; gap: 0.5rem; }
    .empty-icon-wrapper { width: 80px; height: 80px; border-radius: 50%; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #cbd5e1; margin-bottom: 0.75rem; }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin: 0; }
    .empty-text  { font-size: 0.9rem; color: #94a3b8; max-width: 420px; margin: 0; line-height: 1.5; }
    .empty-actions { display: flex; gap: 0.6rem; margin-top: 1rem; flex-wrap: wrap; justify-content: center; }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE MOBILE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 767px) {
        .table-wrapper { overflow-x: visible; }
        .cell-date { display: none !important; }

        .data-table, .data-table tbody { display: block; width: 100%; }
        .data-table thead { display: none; }

        .data-table tbody tr {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-areas:
                "employe  employe  statut"
                "mois     mois     mois"
                "attendu  paye     restant"
                "actions  actions  actions";
            gap: 0.75rem 0.5rem;
            padding: 1rem;
            background: #fff;
            border-radius: 14px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin: 1rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .data-table tbody tr:hover { background: #fff; border-color: #c7d2fe; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08); }

        .data-table tbody tr.row-trashed { background: #fef2f2; border-color: #fecaca; opacity: 1; }
        .data-table tbody tr.row-trashed:hover { border-color: #fca5a5; }

        .data-table tbody td { display: block; padding: 0 !important; border: none; font-size: 0.85rem; }
        .data-table tbody td::before { display: none !important; }

        .cell-employe { grid-area: employe; min-width: 0; overflow: hidden; }
        .eleve-cell { gap: 0.65rem; align-items: center; }
        .eleve-avatar { width: 40px; height: 40px; font-size: 0.9rem; border-radius: 10px; }
        .cell-primary { font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }

        .cell-statut { grid-area: statut; display: flex !important; justify-content: flex-end; align-items: center; align-self: center; }

        .cell-mois { grid-area: mois; padding-bottom: 0.75rem !important; border-bottom: 1px dashed #e2e8f0 !important; display: flex !important; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; }
        .month-label { font-size: 0.8rem; color: #64748b; font-weight: 600; }
        .month-label::before { content: '📅 '; margin-right: 0.25rem; }
        .month-date { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
        .month-date::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #cbd5e1; margin-right: 0.25rem; }
        .row-trashed .month-date { color: #dc2626; }
        .row-trashed .month-date::before { background: #f87171; }

        .cell-amount { padding: 0.25rem 0 !important; text-align: left !important; display: flex !important; flex-direction: column; gap: 0.15rem; min-width: 0; }
        .cell-amount.text-right > .amount-usd, .cell-amount.text-right > .amount-fc { text-align: left; }

        .amount-label { display: block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: #94a3b8; margin-bottom: 0.15rem; }

        .amount-usd { font-size: 0.95rem; font-weight: 700; line-height: 1.2; }
        .amount-fc { font-size: 0.7rem; line-height: 1.2; }

        .data-table tbody td[data-label="Attendu"] { grid-area: attendu; }
        .data-table tbody td[data-label="Payé"]    { grid-area: paye; }
        .data-table tbody td[data-label="Restant"] { grid-area: restant; }

        .progress-sm { max-width: 100% !important; margin: 0.35rem 0 0 0 !important; }

        .cell-actions { grid-area: actions; padding-top: 0.75rem !important; border-top: 1px dashed #e2e8f0 !important; display: flex !important; justify-content: flex-end; }
        .action-bar { display: flex; width: 100%; gap: 0.4rem; justify-content: flex-end; }
        .action-btn { flex: 0 1 auto; min-width: 36px; height: 36px; padding: 0 0.7rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.8rem; gap: 0.4rem; }
        .action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .action-btn.action-danger:hover  { background: #fef2f2; border-color: #fecaca; }
        .action-btn.action-success:hover { background: #ecfdf5; border-color: #a7f3d0; }

        .action-label { display: inline; font-size: 0.78rem; font-weight: 600; }

        .pagination-wrapper { padding: 1rem; text-align: center; }
    }

    @media (max-width: 400px) {
        .page { padding: 1.25rem 0.75rem; }
        .page-title { font-size: 1.4rem; }
        .page-subtitle { font-size: 0.8rem; }
        .content-header { padding: 0.85rem 1rem; }
        .content-title  { font-size: 0.9rem; }

        .data-table tbody tr {
            padding: 0.85rem;
            margin: 0.75rem;
            gap: 0.6rem 0.4rem;
            grid-template-areas:
                "employe  statut"
                "mois     mois"
                "attendu  paye"
                "restant  restant"
                "actions  actions";
            grid-template-columns: 1fr 1fr;
        }

        .eleve-avatar { width: 36px; height: 36px; font-size: 0.8rem; }
        .cell-primary { font-size: 0.9rem; }

        .amount-label { font-size: 0.6rem; }
        .amount-usd   { font-size: 0.88rem; }
        .amount-fc    { font-size: 0.65rem; }

        .data-table tbody td[data-label="Restant"] { display: flex !important; flex-direction: row; align-items: center; justify-content: space-between; padding-top: 0.5rem !important; border-top: 1px dashed #e2e8f0; }
        .data-table tbody td[data-label="Restant"] .amount-label { margin-bottom: 0; }

        .action-btn { padding: 0 0.55rem; height: 34px; font-size: 0.75rem; }
        .action-label { font-size: 0.72rem; }

        .empty-icon-wrapper { width: 64px; height: 64px; font-size: 1.6rem; }
        .empty-title { font-size: 1rem; }
        .empty-text  { font-size: 0.85rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>

<script>
function paiementsSalairesPage() {
    return {
        /* État des sections : true = ouvert, false = fermé */
        sections: {
            mois: true,
        },

        /**
         * Restaure l'état sauvegardé depuis localStorage.
         */
        init() {
            try {
                const saved = localStorage.getItem('paiements_salaires_sections');
                if (saved) {
                    const parsed = JSON.parse(saved);
                    this.sections = { ...this.sections, ...parsed };
                }
            } catch (e) {
                // Ignore
            }
        },

        /**
         * Bascule une section + sauvegarde dans localStorage.
         */
        toggleSection(key) {
            this.sections[key] = !this.sections[key];

            try {
                localStorage.setItem('paiements_salaires_sections', JSON.stringify(this.sections));
            } catch (e) {
                // Ignore
            }
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    let timer = null;
    document.querySelectorAll('[data-auto-submit]').forEach(el => {
        el.addEventListener('change', () => {
            clearTimeout(timer);
            timer = setTimeout(() => el.form?.submit(), 120);
        });
    });
});
</script>
@endsection