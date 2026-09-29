@extends('layouts.admin')

@section('content')
<div class="index-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Corbeille</strong> des années scolaires</h1>
            <p class="index-subtitle">Restaurez ou supprimez définitivement les années supprimées</p>
        </div>
        <a href="{{ route('admin.annees-scolaires.index') }}" class="btn-primary">
            <i class="fa-solid fa-arrow-left"></i> Retour aux années
        </a>
    </div>

    {{-- Tableau / Cartes --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Libellé</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($anneesSupprimees as $annee)
                <tr>
                    <td data-label="Libellé">
                        <span class="cell-primary">{{ $annee->libelle }}</span>
                    </td>
                    <td data-label="Début">
                        @if($annee->date_debut)
                            <i class="fa-regular fa-calendar"></i> {{ $annee->date_debut->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="Fin">
                        @if($annee->date_fin)
                            <i class="fa-regular fa-calendar"></i> {{ $annee->date_fin->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            <form action="{{ route('admin.annees-scolaires.restaurer', $annee->id) }}" method="POST" class="inline-form">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="action-icon restore" title="Restaurer">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.annees-scolaires.force-delete', $annee->id) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer définitivement cette année scolaire ? Cette action est irréversible.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-icon danger" title="Supprimer définitivement">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-cell">
                        <i class="fa-solid fa-trash-can"></i>
                        <p>Corbeille vide</p>
                        <small>Aucune année scolaire supprimée.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $anneesSupprimees->links() }}
    </div>
</div>

<style>
    /* ==== Styles locaux premium (cohérents avec les autres index) ==== */
    .index-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .index-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 1.25rem;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s ease forwards;
        opacity: 0;
    }
    .index-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.7rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s;
        background: #1e293b;
        color: white;
    }
    .btn-primary:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }

    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        animation: cardIn 0.8s 0.2s ease forwards;
        opacity: 0;
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: #475569;
    }
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
    .data-table tbody td {
        padding: 0.9rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    .text-right { text-align: right; }

    .cell-primary {
        font-weight: 600;
        color: #1e293b;
    }

    .action-cell {
        white-space: nowrap;
    }
    .action-icons {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
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
    .action-icon:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .action-icon.restore:hover {
        background: #dcfce7;
        color: #16a34a;
    }
    .action-icon.danger:hover {
        background: #fee2e2;
        color: #dc2626;
    }
    .inline-form { display: inline; }

    .empty-cell {
        text-align: center;
        padding: 3rem;
        color: #94a3b8;
    }
    .empty-cell i {
        font-size: 2.5rem;
        color: #e2e8f0;
        margin-bottom: 0.5rem;
    }
    .empty-cell p {
        font-size: 1.1rem;
        font-weight: 600;
        color: #64748b;
    }
    .empty-cell small {
        color: #cbd5e1;
    }

    .pagination-wrapper {
        margin-top: 1.5rem;
        animation: fadeUp 0.6s 0.4s ease forwards;
        opacity: 0;
    }

    /* ==== Transformation en cartes sur mobile ==== */
    @media (max-width: 640px) {
        .table-card {
            background: transparent;
            box-shadow: none;
            border-radius: 0;
        }
        .data-table,
        .data-table tbody,
        .data-table tr,
        .data-table td {
            display: block;
            width: 100%;
        }
        .data-table thead {
            display: none;
        }
        .data-table tr {
            background: white;
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
        .data-table td[data-label="Actions"] {
            justify-content: flex-end;
        }
        .data-table td[data-label="Actions"]::before {
            display: none;
        }
        .action-icons {
            justify-content: flex-end;
            gap: 0.5rem;
        }
    }
</style>
@endsection