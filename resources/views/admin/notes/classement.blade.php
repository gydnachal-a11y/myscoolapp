@extends('layouts.admin')

@section('page_title', 'Classement des élèves')
@section('page_subtitle', $salle->nom . ' — ' . $periode->nom)

@section('content')
@php
    $nbEleves     = count($classement);
    $avecMoyenne  = collect($classement)->filter(fn ($c) => $c['moyenne'] !== null)->count();
    $meilleureMoy = collect($classement)->max('moyenne');
    $moyenneClasse = $avecMoyenne > 0
        ? round(collect($classement)->avg('moyenne') ?? 0, 2)
        : null;

    // Podium : top 3
    $podium = array_slice($classement, 0, 3);
    $reste  = array_slice($classement, 3);

    $routePrint = request()->routeIs('admin.*')
        ? route('admin.notes.classement.print', ['salle_id' => $salle->id, 'periode_note_id' => $periode->id])
        : (Route::has('member.notes.classement.print')
            ? route('member.notes.classement.print', ['salle_id' => $salle->id, 'periode_note_id' => $periode->id])
            : route('admin.notes.classement.print', ['salle_id' => $salle->id, 'periode_note_id' => $periode->id]));
@endphp

<div class="page">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-trophy title-icon title-icon-gold" aria-hidden="true"></i>
                Classement
            </h1>
            <p class="page-subtitle">
                <span><i class="fa-solid fa-door-open" aria-hidden="true"></i> {{ $salle->nom }}</span>
                <span class="dot-sep">•</span>
                <span><i class="fa-solid fa-calendar" aria-hidden="true"></i> {{ $periode->nom }}</span>
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ $routePrint }}" target="_blank" class="btn btn-ghost">
                <i class="fa-solid fa-print" aria-hidden="true"></i>
                <span>Imprimer</span>
            </a>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- STATS --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="stats-grid">
        <article class="stat-card stat-indigo">
            <div class="stat-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Élèves classés</p>
                <p class="stat-value">{{ $nbEleves }}</p>
            </div>
        </article>

        <article class="stat-card stat-emerald">
            <div class="stat-icon"><i class="fa-solid fa-check-circle" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Avec notes</p>
                <p class="stat-value">{{ $avecMoyenne }}</p>
            </div>
        </article>

        @if($moyenneClasse !== null)
            <article class="stat-card stat-violet">
                <div class="stat-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Moyenne classe</p>
                    <p class="stat-value">
                        {{ number_format($moyenneClasse, 2, ',', ' ') }}
                        <span class="stat-unit">/20</span>
                    </p>
                </div>
            </article>
        @endif

        @if($meilleureMoy !== null)
            <article class="stat-card stat-amber">
                <div class="stat-icon"><i class="fa-solid fa-crown" aria-hidden="true"></i></div>
                <div class="stat-content">
                    <p class="stat-label">Meilleure moyenne</p>
                    <p class="stat-value">
                        {{ number_format($meilleureMoy, 2, ',', ' ') }}
                        <span class="stat-unit">/20</span>
                    </p>
                </div>
            </article>
        @endif
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- PODIUM (Top 3) --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($nbEleves > 0 && !empty($podium))
        <section class="podium-section">
            @php
                // Ordre visuel : 2e, 1er, 3e
                $podiumOrdered = [];
                if (isset($podium[1])) $podiumOrdered[] = ['data' => $podium[1], 'place' => 2];
                if (isset($podium[0])) $podiumOrdered[] = ['data' => $podium[0], 'place' => 1];
                if (isset($podium[2])) $podiumOrdered[] = ['data' => $podium[2], 'place' => 3];
            @endphp

            <div class="podium-grid">
                @foreach($podiumOrdered as $item)
                    @php
                        $place   = $item['place'];
                        $data    = $item['data'];
                        $eleve   = $data['eleve'];
                        $moy     = $data['moyenne'];
                        $initiale = strtoupper(substr($eleve->nom_complet ?? '?', 0, 1));
                    @endphp
                    <article class="podium-card podium-{{ $place }}">
                        <div class="podium-medal">
                            @if($place === 1)
                                <i class="fa-solid fa-crown" aria-hidden="true"></i>
                            @else
                                <span class="medal-number">{{ $place }}</span>
                            @endif
                        </div>
                        <div class="podium-avatar">{{ $initiale }}</div>
                        <p class="podium-name">{{ $eleve->nom_complet }}</p>
                        <p class="podium-score">
                            {{ $moy !== null ? number_format($moy, 2, ',', ' ') : '—' }}
                            <small>/20</small>
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TABLEAU COMPLET --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="content-card">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fa-solid fa-list-ol title-icon-sm" aria-hidden="true"></i>
                Classement complet
                <span class="count-badge">{{ $nbEleves }}</span>
            </h2>
        </div>

        @if($nbEleves > 0)
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col" class="th-rank">Rang</th>
                            <th scope="col">Élève</th>
                            <th scope="col" class="text-right">Moyenne /20</th>
                            <th scope="col" class="text-right">Pourcentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classement as $item)
                            @php
                                $rang     = $item['rang'];
                                $eleve    = $item['eleve'];
                                $moyenne  = $item['moyenne'];
                                $pct      = $item['pourcentage'];
                                $initiale = strtoupper(substr($eleve->nom_complet ?? '?', 0, 1));
                            @endphp
                            <tr class="{{ $rang <= 3 ? 'row-top-' . $rang : '' }}">
                                <td data-label="Rang" class="th-rank text-center">
                                    <span class="rank-badge rank-{{ $rang <= 3 ? $rang : 'other' }}">
                                        @if($rang === 1)
                                            <i class="fa-solid fa-crown" aria-hidden="true"></i>
                                        @else
                                            {{ $rang }}
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Élève">
                                    <div class="eleve-cell">
                                        <div class="eleve-avatar">{{ $initiale }}</div>
                                        <span class="cell-primary">{{ $eleve->nom_complet }}</span>
                                    </div>
                                </td>
                                <td data-label="Moyenne" class="text-right">
                                    @if($moyenne !== null)
                                        <span class="score-cell">{{ number_format($moyenne, 2, ',', ' ') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Pourcentage" class="text-right">
                                    @if($pct !== null)
                                        <span class="pct-badge pct-{{ $pct >= 75 ? 'high' : ($pct >= 50 ? 'mid' : 'low') }}">
                                            {{ number_format($pct, 2, ',', ' ') }} %
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-regular fa-face-frown" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">Aucun élève à classer</h3>
                <p class="empty-text">
                    Aucune note publiée pour cette salle et cette période.
                </p>
            </div>
        @endif
    </section>
</div>

<style>
    /* ========== LAYOUT ========== */
    .page { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }

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
    .title-icon-gold { color: #f59e0b; }
    .title-icon-sm { color: #6366f1; font-size: 1rem; }

    .page-subtitle {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }
    .page-subtitle i { color: #94a3b8; margin-right: 0.2rem; }
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

    /* ========== STATS ========== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 1.1rem;
        background: #fff;
        border-radius: 14px;
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
    .stat-violet  .stat-icon { background: #f5f3ff; color: #7c3aed; }
    .stat-amber   .stat-icon { background: #fffbeb; color: #d97706; }

    .stat-content { min-width: 0; }
    .stat-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #94a3b8;
        margin: 0 0 0.15rem;
    }
    .stat-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
        margin: 0;
    }
    .stat-unit { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }

    /* ========== PODIUM ========== */
    .podium-section { margin-bottom: 2rem; }

    .podium-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        align-items: end;
    }

    .podium-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.25rem 1rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        position: relative;
        transition: transform 0.2s;
    }
    .podium-card:hover { transform: translateY(-3px); }

    .podium-1 {
        order: 2;
        padding-top: 2.5rem;
        padding-bottom: 1.5rem;
        border-color: #fcd34d;
        box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15);
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 60%);
    }
    .podium-2 {
        order: 1;
        border-color: #cbd5e1;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 60%);
    }
    .podium-3 {
        order: 3;
        border-color: #fdba74;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 60%);
    }

    .podium-medal {
        position: absolute;
        top: -14px;
        left: 50%;
        transform: translateX(-50%);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 800;
        color: #fff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }
    .podium-1 .podium-medal { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    .podium-2 .podium-medal { background: linear-gradient(135deg, #94a3b8, #64748b); }
    .podium-3 .podium-medal { background: linear-gradient(135deg, #fb923c, #ea580c); }

    .medal-number { font-size: 0.85rem; }

    .podium-avatar {
        width: 56px;
        height: 56px;
        margin: 0 auto 0.6rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .podium-1 .podium-avatar { background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4); }
    .podium-2 .podium-avatar { background: linear-gradient(135deg, #94a3b8, #64748b); }
    .podium-3 .podium-avatar { background: linear-gradient(135deg, #fb923c, #ea580c); }

    .podium-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 0.3rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .podium-score {
        font-size: 1.4rem;
        font-weight: 800;
        color: #4f46e5;
        margin: 0;
        letter-spacing: -0.5px;
    }
    .podium-1 .podium-score { color: #d97706; font-size: 1.6rem; }
    .podium-2 .podium-score { color: #475569; }
    .podium-3 .podium-score { color: #ea580c; }

    .podium-score small {
        font-size: 0.7rem;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ========== CONTENU ========== */
    .content-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        overflow: hidden;
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
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
        padding: 0.8rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .data-table tbody tr.row-top-1 {
        background: linear-gradient(to right, #fffbeb 0%, #ffffff 40%);
    }
    .data-table tbody tr.row-top-1:hover { background: #fffbeb; }
    .data-table tbody tr.row-top-2 { background: linear-gradient(to right, #f8fafc 0%, #ffffff 40%); }
    .data-table tbody tr.row-top-3 { background: linear-gradient(to right, #fff7ed 0%, #ffffff 40%); }

    .text-center { text-align: center !important; }
    .text-right  { text-align: right !important; }
    .text-muted  { color: #cbd5e1; }

    .th-rank { width: 80px; }

    /* ========== RANG ========== */
    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-weight: 800;
        font-size: 0.85rem;
    }
    .rank-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }
    .rank-2 { background: linear-gradient(135deg, #cbd5e1, #94a3b8); color: #fff; box-shadow: 0 4px 10px rgba(148, 163, 184, 0.3); }
    .rank-3 { background: linear-gradient(135deg, #fdba74, #f97316); color: #fff; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3); }
    .rank-other { background: #f1f5f9; color: #64748b; font-size: 0.75rem; }

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

    .cell-primary { font-weight: 600; color: #0f172a; }

    /* ========== SCORE ========== */
    .score-cell {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.95rem;
    }

    .pct-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.6rem;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 700;
    }
    .pct-high { background: #ecfdf5; color: #047857; }
    .pct-mid  { background: #fffbeb; color: #b45309; }
    .pct-low  { background: #fff1f2; color: #be123c; }

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
        .podium-grid { grid-template-columns: 1fr; }
        .podium-card { order: initial !important; padding-top: 2rem; }
        .podium-1 { order: -1 !important; }

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
        .data-table tbody tr.row-top-1,
        .data-table tbody tr.row-top-2,
        .data-table tbody tr.row-top-3 {
            background: linear-gradient(135deg, #eef2ff, #fff);
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
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>
@endsection