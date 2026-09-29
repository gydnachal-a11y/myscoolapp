@extends('layouts.admin')

@section('page_title', 'Saisie des notes')
@section('page_subtitle', 'Sélectionnez une salle, un cours et une période')

@section('content')
@php
    // ============================================================
    // PRÉPARATION
    // ============================================================
    $routeIndex  = request()->routeIs('admin.*')
        ? route('admin.notes.index')
        : (Route::has('member.notes.index') ? route('member.notes.index') : route('admin.notes.index'));

    $routeSaisie = request()->routeIs('admin.*')
        ? route('admin.notes.saisie')
        : (Route::has('member.notes.saisie') ? route('member.notes.saisie') : route('admin.notes.saisie'));

    $routeStore = request()->routeIs('admin.*')
        ? route('admin.notes.store-mass')
        : (Route::has('member.notes.store-mass') ? route('member.notes.store-mass') : route('admin.notes.store-mass'));

    $hasGrille = isset($grilleData) && $grilleData
        && count($grilleData['eleves']) > 0
        && count($grilleData['cours']) > 0;

    // Nombre de notes déjà publiées existantes (pour info)
    $nbNotesPubliees = 0;
    if ($hasGrille) {
        foreach ($grilleData['notes'] as $eleveNotes) {
            foreach ($eleveNotes as $note) {
                if ($note && ($note->statut ?? null) === \App\Models\Note::STATUT_PUBLIE) {
                    $nbNotesPubliees++;
                }
            }
        }
    }

    // Filtres actifs (chips récap)
    $activeFilters = array_filter([
        'Salle'   => request('salle_id')     ? ($salles->firstWhere('id', request('salle_id'))?->nom) : null,
        'Cours'   => request('cour_salle_id') ? ($coursSalles->firstWhere('id', request('cour_salle_id'))?->cour?->nom) : null,
        'Période' => request('periode_note_id') ? ($periodes->firstWhere('id', request('periode_note_id'))?->nom) : null,
    ]);
@endphp

<div class="page">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-pen-to-square title-icon" aria-hidden="true"></i>
                Saisie des notes
            </h1>
            <p class="page-subtitle">
                Choisissez une salle, un cours et une période pour saisir les notes
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ $routeIndex }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour à la consultation</span>
            </a>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- BROUILLONS EN ATTENTE --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if(isset($brouillons) && $brouillons->isNotEmpty())
        <section class="drafts-section">
            <div class="drafts-header">
                <div class="drafts-header-left">
                    <i class="fa-solid fa-clock-rotate-left drafts-icon" aria-hidden="true"></i>
                    <h2 class="drafts-title">Brouillons en attente</h2>
                    <span class="count-badge count-amber">{{ $brouillons->count() }}</span>
                </div>
                <p class="drafts-subtitle">Reprenez une saisie commencée mais non publiée</p>
            </div>

            <div class="drafts-grid">
                @foreach($brouillons as $courSalleId => $notes)
                    @php
                        $premiereNote = $notes->first();
                        $cour         = $premiereNote->courSalle?->cour;
                        $salle        = $premiereNote->courSalle?->salle;
                        $periode      = $premiereNote->periodeNote;
                        $nbNotes      = $notes->count();
                    @endphp
                    <a href="{{ $routeSaisie }}?salle_id={{ $salle?->id }}&cour_salle_id={{ $courSalleId }}&periode_note_id={{ $periode?->id }}"
                       class="draft-card">
                        <div class="draft-card-icon">
                            <i class="fa-solid fa-file-pen" aria-hidden="true"></i>
                        </div>
                        <div class="draft-card-body">
                            <p class="draft-card-title">{{ $cour?->nom ?? 'Cours supprimé' }}</p>
                            <p class="draft-card-meta">
                                @if($salle) <span>{{ $salle->nom }}</span> @endif
                                @if($periode) <span class="dot-sep">•</span> <span>{{ $periode->nom }}</span> @endif
                            </p>
                        </div>
                        <div class="draft-card-badge">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                            {{ $nbNotes }}
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- FILTRES DE SÉLECTION --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="filter-card">
        <div class="filter-header">
            <div class="filter-header-left">
                <i class="fa-solid fa-sliders filter-icon" aria-hidden="true"></i>
                <span class="filter-title">Sélection</span>
                @if(!empty($activeFilters))
                    <span class="filter-count">{{ count($activeFilters) }}/3</span>
                @endif
            </div>
        </div>

        <div class="filter-body">
            <form method="GET" action="{{ $routeSaisie }}" class="filter-form" id="filterForm">
                <div class="filter-grid">
                    {{-- Salle --}}
                    <div class="filter-field">
                        <label for="f-salle" class="filter-label">
                            <span class="step-num">1</span> Salle de classe
                        </label>
                        <select name="salle_id" id="f-salle"
                                class="filter-select" data-auto-submit>
                            <option value="">— Choisir une salle —</option>
                            @foreach($salles as $salle)
                                <option value="{{ $salle->id }}" @selected(request('salle_id') == $salle->id)>
                                    {{ $salle->nom }} @if($salle->section) ({{ $salle->section->nom }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Cours --}}
                    <div class="filter-field">
                        <label for="f-cours" class="filter-label">
                            <span class="step-num">2</span> Cours
                        </label>
                        <select name="cour_salle_id" id="f-cours"
                                class="filter-select" data-auto-submit
                                @disabled(!request('salle_id'))>
                            <option value="">— Choisir un cours —</option>
                            @foreach($coursSalles as $cs)
                                <option value="{{ $cs->id }}" @selected(request('cour_salle_id') == $cs->id)>
                                    {{ $cs->cour?->nom ?? 'Cours supprimé' }} — {{ $cs->salle?->nom ?? 'Salle supprimée' }}
                                </option>
                            @endforeach
                        </select>
                        @if(!request('salle_id'))
                            <small class="filter-hint">
                                <i class="fa-solid fa-info-circle" aria-hidden="true"></i>
                                Sélectionnez d'abord une salle
                            </small>
                        @endif
                    </div>

                    {{-- Période --}}
                    <div class="filter-field">
                        <label for="f-periode" class="filter-label">
                            <span class="step-num">3</span> Période
                        </label>
                        <select name="periode_note_id" id="f-periode"
                                class="filter-select" data-auto-submit
                                @disabled(!request('cour_salle_id'))>
                            <option value="">— Choisir une période —</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}" @selected(request('periode_note_id') == $periode->id)>
                                    {{ $periode->nom }}
                                </option>
                            @endforeach
                        </select>
                        @if(!request('cour_salle_id'))
                            <small class="filter-hint">
                                <i class="fa-solid fa-info-circle" aria-hidden="true"></i>
                                Sélectionnez d'abord un cours
                            </small>
                        @endif
                    </div>
                </div>

                @if(!empty($activeFilters))
                    <div class="filter-chips">
                        <span class="chips-label">Sélection :</span>
                        @foreach($activeFilters as $key => $value)
                            <span class="chip">
                                <strong>{{ $key }}</strong> · {{ $value }}
                            </span>
                        @endforeach
                        <a href="{{ $routeSaisie }}" class="chip-reset">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Effacer
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- MESSAGE CONTEXTUEL --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($message)
        <div class="alert alert-info">
            <i class="fa-solid fa-circle-info alert-icon" aria-hidden="true"></i>
            <span>{{ $message }}</span>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- GRILLE DE SAISIE --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($hasGrille)
        <form action="{{ $routeStore }}"
              method="POST"
              id="notesForm"
              class="saisie-form"
              onsubmit="return validerNotesAvantEnvoi(event)">
            @csrf
            <input type="hidden" name="salle_classe_id" value="{{ request('salle_id') }}">
            <input type="hidden" name="periode_note_id" value="{{ request('periode_note_id') }}">
            <input type="hidden" name="cour_salle_id"   value="{{ request('cour_salle_id') }}">

            {{-- En-tête contextuelle --}}
            @php
                $courSelectionne = $grilleData['cours']->first();
                $pointMaxCours   = $courSelectionne?->ponderation?->valeur ?? 20;
            @endphp
            <section class="content-card">
                <div class="grille-header">
                    <div class="grille-header-left">
                        <div class="grille-icon">
                            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="grille-title">
                                {{ $courSelectionne?->cour?->nom ?? 'Cours' }}
                            </h2>
                            <p class="grille-meta">
                                <span><i class="fa-solid fa-users" aria-hidden="true"></i> {{ $grilleData['eleves']->count() }} élèves</span>
                                <span class="dot-sep">•</span>
                                <span><i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i> Noté sur {{ $pointMaxCours }}</span>
                                @if($nbNotesPubliees > 0)
                                    <span class="dot-sep">•</span>
                                    <span class="text-warning"><i class="fa-solid fa-lock" aria-hidden="true"></i> {{ $nbNotesPubliees }} déjà publiée(s)</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Barre de progression --}}
                    <div class="grille-progress">
                        <div class="progress-info">
                            <span class="progress-label">Progression</span>
                            <span class="progress-value"><span id="progressCount">0</span> / {{ $grilleData['eleves']->count() }}</span>
                        </div>
                        <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="progressBar">
                            <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table saisie-table">
                        <thead>
                            <tr>
                                <th scope="col" class="col-eleve">Élève</th>
                                <th scope="col" class="col-note text-center">
                                    Note
                                    <span class="th-unit">/ {{ $pointMaxCours }}</span>
                                </th>
                                <th scope="col" class="col-appreciation">Appréciation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grilleData['eleves'] as $eleve)
                                @php
                                    $cour       = $grilleData['cours']->first();
                                    $noteIndex  = $eleve->id . '_' . $cour->id;
                                    $pointMax   = $cour->ponderation->valeur ?? 20;
                                    $existante  = $grilleData['notes'][$eleve->id][$cour->id] ?? null;
                                    $valeur     = $existante?->note ?? '';
                                    $appreciation = $existante?->appreciation ?? '';
                                    $isPublie   = ($existante?->statut ?? null) === \App\Models\Note::STATUT_PUBLIE;
                                    $initiale   = strtoupper(substr($eleve->nom_complet ?? '?', 0, 1));
                                @endphp
                                <tr class="{{ $isPublie ? 'row-locked' : '' }}">
                                    {{-- Élève --}}
                                    <td data-label="Élève" class="col-eleve">
                                        <div class="eleve-cell">
                                            <div class="eleve-avatar">{{ $initiale }}</div>
                                            <span class="eleve-name">{{ $eleve->nom_complet }}</span>
                                        </div>
                                    </td>

                                    {{-- Note --}}
                                    <td data-label="Note" class="col-note text-center">
                                        <input type="hidden"
                                               name="notes[{{ $noteIndex }}][eleve_id]"
                                               value="{{ $eleve->id }}">
                                        <input type="hidden"
                                               name="notes[{{ $noteIndex }}][cour_salle_id]"
                                               value="{{ $cour->id }}">

                                        @if($isPublie)
                                            {{-- Note publiée : lecture seule --}}
                                            <span class="note-locked">
                                                {{ number_format($valeur, 2, ',', ' ') }}
                                            </span>
                                        @else
                                            <input type="number"
                                                   name="notes[{{ $noteIndex }}][note]"
                                                   value="{{ $valeur }}"
                                                   min="0"
                                                   max="{{ $pointMax }}"
                                                   step="0.01"
                                                   inputmode="decimal"
                                                   placeholder="—"
                                                   data-point-max="{{ $pointMax }}"
                                                   class="note-input"
                                                   aria-label="Note de {{ $eleve->nom_complet }}">
                                        @endif
                                    </td>

                                    {{-- Appréciation --}}
                                    <td data-label="Appréciation" class="col-appreciation">
                                        @if($isPublie)
                                            <span class="appreciation-locked">{{ $appreciation ?: '—' }}</span>
                                        @else
                                            <input type="text"
                                                   name="notes[{{ $noteIndex }}][appreciation]"
                                                   value="{{ $appreciation }}"
                                                   maxlength="255"
                                                   placeholder="Facultatif"
                                                   class="appreciation-input"
                                                   aria-label="Appréciation pour {{ $eleve->nom_complet }}">
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer sticky --}}
                <div class="saisie-footer">
                    <div class="footer-info">
                        <span class="footer-counter">
                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                            <strong id="noteCounter">0</strong> note(s) saisie(s) sur {{ $grilleData['eleves']->count() }}
                        </span>
                        @if($nbNotesPubliees > 0)
                            <span class="footer-hint">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                Les notes déjà publiées ne peuvent plus être modifiées.
                            </span>
                        @endif
                    </div>

                    <div class="footer-actions">
                        <button type="submit" name="statut" value="brouillon" class="btn btn-ghost">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            <span>Sauvegarder brouillon</span>
                        </button>
                        <button type="submit" name="statut" value="publie" class="btn btn-primary"
                                id="btnPublier">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            <span>Publier les notes</span>
                        </button>
                    </div>
                </div>
            </section>
        </form>

    {{-- État vide : sélection incomplète --}}
    @elseif(request('salle_id') && request('periode_note_id') && request('cour_salle_id'))
        <div class="empty-state">
            <div class="empty-icon-wrapper">
                <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
            </div>
            <h3 class="empty-title">Aucune donnée disponible</h3>
            <p class="empty-text">
                Vérifiez que des élèves sont bien inscrits dans cette salle, ou essayez une autre combinaison.
            </p>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon-wrapper">
                <i class="fa-regular fa-hand-pointer" aria-hidden="true"></i>
            </div>
            <h3 class="empty-title">Sélectionnez une salle, un cours et une période</h3>
            <p class="empty-text">
                La grille de saisie apparaîtra une fois les trois filtres complétés.
            </p>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- STYLES --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ========== LAYOUT ========== */
    .page { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }

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

    /* ========== BROUILLONS ========== */
    .drafts-section {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .drafts-header { margin-bottom: 1rem; }

    .drafts-header-left {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 0.15rem;
    }
    .drafts-icon { color: #d97706; }
    .drafts-title { font-size: 1rem; font-weight: 700; color: #78350f; margin: 0; }
    .drafts-subtitle { font-size: 0.8rem; color: #a16207; margin: 0; padding-left: 1.75rem; }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 22px;
        padding: 0 0.5rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .count-amber { background: #fef3c7; color: #92400e; }

    .drafts-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.6rem;
    }
    @media (min-width: 640px)  { .drafts-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .drafts-grid { grid-template-columns: repeat(3, 1fr); } }

    .draft-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: #fff;
        border: 1px solid #fde68a;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .draft-card:hover {
        border-color: #f59e0b;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(217, 119, 6, 0.12);
    }

    .draft-card-icon {
        width: 36px;
        height: 36px;
        background: #fef3c7;
        color: #d97706;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.95rem;
    }

    .draft-card-body { flex: 1; min-width: 0; }
    .draft-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #78350f;
        margin: 0 0 0.15rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .draft-card-meta {
        font-size: 0.75rem;
        color: #92400e;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .dot-sep { color: #cbd5e1; }

    .draft-card-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: #fef3c7;
        color: #92400e;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .draft-card-badge i { font-size: 0.65rem; }

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
        padding: 1rem 1.25rem 0;
    }
    .filter-header-left {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .filter-icon { color: #6366f1; }
    .filter-title { font-weight: 700; color: #0f172a; font-size: 0.95rem; }
    .filter-count {
        background: #eef2ff;
        color: #4f46e5;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }

    .filter-body { padding: 1rem 1.25rem 1.25rem; }

    .filter-form { display: flex; flex-direction: column; gap: 1rem; }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) { .filter-grid { grid-template-columns: repeat(3, 1fr); } }

    .filter-field { display: flex; flex-direction: column; gap: 0.4rem; }

    .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 800;
    }

    .filter-select {
        width: 100%;
        padding: 0.65rem 2.5rem 0.65rem 0.9rem;
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
        opacity: 0.55;
    }

    .filter-hint {
        font-size: 0.7rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.15rem;
    }

    .filter-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px dashed #e2e8f0;
        margin-top: 0.25rem;
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

    .chip-reset {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.75rem;
        background: transparent;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
    }
    .chip-reset:hover {
        border-color: #ef4444;
        color: #ef4444;
        background: #fef2f2;
    }

    /* ========== ALERTES ========== */
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        padding: 0.9rem 1.1rem;
        border-radius: 12px;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    .alert-icon { flex-shrink: 0; margin-top: 0.15rem; }
    .alert-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }
    .alert-info .alert-icon { color: #2563eb; }

    /* ========== CARTE CONTENU ========== */
    .content-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        overflow: hidden;
    }

    /* ========== GRILLE HEADER ========== */
    .grille-header {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.25rem;
        background: linear-gradient(135deg, #eef2ff 0%, #ffffff 60%);
        border-bottom: 1px solid #e0e7ff;
    }
    @media (min-width: 768px) {
        .grille-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .grille-header-left { display: flex; align-items: center; gap: 0.9rem; }

    .grille-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }

    .grille-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.2rem;
        letter-spacing: -0.3px;
    }

    .grille-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.8rem;
        color: #64748b;
        margin: 0;
    }
    .grille-meta i { color: #94a3b8; margin-right: 0.2rem; }
    .text-warning { color: #d97706 !important; }
    .text-warning i { color: #d97706 !important; }

    /* ========== PROGRESSION ========== */
    .grille-progress {
        min-width: 220px;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .progress-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
    }
    .progress-label {
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
        color: #64748b;
    }
    .progress-value {
        font-weight: 700;
        color: #4f46e5;
    }

    .progress-track {
        height: 8px;
        background: #e2e8f0;
        border-radius: 9999px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        border-radius: 9999px;
        transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ========== TABLEAU SAISIE ========== */
    .table-wrapper { overflow-x: auto; }

    .saisie-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }

    .saisie-table thead th {
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .th-unit {
        font-size: 0.65rem;
        color: #94a3b8;
        text-transform: none;
        letter-spacing: 0;
        margin-left: 0.15rem;
    }

    .saisie-table tbody td {
        padding: 0.65rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .saisie-table tbody tr:hover { background: #f8fafc; }

    .saisie-table tbody tr:last-child td { border-bottom: none; }

    .saisie-table tbody tr.row-locked {
        background: #f8fafc;
        opacity: 0.75;
    }
    .saisie-table tbody tr.row-locked:hover { background: #f8fafc; }

    .col-eleve { min-width: 220px; }
    .col-note  { width: 130px; }
    .col-appreciation { min-width: 220px; }

    /* ========== CELLULE ÉLÈVE ========== */
    .eleve-cell { display: flex; align-items: center; gap: 0.6rem; }

    .eleve-avatar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.78rem;
        flex-shrink: 0;
    }

    .eleve-name {
        font-weight: 600;
        color: #0f172a;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ========== INPUTS NOTE ========== */
    .note-input {
        width: 90px;
        padding: 0.5rem 0.6rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        text-align: center;
        outline: none;
        transition: all 0.15s ease;
        -moz-appearance: textfield;
    }
    .note-input::-webkit-outer-spin-button,
    .note-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .note-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .note-input:not(:placeholder-shown):not(.error):not(:focus) {
        background: #f0fdf4;
        border-color: #86efac;
        color: #15803d;
    }

    .note-input.error {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #b91c1c;
        animation: shake 0.3s ease;
    }
    .note-input.error:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25%      { transform: translateX(-3px); }
        75%      { transform: translateX(3px); }
    }

    /* Note publiée : lecture seule */
    .note-locked {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 90px;
        padding: 0.5rem 0.6rem;
        background: #eef2ff;
        color: #4338ca;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.95rem;
    }

    /* ========== INPUT APPRÉCIATION ========== */
    .appreciation-input {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        font-size: 0.85rem;
        color: #334155;
        outline: none;
        transition: all 0.15s ease;
    }
    .appreciation-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .appreciation-input::placeholder { color: #cbd5e1; }

    .appreciation-locked {
        display: block;
        padding: 0.4rem 0;
        color: #64748b;
        font-size: 0.85rem;
        font-style: italic;
    }

    /* ========== FOOTER STICKY ========== */
    .saisie-footer {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        position: sticky;
        bottom: 0;
        z-index: 2;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.04);
    }
    @media (min-width: 768px) {
        .saisie-footer {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .footer-info {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .footer-counter {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #475569;
    }
    .footer-counter i { color: #6366f1; }
    .footer-counter strong { color: #4f46e5; font-weight: 800; }

    .footer-hint {
        font-size: 0.75rem;
        color: #d97706;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .footer-hint i { color: #f59e0b; }

    .footer-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .footer-actions { width: 100%; flex-direction: column-reverse; }
        .footer-actions .btn { width: 100%; justify-content: center; }
    }

    /* ========== ÉTAT VIDE ========== */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center;
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        gap: 0.5rem;
    }
    .empty-icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
    }
    .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }
    .empty-text {
        font-size: 0.9rem;
        color: #94a3b8;
        max-width: 420px;
        margin: 0;
        line-height: 1.5;
    }

    /* ========== RESPONSIVE MOBILE ========== */
    @media (max-width: 640px) {
        .saisie-table thead { display: none; }

        .saisie-table tbody tr {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .saisie-table tbody tr:last-child { border-bottom: none; }

        .saisie-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.4rem 0 !important;
            border: none;
        }

        .saisie-table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .saisie-table tbody td.col-eleve::before { display: none; }

        .eleve-cell {
            justify-content: flex-start;
            width: 100%;
        }

        .col-note, .col-appreciation {
            width: 100%;
            justify-content: space-between !important;
        }

        .note-input { width: 100px; }
        .note-locked { min-width: 100px; }

        .grille-progress { width: 100%; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- SCRIPTS --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. AUTO-SUBMIT FILTRES (avec debounce)
    // ============================================================
    let submitTimer = null;
    document.querySelectorAll('[data-auto-submit]').forEach(select => {
        select.addEventListener('change', () => {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(() => {
                document.getElementById('filterForm')?.submit();
            }, 120);
        });
    });

    // ============================================================
    // 2. GRILLE DE SAISIE
    // ============================================================
    const noteInputs = document.querySelectorAll('.note-input');
    const appreciationInputs = document.querySelectorAll('.appreciation-input');
    const counter     = document.getElementById('noteCounter');
    const progressFill  = document.getElementById('progressFill');
    const progressBar   = document.getElementById('progressBar');
    const progressCount = document.getElementById('progressCount');

    if (!noteInputs.length) return;

    const totalEleves = noteInputs.length;

    function updateStats() {
        let filled = 0;

        noteInputs.forEach(input => {
            const val = parseFloat(input.value);
            const max = parseFloat(input.dataset.pointMax || '20');

            // Nettoyage visuel
            input.classList.remove('error');

            if (!isNaN(val)) {
                if (val < 0 || val > max) {
                    input.classList.add('error');
                } else {
                    filled++;
                }
            }
        });

        // Compteur
        if (counter) counter.textContent = filled;

        // Progression
        const pct = totalEleves > 0 ? Math.round((filled / totalEleves) * 100) : 0;
        if (progressFill)  progressFill.style.width = pct + '%';
        if (progressCount) progressCount.textContent = filled;
        if (progressBar)   progressBar.setAttribute('aria-valuenow', pct);
    }

    // ============================================================
    // 3. VALIDATION EN TEMPS RÉEL
    // ============================================================
    noteInputs.forEach(input => {
        input.addEventListener('input', updateStats);
    });

    // ============================================================
    // 4. NAVIGATION CLAVIER : Enter → input suivant
    // ============================================================
    noteInputs.forEach((input, index) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const next = noteInputs[index + 1];
                if (next) {
                    next.focus();
                    next.select();
                } else {
                    // Dernier input → focus sur la première appréciation
                    appreciationInputs[0]?.focus();
                }
            }
        });
        // Sélectionne tout le contenu au focus (facilite la saisie rapide)
        input.addEventListener('focus', () => input.select());
    });

    appreciationInputs.forEach((input, index) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const next = appreciationInputs[index + 1];
                if (next) {
                    next.focus();
                } else {
                    // Dernier input → revient au premier input note
                    noteInputs[0]?.focus();
                }
            }
        });
    });

    // ============================================================
    // 5. VALIDATION AVANT ENVOI
    // ============================================================
    window.validerNotesAvantEnvoi = function (event) {
        let erreur = false;
        let premierInputEnErreur = null;

        noteInputs.forEach(input => {
            const val = parseFloat(input.value);
            const max = parseFloat(input.dataset.pointMax || '20');
            if (!isNaN(val) && (val < 0 || val > max)) {
                input.classList.add('error');
                erreur = true;
                if (!premierInputEnErreur) premierInputEnErreur = input;
            }
        });

        // Vérification métier : publier sans aucune note
        const statut = event.submitter?.value;
        if (statut === 'publie') {
            const nbRemplis = Array.from(noteInputs)
                .filter(i => i.value !== '' && !isNaN(parseFloat(i.value)))
                .length;

            if (nbRemplis === 0) {
                alert("Aucune note n'a été saisie. Publiez au moins une note ou enregistrez en brouillon.");
                return false;
            }
        }

        if (erreur) {
            premierInputEnErreur?.focus();
            alert('Certaines notes dépassent le maximum autorisé. Corrigez-les avant de soumettre.');
            return false;
        }

        return true;
    };

    // ============================================================
    // 6. INITIALISATION
    // ============================================================
    updateStats();

    // Focus automatique sur le premier input vide (s'il existe)
    const premierVide = Array.from(noteInputs).find(i => i.value === '');
    if (premierVide) {
        premierVide.focus();
        premierVide.select();
    }
});
</script>
@endsection