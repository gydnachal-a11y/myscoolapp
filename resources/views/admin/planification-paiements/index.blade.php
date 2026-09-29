@extends('layouts.admin')

@section('page_title', 'Planification des paiements')
@section('page_subtitle', 'Planifiez les sessions de paiement par salle')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endpush

@section('content')
<div class="planif-wrap">

    {{-- ============================================================
         MESSAGES FLASH
         ============================================================ --}}
    @if(session('success'))
        <div class="flash flash-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================
         EN-TÊTE
         ============================================================ --}}
    <div class="planif-header">
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
        <div class="stat-card stat-indigo">
            <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
            <div class="stat-body">
                <div class="stat-label">Salles de classe</div>
                <div class="stat-value">{{ $salles->count() }}</div>
            </div>
            <div class="stat-glow"></div>
        </div>

        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-body">
                <div class="stat-label">Sessions planifiées</div>
                <div class="stat-value">{{ $sessions->count() }}</div>
            </div>
            <div class="stat-glow"></div>
        </div>

        <div class="stat-card stat-amber">
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
    <div class="section-card">
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
    <div class="section-card">
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
    }

    * { box-sizing: border-box; }

    .planif-wrap {
        max-width: 1280px;
        margin: 0 auto;
        padding: 2rem 1rem;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--gray-800);
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
        animation: slideDown 0.3s ease;
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

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ============================================================
       EN-TÊTE
       ============================================================ */
    .planif-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1.5rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    .header-left { flex: 1; min-width: 280px; }

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
        font-size: 1.85rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
        line-height: 1.2;
        letter-spacing: -0.02em;
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
        font-size: 0.95rem;
        margin-top: 0.4rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
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
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
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
        transition: var(--transition);
    }
    .stat-card:hover {
        transform: translateY(-3px);
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
    }
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
    }
    .stat-value {
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
        padding: 1.5rem;
        margin-bottom: 1.5rem;
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
    }

    /* ============================================================
       CARTES DES SALLES
       ============================================================ */
    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
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
    }
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
        letter-spacing: -0.01em;
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
        overflow-x: auto;
        border-radius: var(--radius-md);
        border: 1px solid var(--gray-100);
    }

    .premium-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
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
    .premium-table tbody tr:hover { background: var(--gray-50); }
    .premium-table tbody tr:last-child td { border-bottom: none; }
    .premium-table .text-right { text-align: right; }

    .table-room {
        display: flex;
        align-items: center;
        gap: 0.75rem;
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
    }

    .date-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--gray-600);
        font-size: 0.85rem;
    }
    .date-cell i { color: var(--gray-400); font-size: 0.8rem; }

    /* Actions */
    .action-cell { white-space: nowrap; }
    .action-icons {
        display: flex;
        justify-content: flex-end;
        gap: 0.4rem;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: var(--radius-sm);
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        color: var(--gray-500);
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.85rem;
    }
    .action-btn:hover { transform: translateY(-2px); }
    .action-view { color: var(--primary); background: var(--primary-soft); border-color: #c7d2fe; }
    .action-view:hover { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3); }
    .action-delete { color: var(--danger); background: var(--danger-soft); border-color: #fecaca; }
    .action-delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3); }
    .inline-form { display: inline; }

    /* ============================================================
       EMPTY STATES
       ============================================================ */
    .empty-rooms,
    .empty-sessions {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1.5rem;
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
        margin: 0;
        max-width: 420px;
        margin: 0 auto;
        line-height: 1.5;
    }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 768px) {
        .planif-wrap { padding: 1.25rem 0.75rem; }
        .planif-title { font-size: 1.4rem; }
        .planif-header { flex-direction: column; align-items: stretch; }
        .header-right .btn { width: 100%; justify-content: center; }

        .rooms-grid { grid-template-columns: 1fr; }

        /* Tableau en cartes sur mobile */
        .table-wrapper { border: none; overflow: visible; }
        .premium-table { display: block; }
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
        }
        .premium-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border: none;
            gap: 1rem;
        }
        .premium-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--gray-500);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            min-width: 90px;
        }
        .premium-table td.text-right,
        .premium-table td[data-label="Actions"] {
            justify-content: flex-end;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-100);
            margin-top: 0.5rem;
        }
        .premium-table td[data-label="Actions"]::before { display: none; }
    }
</style>
@endsection