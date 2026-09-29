@extends('layouts.admin')

@section('page_title', 'Demandes d\'avance sur salaire')
@section('page_subtitle', 'Traitez les demandes des membres')

@section('content')
<div class="index-container" x-data="demandeAvanceIndex()">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- ============================================================
         EN-TÊTE
         ============================================================ --}}
    <div class="index-header">
        <div class="header-title-block">
            <h1 class="index-title">
                <strong>Demandes</strong> d'avance sur salaire
            </h1>
            <p class="index-subtitle">
                Validez ou refusez les demandes des membres
                @if(request()->hasAny(['statut', 'recherche']))
                    <span class="filter-indicator"> • Filtres actifs</span>
                @endif
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.session-avances.index') }}" class="btn-secondary">
                <i class="fa-solid fa-calendar-check"></i> Sessions d'ouverture
            </a>
            <a href="{{ route('admin.avances.index') }}" class="btn-secondary">
                <i class="fa-solid fa-hand-holding-usd"></i> Toutes les avances
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="fa-solid fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- ============================================================
         BARRE D'EXPORT
         ============================================================ --}}
    <div class="export-bar">
        <span class="export-label">Exporter :</span>
        <a href="{{ route('admin.demandes-avance.index', array_merge(request()->query(), ['export' => 'pdf'])) }}"
           class="btn-export" title="PDF">
            <i class="fa-solid fa-file-pdf"></i><span class="btn-export-text">PDF</span>
        </a>
        <a href="{{ route('admin.demandes-avance.index', array_merge(request()->query(), ['export' => 'csv'])) }}"
           class="btn-export" title="CSV">
            <i class="fa-solid fa-file-csv"></i><span class="btn-export-text">CSV</span>
        </a>
    </div>

    {{-- ============================================================
         STATISTIQUES GLOBALES
         ============================================================ --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-amber-100 text-amber-600">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($counts['en_attente'], 0, ',', ' ') }}</div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($counts['validee'], 0, ',', ' ') }}</div>
                <div class="stat-label">Validées</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red-100 text-red-600">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($counts['refusee'], 0, ',', ' ') }}</div>
                <div class="stat-label">Refusées</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($montantTotalUSD ?? 0, 0, ',', ' ') }} $</div>
                <div class="stat-label">Total demandé (USD)</div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         FILTRES
         ============================================================ --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.demandes-avance.index') }}" class="filter-form">
            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-circle-dot"></i> Statut</label>
                <select name="statut" class="filter-select" onchange="this.form.submit()">
                    <option value="en_attente" @selected($statut === 'en_attente')>En attente</option>
                    <option value="validee"    @selected($statut === 'validee')>Validées</option>
                    <option value="refusee"    @selected($statut === 'refusee')>Refusées</option>
                </select>
            </div>

            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-user"></i> Recherche membre</label>
                <div class="search-input-wrap">
                    <i class="fa-solid fa-search search-icon"></i>
                    <input type="text" name="recherche" class="filter-input"
                           placeholder="Nom ou email..."
                           value="{{ request('recherche') }}">
                </div>
            </div>

            <button type="submit" class="btn-filter" aria-label="Appliquer les filtres">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>

            @if(request()->hasAny(['statut', 'recherche']))
                <a href="{{ route('admin.demandes-avance.index') }}" class="btn-reset">
                    <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                </a>
            @endif
        </form>

        <div class="filter-result-count" role="status" aria-live="polite">
            {{ $demandes->total() }} demande(s) trouvée(s)
        </div>
    </div>

    {{-- ============================================================
         TABLEAU
         ============================================================ --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-list text-indigo-500"></i>
                <span>Demandes enregistrées</span>
                <span class="badge-count">{{ $demandes->total() }}</span>
            </div>
            <div class="table-actions">
                <span class="text-muted text-sm">
                    Taux : {{ number_format($tauxChange ?? 2800, 2) }} FC/USD
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Membre</th>
                        <th scope="col" class="text-right">Montant demandé</th>
                        <th scope="col">Motif</th>
                        <th scope="col">Soumis le</th>
                        <th scope="col" class="text-center">Statut</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($demandes as $demande)
                        @php
                            $nom = $demande->user->name ?? 'Utilisateur supprimé';
                            $initiale = strtoupper(substr($nom, 0, 1));
                            $badgeClass = match($demande->statut) {
                                'en_attente' => 'badge-warning',
                                'validee'    => 'badge-success',
                                'refusee'    => 'badge-danger',
                                default      => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="Membre">
                                <div class="cell-eleve">
                                    <div class="avatar" aria-hidden="true">{{ $initiale }}</div>
                                    <div>
                                        <span class="cell-primary">{{ $nom }}</span>
                                        <span class="cell-secondary">{{ $demande->user->email ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Montant demandé" class="text-right">
                                <strong>{{ number_format($demande->montant_demande_usd, 0, ',', ' ') }} $</strong>
                                <span class="block text-xs text-gray-400">
                                    ≈ {{ number_format($demande->montant_demande_fc, 0, ',', ' ') }} FC
                                </span>
                            </td>
                            <td data-label="Motif">
                                <span class="motif-cell" title="{{ $demande->motif }}">
                                    {{ Str::limit($demande->motif, 40) }}
                                </span>
                            </td>
                            <td data-label="Soumis le">
                                <span class="date-cell">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    {{ $demande->created_at->format('d/m/Y') }}
                                    <br>
                                    <small class="text-muted">{{ $demande->created_at->format('H:i') }}</small>
                                </span>
                            </td>
                            <td data-label="Statut" class="text-center">
                                <span class="statut-badge {{ $badgeClass }}">
                                    {{ $demande->statut_label }}
                                </span>
                            </td>
                            <td data-label="Actions" class="text-center action-cell">
                                <div class="action-icons">

                                    @if($demande->isEnAttente())
                                        {{-- Accepter --}}
                                        <button type="button"
                                                class="action-icon success"
                                                title="Accepter la demande"
                                                aria-label="Accepter la demande de {{ $nom }}"
                                                @click="openAccepter({{ $demande->id }}, @js($nom), {{ $demande->montant_demande_usd }})">
                                            <i class="fa-solid fa-check"></i>
                                        </button>

                                        {{-- Refuser --}}
                                        <button type="button"
                                                class="action-icon danger"
                                                title="Refuser la demande"
                                                aria-label="Refuser la demande de {{ $nom }}"
                                                @click="openRefuser({{ $demande->id }}, @js($nom))">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    @endif

                                    {{-- Voir --}}
                                    <a href="{{ route('admin.demandes-avance.show', $demande) }}"
                                       class="action-icon"
                                       title="Voir le détail"
                                       aria-label="Voir la demande de {{ $nom }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                                    <p>Aucune demande dans cette catégorie.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pied du tableau --}}
        <div class="table-footer">
            <span class="text-muted text-sm">
                Total affiché :
                <strong>{{ number_format($demandes->sum('montant_demande_usd'), 0, ',', ' ') }} $</strong>
            </span>
        </div>

        {{-- Pagination --}}
        @if($demandes->hasPages())
            <div class="pagination-container">
                {{ $demandes->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         MODALE : ACCEPTER
         ============================================================ --}}
    <div x-show="accepterModal.open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="modal-overlay"
         @click.self="closeAccepter()">

        <div class="modal-content">
            <div class="modal-header modal-header-success">
                <div class="flex items-center gap-2 text-green-700 font-bold text-lg">
                    <i class="fa-solid fa-circle-check"></i> Accepter la demande
                </div>
                <button type="button" @click="closeAccepter()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="`/admin/demandes-avance/${accepterModal.demandeId}/valider`"
                  method="POST" class="modal-body">
                @csrf

                <div class="info-box info-box-success">
                    <div class="text-xs uppercase font-semibold opacity-70">Membre</div>
                    <div class="font-bold text-base" x-text="accepterModal.userName"></div>
                    <div class="text-sm mt-1">
                        Montant demandé :
                        <strong x-text="Number(accepterModal.montant).toLocaleString('fr-FR') + ' $'"></strong>
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label">
                        Mois scolaire <span class="required">*</span>
                    </label>
                    <select name="mois_scolaire_id" required class="form-select">
                        @foreach($moisScolaires as $mois)
                            <option value="{{ $mois->id }}">{{ $mois->nom_mois ?? $mois->mois }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label">
                        Date de l'avance <span class="required">*</span>
                    </label>
                    <input type="date" name="date_avance"
                           value="{{ now()->toDateString() }}" required class="form-input">
                </div>

                <div class="form-field">
                    <label class="form-label">Commentaire (optionnel)</label>
                    <input type="text" name="commentaire"
                           placeholder="Ex : validé par la direction..."
                           class="form-input">
                </div>

                <div class="modal-actions">
                    <button type="button" @click="closeAccepter()" class="btn-modal-cancel">
                        Annuler
                    </button>
                    <button type="submit" class="btn-modal-success">
                        <i class="fa-solid fa-check"></i> Confirmer et créer l'avance
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODALE : REFUSER
         ============================================================ --}}
    <div x-show="refuserModal.open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="modal-overlay"
         @click.self="closeRefuser()">

        <div class="modal-content">
            <div class="modal-header modal-header-danger">
                <div class="flex items-center gap-2 text-red-700 font-bold text-lg">
                    <i class="fa-solid fa-circle-xmark"></i> Refuser la demande
                </div>
                <button type="button" @click="closeRefuser()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="`/admin/demandes-avance/${refuserModal.demandeId}/refuser`"
                  method="POST" class="modal-body">
                @csrf

                <div class="info-box info-box-danger">
                    <div class="text-xs uppercase font-semibold opacity-70">Membre concerné</div>
                    <div class="font-bold text-base" x-text="refuserModal.userName"></div>
                </div>

                <div class="form-field">
                    <label class="form-label">
                        Motif du refus <span class="required">*</span>
                    </label>
                    <textarea name="motif_refus" rows="5" required minlength="10" maxlength="1000"
                              placeholder="Expliquez clairement la raison du refus (min. 10 caractères)..."
                              class="form-textarea"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" @click="closeRefuser()" class="btn-modal-cancel">
                        Annuler
                    </button>
                    <button type="submit" class="btn-modal-danger">
                        <i class="fa-solid fa-xmark"></i> Confirmer le refus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    :root {
        --color-primary: #1e293b;
        --color-primary-hover: #667eea;
        --color-secondary: #475569;
        --color-border: #e2e8f0;
        --color-muted: #94a3b8;
        --color-bg-light: #f8fafc;
        --color-white: #ffffff;
        --color-success: #16a34a;
        --color-danger: #dc2626;
        --color-warning: #d97706;
        --shadow-card: 0 4px 12px rgba(0,0,0,0.04);
        --shadow-hover: 0 10px 30px rgba(102,126,234,0.3);
        --radius-card: 16px;
        --radius-btn: 12px;
    }

    .index-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    .index-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
    .header-title-block { flex: 1; min-width: 0; }
    .header-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: flex-end; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: var(--color-primary); margin: 0; line-height: 1.2; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: var(--color-muted); font-size: 0.95rem; margin-top: 0.25rem; }
    .filter-indicator { color: var(--color-primary-hover); font-weight: 600; }

    /* Boutons d'en-tête */
    .btn-primary, .btn-secondary, .btn-print, .btn-export, .btn-filter, .btn-reset {
        display: inline-flex; align-items: center; gap: 8px; padding: 0.7rem 1.5rem;
        border-radius: var(--radius-btn); font-weight: 600; font-size: 0.95rem;
        text-decoration: none; transition: all 0.3s; border: none; cursor: pointer;
    }
    .btn-primary, .btn-print, .btn-filter { background: var(--color-primary); color: white; }
    .btn-primary:hover, .btn-print:hover, .btn-filter:hover {
        background: var(--color-primary-hover); transform: translateY(-2px); box-shadow: var(--shadow-hover);
    }
    .btn-print, .btn-filter { background: var(--color-secondary); }
    .btn-secondary, .btn-reset { background: var(--color-white); color: var(--color-primary); border: 1.5px solid var(--color-border); }
    .btn-secondary:hover, .btn-reset:hover { border-color: var(--color-primary-hover); color: var(--color-primary-hover); background: var(--color-bg-light); }
    .btn-export { padding: 0.5rem 0.8rem; background: var(--color-bg-light); color: var(--color-secondary); border: 1.5px solid var(--color-border); border-radius: 8px; }
    .btn-export:hover { background: var(--color-border); color: var(--color-primary); border-color: #cbd5e1; transform: translateY(-1px); }

    .export-bar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
        margin-bottom: 1.5rem; background: var(--color-white); border-radius: var(--radius-card);
        padding: 0.75rem 1rem; box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .export-label { font-size: 0.8rem; color: var(--color-secondary); font-weight: 600; margin-right: 0.25rem; }

    /* Statistiques */
    .stats-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem; margin-bottom: 1.5rem;
    }
    .stat-card {
        background: var(--color-white); border-radius: 14px; padding: 1rem 1.25rem;
        display: flex; align-items: center; gap: 1rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--color-primary); line-height: 1.2; }
    .stat-label { font-size: 0.75rem; color: var(--color-muted); text-transform: uppercase; letter-spacing: 0.3px; }

    .bg-amber-100   { background: #fef3c7; }
    .text-amber-600 { color: #d97706; }
    .bg-green-100   { background: #dcfce7; }
    .text-green-600 { color: #16a34a; }
    .bg-red-100     { background: #fee2e2; }
    .text-red-600   { color: #dc2626; }
    .bg-indigo-100  { background: #e0e7ff; }
    .text-indigo-600{ color: #4f46e5; }

    /* Filtres */
    .filter-card {
        background: var(--color-white); border-radius: var(--radius-card);
        padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; min-width: 160px; }
    .filter-label { font-size: 0.75rem; font-weight: 600; color: var(--color-secondary); letter-spacing: 0.3px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    .filter-select, .filter-input {
        width: 100%; padding: 0.6rem 1rem; border: 2px solid var(--color-border);
        border-radius: 10px; background: var(--color-bg-light); font-size: 0.95rem;
        color: var(--color-primary); transition: all 0.3s; outline: none; appearance: none;
    }
    .filter-input { padding-left: 2.2rem; }
    .filter-select:focus, .filter-input:focus { border-color: var(--color-primary-hover); background: var(--color-white); box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .search-input-wrap { position: relative; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-muted); font-size: 0.9rem; pointer-events: none; }
    .filter-result-count { margin-top: 0.75rem; font-size: 0.9rem; color: var(--color-secondary); padding-top: 0.5rem; border-top: 1px solid var(--color-border); }

    /* Tableau */
    .table-card {
        background: var(--color-white); border-radius: var(--radius-card);
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9; overflow: hidden;
    }
    .table-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 0.5rem; }
    .table-title { font-size: 1.05rem; font-weight: 600; color: var(--color-primary); display: flex; align-items: center; gap: 8px; }
    .badge-count { background: var(--color-border); color: var(--color-secondary); padding: 0.1rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .table-actions { font-size: 0.8rem; }
    .text-muted { color: var(--color-muted); }
    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: var(--color-secondary); }
    .data-table thead th {
        text-align: left; padding: 0.8rem 1.25rem; font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-muted);
        background: var(--color-bg-light); border-bottom: 2px solid var(--color-border);
    }
    .data-table thead th.text-right { text-align: right; }
    .data-table thead th.text-center { text-align: center; }
    .data-table tbody td { padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: var(--color-bg-light); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .text-right { text-align: right; }
    .data-table .text-center { text-align: center; }
    .empty-cell { text-align: center; padding: 3rem 1.5rem; color: var(--color-muted); }
    .table-footer { padding: 0.8rem 1.5rem; background: var(--color-bg-light); border-top: 1px solid var(--color-border); text-align: right; font-size: 0.8rem; color: var(--color-secondary); }
    .cell-eleve { display: flex; align-items: center; gap: 0.75rem; }
    .avatar { width: 36px; height: 36px; border-radius: 10px; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; }
    .cell-primary { font-weight: 600; color: var(--color-primary); display: block; }
    .cell-secondary { font-size: 0.75rem; color: var(--color-muted); }
    .motif-cell { display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--color-secondary); }
    .date-cell { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 2px; color: var(--color-secondary); font-size: 0.85rem; }
    .statut-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-success { background: #dcfce7; color: #065f46; }
    .badge-danger  { background: #fee2e2; color: #991b1b; }
    .badge-neutral { background: #f1f5f9; color: #475569; }

    /* Actions */
    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: center; gap: 0.4rem; }
    .action-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 9px; color: var(--color-muted);
        text-decoration: none; transition: all 0.2s; background: #f8fafc;
        border: 1px solid var(--color-border); cursor: pointer; font-size: 0.95rem;
    }
    .action-icon:hover { transform: translateY(-1px); }
    .action-icon.success { color: #16a34a; border-color: #bbf7d0; background: #f0fdf4; }
    .action-icon.success:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
    .action-icon.danger { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
    .action-icon.danger:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
    .action-icon:hover { background: #e0e7ff; color: var(--color-primary-hover); border-color: #c7d2fe; }

    /* Modales */
    .modal-overlay {
        position: fixed; inset: 0; z-index: 100;
        display: flex; align-items: center; justify-content: center;
        background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px);
        padding: 1rem;
    }
    .modal-content {
        background: #fff; border-radius: 20px; max-width: 520px; width: 100%;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    }
    .modal-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border);
    }
    .modal-header-success { background: #f0fdf4; border-radius: 20px 20px 0 0; }
    .modal-header-danger  { background: #fef2f2; border-radius: 20px 20px 0 0; }
    .modal-close { background: none; border: none; color: var(--color-muted); font-size: 1.25rem; cursor: pointer; padding: 0.25rem; border-radius: 8px; transition: all 0.2s; }
    .modal-close:hover { background: var(--color-bg-light); color: var(--color-primary); }
    .modal-body { padding: 1.5rem; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; margin-top: 1rem; border-top: 1px solid var(--color-border); }
    .btn-modal-cancel, .btn-modal-success, .btn-modal-danger {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.7rem 1.25rem; border-radius: 10px; font-weight: 600;
        font-size: 0.9rem; cursor: pointer; transition: all 0.2s; border: none;
    }
    .btn-modal-cancel { background: #fff; color: var(--color-secondary); border: 1.5px solid var(--color-border); }
    .btn-modal-cancel:hover { background: var(--color-bg-light); }
    .btn-modal-success { background: #16a34a; color: #fff; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); }
    .btn-modal-success:hover { background: #15803d; transform: translateY(-1px); }
    .btn-modal-danger { background: #dc2626; color: #fff; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
    .btn-modal-danger:hover { background: #b91c1c; transform: translateY(-1px); }

    .info-box {
        padding: 0.9rem 1rem; border-radius: 12px; margin-bottom: 1rem;
    }
    .info-box-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .info-box-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

    .form-field { margin-bottom: 1rem; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--color-primary); margin-bottom: 0.5rem; }
    .required { color: #dc2626; }
    .form-select, .form-input, .form-textarea {
        width: 100%; padding: 0.7rem 1rem; border: 1.5px solid var(--color-border);
        border-radius: 10px; background: #fff; font-size: 0.95rem;
        color: var(--color-primary); transition: all 0.2s; outline: none;
    }
    .form-textarea { resize: vertical; min-height: 100px; font-family: inherit; }
    .form-select:focus, .form-input:focus, .form-textarea:focus {
        border-color: var(--color-primary-hover);
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }

    .empty-state { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
    .empty-state i { font-size: 3rem; color: #cbd5e1; }
    .pagination-container { padding: 0.8rem 1.5rem; border-top: 1px solid #f1f5f9; }

    /* Responsive */
    @media (max-width: 768px) {
        .index-header { flex-direction: column; align-items: stretch; }
        .header-actions { width: 100%; flex-direction: column; }
        .header-actions .btn-primary, .header-actions .btn-print, .header-actions .btn-secondary { width: 100%; justify-content: center; }
        .export-bar { flex-direction: column; align-items: stretch; }
        .btn-export { justify-content: center; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-form { flex-direction: column; }
        .filter-field { min-width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }
        .table-responsive { overflow-x: visible; }
        .data-table thead { display: none; }
        .data-table tbody tr {
            display: block; background: var(--color-white); border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.25rem; padding: 1rem 1.25rem;
        }
        .data-table tbody td {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; gap: 0.5rem;
        }
        .data-table tbody td:last-child { border-bottom: none; }
        .data-table tbody td::before {
            content: attr(data-label); font-weight: 600; color: var(--color-muted);
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.3px;
            min-width: 100px; flex-shrink: 0;
        }
        .data-table tbody td.text-right,
        .data-table tbody td.text-center { text-align: left; justify-content: space-between; }
        .action-icons { justify-content: flex-start; }
        .badge-count { display: none; }
        .table-header { flex-direction: column; align-items: flex-start; }
        .table-footer { text-align: center; }
        .modal-actions { flex-direction: column-reverse; }
        .btn-modal-cancel, .btn-modal-success, .btn-modal-danger { width: 100%; justify-content: center; }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .stat-card { padding: 0.75rem; }
        .stat-icon { width: 36px; height: 36px; font-size: 1rem; }
        .stat-value { font-size: 1.1rem; }
        .index-title { font-size: 1.3rem; }
    }

    /* Impression */
    @media print {
        .index-header, .filter-card, .pagination-container, .stats-grid,
        .table-footer, .header-actions, .export-bar, .action-cell { display: none !important; }
        .table-card { box-shadow: none; }
        .data-table { min-width: 0; font-size: 0.75rem; }
        .data-table thead th { background: #f8fafc !important; color: #1e293b !important; }
        .avatar { display: none !important; }
    }
</style>

<script>
function demandeAvanceIndex() {
    return {
        accepterModal: { open: false, demandeId: null, userName: '', montant: 0 },
        refuserModal:  { open: false, demandeId: null, userName: '' },

        openAccepter(id, userName, montant) {
            this.accepterModal = { open: true, demandeId: id, userName, montant };
        },
        closeAccepter() {
            this.accepterModal.open = false;
        },
        openRefuser(id, userName) {
            this.refuserModal = { open: true, demandeId: id, userName };
        },
        closeRefuser() {
            this.refuserModal.open = false;
        }
    };
}
</script>
@endsection