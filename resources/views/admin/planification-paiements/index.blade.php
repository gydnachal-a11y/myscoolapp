@extends('layouts.admin')

@section('page_title', 'Planification des paiements')
@section('page_subtitle', 'Planifiez les sessions de paiement par salle')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endpush

@section('content')
<div class="planif-wrap">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ============================================================
         MESSAGES FLASH
         ============================================================ --}}
    @if(session('success'))
        <div class="flash flash-success" role="status" data-reveal="auto">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error" role="alert" data-reveal="auto">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================
         EN-TÊTE
         ============================================================ --}}
    <div class="planif-header" data-reveal="auto">
        <div class="header-left">
            <div class="title-badge">
                <i class="fa-solid fa-calendar-plus"></i>
                <span>Module planification</span>
            </div>
            <h1 class="planif-title">
                Planification des <strong>paiements</strong>
            </h1>
            <p class="planif-subtitle">
                Planifiez et gérez les sessions de paiement pour chaque salle de classe
            </p>
        </div>
        <div class="header-right">
            <form action="{{ route('admin.planification-paiements.planifier-toutes') }}" method="POST"
                  onsubmit="return confirm('Planifier automatiquement pour toutes les salles ? Cette action ignorera les salles déjà planifiées.')">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Planifier toutes les salles</span>
                </button>
            </form>
        </div>
    </div>

    {{-- ============================================================
         STATISTIQUES
         ============================================================ --}}
    <div class="stats-grid">
        <div class="stat-card stat-indigo" data-reveal="auto" data-delay="1">
            <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
            <div class="stat-body">
                <div class="stat-label">Salles de classe</div>
                <div class="stat-value">{{ $salles->count() }}</div>
            </div>
            <div class="stat-glow"></div>
        </div>

        <div class="stat-card stat-green" data-reveal="auto" data-delay="2">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-body">
                <div class="stat-label">Sessions planifiées</div>
                <div class="stat-value">{{ $sessions->count() }}</div>
            </div>
            <div class="stat-glow"></div>
        </div>

        <div class="stat-card stat-amber" data-reveal="auto" data-delay="3">
            <div class="stat-icon"><i class="fa-solid fa-calendar-days"></i></div>
            <div class="stat-body">
                <div class="stat-label">Année active</div>
                <div class="stat-value stat-value-sm">{{ $anneeActive->libelle ?? '—' }}</div>
            </div>
            <div class="stat-glow"></div>
        </div>
    </div>

    {{-- ============================================================
         SALLES DE CLASSE
         ============================================================ --}}
    <div class="section-card" data-reveal="auto" data-delay="1">
        <div class="section-header">
            <div class="section-title">
                <div class="section-icon icon-indigo">
                    <i class="fa-solid fa-door-open"></i>
                </div>
                <div>
                    <h3>Salles de classe</h3>
                    <p>Cliquez sur une salle pour gérer sa planification</p>
                </div>
            </div>
            <span class="section-count">{{ $salles->count() }} salle(s)</span>
        </div>

        <div class="rooms-grid">
            @forelse($salles as $salle)
                @php
                    $modeClass = $salle->mode_paiement === 'mensuel' ? 'mode-monthly' : 'mode-tranche';
                    $modeIcon  = $salle->mode_paiement === 'mensuel' ? 'fa-calendar-days' : 'fa-layer-group';
                    $nbSessions = $sessions->where('salle_classe_id', $salle->id)->count();
                @endphp
                <a href="{{ route('admin.planification-paiements.show', $salle) }}" class="room-card">
                    <div class="room-header">
                        <div class="room-avatar">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <span class="room-mode {{ $modeClass }}">
                            <i class="fa-solid {{ $modeIcon }}"></i>
                            {{ ucfirst($salle->mode_paiement) }}
                        </span>
                    </div>

                    <div class="room-body">
                        <h4 class="room-name">{{ $salle->nom }}</h4>
                        <div class="room-meta">
                            <span class="room-sessions">
                                <i class="fa-solid fa-calendar-check"></i>
                                {{ $nbSessions }} session(s)
                            </span>
                        </div>
                    </div>

                    <div class="room-footer">
                        <span class="room-action">
                            Gérer la planification
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>
                    </div>
                </a>
            @empty
                <div class="empty-rooms">
                    <div class="empty-icon">
                        <i class="fa-solid fa-door-closed"></i>
                    </div>
                    <p class="empty-title">Aucune salle disponible</p>
                    <p class="empty-text">Créez d'abord des salles de classe pour planifier les paiements.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ============================================================
         SESSIONS PLANIFIÉES
         ============================================================ --}}
    <div class="section-card" data-reveal="auto" data-delay="2">
        <div class="section-header">
            <div class="section-title">
                <div class="section-icon icon-green">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h3>Sessions planifiées</h3>
                    <p>Liste de toutes les sessions de paiement planifiées</p>
                </div>
            </div>
            <span class="section-count">{{ $sessions->count() }} session(s)</span>
        </div>

        @if($sessions->isNotEmpty())
            <div class="table-wrapper">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Salle</th>
                            <th>Période</th>
                            <th>Début</th>
                            <th>Fin</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $session)
                            <tr>
                                <td data-label="Salle">
                                    <div class="table-room">
                                        <div class="table-room-icon">
                                            <i class="fa-solid fa-chalkboard-user"></i>
                                        </div>
                                        <span>{{ $session->salleClasse->nom ?? '—' }}</span>
                                    </div>
                                </td>
                                <td data-label="Période">
                                    <span class="period-chip">
                                        <i class="fa-solid fa-calendar"></i>
                                        {{ $session->periode }}
                                    </span>
                                </td>
                                <td data-label="Début">
                                    <div class="date-cell">
                                        <i class="fa-regular fa-calendar"></i>
                                        {{ $session->date_debut_session->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td data-label="Fin">
                                    <div class="date-cell">
                                        <i class="fa-regular fa-calendar-check"></i>
                                        {{ $session->date_fin_session->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td data-label="Actions" class="text-right action-cell">
                                    <div class="action-icons">
                                        <a href="{{ route('admin.planification-paiements.show', $session->salleClasse) }}"
                                           class="action-btn action-view"
                                           title="Voir la salle"
                                           aria-label="Voir la planification de {{ $session->salleClasse->nom ?? 'la salle' }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.sessions-paiement.destroy', $session) }}"
                                              method="POST"
                                              class="inline-form"
                                              onsubmit="return confirm('Supprimer cette session ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="action-btn action-delete"
                                                    title="Supprimer"
                                                    aria-label="Supprimer la session">
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
        @else
            <div class="empty-sessions">
                <div class="empty-icon">
                    <i class="fa-solid fa-calendar-xmark"></i>
                </div>
                <p class="empty-title">Aucune session planifiée</p>
                <p class="empty-text">
                    Utilisez le bouton <strong>« Planifier toutes les salles »</strong> ou cliquez sur une salle pour créer sa première session.
                </p>
            </div>
        @endif
    </div>
</div>

<style>
    /* ============================================================
       VARIABLES
       ============================================================ */
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --primary-soft: #eef2ff;
        --success: #10b981;
        --success-soft: #d1fae5;
        --warning: #f59e0b;
        --warning-soft: #fef3c7;
        --danger: #ef4444;
        --danger-soft: #fee2e2;

        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-600: #475569;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --gray-900: #0f172a;

        --radius: 18px;
        --radius-md: 12px;
        --radius-sm: 10px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.04), 0 2px 4px rgba(0,0,0,0.03);
        --shadow-lg: 0 10px 30px rgba(0,0,0,0.06);
        --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        --ease-out-expo: cubic-bezier(.16, 1, .3, 1);
        --ease-soft: cubic-bezier(.4, 0, .2, 1);
    }

    * { box-sizing: border-box; }

    .planif-wrap {
        max-width: 1280px;
        margin: 0 auto;
        padding: clamp(1rem, 2.5vw, 2rem) clamp(.75rem, 2vw, 1.25rem);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--gray-800);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .planif-wrap h1,
    .planif-wrap h2,
    .planif-wrap h3,
    .planif-wrap h4 {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        letter-spacing: -0.02em;
    }

    /* ============================================================
       FLASH
       ============================================================ */
    .flash {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        font-weight: 500;
    }
    .flash-success {
        background: var(--success-soft);
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .flash-error {
        background: var(--danger-soft);
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .flash i { font-size: 1.1rem; }

    /* ============================================================
       EN-TÊTE
       ============================================================ */
    .planif-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .planif-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-end;
        }
    }

    .header-left { flex: 1; min-width: 0; }

    .title-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.3rem 0.75rem;
        background: var(--primary-soft);
        color: var(--primary);
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.75rem;
    }

    .planif-title {
        font-size: clamp(1.4rem, 3vw, 1.85rem);
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
        line-height: 1.2;
    }
    .planif-title strong {
        font-weight: 800;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .planif-subtitle {
        color: var(--gray-500);
        font-size: clamp(.85rem, 1.4vw, .95rem);
        margin-top: 0.4rem;
        line-height: 1.5;
    }

    .header-right .btn { width: 100%; }
    @media (min-width: 768px) {
        .header-right .btn { width: auto; }
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.8rem 1.4rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        white-space: nowrap;
        text-align: center;
    }
    .btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
    }

    /* ============================================================
       STATISTIQUES
       ============================================================ */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: clamp(.75rem, 1.5vw, 1rem);
        margin-bottom: 2rem;
    }
    @media (min-width: 640px) {
        .stats-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    }

    .stat-card {
        position: relative;
        background: #fff;
        border-radius: var(--radius);
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-100);
        transition: transform 400ms var(--ease-out-expo), box-shadow 400ms var(--ease-out-expo);
        min-width: 0;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        transition: transform 400ms var(--ease-out-expo);
    }
    .stat-card:hover .stat-icon { transform: scale(1.08) rotate(-5deg); }

    .stat-indigo .stat-icon { background: var(--primary-soft); color: var(--primary); }
    .stat-green  .stat-icon { background: var(--success-soft); color: var(--success); }
    .stat-amber  .stat-icon { background: var(--warning-soft); color: var(--warning); }

    .stat-body { flex: 1; min-width: 0; position: relative; z-index: 1; }
    .stat-label {
        font-size: 0.72rem;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        margin: 0;
    }
    .stat-value {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--gray-900);
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-top: 0.15rem;
    }
    .stat-value-sm { font-size: 1.15rem; font-weight: 700; }

    .stat-glow {
        position: absolute;
        top: -30%;
        right: -20%;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        opacity: 0.35;
        filter: blur(30px);
        pointer-events: none;
    }
    .stat-indigo .stat-glow { background: #6366f1; }
    .stat-green  .stat-glow { background: #22c55e; }
    .stat-amber  .stat-glow { background: #f59e0b; }

    /* ============================================================
       SECTION CARD
       ============================================================ */
    .section-card {
        background: #fff;
        border-radius: var(--radius);
        border: 1px solid var(--gray-100);
        box-shadow: var(--shadow-sm);
        padding: clamp(1rem, 2vw, 1.5rem);
        margin-bottom: 1.5rem;
        width: 100%;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--gray-100);
        flex-wrap: wrap;
    }
    .section-title {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }
    .section-icon {
        width: 42px;
        height: 42px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .icon-indigo { background: var(--primary-soft); color: var(--primary); }
    .icon-green  { background: var(--success-soft); color: var(--success); }

    .section-title h3 {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
    }
    .section-title p {
        font-size: 0.8rem;
        color: var(--gray-500);
        margin: 0.15rem 0 0;
    }

    .section-count {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.75rem;
        background: var(--gray-100);
        color: var(--gray-700);
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* ============================================================
       CARTES DES SALLES
       ============================================================ */
    .rooms-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px) {
        .rooms-grid { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
    }

    .room-card {
        display: flex;
        flex-direction: column;
        padding: 1.25rem;
        background: var(--gray-50);
        border: 1.5px solid transparent;
        border-radius: var(--radius-md);
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
    }
    .room-card:hover {
        background: #fff;
        border-color: var(--primary);
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(99, 102, 241, 0.12);
    }

    .room-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    .room-avatar {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        transition: transform 400ms var(--ease-out-expo);
    }
    .room-card:hover .room-avatar { transform: scale(1.06) rotate(-5deg); }

    .room-mode {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .mode-monthly {
        background: var(--primary-soft);
        color: var(--primary);
    }
    .mode-tranche {
        background: var(--warning-soft);
        color: #92400e;
    }

    .room-body { flex: 1; }
    .room-name {
        font-size: 1rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 0.5rem;
    }
    .room-meta {
        display: flex;
        gap: 0.75rem;
        font-size: 0.8rem;
        color: var(--gray-500);
    }
    .room-sessions {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .room-sessions i { color: var(--success); }

    .room-footer {
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px dashed var(--gray-200);
    }
    .room-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--primary);
        transition: var(--transition);
    }
    .room-card:hover .room-action {
        gap: 0.6rem;
    }

    /* ============================================================
       TABLEAU PREMIUM
       ============================================================ */
    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: var(--radius-md);
        border: 1px solid var(--gray-100);
    }

    .premium-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: var(--gray-700);
    }
    .premium-table thead th {
        text-align: left;
        padding: 0.85rem 1.25rem;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--gray-500);
        background: var(--gray-50);
        border-bottom: 1.5px solid var(--gray-200);
        white-space: nowrap;
    }
    .premium-table thead th.text-right { text-align: right; }

    .premium-table tbody td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }
    .premium-table tbody tr { transition: background 250ms ease; }
    .premium-table tbody tr:hover { background: var(--gray-50); }
    .premium-table tbody tr:last-child td { border-bottom: none; }
    .premium-table .text-right { text-align: right; }

    /* ✅ FIX DÉFINITIF — Restaure <table> en desktop */
    @media (min-width: 768px) {
        .planif-wrap .premium-table {
            display: table !important;
            width: 100% !important;
            max-width: 100%;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        .planif-wrap .premium-table thead { display: table-header-group !important; }
        .planif-wrap .premium-table tbody { display: table-row-group !important; }
        .planif-wrap .premium-table tr    { display: table-row !important; }
        .planif-wrap .premium-table th,
        .planif-wrap .premium-table td    { display: table-cell !important; }

        /* Distribution des 5 colonnes = 100% */
        .planif-wrap .premium-table thead th:nth-child(1) { width: 26%; } /* Salle */
        .planif-wrap .premium-table thead th:nth-child(2) { width: 22%; } /* Période */
        .planif-wrap .premium-table thead th:nth-child(3) { width: 18%; } /* Début */
        .planif-wrap .premium-table thead th:nth-child(4) { width: 18%; } /* Fin */
        .planif-wrap .premium-table thead th:nth-child(5) { width: 16%; } /* Actions */
    }

    /* Empêche le débordement */
    .table-room span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table-room {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }
    .table-room-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-sm);
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .table-room span {
        font-weight: 600;
        color: var(--gray-900);
    }

    .period-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.3rem 0.75rem;
        background: var(--primary-soft);
        color: var(--primary);
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .date-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--gray-600);
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .date-cell i { color: var(--gray-400); font-size: 0.8rem; }

    /* Actions */
    .action-cell { white-space: nowrap; }
    .action-icons {
        display: inline-flex;
        justify-content: flex-end;
        gap: 0.4rem;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: var(--radius-sm);
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        color: var(--gray-500);
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.85rem;
        text-decoration: none;
        flex-shrink: 0;
    }
    .action-btn:hover { transform: translateY(-2px); }
    .action-view { color: var(--primary); background: var(--primary-soft); border-color: #c7d2fe; }
    .action-view:hover { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3); }
    .action-delete { color: var(--danger); background: var(--danger-soft); border-color: #fecaca; }
    .action-delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3); }
    .inline-form { display: inline-block; }

    /* ============================================================
       EMPTY STATES
       ============================================================ */
    .empty-rooms,
    .empty-sessions {
        grid-column: 1 / -1;
        text-align: center;
        padding: clamp(2rem, 6vw, 3rem) 1.5rem;
    }
    .empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: var(--gray-100);
        color: var(--gray-400);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin: 0 auto 1rem;
    }
    .empty-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--gray-800);
        margin: 0 0 0.35rem;
    }
    .empty-text {
        font-size: 0.85rem;
        color: var(--gray-500);
        margin: 0 auto;
        max-width: 420px;
        line-height: 1.5;
    }

    /* ============================================================
       ✅ ANIMATIONS BIDIRECTIONNELLES
       ============================================================ */
    .planif-wrap [data-reveal] {
        opacity: 0;
        transform: translateY(32px) scale(.985);
        filter: blur(6px);
        transition:
            opacity 700ms var(--ease-out-expo),
            transform 700ms var(--ease-out-expo),
            filter 700ms var(--ease-out-expo);
        will-change: opacity, transform, filter;
    }

    .planif-wrap [data-reveal="auto"][data-scroll-dir="up"]   { transform: translateY(-32px); }
    .planif-wrap [data-reveal="auto"][data-scroll-dir="down"] { transform: translateY(32px); }

    .planif-wrap [data-reveal].is-visible {
        opacity: 1;
        transform: translate(0, 0) scale(1);
        filter: blur(0);
    }

    .planif-wrap [data-delay="1"] { transition-delay: 60ms; }
    .planif-wrap [data-delay="2"] { transition-delay: 120ms; }
    .planif-wrap [data-delay="3"] { transition-delay: 180ms; }
    .planif-wrap [data-delay="4"] { transition-delay: 240ms; }
    .planif-wrap [data-delay="5"] { transition-delay: 300ms; }

    @media (prefers-reduced-motion: reduce) {
        .planif-wrap [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        .stat-card:hover,
        .stat-card:hover .stat-icon,
        .room-card:hover,
        .room-card:hover .room-avatar,
        .btn-primary:hover,
        .action-btn:hover { transform: none; }
    }

    /* ============================================================
       RESPONSIVE — MOBILE (≤ 767px) — Cartes
       ============================================================ */
    @media (max-width: 767px) {
        .planif-wrap { padding: 1.25rem 0.75rem; }

        /* Tableau → cartes */
        .table-wrapper { border: none; overflow: visible; }
        .premium-table { display: block; width: 100%; }
        .premium-table thead { display: none; }
        .premium-table tbody { display: block; }

        .premium-table tr {
            display: block;
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 1rem;
            padding: 1rem 1.25rem;
            transition: box-shadow 300ms var(--ease-soft), transform 300ms var(--ease-soft);
        }
        .premium-table tr:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .premium-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0 !important;
            border: none;
            gap: 1rem;
            text-align: right;
            min-height: 40px;
        }
        .premium-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--gray-500);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
            text-align: left;
            min-width: 90px;
        }

        .premium-table td.text-right,
        .premium-table td[data-label="Actions"] {
            justify-content: flex-end;
            padding-top: 0.75rem !important;
            border-top: 1px solid var(--gray-100);
            margin-top: 0.5rem;
            min-height: auto;
        }
        .premium-table td[data-label="Actions"]::before { display: none; }

        .action-icons {
            width: 100%;
            justify-content: flex-end;
            gap: .5rem;
        }
        .action-btn {
            width: 42px;
            height: 42px;
            border-radius: 11px;
        }

        /* Room cards mobile-friendly */
        .rooms-grid { grid-template-columns: 1fr; }
    }

    /* ============================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
       ============================================================ */
    @media (max-width: 480px) {
        .planif-wrap { padding-inline: .85rem; }
        .planif-title { font-size: 1.3rem; }
        .planif-subtitle { font-size: .82rem; }

        .stat-card { padding: 1rem; gap: .75rem; }
        .stat-icon { width: 40px; height: 40px; font-size: 1rem; }
        .stat-value { font-size: 1.3rem; }

        .section-card { padding: .9rem; }
        .room-card { padding: 1rem; }
        .room-avatar { width: 40px; height: 40px; font-size: 1rem; }
        .room-name { font-size: .95rem; }

        .premium-table tr { padding: .85rem 1rem; }
        .premium-table td { font-size: .88rem; }
        .premium-table td::before { font-size: .65rem; min-width: 80px; }
    }

    /* ============================================================
       TRÈS PETIT MOBILE (≤ 360px)
       ============================================================ */
    @media (max-width: 360px) {
        .premium-table td {
            font-size: .84rem;
            padding: .4rem 0 !important;
            gap: .5rem;
        }
        .premium-table td::before { font-size: .62rem; min-width: 70px; }
        .stat-value { font-size: 1.15rem; }
    }

    /* ============================================================
       IMPRESSION
       ============================================================ */
    @media print {
        .planif-wrap { padding: 0; max-width: 100%; }
        .header-right, .action-cell, .room-footer { display: none !important; }
        .section-card, .stat-card, .room-card {
            box-shadow: none;
            border: 1px solid #ddd;
            break-inside: avoid;
        }
        .planif-wrap [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
    }
</style>

<script>
    (function () {
        'use strict';

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /* ============================================================
           DÉTECTION DE DIRECTION DE SCROLL
        ============================================================ */
        let lastY = window.scrollY;
        let scrollDir = 'down';

        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            if (Math.abs(y - lastY) > 4) {
                scrollDir = y > lastY ? 'down' : 'up';
                lastY = y;
            }
        }, { passive: true });

        /* ============================================================
           ANIMATIONS BIDIRECTIONNELLES
        ============================================================ */
        function initReveal() {
            const els = document.querySelectorAll('.planif-wrap [data-reveal]');
            if (!els.length) return;

            if (prefersReducedMotion || !('IntersectionObserver' in window)) {
                els.forEach(el => el.classList.add('is-visible'));
                return;
            }

            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const el = entry.target;
                    const isAuto = el.dataset.reveal === 'auto';

                    if (entry.isIntersecting) {
                        if (isAuto) el.dataset.scrollDir = scrollDir;
                        el.classList.add('is-visible');
                    } else if (entry.intersectionRatio === 0) {
                        el.classList.remove('is-visible');
                        if (isAuto) delete el.dataset.scrollDir;
                    }
                });
            }, {
                threshold: [0, 0.1],
                rootMargin: '0px 0px -30px 0px'
            });

            els.forEach(el => io.observe(el));
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initReveal);
        } else {
            initReveal();
        }
    })();
</script>
@endsection