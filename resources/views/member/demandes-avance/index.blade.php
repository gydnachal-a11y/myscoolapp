@extends('layouts.admin')

@section('page_title', 'Mes demandes d\'avance')
@section('page_subtitle', 'Historique et suivi de vos demandes')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- ============================================================
         EN-TÊTE
         ============================================================ --}}
    <div class="index-header">
        <div class="header-title-block">
            <div class="title-badge">
                <i class="fa-solid fa-user"></i>
                <span>Mon espace</span>
            </div>
            <h1 class="index-title">
                Mes demandes d'<strong>avance sur salaire</strong>
            </h1>
            <p class="index-subtitle">
                Suivez l'état de vos demandes et vos avances en cours
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('member.salaire') }}" class="btn-secondary">
                <i class="fa-solid fa-money-bill"></i>
                <span>Mon salaire</span>
            </a>

            @if($peutDemander)
                <a href="{{ route('member.demandes-avance.create') }}" class="btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    <span>Nouvelle demande</span>
                </a>
            @elseif($aDemandeEnAttente)
                <span class="btn-pending">
                    <i class="fa-solid fa-hourglass-half"></i>
                    Demande en cours
                </span>
            @endif
        </div>
    </div>

    {{-- ============================================================
         BANDEAU D'INFORMATION (session ou statut)
         ============================================================ --}}
    @if($sessionActive && $peutDemander)
        <div class="info-banner info-banner-success">
            <div class="banner-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="banner-content">
                <div class="banner-title">Demandes ouvertes</div>
                <div class="banner-text">
                    La session <strong>{{ $sessionActive->libelle }}</strong> est ouverte jusqu'au
                    <strong>{{ $sessionActive->date_fin->translatedFormat('d M Y') }}</strong>.
                    Vous pouvez soumettre votre demande.
                </div>
            </div>
            <a href="{{ route('member.demandes-avance.create') }}" class="banner-action">
                Faire ma demande <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    @elseif($aDemandeEnAttente)
        <div class="info-banner info-banner-warning">
            <div class="banner-icon">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="banner-content">
                <div class="banner-title">Demande en cours de traitement</div>
                <div class="banner-text">
                    Votre demande d'avance est en attente de validation par l'administration.
                </div>
            </div>
        </div>
    @elseif(!$sessionActive)
        <div class="info-banner info-banner-neutral">
            <div class="banner-icon">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="banner-content">
                <div class="banner-title">Demandes fermées</div>
                <div class="banner-text">
                    Aucune session de demandes n'est ouverte actuellement. Vous serez notifié à la prochaine ouverture.
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================
         STATISTIQUES PERSONNELLES
         ============================================================ --}}
    <div class="stats-grid">
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($counts['en_attente'], 0, ',', ' ') }}</div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-glow"></div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($counts['validee'], 0, ',', ' ') }}</div>
                <div class="stat-label">Validées</div>
            </div>
            <div class="stat-glow"></div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($counts['refusee'], 0, ',', ' ') }}</div>
                <div class="stat-label">Refusées</div>
            </div>
            <div class="stat-glow"></div>
        </div>

        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ number_format($montantTotalUSD ?? 0, 0, ',', ' ') }} <small>$</small></div>
                <div class="stat-label">Total demandé</div>
            </div>
            <div class="stat-glow"></div>
        </div>
    </div>

    {{-- ============================================================
         SITUATION FINANCIÈRE
         ============================================================ --}}
    <div class="financial-grid">
        <div class="financial-card">
            <div class="financial-header">
                <i class="fa-solid fa-wallet"></i>
                <span>Salaire mensuel</span>
            </div>
            <div class="financial-value">
                {{ number_format($salaireMensuel, 0, ',', ' ') }} <small>$</small>
            </div>
        </div>

        <div class="financial-card financial-danger">
            <div class="financial-header">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span>Dette actuelle</span>
            </div>
            <div class="financial-value">
                {{ number_format($detteTotale, 0, ',', ' ') }} <small>$</small>
            </div>
            <div class="financial-sub">
                ≈ {{ number_format($detteTotale * $tauxChange, 0, ',', ' ') }} FC
            </div>
        </div>

        <div class="financial-card financial-info">
            <div class="financial-header">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Limite d'emprunt</span>
            </div>
            <div class="financial-value">
                {{ number_format($limiteEmprunt, 0, ',', ' ') }} <small>$</small>
            </div>
        </div>

        <div class="financial-card financial-success">
            <div class="financial-header">
                <i class="fa-solid fa-circle-check"></i>
                <span>Disponible</span>
            </div>
            <div class="financial-value">
                {{ number_format(max(0, $limiteEmprunt - $detteTotale), 0, ',', ' ') }} <small>$</small>
            </div>
        </div>
    </div>

    {{-- ============================================================
         AVANCES EN COURS
         ============================================================ --}}
    @if($avancesEnCours->count())
        <div class="section-title-block">
            <div class="section-title-icon icon-warning">
                <i class="fa-solid fa-hand-holding-usd"></i>
            </div>
            <div>
                <h2 class="section-title-text">Avances en cours</h2>
                <p class="section-title-sub">{{ $avancesEnCours->count() }} avance(s) à rembourser</p>
            </div>
        </div>

        <div class="avances-grid">
            @foreach($avancesEnCours as $avance)
                <div class="avance-card">
                    <div class="avance-header">
                        <div class="avance-date">
                            <i class="fa-regular fa-calendar"></i>
                            {{ \Carbon\Carbon::parse($avance->date_avance)->translatedFormat('d M Y') }}
                        </div>
                        <span class="avance-statut">
                            {{ $avance->statut === 'en_attente' ? 'En cours' : 'Partiel' }}
                        </span>
                    </div>

                    <div class="avance-amounts">
                        <div class="avance-row">
                            <span>Montant initial</span>
                            <strong>{{ number_format($avance->montant_avance_usd, 0, ',', ' ') }} $</strong>
                        </div>
                        <div class="avance-row avance-row-success">
                            <span>Remboursé</span>
                            <strong>{{ number_format($avance->montant_rembourse_usd, 0, ',', ' ') }} $</strong>
                        </div>
                        <div class="avance-row avance-row-danger">
                            <span>Dette restante</span>
                            <strong>{{ number_format($avance->dette_restante_usd, 0, ',', ' ') }} $</strong>
                        </div>
                    </div>

                    {{-- Barre de progression --}}
                    @php
                        $progression = $avance->montant_avance_usd > 0
                            ? round(($avance->montant_rembourse_usd / $avance->montant_avance_usd) * 100)
                            : 0;
                    @endphp
                    <div class="progress-wrap">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $progression }}%"></div>
                        </div>
                        <div class="progress-label">{{ $progression }}% remboursé</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         TABLEAU DES DEMANDES
         ============================================================ --}}
    <div class="section-title-block">
        <div class="section-title-icon icon-primary">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div>
            <h2 class="section-title-text">Historique de mes demandes</h2>
            <p class="section-title-sub">{{ $demandes->total() }} demande(s) au total</p>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-right">Montant</th>
                        <th scope="col">Motif</th>
                        <th scope="col" class="text-center">Statut</th>
                        <th scope="col">Traitement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($demandes as $demande)
                        @php
                            $badgeClass = match($demande->statut) {
                                'en_attente' => 'badge-warning',
                                'validee'    => 'badge-success',
                                'refusee'    => 'badge-danger',
                                default      => 'badge-neutral',
                            };
                            $statutIcon = match($demande->statut) {
                                'en_attente' => 'fa-hourglass-half',
                                'validee'    => 'fa-circle-check',
                                'refusee'    => 'fa-circle-xmark',
                                default      => 'fa-circle',
                            };
                        @endphp
                        <tr class="table-row">
                            <td data-label="Date">
                                <div class="date-cell">
                                    <i class="fa-regular fa-calendar"></i>
                                    <div>
                                        <div class="date-main">{{ $demande->created_at->format('d/m/Y') }}</div>
                                        <div class="date-time">{{ $demande->created_at->format('H:i') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Montant" class="text-right">
                                <div class="montant-block">
                                    <strong class="montant-usd">{{ number_format($demande->montant_demande_usd, 0, ',', ' ') }} $</strong>
                                    <span class="montant-fc">≈ {{ number_format($demande->montant_demande_fc, 0, ',', ' ') }} FC</span>
                                </div>
                            </td>
                            <td data-label="Motif">
                                <span class="motif-cell" title="{{ $demande->motif }}">
                                    {{ Str::limit($demande->motif, 50) }}
                                </span>
                                @if($demande->isRefusee() && $demande->motif_refus)
                                    <div class="motif-refus">
                                        <i class="fa-solid fa-circle-info"></i>
                                        {{ Str::limit($demande->motif_refus, 80) }}
                                    </div>
                                @endif
                            </td>
                            <td data-label="Statut" class="text-center">
                                <span class="statut-badge {{ $badgeClass }}">
                                    <i class="fa-solid {{ $statutIcon }}"></i>
                                    {{ $demande->statut_label }}
                                </span>
                            </td>
                            <td data-label="Traitement">
                                @if($demande->traite_le)
                                    <div class="traitement-info">
                                        <i class="fa-solid fa-user-shield"></i>
                                        <div>
                                            <div class="traitement-date">
                                                {{ $demande->traite_le->format('d/m/Y') }}
                                            </div>
                                            <div class="traitement-time">
                                                {{ $demande->traite_le->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-cell">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fa-solid fa-inbox"></i>
                                    </div>
                                    <h4 class="empty-title">Aucune demande</h4>
                                    <p class="empty-text">
                                        Vous n'avez pas encore soumis de demande d'avance sur salaire.
                                    </p>
                                    @if($peutDemander)
                                        <a href="{{ route('member.demandes-avance.create') }}" class="btn-primary" style="margin-top: 0.5rem;">
                                            <i class="fa-solid fa-plus"></i> Faire ma première demande
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($demandes->hasPages())
            <div class="pagination-container">
                {{ $demandes->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    /* ============================================================
       VARIABLES
       ============================================================ */
    :root {
        --color-primary: #1e293b;
        --color-primary-hover: #6366f1;
        --color-secondary: #475569;
        --color-border: #e2e8f0;
        --color-muted: #94a3b8;
        --color-bg-light: #f8fafc;
        --color-white: #ffffff;
        --color-success: #16a34a;
        --color-danger: #dc2626;
        --color-warning: #d97706;
        --shadow-card: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        --shadow-hover: 0 10px 30px rgba(99, 102, 241, 0.25);
        --radius-card: 16px;
        --radius-btn: 10px;
        --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .index-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }

    /* ============================================================
       EN-TÊTE
       ============================================================ */
    .index-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        margin-bottom: 2rem; flex-wrap: wrap; gap: 1.5rem;
    }
    .header-title-block { flex: 1; min-width: 0; }

    .title-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 0.3rem 0.75rem;
        background: linear-gradient(135deg, #e0e7ff, #ede9fe);
        color: #4f46e5; border-radius: 999px;
        font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 0.75rem;
    }

    .index-title {
        font-size: 1.85rem; font-weight: 600; color: var(--color-primary);
        margin: 0; line-height: 1.2; letter-spacing: -0.02em;
    }
    .index-title strong {
        font-weight: 800;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .index-subtitle {
        color: var(--color-muted); font-size: 0.95rem;
        margin-top: 0.5rem;
    }

    .header-actions {
        display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; justify-content: flex-end;
    }

    /* ============================================================
       BOUTONS
       ============================================================ */
    .btn-primary, .btn-secondary, .btn-pending {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 0.65rem 1.25rem;
        border-radius: var(--radius-btn);
        font-weight: 600; font-size: 0.9rem;
        text-decoration: none; transition: var(--transition);
        border: 1.5px solid transparent; cursor: pointer; white-space: nowrap;
    }

    .btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .btn-primary:hover {
        transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
    }

    .btn-secondary {
        background: #fff; color: var(--color-secondary); border-color: var(--color-border);
    }
    .btn-secondary:hover {
        border-color: var(--color-primary-hover); color: var(--color-primary-hover);
        background: #f8faff; transform: translateY(-1px);
    }

    .btn-pending {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e; cursor: default;
    }

    /* ============================================================
       BANDEAU D'INFORMATION
       ============================================================ */
    .info-banner {
        display: flex; align-items: center; gap: 1.25rem;
        padding: 1.25rem 1.5rem;
        border-radius: var(--radius-card);
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .info-banner-success { background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #bbf7d0; }
    .info-banner-warning { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fde68a; }
    .info-banner-neutral { background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid #e2e8f0; }

    .banner-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .info-banner-success .banner-icon { background: #16a34a; color: #fff; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); }
    .info-banner-warning .banner-icon { background: #d97706; color: #fff; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); }
    .info-banner-neutral .banner-icon { background: #64748b; color: #fff; }

    .banner-content { flex: 1; min-width: 0; }
    .banner-title {
        font-weight: 700; font-size: 1rem;
        margin-bottom: 0.25rem;
    }
    .info-banner-success .banner-title { color: #166534; }
    .info-banner-warning .banner-title { color: #92400e; }
    .info-banner-neutral .banner-title { color: var(--color-primary); }

    .banner-text { font-size: 0.88rem; line-height: 1.5; }
    .info-banner-success .banner-text { color: #15803d; }
    .info-banner-warning .banner-text { color: #b45309; }
    .info-banner-neutral .banner-text { color: var(--color-secondary); }

    .banner-action {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: #16a34a; color: #fff;
        border-radius: 10px; font-weight: 600; font-size: 0.88rem;
        text-decoration: none; transition: var(--transition);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    }
    .banner-action:hover { background: #15803d; transform: translateY(-2px); }

    /* ============================================================
       STATISTIQUES
       ============================================================ */
    .stats-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem; margin-bottom: 1.5rem;
    }

    .stat-card {
        position: relative; background: #fff;
        border-radius: 14px; padding: 1.1rem 1.25rem;
        display: flex; align-items: center; gap: 1rem;
        box-shadow: var(--shadow-card);
        border: 1px solid #f1f5f9;
        overflow: hidden; transition: var(--transition);
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }

    .stat-icon {
        width: 46px; height: 46px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
        position: relative; z-index: 1;
    }

    .stat-body { flex: 1; min-width: 0; position: relative; z-index: 1; }
    .stat-value {
        font-size: 1.5rem; font-weight: 800; color: var(--color-primary);
        line-height: 1.1; letter-spacing: -0.02em;
    }
    .stat-value small { font-size: 0.85rem; font-weight: 600; color: var(--color-muted); }
    .stat-label {
        font-size: 0.72rem; color: var(--color-muted);
        text-transform: uppercase; letter-spacing: 0.5px;
        font-weight: 600; margin-top: 0.25rem;
    }

    .stat-glow {
        position: absolute; top: -30%; right: -20%;
        width: 100px; height: 100px; border-radius: 50%;
        opacity: 0.4; filter: blur(30px); pointer-events: none;
    }

    .stat-warning .stat-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
    .stat-warning .stat-glow { background: #fbbf24; }
    .stat-success .stat-icon { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .stat-success .stat-glow { background: #22c55e; }
    .stat-danger .stat-icon { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #b91c1c; }
    .stat-danger .stat-glow { background: #ef4444; }
    .stat-primary .stat-icon { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5; }
    .stat-primary .stat-glow { background: #6366f1; }

    /* ============================================================
       SITUATION FINANCIÈRE
       ============================================================ */
    .financial-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem; margin-bottom: 2rem;
    }

    .financial-card {
        background: #fff; border-radius: 14px; padding: 1.1rem 1.25rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
        transition: var(--transition);
    }
    .financial-card:hover { transform: translateY(-2px); }

    .financial-header {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.75rem; color: var(--color-muted);
        text-transform: uppercase; letter-spacing: 0.4px;
        font-weight: 600; margin-bottom: 0.5rem;
    }
    .financial-header i { font-size: 0.9rem; }

    .financial-value {
        font-size: 1.6rem; font-weight: 800;
        color: var(--color-primary); line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .financial-value small { font-size: 0.9rem; color: var(--color-muted); font-weight: 600; }

    .financial-sub {
        font-size: 0.78rem; color: var(--color-muted);
        margin-top: 0.35rem;
    }

    .financial-danger .financial-header i { color: #dc2626; }
    .financial-danger .financial-value { color: #dc2626; }
    .financial-info .financial-header i { color: #6366f1; }
    .financial-info .financial-value { color: #4f46e5; }
    .financial-success .financial-header i { color: #16a34a; }
    .financial-success .financial-value { color: #15803d; }

    /* ============================================================
       SECTION TITLE
       ============================================================ */
    .section-title-block {
        display: flex; align-items: center; gap: 0.85rem;
        margin-bottom: 1.25rem;
        margin-top: 1.5rem;
    }
    .section-title-icon {
        width: 42px; height: 42px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .icon-primary { background: linear-gradient(135deg, #e0e7ff, #ede9fe); color: #4f46e5; }
    .icon-warning { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }

    .section-title-text {
        font-size: 1.15rem; font-weight: 700;
        color: var(--color-primary); margin: 0;
        letter-spacing: -0.01em;
    }
    .section-title-sub {
        font-size: 0.82rem; color: var(--color-muted);
        margin: 0.1rem 0 0;
    }

    /* ============================================================
       AVANCES EN COURS
       ============================================================ */
    .avances-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem; margin-bottom: 1.5rem;
    }

    .avance-card {
        background: #fff; border-radius: 14px; padding: 1.25rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
        transition: var(--transition);
    }
    .avance-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }

    .avance-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .avance-date {
        display: flex; align-items: center; gap: 6px;
        font-size: 0.82rem; color: var(--color-muted); font-weight: 500;
    }
    .avance-statut {
        font-size: 0.7rem; padding: 0.2rem 0.6rem;
        background: #fef3c7; color: #92400e;
        border-radius: 999px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
    }

    .avance-amounts { display: flex; flex-direction: column; gap: 0.5rem; }
    .avance-row {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 0.85rem;
    }
    .avance-row span { color: var(--color-secondary); }
    .avance-row strong { color: var(--color-primary); font-weight: 700; }
    .avance-row-success strong { color: #16a34a; }
    .avance-row-danger strong { color: #dc2626; }

    .progress-wrap { margin-top: 1rem; }
    .progress-bar {
        width: 100%; height: 8px;
        background: #f1f5f9; border-radius: 999px; overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        border-radius: 999px;
        transition: width 0.6s ease;
    }
    .progress-label {
        font-size: 0.72rem; color: var(--color-muted);
        margin-top: 0.4rem; text-align: right;
    }

    /* ============================================================
       TABLEAU
       ============================================================ */
    .table-card {
        background: #fff; border-radius: var(--radius-card);
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
        overflow: hidden; margin-bottom: 1.5rem;
    }
    .table-responsive { overflow-x: auto; }

    .data-table {
        width: 100%; border-collapse: collapse;
        font-size: 0.9rem; color: var(--color-secondary);
    }
    .data-table thead th {
        text-align: left; padding: 0.9rem 1.25rem;
        font-size: 0.68rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.6px;
        color: var(--color-muted); background: #fafbfc;
        border-bottom: 1.5px solid #eef1f5; white-space: nowrap;
    }
    .data-table thead th.text-right { text-align: right; }
    .data-table thead th.text-center { text-align: center; }

    .table-row { transition: var(--transition); }
    .table-row:hover { background: #fafbff; }

    .data-table tbody td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .text-right { text-align: right; }
    .data-table .text-center { text-align: center; }

    .date-cell { display: flex; align-items: center; gap: 8px; }
    .date-cell > i { color: var(--color-muted); font-size: 0.85rem; }
    .date-main { font-size: 0.85rem; font-weight: 500; }
    .date-time { font-size: 0.72rem; color: var(--color-muted); }

    .montant-block { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
    .montant-usd { font-weight: 800; color: var(--color-primary); font-size: 0.98rem; }
    .montant-fc { font-size: 0.72rem; color: var(--color-muted); font-weight: 500; }

    .motif-cell {
        display: block; max-width: 260px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        color: var(--color-secondary); font-size: 0.88rem;
    }
    .motif-refus {
        display: flex; align-items: center; gap: 6px;
        margin-top: 0.5rem; padding: 0.4rem 0.6rem;
        background: #fef2f2; color: #991b1b;
        border-radius: 8px; font-size: 0.78rem;
        border: 1px solid #fecaca;
    }

    .statut-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 0.35rem 0.8rem; border-radius: 999px;
        font-size: 0.75rem; font-weight: 700; white-space: nowrap;
    }
    .badge-warning { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
    .badge-success { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #065f46; }
    .badge-danger  { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; }
    .badge-neutral { background: #f1f5f9; color: #475569; }

    .traitement-info { display: flex; align-items: center; gap: 8px; }
    .traitement-info > i { color: var(--color-muted); font-size: 0.85rem; }
    .traitement-date { font-size: 0.82rem; font-weight: 500; }
    .traitement-time { font-size: 0.72rem; color: var(--color-muted); }

    .empty-cell { text-align: center; padding: 4rem 1.5rem; }
    .empty-state { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
    .empty-icon {
        width: 80px; height: 80px; border-radius: 50%;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem;
    }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: var(--color-primary); margin: 0; }
    .empty-text { font-size: 0.9rem; color: var(--color-muted); max-width: 320px; margin: 0; line-height: 1.5; }

    .pagination-container { padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }
    .text-muted { color: var(--color-muted); }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 1024px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .financial-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .index-container { padding: 1.25rem 0.75rem; }
        .index-title { font-size: 1.4rem; }
        .index-header { flex-direction: column; align-items: stretch; }
        .header-actions { width: 100%; }
        .header-actions .btn-primary,
        .header-actions .btn-secondary,
        .header-actions .btn-pending { flex: 1; justify-content: center; }

        .info-banner { padding: 1rem; }
        .banner-content { flex: 1 1 100%; }
        .banner-action { width: 100%; justify-content: center; }

        .stats-grid { grid-template-columns: 1fr 1fr; }
        .financial-grid { grid-template-columns: 1fr 1fr; }
        .avances-grid { grid-template-columns: 1fr; }

        .table-responsive { overflow-x: visible; }
        .data-table thead { display: none; }
        .data-table tbody tr {
            display: block; background: #fff; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 1rem;
            padding: 1rem 1.25rem; border: 1px solid #f1f5f9;
        }
        .data-table tbody td {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.5rem 0; border-bottom: 1px solid #f8fafc; gap: 0.75rem;
        }
        .data-table tbody td:last-child { border-bottom: none; padding-top: 0.75rem; }
        .data-table tbody td::before {
            content: attr(data-label); font-weight: 700; color: var(--color-muted);
            font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;
            min-width: 90px; flex-shrink: 0;
        }
        .data-table tbody td.text-right,
        .data-table tbody td.text-center { text-align: left; justify-content: space-between; }
        .montant-block { align-items: flex-end; }
        .motif-cell { max-width: 100%; }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
        .financial-grid { grid-template-columns: 1fr; }
        .stat-value { font-size: 1.3rem; }
        .financial-value { font-size: 1.4rem; }
        .index-title { font-size: 1.2rem; }
    }
</style>
@endsection