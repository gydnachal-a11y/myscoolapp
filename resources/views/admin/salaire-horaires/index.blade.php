@extends('layouts.admin')

@section('content')
<div class="index-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Taux horaires</strong></h1>
            <p class="index-subtitle">Gérez les taux de rémunération horaire</p>
        </div>
        <a href="{{ route('admin.salaire-horaires.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nouveau taux
        </a>
    </div>

    {{-- Tableau / Cartes --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Taux (USD/h)</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tauxHoraires as $taux)
                <tr>
                    <td data-label="Taux (USD/h)">
                        <span class="cell-primary">{{ $taux->taux_usd }} $</span>
                    </td>
                    <td data-label="Statut">
                        @if($taux->actif)
                            <span class="badge badge-green"><i class="fa-solid fa-circle"></i> Actif</span>
                        @else
                            <span class="badge badge-gray"><i class="fa-regular fa-circle"></i> Inactif</span>
                        @endif
                    </td>
                    <td data-label="Créé le">{{ $taux->created_at->format('d/m/Y H:i') }}</td>
                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            @if(!$taux->actif)
                                <form action="{{ route('admin.salaire-horaires.activer', $taux) }}" method="POST" class="inline-form" title="Activer">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="action-icon success">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.salaire-horaires.edit', $taux) }}" class="action-icon" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.salaire-horaires.destroy', $taux) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer ce taux ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-icon danger" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-cell">Aucun taux enregistré.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    /* ==== Styles locaux premium (cohérents avec les autres index) ==== */
    .index-container {
        max-width: 1000px;
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
        padding: 0.7rem 1.5rem;
        background: #1e293b;
        color: white;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: all 0.3s;
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
        animation: cardIn 0.8s 0.5s ease forwards;
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

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #16a34a; }
    .badge-gray { background: #f1f5f9; color: #64748b; }

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
    .action-icon.success:hover {
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