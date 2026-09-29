@extends('layouts.admin')

@section('content')
<div class="index-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Cours</strong></h1>
            <p class="index-subtitle">Gérez les cours et leurs assignations</p>
        </div>
        <a href="{{ route('admin.cours.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nouveau cours
        </a>
    </div>

    {{-- Cartes statistiques --}}
    @php
        $totalAssignations = $cours->sum('salles_count');
        $categoriesUtilisees = $categories->whereIn('id', $cours->pluck('categorie_id')->unique())->count();
    @endphp
    <div class="stats-grid">
        <div class="stat-card border-indigo">
            <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
            <div>
                <p class="stat-label">Total cours</p>
                <p class="stat-value">{{ $cours->total() }}</p>
            </div>
        </div>
        <div class="stat-card border-purple">
            <div class="stat-icon"><i class="fa-solid fa-folder"></i></div>
            <div>
                <p class="stat-label">Catégories utilisées</p>
                <p class="stat-value">{{ $categoriesUtilisees }}</p>
            </div>
        </div>
        <div class="stat-card border-orange">
            <div class="stat-icon"><i class="fa-solid fa-diagram-project"></i></div>
            <div>
                <p class="stat-label">Assignations totales</p>
                <p class="stat-value">{{ $totalAssignations }}</p>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.cours.index') }}" class="filter-form">
            <div class="filter-field">
                <label class="filter-label">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un cours..."
                       class="filter-input">
            </div>
            <div class="filter-field">
                <label class="filter-label">Catégorie</label>
                <select name="categorie_id" class="filter-select">
                    <option value="">Toutes</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('categorie_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            @if(request('search') || request('categorie_id'))
                <a href="{{ route('admin.cours.index') }}" class="btn-reset">
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Tableau / Cartes --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Assignations</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cours as $cour)
                <tr>
                    <td data-label="Nom">
                        <div class="course-cell">
                            <div class="avatar">{{ strtoupper(substr($cour->nom, 0, 2)) }}</div>
                            <span class="course-name">{{ $cour->nom }}</span>
                        </div>
                    </td>
                    <td data-label="Catégorie">
                        @if($cour->categorie)
                            <span class="badge badge-purple">{{ $cour->categorie->nom }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td data-label="Assignations">
                        <span class="badge badge-gray">{{ $cour->salles_count }} salle(s)</span>
                    </td>
                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            <a href="{{ route('admin.cours.show', $cour) }}" class="action-icon" title="Voir">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.cours.edit', $cour) }}" class="action-icon" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="{{ route('admin.cours.assign', $cour) }}" class="action-icon" title="Assigner">
                                <i class="fa-solid fa-calendar-plus"></i>
                            </a>
                            <form action="{{ route('admin.cours.destroy', $cour) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer ce cours ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-icon danger" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-cell">
                        @if(request('search') || request('categorie_id'))
                            Aucun cours trouvé avec ces critères.
                        @else
                            Aucun cours enregistré.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $cours->appends(request()->query())->links() }}
    </div>
</div>

<style>
    /* ==== Styles locaux (sans animations) ==== */
    .index-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .index-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 1.25rem;
        margin-bottom: 2rem;
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

    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 1rem;
        border-left: 4px solid;
    }
    .border-indigo { border-left-color: #667eea; }
    .border-purple { border-left-color: #a855f7; }
    .border-orange { border-left-color: #f97316; }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .border-indigo .stat-icon { background: #eef2ff; color: #667eea; }
    .border-purple .stat-icon { background: #faf5ff; color: #a855f7; }
    .border-orange .stat-icon { background: #fff7ed; color: #f97316; }
    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        font-weight: 600;
    }
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0.25rem;
    }

    .filter-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        padding: 1.25rem;
        margin-bottom: 2rem;
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem;
    }
    .filter-field {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        flex: 1;
        min-width: 180px;
    }
    .filter-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }
    .filter-input,
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
    .filter-input:focus,
    .filter-select:focus {
        border-color: #667eea;
        background: white;
    }
    .btn-filter,
    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.7rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        border: none;
    }
    .btn-filter {
        background: #1e293b;
        color: white;
    }
    .btn-filter:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }
    .btn-reset {
        background: white;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
    }
    .btn-reset:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }

    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        overflow: hidden;
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

    .course-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #e0e7ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    .course-name {
        font-weight: 600;
        color: #1e293b;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-purple {
        background: #f3e8ff;
        color: #7e22ce;
    }
    .badge-gray {
        background: #f1f5f9;
        color: #475569;
    }
    .text-muted { color: #94a3b8; }

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

    .pagination-wrapper {
        margin-top: 1.5rem;
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
        .course-cell {
            justify-content: flex-start;
        }
    }
</style>
@endsection