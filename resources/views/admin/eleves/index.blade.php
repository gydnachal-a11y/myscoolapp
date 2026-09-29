@extends('layouts.admin')

@section('page_title', 'Élèves')
@section('page_subtitle', 'Gérez les élèves inscrits')

@section('content')
<div class="index-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
            <i class="fa-regular fa-check-circle mt-0.5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
            <i class="fa-regular fa-circle-exclamation mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Élèves</strong></h1>
            <p class="index-subtitle">Gérez les élèves inscrits</p>
        </div>
        <a href="{{ route('admin.eleves.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nouvel élève
        </a>
    </div>

    {{-- Cartes statistiques --}}
    <div class="stats-grid">
        <div class="stat-card border-indigo">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <p class="stat-label">Total élèves</p>
                <p class="stat-value">{{ $totalEleves }}</p>
            </div>
        </div>
        <div class="stat-card border-blue">
            <div class="stat-icon"><i class="fa-solid fa-mars"></i></div>
            <div>
                <p class="stat-label">Garçons</p>
                <p class="stat-value">{{ $totalGarcons }}</p>
            </div>
        </div>
        <div class="stat-card border-pink">
            <div class="stat-icon"><i class="fa-solid fa-venus"></i></div>
            <div>
                <p class="stat-label">Filles</p>
                <p class="stat-value">{{ $totalFilles }}</p>
            </div>
        </div>
    </div>

    {{-- Recherche et filtre --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.eleves.index') }}" class="filter-form">
            <div class="filter-field">
                <label for="search" class="filter-label">Recherche</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="Nom, prénom..." class="filter-input">
            </div>
            <div class="filter-field">
                <label for="sexe" class="filter-label">Sexe</label>
                <select name="sexe" id="sexe" class="filter-select">
                    <option value="">Tous</option>
                    <option value="M" {{ request('sexe') == 'M' ? 'selected' : '' }}>Masculin</option>
                    <option value="F" {{ request('sexe') == 'F' ? 'selected' : '' }}>Féminin</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            @if(request('search') || request('sexe'))
                <a href="{{ route('admin.eleves.index') }}" class="btn-reset">
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
                    <th scope="col">Nom complet</th>
                    <th scope="col">Sexe</th>
                    <th scope="col">Âge</th>
                    <th scope="col">Date naissance</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eleves as $eleve)
                <tr>
                    <td data-label="Nom complet">
                        <div class="eleve-cell">
                            <div class="avatar" aria-hidden="true">
                                {{ strtoupper(substr($eleve->nom, 0, 1) . substr($eleve->prenom, 0, 1)) }}
                            </div>
                            <span class="cell-primary">{{ $eleve->nom }} {{ $eleve->postnom }} {{ $eleve->prenom }}</span>
                        </div>
                    </td>
                    <td data-label="Sexe">
                        @if($eleve->sexe === 'M')
                            <span class="badge badge-blue">Garçon</span>
                        @else
                            <span class="badge badge-pink">Fille</span>
                        @endif
                    </td>
                    <td data-label="Âge">{{ $eleve->date_naissance->age ?? '?' }} ans</td>
                    <td data-label="Date naissance">{{ $eleve->date_naissance ? $eleve->date_naissance->format('d/m/Y') : 'Inconnue' }}</td>
                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            <a href="{{ route('admin.eleves.show', $eleve) }}" class="action-icon" title="Voir" aria-label="Voir l'élève">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.eleves.edit', $eleve) }}" class="action-icon" title="Modifier" aria-label="Modifier l'élève">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.eleves.destroy', $eleve) }}" method="POST" class="inline-form"
                                  onsubmit="return confirm('Supprimer définitivement cet élève ? Cette action est irréversible.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-icon danger" title="Supprimer" aria-label="Supprimer l'élève">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-cell">
                        @if(request('search') || request('sexe'))
                            Aucun élève trouvé avec ces critères.
                        @else
                            Aucun élève enregistré.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $eleves->appends(request()->query())->links() }}
    </div>
</div>

<style>
    /* ==== Styles locaux premium (sans animations) ==== */
    .index-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .index-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
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
        border: none;
        cursor: pointer;
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
    .border-blue { border-left-color: #3b82f6; }
    .border-pink { border-left-color: #ec4899; }
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
    .border-blue .stat-icon { background: #dbeafe; color: #3b82f6; }
    .border-pink .stat-icon { background: #fce7f3; color: #ec4899; }
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

    .eleve-cell {
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
    .cell-primary {
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
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-pink { background: #fce7f3; color: #be185d; }

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
        .eleve-cell {
            justify-content: flex-start;
        }
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .stat-card {
            padding: 1rem;
        }
    }
</style>
@endsection