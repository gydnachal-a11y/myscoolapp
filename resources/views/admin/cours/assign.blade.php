@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    {{-- CDN Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Assignations du cours <strong>{{ $cour->nom }}</strong></h1>
            <p class="form-subtitle">Gérez les salles, horaires et titulaires pour ce cours</p>
        </div>
        <a href="{{ route('admin.cours.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Retour aux cours
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Bouton toggle formulaire --}}
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <button type="button" id="toggleFormBtn" class="btn-toggle">
            <i class="bi bi-plus-circle"></i> Ajouter une assignation
        </button>
        <span class="text-sm text-gray-500">
            <i class="bi bi-info-circle"></i> Sélectionnez les jours pour définir le nombre de séances
        </span>
    </div>

    {{-- Formulaire d'ajout (masqué par défaut) --}}
    <div id="assignForm" class="form-container" style="display:none;">
        <h2 class="section-title">Ajouter une assignation</h2>
        <form action="{{ route('admin.cours.assign.store', $cour) }}" method="POST" class="assign-form-grid" novalidate>
            @csrf

            {{-- Salle de classe --}}
            <div class="form-field">
                <label for="salle_classe_id" class="field-label">Salle de classe <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="salle_classe_id" id="salle_classe_id" required class="@error('salle_classe_id') is-invalid @enderror">
                        <option value="">Choisir...</option>
                        @foreach($salles as $salle)
                            <option value="{{ $salle->id }}">{{ $salle->nom }} ({{ optional($salle->section)->nom ?? '?' }})</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('salle_classe_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Libellé --}}
            <div class="form-field">
                <label for="libelle_id" class="field-label">Libellé <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="libelle_id" id="libelle_id" required class="@error('libelle_id') is-invalid @enderror">
                        @foreach($libelles as $l)
                            <option value="{{ $l->id }}">{{ $l->nom }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('libelle_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Volume horaire du cours --}}
            <div class="form-field">
                <label for="nombre_heure_id" class="field-label">Volume horaire (heures) <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="nombre_heure_id" id="nombre_heure_id" required class="@error('nombre_heure_id') is-invalid @enderror">
                        @foreach($nombreHeures as $nh)
                            <option value="{{ $nh->id }}">{{ $nh->libelle }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('nombre_heure_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Pondération --}}
            <div class="form-field">
                <label for="ponderation_id" class="field-label">Pondération <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="ponderation_id" id="ponderation_id" required class="@error('ponderation_id') is-invalid @enderror">
                        @foreach($ponderations as $p)
                            <option value="{{ $p->id }}">{{ $p->nom }} ({{ $p->valeur }})</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('ponderation_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Titulaire --}}
            <div class="form-field">
                <label for="titulaire_id" class="field-label">Titulaire</label>
                <div class="select-wrap">
                    <select name="titulaire_id" id="titulaire_id">
                        <option value="">-- Aucun --</option>
                        @foreach($titulaires as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
            </div>

            {{-- Jours de cours --}}
            <div class="form-field">
                <label class="field-label">Jours de cours <span style="color:#ef4444;">*</span></label>
                <div class="day-chips">
                    @foreach($jours as $jour)
                        <label class="day-chip">
                            <input type="checkbox" name="jours[]" value="{{ $jour }}" class="day-chip-input" onchange="updateSeances()">
                            <span class="day-chip-label">{{ $jour }}</span>
                        </label>
                    @endforeach
                </div>
                <small class="text-gray-500">Nombre de séances : <span id="seances-count">0</span></small>
            </div>

            {{-- Créneau horaire --}}
            <div class="form-field">
                <label for="creneau_horaire_id" class="field-label">Créneau horaire <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="creneau_horaire_id" id="creneau_horaire_id" required class="@error('creneau_horaire_id') is-invalid @enderror">
                        <option value="">Choisir...</option>
                        @foreach($creneauxHoraires as $creneau)
                            <option value="{{ $creneau->id }}" {{ old('creneau_horaire_id') == $creneau->id ? 'selected' : '' }}>
                                {{ $creneau->libelle }} ({{ $creneau->heure_debut->format('H:i') }} - {{ $creneau->heure_fin->format('H:i') }})
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('creneau_horaire_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Boutons --}}
            <div class="button-group">
                <a href="{{ route('admin.cours.index') }}" class="btn-cancel">Annuler</a>
                <button type="submit" class="btn-submit">Ajouter <i class="bi bi-plus-lg"></i></button>
            </div>
        </form>
    </div>

    {{-- Statistiques --}}
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-diagram-3"></i></div>
            <div class="stat-info">
                <span class="stat-label">Assignations</span>
                <span class="stat-value">{{ $assignations->count() }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-door-open"></i></div>
            <div class="stat-info">
                <span class="stat-label">Salles</span>
                <span class="stat-value">{{ $assignations->pluck('salle_id')->unique()->count() }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
            <div class="stat-info">
                <span class="stat-label">Titulaires</span>
                <span class="stat-value">{{ $assignations->pluck('titulaire_id')->filter()->unique()->count() }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-clock"></i></div>
            <div class="stat-info">
                <span class="stat-label">Heures hebdo.</span>
                <span class="stat-value">{{ $totalHebdo ?? 0 }} h</span>
            </div>
        </div>
    </div>

    {{-- Liste des assignations existantes --}}
    <div class="table-container">
        <div class="table-header">
            <h2 class="section-title">Assignations existantes</h2>
        </div>
        <div class="table-wrapper">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Salle</th>
                        <th style="width: 7%;">Libellé</th>
                        <th style="width: 7%;">Volume</th>
                        <th style="width: 9%;">Pondération</th>
                        <th style="width: 10%;">Titulaire</th>
                        <th style="width: 14%;">Jours</th>
                        <th style="width: 15%;">Créneau</th>
                        <th style="width: 5%;">Séances</th>
                        <th style="width: 7%;">Total</th>
                        <th style="width: 8%;" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignations as $assign)
                        @php
                            $dureeSeance = 0;
                            if ($assign->creneauHoraire) {
                                $dureeSeance = $assign->creneauHoraire->heure_debut->diffInMinutes($assign->creneauHoraire->heure_fin) / 60;
                            }
                            $joursArray = $assign->jours ? explode(',', $assign->jours) : [];
                            $nbSeances = $assign->nombre_seances ?? count($joursArray);
                            $totalHebdoAssign = $dureeSeance * $nbSeances;
                        @endphp
                        <tr>
                            <td data-label="Salle" class="cell-salle">
                                <span class="cell-primary">{{ optional($assign->salle)->nom ?? '—' }}</span>
                                @if(optional($assign->salle)->section)
                                    <span class="text-muted small">({{ optional($assign->salle->section)->nom }})</span>
                                @endif
                            </td>
                            <td data-label="Libellé">{{ optional($assign->libelle)->nom ?? '—' }}</td>
                            <td data-label="Volume">
                                <span class="badge badge-blue">{{ optional($assign->nombreHeure)->libelle ?? '—' }}</span>
                            </td>
                            <td data-label="Pondération">
                                <span class="badge badge-purple">{{ optional($assign->ponderation)->nom ?? '—' }} ({{ optional($assign->ponderation)->valeur ?? '' }})</span>
                            </td>
                            <td data-label="Titulaire" class="cell-titulaire">
                                @if($assign->titulaire)
                                    <span class="avatar-sm">{{ strtoupper(substr($assign->titulaire->name,0,1)) }}</span>
                                    {{ $assign->titulaire->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Jours">
                                @if($joursArray)
                                    <div class="badge-group">
                                        @foreach($joursArray as $jour)
                                            <span class="badge badge-day">{{ $jour }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Créneau">
                                @if($assign->creneauHoraire)
                                    <span class="creneau-text">{{ $assign->creneauHoraire->libelle }} ({{ $assign->creneauHoraire->heure_debut->format('H:i') }} - {{ $assign->creneauHoraire->heure_fin->format('H:i') }})</span>
                                @else
                                    {{ $assign->duree ?? '—' }}
                                @endif
                            </td>
                            <td data-label="Séances/sem.">{{ $nbSeances }}</td>
                            <td data-label="Total/sem.">{{ $totalHebdoAssign > 0 ? $totalHebdoAssign . ' h' : '—' }}</td>
                            <td data-label="Actions" class="text-right action-cell">
                                <div class="action-icons">
                                    <a href="{{ route('admin.cours.assign.edit', $assign->id) }}" class="action-icon" title="Modifier">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('admin.cours.assign.destroy', $assign->id) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette assignation ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-icon danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty-cell">Aucune assignation pour ce cours.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #4f46e5;
        --primary-hover: #6366f1;
        --danger: #ef4444;
        --gray-100: #f8fafc;
        --gray-200: #e2e8f0;
        --text-primary: #1e293b;
        --text-muted: #94a3b8;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .form-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.5px;
        line-height: 1.2;
    }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: var(--text-muted); font-size: 0.85rem; margin-top: 0.2rem; }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--primary);
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    .back-link:hover { color: var(--primary-hover); }

    .alert-success {
        background: #f0fdf4;
        color: #16a34a;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        border-left: 3px solid #22c55e;
    }

    .btn-toggle {
        background: #1e293b;
        color: white;
        border: none;
        padding: 0.7rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }
    .btn-toggle:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(79,70,229,0.3);
    }

    .form-container {
        background: white;
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: var(--primary); }

    .assign-form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    @media (min-width: 768px) {
        .assign-form-grid { grid-template-columns: repeat(3, 1fr); }
        .button-group { grid-column: 1 / -1; }
    }

    .form-field { position: relative; }

    .field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .select-wrap { position: relative; }
    .select-wrap select {
        width: 100%;
        padding: 0.7rem 2.5rem 0.7rem 0;
        border: none;
        border-bottom: 2px solid var(--gray-200);
        background: transparent;
        font-size: 0.95rem;
        color: var(--text-primary);
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        cursor: pointer;
    }
    .select-wrap select:focus { border-bottom-color: var(--primary); }
    .select-wrap select.is-invalid { border-bottom-color: var(--danger); }
    .select-wrap .select-icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1rem;
        pointer-events: none;
        transition: color 0.3s;
    }
    .select-wrap select:focus ~ .select-icon { color: var(--primary); }
    .select-wrap .line-focus {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--primary), #764ba2);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .select-wrap select:focus ~ .line-focus { width: 100%; }

    /* Chips jours */
    .day-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .day-chip { cursor: pointer; }
    .day-chip-input { display: none; }
    .day-chip-label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.4rem 1rem;
        border-radius: 9999px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #475569;
        background: var(--gray-100);
        border: 1.5px solid var(--gray-200);
        transition: all 0.2s ease;
    }
    .day-chip-input:checked + .day-chip-label {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 4px 8px rgba(79,70,229,0.3);
    }
    .day-chip-label:hover {
        border-color: var(--primary);
        background: #eef2ff;
        color: var(--primary);
    }
    .day-chip-input:checked + .day-chip-label:hover {
        background: var(--primary);
        color: white;
    }

    .button-group {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--gray-200);
        margin-top: 0.5rem;
    }
    .btn-submit {
        padding: 0.7rem 1.75rem;
        background: #1e293b;
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s;
        outline: none;
    }
    .btn-submit:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 8px 16px rgba(79,70,229,0.3); }
    .btn-cancel {
        padding: 0.7rem 1.25rem;
        border: 1.5px solid var(--gray-200);
        border-radius: 10px;
        color: #64748b;
        background: white;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-cancel:hover { border-color: var(--primary); color: var(--primary); background: var(--gray-100); }

    .error-text { color: var(--danger); font-size: 0.8rem; margin-top: 0.3rem; }

    /* Statistiques */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: var(--primary);
        font-size: 1.25rem;
    }
    .stat-info { flex: 1; min-width: 0; }
    .stat-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        font-weight: 600;
        display: block;
    }
    .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-top: 0.25rem;
    }

    /* Table */
    .table-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        padding: 1.5rem;
    }
    .table-wrapper { overflow-x: auto; }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        color: #475569;
        min-width: 1000px;
        table-layout: fixed;
    }
    .custom-table thead th {
        text-align: left;
        padding: 0.75rem 0.5rem;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        background: var(--gray-100);
        border-bottom: 2px solid var(--gray-200);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .custom-table tbody td {
        padding: 0.75rem 0.5rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .custom-table tbody tr:hover { background: var(--gray-100); }
    .text-right { text-align: right; }

    .cell-salle { max-width: 0; }
    .cell-titulaire { max-width: 0; }

    .cell-primary { font-weight: 600; color: var(--text-primary); }
    .text-muted { color: var(--text-muted); font-size: 0.8rem; }

    .badge {
        display: inline-block;
        padding: 0.2rem 0.4rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 600;
        margin-right: 3px;
        white-space: nowrap;
    }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-purple { background: #f3e8ff; color: #7e22ce; }
    .badge-day { background: #e0e7ff; color: #4338ca; }
    .creneau-text { white-space: nowrap; }

    .avatar-sm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        margin-right: 4px;
    }

    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: flex-end; gap: 0.4rem; }
    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .action-icon:hover { background: var(--gray-200); color: var(--text-primary); }
    .action-icon.danger:hover { background: #fee2e2; color: var(--danger); }
    .inline-form { display: inline; }

    .empty-cell { text-align: center; padding: 2rem; color: var(--text-muted); }

    /* Responsive */
    @media (max-width: 768px) {
        .header-container { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .form-container { padding: 1.5rem; }
        .table-container { padding: 1rem; }
        .assign-form-grid { grid-template-columns: 1fr; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .custom-table { min-width: 800px; }
        .custom-table thead th { padding: 0.6rem 0.4rem; font-size: 0.7rem; }
        .custom-table tbody td { padding: 0.6rem 0.4rem; font-size: 0.8rem; }
    }

    @media (max-width: 640px) {
        .table-container { background: transparent; box-shadow: none; padding: 0; }
        .table-wrapper { overflow-x: visible; }
        .custom-table,
        .custom-table tbody,
        .custom-table tr,
        .custom-table td {
            display: block;
            width: 100%;
            box-sizing: border-box;
            min-width: 0; /* Écrase min-width défini précédemment */
            table-layout: auto; /* Réinitialise table-layout pour mobile */
        }
        .custom-table thead { display: none; }
        .custom-table tr {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-bottom: 1.25rem;
            padding: 1rem;
            border: 1px solid #f1f5f9;
            overflow: hidden; /* Empêche tout débordement */
        }
        .custom-table td {
            border: none;
            padding: 0.5rem 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px dashed #f1f5f9;
            white-space: normal; /* Autorise le retour à la ligne */
            overflow: visible;
            text-overflow: clip;
            word-break: break-word;
        }
        .custom-table td:last-child { border-bottom: none; }
        .custom-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 90px;
            flex-shrink: 0;
        }
        .custom-table td[data-label="Actions"] { justify-content: flex-end; }
        .custom-table td[data-label="Actions"]::before { display: none; }
        .action-icons { justify-content: flex-end; gap: 0.5rem; }
        .action-icon { width: 32px; height: 32px; font-size: 1rem; }
        .stats-grid { grid-template-columns: 1fr; }
        .day-chips { justify-content: center; }
        /* Force les enfants à ne pas dépasser */
        .custom-table td > * {
            max-width: 100%;
            min-width: 0;
        }
    }
</style>

<script>
    // Toggle formulaire
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleFormBtn');
        const form = document.getElementById('assignForm');

        toggleBtn.addEventListener('click', function() {
            if (form.style.display === 'none') {
                form.style.display = 'block';
                toggleBtn.innerHTML = '<i class="bi bi-x-circle"></i> Fermer le formulaire';
            } else {
                form.style.display = 'none';
                toggleBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Ajouter une assignation';
            }
        });
    });

    // Mise à jour du nombre de séances
    function updateSeances() {
        const checkboxes = document.querySelectorAll('.day-chip-input:checked');
        document.getElementById('seances-count').textContent = checkboxes.length;
    }
</script>
@endsection