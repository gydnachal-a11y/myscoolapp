@extends('layouts.admin')

@section('page_title', 'Bulletin de notes')
@section('page_subtitle', $eleve->nom_complet . ' — ' . $periode->nom)

@section('content')
@php
    // ============================================================
    // PRÉPARATION
    // ============================================================
    $notesCollection = collect($notes);
    $nbNotes         = $notesCollection->count();
    $nbNotesRemplies = $notesCollection->whereNotNull('note')->count();

    $coeffTotal = $notesCollection->sum(function ($n) {
        return $n->courSalle?->ponderation?->valeur ?? 1;
    });

    $mention = null;
    if ($moyenne !== null) {
        $mention = match (true) {
            $moyenne >= 16 => 'Très bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez bien',
            $moyenne >= 10 => 'Passable',
            default        => 'Insuffisant',
        };
    }

    $mentionKey = $mention ? match ($mention) {
        'Très bien'   => 'excellent',
        'Bien'        => 'good',
        'Assez bien'  => 'ok',
        'Passable'    => 'low',
        default       => 'fail',
    } : 'none';

    // Inscription / salle de l'élève
    $inscription  = $eleve->inscriptions->first();
    $salle        = $inscription?->salleDeClasse;

    // Routes dynamiques
    $routeIndex  = request()->routeIs('admin.*')
        ? route('admin.notes.index')
        : (Route::has('member.notes.index') ? route('member.notes.index') : route('admin.notes.index'));
    $routePrint  = route('admin.notes.bulletin.print', ['eleve_id' => $eleve->id, 'periode_note_id' => $periode->id]);
    $routePdf    = Route::has('admin.notes.bulletin.pdf')
        ? route('admin.notes.bulletin.pdf', ['eleve_id' => $eleve->id, 'periode_note_id' => $periode->id])
        : null;
@endphp

<div class="page">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- BREADCRUMB / RETOUR --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <nav class="breadcrumb">
        <a href="{{ $routeIndex }}" class="breadcrumb-link">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Consultation des notes
        </a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current">Bulletin</span>
    </nav>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HEADER ÉLÈVE + ACTIONS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <header class="bulletin-header">
        <div class="student-block">
            <div class="student-avatar">
                {{ strtoupper(substr($eleve->nom_complet ?? '?', 0, 1)) }}
            </div>
            <div>
                <h1 class="student-name">{{ $eleve->nom_complet }}</h1>
                <p class="student-meta">
                    @if($salle)
                        <span><i class="fa-solid fa-door-open" aria-hidden="true"></i> {{ $salle->nom }}</span>
                    @endif
                    <span class="dot-sep">•</span>
                    <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $periode->nom }}</span>
                    @if($periode->anneeScolaire)
                        <span class="dot-sep">•</span>
                        <span><i class="fa-solid fa-book" aria-hidden="true"></i> {{ $periode->anneeScolaire->libelle ?? $periode->anneeScolaire->nom }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="header-actions">
            <a href="{{ $routePrint }}" target="_blank" class="btn btn-ghost">
                <i class="fa-solid fa-print" aria-hidden="true"></i>
                <span>Imprimer</span>
            </a>
            @if($routePdf)
                <a href="{{ $routePdf }}" class="btn btn-primary">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    <span>Exporter en PDF</span>
                </a>
            @endif
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- RÉSULTATS PRINCIPAUX --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="results-grid">
        {{-- Moyenne --}}
        <article class="result-card result-primary">
            <div class="result-header">
                <div class="result-icon"><i class="fa-solid fa-calculator" aria-hidden="true"></i></div>
                <span class="result-label">Moyenne pondérée</span>
            </div>
            <div class="result-value">
                @if($moyenne !== null)
                    {{ number_format($moyenne, 2, ',', ' ') }}
                    <small>/20</small>
                @else
                    <span class="result-empty">—</span>
                @endif
            </div>
        </article>

        {{-- Pourcentage --}}
        <article class="result-card result-emerald">
            <div class="result-header">
                <div class="result-icon"><i class="fa-solid fa-percent" aria-hidden="true"></i></div>
                <span class="result-label">Pourcentage</span>
            </div>
            <div class="result-value">
                @if($pourcentage !== null)
                    {{ number_format($pourcentage, 2, ',', ' ') }}
                    <small>%</small>
                @else
                    <span class="result-empty">—</span>
                @endif
            </div>
        </article>

        {{-- Mention --}}
        @if($mention)
            <article class="result-card result-mention result-mention-{{ $mentionKey }}">
                <div class="result-header">
                    <div class="result-icon"><i class="fa-solid fa-medal" aria-hidden="true"></i></div>
                    <span class="result-label">Mention</span>
                </div>
                <div class="result-value result-mention-value">
                    {{ $mention }}
                </div>
            </article>
        @endif

        {{-- Nombre de notes --}}
        <article class="result-card result-neutral">
            <div class="result-header">
                <div class="result-icon"><i class="fa-solid fa-list-ol" aria-hidden="true"></i></div>
                <span class="result-label">Notes</span>
            </div>
            <div class="result-value">
                {{ $nbNotesRemplies }}
                <small>/ {{ $nbNotes }}</small>
            </div>
        </article>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TABLEAU DES NOTES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="content-card">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fa-solid fa-list-check title-icon-sm" aria-hidden="true"></i>
                Détail des notes
                <span class="count-badge">{{ $nbNotes }}</span>
            </h2>
            <div class="content-meta">
                <span class="meta-item">
                    <i class="fa-solid fa-weight-hanging" aria-hidden="true"></i>
                    Coeff. total : <strong>{{ $coeffTotal }}</strong>
                </span>
            </div>
        </div>

        @if($nbNotes > 0)
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Cours</th>
                            <th scope="col" class="text-center">Pondération</th>
                            <th scope="col" class="text-center">Note /20</th>
                            <th scope="col" class="text-center">Points pondérés</th>
                            <th scope="col">Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notes as $note)
                            @php
                                $coeff       = $note->courSalle?->ponderation?->valeur ?? 1;
                                $noteValue   = $note->note;
                                $points      = $noteValue !== null ? round($noteValue * $coeff, 2) : null;
                                $noteLabel   = match (true) {
                                    $noteValue === null => 'empty',
                                    $noteValue >= 16    => 'high',
                                    $noteValue >= 12    => 'good',
                                    $noteValue >= 10    => 'ok',
                                    $noteValue >= 6     => 'low',
                                    default             => 'fail',
                                };
                            @endphp
                            <tr>
                                <td data-label="Cours">
                                    <div class="cours-cell">
                                        <span class="cell-primary">
                                            {{ $note->courSalle?->cour?->nom ?? 'Cours supprimé' }}
                                        </span>
                                        @if($note->courSalle?->titulaire)
                                            <span class="cours-prof">
                                                <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                                                {{ $note->courSalle->titulaire->name }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Pondération" class="text-center">
                                    <span class="pill pill-neutral">
                                        ×{{ $coeff }}
                                    </span>
                                </td>
                                <td data-label="Note" class="text-center">
                                    <span class="note-badge note-{{ $noteLabel }}">
                                        {{ $noteValue !== null ? number_format($noteValue, 2, ',', ' ') : '—' }}
                                    </span>
                                </td>
                                <td data-label="Points pondérés" class="text-center">
                                    @if($points !== null)
                                        <span class="points-cell">{{ number_format($points, 2, ',', ' ') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Appréciation">
                                    <span class="appreciation-cell">
                                        {{ $note->appreciation ?: '—' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="foot-row">
                            <td colspan="2" class="foot-label">
                                <i class="fa-solid fa-sigma" aria-hidden="true"></i>
                                Total
                            </td>
                            <td class="text-center">
                                @php
                                    $sommeNotes = $notesCollection->sum('note');
                                @endphp
                                <strong>{{ number_format($sommeNotes, 2, ',', ' ') }}</strong>
                            </td>
                            <td class="text-center">
                                @php
                                    $sommePonderee = $notesCollection
                                        ->filter(fn ($n) => $n->note !== null)
                                        ->sum(fn ($n) => $n->note * ($n->courSalle?->ponderation?->valeur ?? 1));
                                @endphp
                                <strong>{{ number_format($sommePonderee, 2, ',', ' ') }}</strong>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Récap final --}}
            @if($moyenne !== null)
                <div class="recap">
                    <div class="recap-formula">
                        <span class="formula-label">Calcul :</span>
                        <span class="formula">
                            Σ(points pondérés) / Σ(coefficients)
                            =
                            {{ number_format($sommePonderee, 2, ',', ' ') }}
                            /
                            {{ $coeffTotal }}
                            =
                            <strong>{{ number_format($moyenne, 2, ',', ' ') }}</strong>
                        </span>
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">Aucune note disponible</h3>
                <p class="empty-text">
                    Aucune note n'a encore été publiée pour cet élève sur cette période.
                </p>
            </div>
        @endif
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- INFOS SUPPLÉMENTAIRES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($nbNotes > 0)
        <section class="extra-info">
            <div class="extra-card">
                <div class="extra-icon extra-indigo">
                    <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="extra-label">Titulaire principal</p>
                    <p class="extra-value">
                        {{ $notesCollection->first()?->courSalle?->titulaire?->name ?? 'Non défini' }}
                    </p>
                </div>
            </div>

            <div class="extra-card">
                <div class="extra-icon extra-emerald">
                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="extra-label">Période d'évaluation</p>
                    <p class="extra-value">
                        {{ $periode->date_debut?->format('d/m/Y') ?? '—' }}
                        →
                        {{ $periode->date_fin?->format('d/m/Y') ?? '—' }}
                    </p>
                </div>
            </div>

            <div class="extra-card">
                <div class="extra-icon extra-amber">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="extra-label">Généré le</p>
                    <p class="extra-value">{{ now()->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
        </section>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- STYLES --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ========== LAYOUT ========== */
    .page { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }

    /* ========== BREADCRUMB ========== */
    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        font-size: 0.85rem;
    }
    .breadcrumb-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: #6366f1;
        font-weight: 600;
        text-decoration: none;
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
        transition: background 0.15s;
    }
    .breadcrumb-link:hover {
        background: #eef2ff;
        color: #4338ca;
    }
    .breadcrumb-sep { color: #cbd5e1; }
    .breadcrumb-current { color: #64748b; font-weight: 500; }

    /* ========== HEADER ÉLÈVE ========== */
    .bulletin-header {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #eef2ff 0%, #ffffff 100%);
        border-radius: 16px;
        border: 1px solid #e0e7ff;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 768px) {
        .bulletin-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .student-block { display: flex; align-items: center; gap: 1rem; }

    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        font-weight: 800;
        flex-shrink: 0;
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3);
    }

    .student-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.3rem;
        letter-spacing: -0.5px;
    }

    .student-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #64748b;
        margin: 0;
    }
    .student-meta i { color: #94a3b8; margin-right: 0.2rem; }
    .dot-sep { color: #cbd5e1; }

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
        background: #fff;
        color: #64748b;
        border: 1.5px solid #e2e8f0;
    }
    .btn-ghost:hover {
        border-color: #6366f1;
        color: #4f46e5;
        background: #f8fafc;
    }

    /* ========== RÉSULTATS ========== */
    .results-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 768px)  { .results-grid { grid-template-columns: repeat(4, 1fr); } }

    .result-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .result-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }

    .result-primary {
        background: linear-gradient(135deg, #eef2ff 0%, #ffffff 100%);
        border-color: #c7d2fe;
    }
    .result-emerald {
        background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
        border-color: #a7f3d0;
    }
    .result-neutral {
        background: #f8fafc;
    }

    .result-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .result-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        background: #eef2ff;
        color: #4f46e5;
    }
    .result-emerald .result-icon { background: #d1fae5; color: #059669; }
    .result-neutral .result-icon { background: #f1f5f9; color: #64748b; }

    .result-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: #64748b;
    }

    .result-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
        letter-spacing: -1px;
    }
    .result-value small {
        font-size: 0.85rem;
        font-weight: 500;
        color: #94a3b8;
        letter-spacing: 0;
    }
    .result-primary .result-value { color: #4f46e5; }
    .result-emerald .result-value { color: #059669; }

    .result-empty { color: #cbd5e1; font-size: 1.5rem; }

    /* Mentions */
    .result-mention-excellent { background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%); border-color: #6ee7b7; }
    .result-mention-good      { background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border-color: #93c5fd; }
    .result-mention-ok        { background: linear-gradient(135deg, #fefce8 0%, #ffffff 100%); border-color: #fde047; }
    .result-mention-low       { background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); border-color: #fdba74; }
    .result-mention-fail      { background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); border-color: #fca5a5; }

    .result-mention-excellent .result-icon { background: #d1fae5; color: #047857; }
    .result-mention-good      .result-icon { background: #dbeafe; color: #2563eb; }
    .result-mention-ok        .result-icon { background: #fef9c3; color: #ca8a04; }
    .result-mention-low       .result-icon { background: #ffedd5; color: #ea580c; }
    .result-mention-fail      .result-icon { background: #fee2e2; color: #dc2626; }

    .result-mention-value {
        font-size: 1.4rem;
        letter-spacing: -0.5px;
    }
    .result-mention-excellent .result-mention-value { color: #047857; }
    .result-mention-good      .result-mention-value { color: #2563eb; }
    .result-mention-ok        .result-mention-value { color: #ca8a04; }
    .result-mention-low       .result-mention-value { color: #ea580c; }
    .result-mention-fail      .result-mention-value { color: #dc2626; }

    /* ========== CONTENU ========== */
    .content-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 0.75rem;
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
    .title-icon-sm { color: #6366f1; font-size: 1rem; }

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

    .content-meta { display: flex; gap: 1rem; font-size: 0.85rem; color: #64748b; }
    .meta-item { display: inline-flex; align-items: center; gap: 0.35rem; }
    .meta-item i { color: #94a3b8; }
    .meta-item strong { color: #0f172a; }

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
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: 1px solid #e2e8f0; }

    .text-center { text-align: center !important; }
    .cell-primary { font-weight: 600; color: #0f172a; }

    /* Cellule Cours */
    .cours-cell { display: flex; flex-direction: column; gap: 0.15rem; }
    .cours-prof {
        font-size: 0.72rem;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .cours-prof i { font-size: 0.65rem; }

    /* Pill */
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    /* Note badges */
    .note-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 56px;
        padding: 0.3rem 0.7rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .note-high  { background: #ecfdf5; color: #047857; }
    .note-good  { background: #eff6ff; color: #2563eb; }
    .note-ok    { background: #fefce8; color: #ca8a04; }
    .note-low   { background: #fff7ed; color: #ea580c; }
    .note-fail  { background: #fef2f2; color: #dc2626; }
    .note-empty { background: #f8fafc; color: #cbd5e1; }

    /* Points */
    .points-cell { font-weight: 600; color: #334155; }
    .text-muted { color: #cbd5e1; }

    /* Appréciation */
    .appreciation-cell {
        color: #64748b;
        font-size: 0.85rem;
        font-style: italic;
    }

    /* ========== FOOTER TABLEAU ========== */
    .data-table tfoot .foot-row {
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
    }
    .data-table tfoot td {
        padding: 0.85rem 1.25rem;
        font-size: 0.9rem;
        color: #334155;
        border-bottom: none;
    }
    .foot-label {
        text-transform: uppercase;
        font-size: 0.75rem !important;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: #64748b !important;
    }
    .foot-label i { margin-right: 0.25rem; }

    /* ========== RÉCAP CALCUL ========== */
    .recap {
        padding: 1rem 1.25rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
    }
    .recap-formula {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.85rem;
    }
    .formula-label {
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-size: 0.72rem;
    }
    .formula {
        font-family: 'SF Mono', 'Consolas', monospace;
        background: #fff;
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .formula strong { color: #4f46e5; }

    /* ========== INFOS EXTRA ========== */
    .extra-info {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-top: 1rem;
    }
    @media (min-width: 640px)  { .extra-info { grid-template-columns: repeat(3, 1fr); } }

    .extra-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: #fff;
        border-radius: 14px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .extra-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .extra-indigo  { background: #eef2ff; color: #4f46e5; }
    .extra-emerald { background: #ecfdf5; color: #059669; }
    .extra-amber   { background: #fffbeb; color: #d97706; }

    .extra-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
        color: #94a3b8;
        margin: 0 0 0.15rem;
    }
    .extra-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
    }

    /* ========== ÉTAT VIDE ========== */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center;
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
        .cours-cell { align-items: flex-end; text-align: right; }

        .data-table tfoot .foot-row td { display: flex; justify-content: space-between; }
        .data-table tfoot .foot-row td:first-child { display: block; text-align: center; }

        .header-actions { width: 100%; flex-direction: column-reverse; }
        .header-actions .btn { width: 100%; justify-content: center; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>
@endsection