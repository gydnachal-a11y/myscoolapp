@extends('layouts.admin')

@section('page_title', 'Tranches scolaires')
@section('page_subtitle', 'Gérez les tranches de paiement par année')

@section('content')
<div class="index-container">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title">
                <strong>Tranches scolaires</strong>
                <span class="count-badge">{{ $tranches->total() }}</span>
            </h1>
            <p class="index-subtitle">Gérez les tranches de paiement par année</p>
        </div>
        <div class="header-actions">
            <form action="{{ route('admin.tranches-scolaires.generer') }}" method="POST" class="inline-form">
                @csrf
                <button type="submit" class="btn-secondary"
                        @if($tranches->total() > 0)
                            onclick="return confirm('Des tranches existent déjà. Régénérer va les remplacer. Continuer ?')"
                        @endif>
                    <i class="fa-solid fa-bolt" aria-hidden="true"></i> Générer
                </button>
            </form>

            <form action="{{ route('admin.tranches-scolaires.vider') }}" method="POST" class="inline-form"
                  onsubmit="return confirm('Vider TOUTES les tranches scolaires ? Cette action est irréversible.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" @disabled($tranches->total() === 0)>
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Vider tout
                </button>
            </form>

            <a href="{{ route('admin.tranches-scolaires.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Ajouter une tranche
            </a>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="filter-card">
        <form method="GET" class="filter-form" role="search">
            <div class="filter-field">
                <label for="annee-filter" class="filter-label">Filtrer par année</label>
                <select id="annee-filter"
                        name="annee_scolaire_id"
                        onchange="this.form.submit()"
                        class="filter-select">
                    <option value="">Toutes les années</option>
                    @foreach($annees as $an)
                        <option value="{{ $an->id }}" @selected((int) $anneeId === $an->id)>
                            {{ $an->libelle }}
                            @if(!$an->cloturee) · en cours @endif
                        </option>
                    @endforeach
                </select>
            </div>

            @if($anneeId)
                <a href="{{ route('admin.tranches-scolaires.index') }}" class="btn-reset">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Effacer le filtre
                </a>
            @endif
        </form>
    </div>

    {{-- Tableau --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Tranche</th>
                    <th scope="col">Année</th>
                    <th scope="col">Période</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tranches as $t)
                <tr>
                    <td data-label="Tranche">
                        <span class="badge-tranche">
                            @if($t->est_en_cours)
                                <span class="badge-dot" title="Tranche en cours">●</span>
                            @endif
                            {{ $t->tranche }}
                        </span>
                    </td>

                    <td data-label="Année">
                        @if($t->anneeScolaire)
                            <span class="year-badge">{{ $t->anneeScolaire->libelle }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- ✅ Utilise l'accessor ->periode --}}
                    <td data-label="Période">{{ $t->periode }}</td>

                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            <a href="{{ route('admin.tranches-scolaires.edit', $t) }}"
                               class="action-icon"
                               aria-label="Modifier la tranche {{ $t->tranche }}"
                               title="Modifier">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                            </a>
                            <form action="{{ route('admin.tranches-scolaires.destroy', $t) }}"
                                  method="POST"
                                  class="inline-form"
                                  onsubmit="return confirm('Supprimer la tranche « {{ $t->tranche }} » ?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="action-icon danger"
                                        aria-label="Supprimer la tranche {{ $t->tranche }}"
                                        title="Supprimer">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-cell">
                        <div class="empty-state">
                            <i class="fa-regular fa-layer-group empty-icon" aria-hidden="true"></i>
                            <p class="empty-title">Aucune tranche enregistrée</p>
                            <p class="empty-text">
                                @if($anneeId)
                                    Aucune tranche pour cette année. Ajoutez-en une ou générez-les.
                                @else
                                    Commencez par générer les tranches de l'année en cours.
                                @endif
                            </p>
                            <div class="empty-actions">
                                <a href="{{ route('admin.tranches-scolaires.create') }}" class="btn-primary">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Ajouter une tranche
                                </a>
                                <form action="{{ route('admin.tranches-scolaires.generer') }}" method="POST" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn-secondary">
                                        <i class="fa-solid fa-bolt" aria-hidden="true"></i> Générer automatiquement
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tranches->hasPages())
        <div class="pagination-wrapper">
            {{ $tranches->links() }}
        </div>
    @endif
</div>

<style>
    .index-container { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }

    .index-header { display: flex; flex-direction: column; align-items: flex-start; gap: 1.25rem; margin-bottom: 2rem; }
    .index-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .index-title strong { font-weight: 800; }
    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 0 0.5rem;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; }
    .header-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }

    .btn-primary, .btn-secondary, .btn-danger, .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.7rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        color: #fff;
    }
    .btn-primary   { background: #1e293b; }
    .btn-secondary { background: #16a34a; }
    .btn-danger    { background: #dc2626; }

    .btn-primary:hover:not(:disabled),
    .btn-secondary:hover:not(:disabled),
    .btn-danger:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .btn-primary:hover:not(:disabled)   { background: #667eea; }
    .btn-secondary:hover:not(:disabled) { background: #15803d; }
    .btn-danger:hover:not(:disabled)    { background: #b91c1c; }

    .btn-primary:disabled,
    .btn-secondary:disabled,
    .btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }

    .btn-reset {
        background: transparent;
        color: #64748b;
        border: 1.5px solid #e2e8f0;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }
    .btn-reset:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }

    .inline-form { display: inline; }

    .filter-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        padding: 1.25rem;
        margin-bottom: 2rem;
    }
    .filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; min-width: 220px; }
    .filter-label { font-size: 0.8rem; font-weight: 600; color: #475569; }
    .filter-select {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 0.95rem;
        color: #1e293b;
        transition: border-color 0.3s;
        outline: none;
    }
    .filter-select:focus { border-color: #667eea; background: #fff; }

    .table-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th {
        text-align: left;
        padding: 0.9rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .data-table tbody td { padding: 0.9rem 1.5rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .text-right { text-align: right; }

    .badge-tranche {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        background: #e0e7ff;
        color: #4f46e5;
    }
    .badge-dot { color: #16a34a; font-size: 0.6rem; }

    .year-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.7rem;
        background: #f1f5f9;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 500;
        color: #334155;
    }

    .text-muted { color: #94a3b8; }

    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: flex-end; gap: 0.5rem; }
    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }
    .action-icon:hover { background: #e2e8f0; color: #1e293b; }
    .action-icon.danger:hover { background: #fee2e2; color: #dc2626; }

    .empty-cell { padding: 3rem 1rem; text-align: center; }
    .empty-state { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
    .empty-icon { font-size: 3rem; color: #cbd5e1; }
    .empty-title { font-size: 1.1rem; font-weight: 600; color: #334155; }
    .empty-text { color: #94a3b8; font-size: 0.9rem; max-width: 400px; }
    .empty-actions { display: flex; gap: 0.75rem; margin-top: 0.75rem; flex-wrap: wrap; justify-content: center; }

    .pagination-wrapper { margin-top: 1.5rem; }

    @media (max-width: 640px) {
        .table-card { background: transparent; box-shadow: none; border-radius: 0; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table thead { display: none; }
        .data-table tr {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-bottom: 1.25rem;
            padding: 1rem;
        }
        .data-table td {
            border: none;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .data-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 100px;
        }
        .data-table td[data-label="Actions"] { justify-content: flex-end; }
        .data-table td[data-label="Actions"]::before { display: none; }
        .action-icons { justify-content: flex-end; gap: 0.5rem; }
    }
</style>
@endsection