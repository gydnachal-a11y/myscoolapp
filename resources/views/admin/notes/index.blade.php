@extends('layouts.admin')

@section('page_title', 'Consultation des notes')
@section('page_subtitle', 'Filtrez par période, session, section, salle et élève')

@section('content')
@php
    // ============================================================
    // PRÉPARATION DES DONNÉES (sortie du template pour la lisibilité)
    // ============================================================
    $isAdmin = $isAdmin ?? false;

    $isPaginator = $notes instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $totalNotes  = $isPaginator ? $notes->total() : $notes->count();
    $totalEleves = $eleves->count();
    $coursPublies = $periodeId ? ($nombreCoursPublies ?? 0) : 0;

    // Filtres actifs (pour chips de récap)
    $activeFilters = array_filter([
        'Période'  => $periodeId  ? $periodes->firstWhere('id', $periodeId)?->nom      : null,
        'Session'  => $sessionId  ? $sessions->firstWhere('id', $sessionId)?->nom      : null,
        'Section'  => $sectionId  ? $sections->firstWhere('id', $sectionId)?->nom      : null,
        'Option'   => $optionId   ? $options->firstWhere('id', $optionId)?->nom        : null,
        'Salle'    => $salleId    ? $salles->firstWhere('id', $salleId)?->nom          : null,
        'Élève'    => $eleveId    ? $eleves->firstWhere('id', $eleveId)?->nom_complet  : null,
    ]);

    // Élève / salle / période sélectionnés pour la vue détail
    $eleveSelectionne   = $eleveId   ? $eleves->firstWhere('id', $eleveId)   : null;
    $salleSelectionnee  = $salleId   ? $salles->firstWhere('id', $salleId)   : null;
    $periodeSelectionnee = $periodeId ? $periodes->firstWhere('id', $periodeId) : null;

    // Routes dynamiques (admin vs member)
    $routeIndex = request()->routeIs('admin.*')
        ? route('admin.notes.index')
        : (Route::has('member.notes.index') ? route('member.notes.index') : route('admin.notes.index'));

    $routeSaisie = request()->routeIs('admin.*')
        ? route('admin.notes.saisie')
        : (Route::has('member.notes.saisie') ? route('member.notes.saisie') : route('admin.notes.saisie'));
@endphp

<div class="page" x-data="{ filtersOpen: true }">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-chart-line title-icon" aria-hidden="true"></i>
                Consultation des notes
            </h1>
            <p class="page-subtitle">
                Filtrez par période, session, section, salle et élève pour afficher les notes
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ $routeSaisie }}" class="btn btn-primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                <span>Aller à la saisie</span>
            </a>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- STATS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="stats-grid" aria-label="Statistiques">
        <article class="stat-card stat-indigo">
            <div class="stat-icon"><i class="fa-solid fa-list-ol" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Notes affichées</p>
                <p class="stat-value">{{ number_format($totalNotes, 0, ',', ' ') }}</p>
            </div>
        </article>

        <article class="stat-card stat-emerald">
            <div class="stat-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Élèves</p>
                <p class="stat-value">{{ number_format($totalEleves, 0, ',', ' ') }}</p>
            </div>
        </article>

        <article class="stat-card stat-amber">
            <div class="stat-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Cours publiés</p>
                <p class="stat-value">{{ number_format($coursPublies, 0, ',', ' ') }}</p>
            </div>
        </article>

        @if(isset($moyenne) && $moyenne !== null)
            <article class="stat-card stat-violet">
                <div class="stat-icon"><i class="fa-solid fa-calculator" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Moyenne</p>
                    <p class="stat-value">
                        {{ number_format($moyenne, 2, ',', ' ') }}
                        <span class="stat-unit">/20</span>
                    </p>
                </div>
            </article>
        @endif

        @if(isset($pourcentage) && $pourcentage !== null)
            <article class="stat-card stat-cyan">
                <div class="stat-icon"><i class="fa-solid fa-percent" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Pourcentage</p>
                    <p class="stat-value">
                        {{ $pourcentage }}
                        <span class="stat-unit">%</span>
                    </p>
                </div>
            </article>
        @endif
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- FILTRES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="filter-card">
        <div class="filter-header" @click="filtersOpen = !filtersOpen">
            <div class="filter-header-left">
                <i class="fa-solid fa-sliders filter-icon" aria-hidden="true"></i>
                <span class="filter-title">Filtres</span>
                @if(!empty($activeFilters))
                    <span class="filter-count">{{ count($activeFilters) }}</span>
                @endif
            </div>
            <button type="button" class="filter-toggle" :aria-expanded="filtersOpen.toString()">
                <i class="fa-solid fa-chevron-down" :class="{ 'rotated': !filtersOpen }" aria-hidden="true"></i>
            </button>
        </div>

        <div class="filter-body" x-show="filtersOpen" x-collapse>
            <form method="GET" action="{{ $routeIndex }}" class="filter-form" id="filterForm">
                <div class="filter-grid">
                    {{-- Période --}}
                    <div class="filter-field">
                        <label for="f-periode" class="filter-label">
                            <i class="fa-regular fa-calendar" aria-hidden="true"></i> Période
                        </label>
                        <select name="periode_note_id" id="f-periode" class="filter-select" data-auto-submit>
                            <option value="">Toutes les périodes</option>
                            @foreach($periodes as $p)
                                <option value="{{ $p->id }}" @selected($periodeId == $p->id)>{{ $p->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Session --}}
                    <div class="filter-field">
                        <label for="f-session" class="filter-label">
                            <i class="fa-regular fa-layer-group" aria-hidden="true"></i> Session
                        </label>
                        <select name="session_id" id="f-session" class="filter-select" data-auto-submit>
                            <option value="">Toutes les sessions</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" @selected($sessionId == $session->id)>{{ $session->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Section --}}
                    <div class="filter-field">
                        <label for="f-section" class="filter-label">
                            <i class="fa-regular fa-th-large" aria-hidden="true"></i> Section
                        </label>
                        <select name="section_id" id="f-section" class="filter-select" data-auto-submit>
                            <option value="">Toutes les sections</option>
                            @foreach($sections as $s)
                                <option value="{{ $s->id }}" @selected($sectionId == $s->id)>{{ $s->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Option --}}
                    <div class="filter-field">
                        <label for="f-option" class="filter-label">
                            <i class="fa-regular fa-cog" aria-hidden="true"></i> Option
                        </label>
                        <select name="option_id" id="f-option" class="filter-select" data-auto-submit>
                            <option value="">Toutes les options</option>
                            @foreach($options as $opt)
                                <option value="{{ $opt->id }}" @selected($optionId == $opt->id)>{{ $opt->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Salle --}}
                    <div class="filter-field">
                        <label for="f-salle" class="filter-label">
                            <i class="fa-regular fa-building" aria-hidden="true"></i> Salle de classe
                        </label>
                        <select name="salle_id" id="f-salle" class="filter-select" data-auto-submit>
                            <option value="">Toutes les salles</option>
                            @foreach($salles as $s)
                                <option value="{{ $s->id }}" @selected($salleId == $s->id)>{{ $s->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Élève --}}
                    <div class="filter-field">
                        <label for="f-eleve" class="filter-label">
                            <i class="fa-regular fa-user" aria-hidden="true"></i> Élève
                        </label>
                        <select name="eleve_id" id="f-eleve" class="filter-select"
                                data-auto-submit @disabled(!$salleId)>
                            <option value="">Tous les élèves</option>
                            @foreach($eleves as $e)
                                <option value="{{ $e->id }}" @selected($eleveId == $e->id)>{{ $e->nom_complet }}</option>
                            @endforeach
                        </select>
                        @if(!$salleId)
                            <small class="filter-hint">
                                <i class="fa-solid fa-info-circle" aria-hidden="true"></i>
                                Sélectionnez d'abord une salle
                            </small>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="filter-actions">
                    @if(!empty($activeFilters))
                        <a href="{{ $routeIndex }}" class="btn btn-ghost">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Réinitialiser
                        </a>
                    @endif
                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Appliquer
                    </button>
                </div>
            </form>

            {{-- Récap des filtres actifs --}}
            @if(!empty($activeFilters))
                <div class="filter-chips">
                    <span class="chips-label">Filtres actifs :</span>
                    @foreach($activeFilters as $key => $value)
                        <span class="chip">
                            <strong>{{ $key }}</strong> · {{ $value }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- CONTENU PRINCIPAL --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}

    {{-- Cas 1 : Admin sans élève → liste paginée --}}
    @if($isAdmin && !$eleveId)
        <section class="content-card">
            <div class="content-header">
                <h2 class="content-title">
                    <i class="fa-solid fa-list-ul title-icon-sm" aria-hidden="true"></i>
                    Liste des notes
                    @if($isPaginator)
                        <span class="count-badge">{{ number_format($notes->total(), 0, ',', ' ') }}</span>
                    @endif
                </h2>
            </div>

            @if($notes->count())
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Élève</th>
                                <th scope="col">Cours</th>
                                <th scope="col">Période</th>
                                <th scope="col" class="text-center">Note</th>
                                <th scope="col">Appréciation</th>
                                <th scope="col" class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notes as $note)
                                <tr>
                                    <td data-label="Élève">
                                        <span class="cell-primary">{{ $note->eleve?->nom_complet ?? '—' }}</span>
                                    </td>
                                    <td data-label="Cours">{{ $note->courSalle?->cour?->nom ?? 'Cours supprimé' }}</td>
                                    <td data-label="Période">{{ $note->periodeNote?->nom ?? '—' }}</td>
                                    <td data-label="Note" class="text-center">
                                        <span class="note-badge">
                                            {{ $note->note !== null ? number_format($note->note, 2, ',', ' ') : '—' }}
                                        </span>
                                    </td>
                                    <td data-label="Appréciation">{{ $note->appreciation ?: '—' }}</td>
                                    <td data-label="Statut" class="text-center">
                                        @if($note->statut === \App\Models\Note::STATUT_PUBLIE)
                                            <span class="pill pill-success">
                                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                                Publié
                                            </span>
                                        @else
                                            <span class="pill pill-warning">
                                                <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                                Brouillon
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($isPaginator && $notes->hasPages())
                    <div class="pagination-wrapper">{{ $notes->links() }}</div>
                @endif
            @else
                @include('admin.notes._empty-state', [
                    'icon'  => 'fa-folder-open',
                    'title' => 'Aucune note trouvée',
                    'text'  => 'Modifiez les filtres ou ajoutez de nouvelles notes.',
                ])
            @endif
        </section>

    {{-- Cas 2 : Élève sélectionné → bulletin condensé --}}
    @elseif($eleveId)
        <section class="content-card content-card-highlight">
            <div class="student-header">
                <div class="student-info">
                    <div class="student-avatar">
                        {{ strtoupper(substr($eleveSelectionne?->nom_complet ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="student-name">
                            {{ $eleveSelectionne?->nom_complet ?? 'Élève introuvable' }}
                        </h2>
                        <p class="student-meta">
                            @if($salleSelectionnee)
                                <span><i class="fa-solid fa-door-open" aria-hidden="true"></i> {{ $salleSelectionnee->nom }}</span>
                            @endif
                            @if($periodeSelectionnee)
                                <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $periodeSelectionnee->nom }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="student-results">
                    @if($moyenne !== null)
                        <div class="result-box result-indigo">
                            <span class="result-label">Moyenne</span>
                            <span class="result-value">{{ number_format($moyenne, 2, ',', ' ') }}<small>/20</small></span>
                        </div>
                    @endif
                    @if($pourcentage !== null)
                        <div class="result-box result-emerald">
                            <span class="result-label">Pourcentage</span>
                            <span class="result-value">{{ $pourcentage }}<small>%</small></span>
                        </div>
                    @endif
                </div>
            </div>

            @if($notes->count())
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Cours</th>
                                <th scope="col" class="text-center">Pondération</th>
                                <th scope="col" class="text-center">Note</th>
                                <th scope="col">Titulaire</th>
                                <th scope="col">Appréciation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notes as $note)
                                <tr>
                                    <td data-label="Cours">
                                        <span class="cell-primary">{{ $note->courSalle?->cour?->nom ?? 'Cours supprimé' }}</span>
                                    </td>
                                    <td data-label="Pondération" class="text-center">
                                        <span class="pill pill-neutral">
                                            ×{{ $note->courSalle?->ponderation?->valeur ?? 1 }}
                                        </span>
                                    </td>
                                    <td data-label="Note" class="text-center">
                                        <span class="note-badge note-badge-lg">
                                            {{ $note->note !== null ? number_format($note->note, 2, ',', ' ') : '—' }}
                                        </span>
                                    </td>
                                    <td data-label="Titulaire">{{ $note->courSalle?->titulaire?->name ?? '—' }}</td>
                                    <td data-label="Appréciation">{{ $note->appreciation ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="content-footer">
                    <a href="{{ route('admin.notes.bulletin.print', ['eleve_id' => $eleveId, 'periode_note_id' => $periodeId]) }}"
                       target="_blank" class="btn btn-ghost">
                        <i class="fa-solid fa-print" aria-hidden="true"></i> Imprimer le bulletin
                    </a>
                    @if(Route::has('admin.notes.bulletin.pdf'))
                        <a href="{{ route('admin.notes.bulletin.pdf', ['eleve_id' => $eleveId, 'periode_note_id' => $periodeId]) }}"
                           class="btn btn-primary">
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Exporter en PDF
                        </a>
                    @endif
                </div>
            @else
                @include('admin.notes._empty-state', [
                    'icon'  => 'fa-folder-open',
                    'title' => 'Aucune note pour cet élève',
                    'text'  => 'Les notes n\'ont peut-être pas encore été publiées pour cette période.',
                ])
            @endif
        </section>

    {{-- Cas 3 : Aucun élève sélectionné --}}
    @else
        @include('admin.notes._empty-state', [
            'icon'  => 'fa-hand-pointer',
            'title' => $isAdmin
                ? 'Sélectionnez une période et une salle'
                : 'Sélectionnez une période, une session et une salle',
            'text'  => 'Utilisez les filtres ci-dessus pour afficher les notes.',
        ])
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- STYLES --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ========== LAYOUT ========== */
    .page {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* ========== HEADER ========== */
    .page-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.75rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        margin: 0 0 0.25rem;
    }
    .title-icon { color: #6366f1; }
    .title-icon-sm { color: #6366f1; font-size: 1rem; }

    .page-subtitle {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }

    .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }

    /* ========== BOUTONS ========== */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
    }

    .btn-secondary {
        background: #1e293b;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #334155;
        transform: translateY(-1px);
    }

    .btn-ghost {
        background: transparent;
        color: #64748b;
        border: 1.5px solid #e2e8f0;
    }
    .btn-ghost:hover {
        border-color: #6366f1;
        color: #4f46e5;
        background: #f8fafc;
    }

    /* ========== STATS ========== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 768px)  { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); } }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .stat-indigo  .stat-icon { background: #eef2ff; color: #4f46e5; }
    .stat-emerald .stat-icon { background: #ecfdf5; color: #059669; }
    .stat-amber   .stat-icon { background: #fffbeb; color: #d97706; }
    .stat-violet  .stat-icon { background: #f5f3ff; color: #7c3aed; }
    .stat-cyan    .stat-icon { background: #ecfeff; color: #0891b2; }

    .stat-content { min-width: 0; }
    .stat-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
        color: #94a3b8;
        margin: 0 0 0.15rem;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
        margin: 0;
    }
    .stat-unit {
        font-size: 0.8rem;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ========== FILTRES ========== */
    .filter-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        cursor: pointer;
        user-select: none;
        transition: background 0.15s;
    }
    .filter-header:hover { background: #f8fafc; }

    .filter-header-left {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .filter-icon { color: #6366f1; }
    .filter-title {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.95rem;
    }
    .filter-count {
        background: #eef2ff;
        color: #4f46e5;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }
    .filter-toggle {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.25rem;
        transition: transform 0.25s;
    }
    .filter-toggle .rotated { transform: rotate(-90deg); }

    .filter-body {
        padding: 0 1.25rem 1.25rem;
        border-top: 1px solid #f1f5f9;
    }

    .filter-form { display: flex; flex-direction: column; gap: 1.25rem; padding-top: 1.25rem; }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px)  { .filter-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .filter-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1280px) { .filter-grid { grid-template-columns: repeat(6, 1fr); } }

    .filter-field {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .filter-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #475569;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .filter-label i { color: #94a3b8; font-size: 0.75rem; }

    .filter-select {
        width: 100%;
        padding: 0.6rem 2.5rem 0.6rem 0.9rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 0.9rem;
        color: #0f172a;
        transition: all 0.2s;
        outline: none;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
        background-size: 1.1rem;
    }
    .filter-select:focus {
        border-color: #6366f1;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .filter-select:disabled {
        background: #f1f5f9;
        cursor: not-allowed;
        color: #94a3b8;
        opacity: 0.6;
    }

    .filter-hint {
        font-size: 0.7rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.15rem;
    }

    .filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        padding-top: 0.5rem;
        border-top: 1px dashed #e2e8f0;
    }

    .filter-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid #f1f5f9;
        margin-top: 0.5rem;
    }
    .chips-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.3rem 0.75rem;
        background: #eef2ff;
        color: #4338ca;
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 500;
    }
    .chip strong { color: #4f46e5; font-weight: 700; }

    /* ========== CONTENU ========== */
    .content-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        overflow: hidden;
    }
    .content-card-highlight {
        border-color: #c7d2fe;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.08);
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .content-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 24px;
        padding: 0 0.5rem;
        background: #f1f5f9;
        color: #475569;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    /* ========== HEADER ÉLÈVE ========== */
    .student-header {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #eef2ff 0%, #ffffff 100%);
        border-bottom: 1px solid #e0e7ff;
    }
    @media (min-width: 768px) {
        .student-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .student-info { display: flex; align-items: center; gap: 1rem; }

    .student-avatar {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }

    .student-name {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.25rem;
        letter-spacing: -0.3px;
    }

    .student-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.85rem;
        color: #64748b;
        margin: 0;
    }
    .student-meta i { color: #94a3b8; margin-right: 0.25rem; }

    .student-results { display: flex; gap: 0.75rem; }

    .result-box {
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        text-align: center;
        min-width: 100px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .result-indigo  { border-color: #c7d2fe; }
    .result-emerald { border-color: #a7f3d0; }

    .result-label {
        display: block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 0.15rem;
    }
    .result-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
    }
    .result-indigo  .result-value { color: #4f46e5; }
    .result-emerald .result-value { color: #059669; }
    .result-value small {
        font-size: 0.7rem;
        font-weight: 600;
        color: #94a3b8;
        margin-left: 0.15rem;
    }

    /* ========== TABLEAU ========== */
    .table-wrapper { overflow-x: auto; }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: #475569;
    }

    .data-table thead th {
        text-align: left;
        padding: 0.8rem 1.25rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .data-table tbody td {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .text-center { text-align: center !important; }
    .cell-primary { font-weight: 600; color: #0f172a; }

    /* ========== BADGES ========== */
    .note-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        padding: 0.25rem 0.65rem;
        background: #eef2ff;
        color: #4338ca;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .note-badge-lg { font-size: 0.9rem; padding: 0.35rem 0.8rem; }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .pill i { font-size: 0.7rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    /* ========== FOOTER CONTENU ========== */
    .content-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        padding: 1rem 1.25rem;
        border-top: 1px solid #f1f5f9;
        background: #f8fafc;
        flex-wrap: wrap;
    }

    /* ========== PAGINATION ========== */
    .pagination-wrapper {
        padding: 0.9rem 1.25rem;
        border-top: 1px solid #f1f5f9;
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 640px) {
        .data-table, .data-table tbody, .data-table tr, .data-table td {
            display: block;
            width: 100%;
        }
        .data-table thead { display: none; }

        .data-table tbody tr {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            border: 1px solid #f1f5f9;
            margin: 1rem;
            padding: 0.75rem;
        }

        .data-table td {
            padding: 0.4rem 0 !important;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table td:last-child { border-bottom: none; }

        .data-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .data-table td.text-center { justify-content: space-between; }

        .filter-actions { flex-direction: column-reverse; }
        .filter-actions .btn { width: 100%; justify-content: center; }

        .student-results {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- SCRIPTS --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ✅ Auto-submit des filtres (avec debounce pour éviter les double-envois)
        let timer = null;
        document.querySelectorAll('[data-auto-submit]').forEach(select => {
            select.addEventListener('change', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    document.getElementById('filterForm')?.submit();
                }, 150);
            });
        });
    });
</script>
@endsection