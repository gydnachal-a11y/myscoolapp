@extends('layouts.admin')

@section('page_title', 'Sélection du classement')
@section('page_subtitle', 'Choisissez une session, une section, une salle et une période')

@section('content')
@php
    $routeClassement = request()->routeIs('admin.*')
        ? route('admin.notes.classement')
        : (Route::has('member.notes.classement') ? route('member.notes.classement') : route('admin.notes.classement'));

    $activeFilters = array_filter([
        'Session' => $sessionId ? $sessions->firstWhere('id', $sessionId)?->nom : null,
        'Section' => $sectionId ? $sections->firstWhere('id', $sectionId)?->nom : null,
        'Option'  => $optionId  ? $options->firstWhere('id', $optionId)?->nom   : null,
        'Salle'   => $salleId   ? $salles->firstWhere('id', $salleId)?->nom     : null,
        'Période' => $periodeId ? $periodes->firstWhere('id', $periodeId)?->nom : null,
    ]);
@endphp

<div class="page page-narrow">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-trophy title-icon title-icon-gold" aria-hidden="true"></i>
                Classement des élèves
            </h1>
            <p class="page-subtitle">
                Sélectionnez une salle et une période pour afficher le classement
            </p>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- CARTE DE SÉLECTION --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="select-card">
        <div class="select-header">
            <i class="fa-solid fa-sliders select-icon" aria-hidden="true"></i>
            <h2 class="select-title">Paramètres du classement</h2>
            @if(!empty($activeFilters))
                <span class="select-count">{{ count($activeFilters) }}/5</span>
            @endif
        </div>

        <form method="GET" action="{{ $routeClassement }}" class="select-form" id="classementForm">
            <div class="select-grid">
                {{-- Session --}}
                <div class="filter-field">
                    <label for="f-session" class="filter-label">
                        <span class="step-num">1</span> Session
                    </label>
                    <select name="session_id" id="f-session" class="filter-select" data-auto-submit>
                        <option value="">Toutes les sessions</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}" @selected($sessionId == $session->id)>
                                {{ $session->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Section --}}
                <div class="filter-field">
                    <label for="f-section" class="filter-label">
                        <span class="step-num">2</span> Section
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
                        <span class="step-num">3</span> Option
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
                        <span class="step-num">4</span> Salle <span class="required">*</span>
                    </label>
                    <select name="salle_id" id="f-salle" class="filter-select" required>
                        <option value="">— Choisir une salle —</option>
                        @foreach($salles as $s)
                            <option value="{{ $s->id }}" @selected($salleId == $s->id)>{{ $s->nom }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Période --}}
                <div class="filter-field">
                    <label for="f-periode" class="filter-label">
                        <span class="step-num">5</span> Période <span class="required">*</span>
                    </label>
                    <select name="periode_note_id" id="f-periode" class="filter-select" required>
                        <option value="">— Choisir une période —</option>
                        @foreach($periodes as $p)
                            <option value="{{ $p->id }}" @selected($periodeId == $p->id)>{{ $p->nom }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Récap filtres --}}
            @if(!empty($activeFilters))
                <div class="filter-chips">
                    <span class="chips-label">Sélection :</span>
                    @foreach($activeFilters as $key => $value)
                        <span class="chip">
                            <strong>{{ $key }}</strong> · {{ $value }}
                        </span>
                    @endforeach
                    <a href="{{ $routeClassement }}" class="chip-reset">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i> Effacer
                    </a>
                </div>
            @endif

            <div class="select-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                    Afficher le classement
                </button>
            </div>
        </form>
    </section>

    {{-- Illustration vide --}}
    <div class="empty-state">
        <div class="empty-icon-wrapper empty-icon-gold">
            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
        </div>
        <h3 class="empty-title">Prêt à découvrir le classement ?</h3>
        <p class="empty-text">
            Sélectionnez une salle et une période, puis cliquez sur « Afficher le classement ».
        </p>
    </div>
</div>

<style>
    .page { max-width: 800px; margin: 0 auto; padding: 2rem 1rem; }

    /* HEADER */
    .page-header { margin-bottom: 2rem; }
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
    .title-icon-gold { color: #f59e0b; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }

    /* CARTE SÉLECTION */
    .select-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .select-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .select-icon { color: #6366f1; }
    .select-title {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        flex: 1;
    }
    .select-count {
        background: #eef2ff;
        color: #4f46e5;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
    }

    .select-form { display: flex; flex-direction: column; gap: 1.25rem; }

    .select-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px) { .select-grid { grid-template-columns: repeat(2, 1fr); } }

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

    .required { color: #ef4444; }

    .filter-select {
        width: 100%;
        padding: 0.7rem 2.5rem 0.7rem 1rem;
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

    /* CHIPS */
    .filter-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px dashed #e2e8f0;
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

    .select-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 0.5rem;
        border-top: 1px solid #f1f5f9;
    }

    /* BOUTONS */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1.5rem;
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

    /* ÉTAT VIDE */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1.5rem;
        text-align: center;
        background: #fff;
        border: 1px dashed #e2e8f0;
        border-radius: 16px;
        gap: 0.5rem;
    }
    .empty-icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #fffbeb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }
    .empty-icon-gold { color: #f59e0b; }
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
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let timer = null;
    document.querySelectorAll('[data-auto-submit]').forEach(select => {
        select.addEventListener('change', () => {
            clearTimeout(timer);
            // On NE soumet PAS automatiquement : l'utilisateur doit cliquer sur le bouton
            // (car il faut les 2 champs requis : salle ET période)
        });
    });
});
</script>
@endsection