@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    /* ============================================================
       FICHE EMPLOYÉ — Styles responsive
       ============================================================ */
    .fiche-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        padding: 1rem;
    }

    /* Header actions */
    .fiche-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .fiche-actions-title h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 0.15rem;
        line-height: 1.2;
    }
    .fiche-actions-title p {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
    }
    .fiche-actions-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .fiche-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.55rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        text-decoration: none;
    }
    .fiche-btn-outline {
        background: #fff;
        border-color: #d1d5db;
        color: #374151;
    }
    .fiche-btn-outline:hover { background: #f9fafb; }
    .fiche-btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    .fiche-btn-secondary:hover { background: #e5e7eb; }
    .fiche-btn-primary {
        background: #4f46e5;
        color: #fff;
    }
    .fiche-btn-primary:hover { background: #4338ca; }
    .fiche-btn-success {
        background: #059669;
        color: #fff;
    }
    .fiche-btn-success:hover { background: #047857; }
    .fiche-btn-danger {
        background: #dc2626;
        color: #fff;
    }
    .fiche-btn-danger:hover { background: #b91c1c; }

    /* Carte principale */
    .fiche-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    /* Header de la fiche */
    .fiche-header {
        padding: 1.25rem;
        background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
        border-bottom: 1px solid #e5e7eb;
    }
    .fiche-header-inner {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .fiche-header-user {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .fiche-avatar {
        width: 5rem;
        height: 5rem;
        border-radius: 9999px;
        object-fit: cover;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }
    .fiche-avatar-placeholder {
        width: 5rem;
        height: 5rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 2rem;
        font-weight: 700;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .fiche-header-info {
        min-width: 0;
        flex: 1;
    }
    .fiche-header-info h2 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #111827;
        margin: 0 0 0.25rem;
        line-height: 1.2;
        word-break: break-word;
    }
    .fiche-header-info p {
        font-size: 0.95rem;
        color: #4b5563;
        margin: 0 0 0.5rem;
    }
    .fiche-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .fiche-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 500;
        white-space: nowrap;
    }
    .fiche-tag-blue { background: #dbeafe; color: #1e40af; }
    .fiche-tag-amber { background: #fef3c7; color: #92400e; }

    .fiche-matricule {
        text-align: left;
    }
    .fiche-matricule-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        margin: 0 0 0.15rem;
    }
    .fiche-matricule-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    /* Sections */
    .fiche-section {
        padding: 1.25rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .fiche-section:last-of-type {
        border-bottom: none;
    }
    .fiche-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.05rem;
        font-weight: 600;
        color: #374151;
        margin: 0 0 1rem;
    }
    .fiche-section-title i {
        color: #4f46e5;
        font-size: 1.1rem;
    }

    /* Grille d'infos */
    .fiche-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .fiche-grid-item {
        min-width: 0;
    }
    .fiche-grid-item .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #6b7280;
        margin: 0 0 0.25rem;
        font-weight: 500;
    }
    .fiche-grid-item .value {
        font-size: 0.95rem;
        color: #1f2937;
        font-weight: 500;
        margin: 0;
        word-break: break-word;
    }

    /* Tableau des cours */
    .fiche-table-wrapper {
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        -webkit-overflow-scrolling: touch;
        position: relative;
    }
    .fiche-table-wrapper::after {
        content: '← Faites défiler →';
        display: none;
        text-align: center;
        font-size: 0.75rem;
        color: #9ca3af;
        padding: 0.5rem 0;
    }
    .fiche-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        min-width: 720px;
    }
    .fiche-table thead {
        background: #f9fafb;
    }
    .fiche-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #4b5563;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .fiche-table td {
        padding: 0.75rem 1rem;
        border-top: 1px solid #f3f4f6;
        color: #374151;
    }
    .fiche-table tbody tr:hover {
        background: #f9fafb;
    }
    .fiche-table tfoot {
        background: #f9fafb;
    }
    .fiche-table tfoot td {
        font-weight: 600;
        color: #1f2937;
    }
    .fiche-jours {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    .fiche-jour-badge {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 0.7rem;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Résumé salaire */
    .fiche-salaire-summary {
        margin-top: 1rem;
        padding: 1rem;
        background: #eef2ff;
        border-radius: 0.75rem;
    }
    .fiche-salaire-summary p {
        margin: 0;
        font-size: 0.9rem;
        color: #374151;
    }
    .fiche-salaire-summary p + p {
        margin-top: 0.4rem;
        font-size: 0.85rem;
        color: #6b7280;
    }
    .fiche-salaire-summary strong {
        color: #1f2937;
        font-weight: 700;
    }

    /* Alerte */
    .fiche-alert {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        border-radius: 0.5rem;
        background: #fffbeb;
        border-left: 4px solid #f59e0b;
        color: #92400e;
    }
    .fiche-alert i {
        font-size: 1.15rem;
        flex-shrink: 0;
        margin-top: 0.1rem;
    }
    .fiche-alert p {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Footer actions */
    .fiche-footer {
        padding: 1rem 1.25rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
    }
    .fiche-footer-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        width: 100%;
    }
    .fiche-footer-date {
        font-size: 0.8rem;
        color: #9ca3af;
        margin: 0;
        width: 100%;
        text-align: center;
    }

    /* ============================================================
       RESPONSIVE — TABLETTE (≥ 640px)
       ============================================================ */
    @media (min-width: 640px) {
        .fiche-wrapper { padding: 1.5rem; }
        .fiche-actions-title h1 { font-size: 1.75rem; }
        .fiche-header-inner {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
        .fiche-matricule { text-align: right; }
        .fiche-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem 2rem;
        }
        .fiche-grid-full {
            grid-column: 1 / -1;
        }
        .fiche-footer-buttons {
            width: auto;
        }
        .fiche-footer-date {
            width: auto;
            text-align: right;
        }
    }

    /* ============================================================
       RESPONSIVE — DESKTOP (≥ 1024px)
       ============================================================ */
    @media (min-width: 1024px) {
        .fiche-wrapper { padding: 2rem; }
    }

    /* ============================================================
       RESPONSIVE — MOBILE (≤ 639px)
       ============================================================ */
    @media (max-width: 639px) {
        .fiche-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .fiche-actions-buttons {
            width: 100%;
        }
        .fiche-actions-buttons .fiche-btn {
            flex: 1;
        }

        .fiche-header-user {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .fiche-header-info { text-align: center; }
        .fiche-tags { justify-content: center; }
        .fiche-matricule {
            text-align: center;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(79, 70, 229, 0.1);
        }

        .fiche-table-wrapper::after {
            display: block;
        }

        /* Barre d'actions sticky en bas sur mobile */
        .fiche-footer {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #e5e7eb;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
            z-index: 10;
            padding: 0.75rem 1rem;
        }
        .fiche-footer-buttons {
            flex-direction: column;
        }
        .fiche-footer-buttons .fiche-btn {
            width: 100%;
            justify-content: center;
            padding: 0.7rem 1rem;
        }
        .fiche-footer-date {
            text-align: center;
            font-size: 0.75rem;
        }
    }

    /* ============================================================
       IMPRESSION
       ============================================================ */
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        .fiche-wrapper { padding: 0; max-width: 100%; }
        .fiche-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            border-radius: 0 !important;
        }
        .fiche-header {
            background: #f9fafb !important;
        }
        .fiche-table-wrapper {
            overflow: visible !important;
        }
        .fiche-table-wrapper::after {
            display: none !important;
        }
        .fiche-footer {
            position: static !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="fiche-wrapper">

    {{-- Barre d'actions --}}
    <div class="fiche-actions no-print">
        <div class="fiche-actions-title">
            <h1>Fiche de l'employé</h1>
            <p>Informations personnelles et professionnelles</p>
        </div>
        <div class="fiche-actions-buttons">
            <a href="{{ route('admin.users.index') }}" class="fiche-btn fiche-btn-outline">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <button onclick="window.print()" class="fiche-btn fiche-btn-secondary">
                <i class="bi bi-printer"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- Carte principale --}}
    <div class="fiche-card">

        {{-- Header de la fiche --}}
        <div class="fiche-header">
            <div class="fiche-header-inner">

                <div class="fiche-header-user">
                    @if($user->photo)
                        <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" class="fiche-avatar">
                    @else
                        <div class="fiche-avatar-placeholder">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif

                    <div class="fiche-header-info">
                        <h2>{{ $user->name }}</h2>
                        <p>{{ optional($user->fonction)->nom ?? 'Fonction non définie' }}</p>
                        <div class="fiche-tags">
                            @if($user->section)
                                <span class="fiche-tag fiche-tag-blue">
                                    <i class="bi bi-building"></i> {{ $user->section->nom }}
                                </span>
                            @endif
                            <span class="fiche-tag fiche-tag-amber">
                                <i class="bi bi-shield-lock"></i> {{ ucfirst($user->role) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="fiche-matricule">
                    <p class="fiche-matricule-label">Matricule</p>
                    <p class="fiche-matricule-value">{{ $user->matricule ?? 'Non défini' }}</p>
                </div>

            </div>
        </div>

        {{-- Informations personnelles --}}
        <div class="fiche-section">
            <h3 class="fiche-section-title">
                <i class="bi bi-person-vcard"></i> Informations personnelles
            </h3>
            <div class="fiche-grid">
                <div class="fiche-grid-item">
                    <p class="label">Sexe</p>
                    <p class="value">{{ $user->sexe === 'M' ? 'Masculin' : ($user->sexe === 'F' ? 'Féminin' : '—') }}</p>
                </div>
                <div class="fiche-grid-item">
                    <p class="label">Email</p>
                    <p class="value">{{ $user->email }}</p>
                </div>
                <div class="fiche-grid-item">
                    <p class="label">Téléphone</p>
                    <p class="value">{{ $user->telephone ?? '—' }}</p>
                </div>
                <div class="fiche-grid-item">
                    <p class="label">Date de naissance</p>
                    <p class="value">{{ $user->date_naissance ? $user->date_naissance->format('d/m/Y') : '—' }}</p>
                </div>
                <div class="fiche-grid-item fiche-grid-full">
                    <p class="label">Adresse</p>
                    <p class="value">{{ $user->adresse ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Cours et salaire --}}
        <div class="fiche-section">
            <h3 class="fiche-section-title">
                <i class="bi bi-book"></i> Cours et salaire
            </h3>

            @if(isset($assignations) && $assignations->isNotEmpty())

                <div class="fiche-table-wrapper">
                    <table class="fiche-table">
                        <thead>
                            <tr>
                                <th>Salle</th>
                                <th>Cours</th>
                                <th>Jours</th>
                                <th>Créneau</th>
                                <th>Durée/séance</th>
                                <th>Séances/sem.</th>
                                <th>Total/sem.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignations as $assign)
                                @php
                                    $dureeSeance = 0;
                                    if ($assign->creneauHoraire) {
                                        $dureeSeance = $assign->creneauHoraire->heure_debut->diffInMinutes($assign->creneauHoraire->heure_fin) / 60;
                                    } elseif ($assign->nombreHeure && $assign->nombreHeure->valeur) {
                                        $dureeSeance = (float)$assign->nombreHeure->valeur;
                                    }
                                    $joursArray = $assign->jours ? explode(',', $assign->jours) : [];
                                    $nbSeances = $assign->nombre_seances ?? count($joursArray);
                                    $totalHebdo = $dureeSeance * $nbSeances;
                                @endphp
                                <tr>
                                    <td>{{ optional($assign->salle)->nom ?? '—' }}</td>
                                    <td>{{ optional($assign->cour)->nom ?? '—' }}</td>
                                    <td>
                                        @if($joursArray)
                                            <div class="fiche-jours">
                                                @foreach($joursArray as $jour)
                                                    <span class="fiche-jour-badge">{{ trim($jour) }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($assign->creneauHoraire)
                                            {{ $assign->creneauHoraire->libelle }}
                                            <br>
                                            <small style="color:#9ca3af;">
                                                {{ $assign->creneauHoraire->heure_debut->format('H:i') }} - {{ $assign->creneauHoraire->heure_fin->format('H:i') }}
                                            </small>
                                        @else
                                            {{ $assign->duree ?? '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $dureeSeance > 0 ? $dureeSeance . ' h' : '—' }}</td>
                                    <td>{{ $nbSeances }}</td>
                                    <td style="font-weight:600;">{{ $totalHebdo > 0 ? $totalHebdo . ' h' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:right;">Total hebdomadaire</td>
                                <td>{{ $heuresTotales ?? 0 }} h</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="fiche-salaire-summary">
                    <p>
                        Salaire de base calculé :
                        <strong>{{ number_format($salaireAutoBaseUsd ?? 0, 0) }} $</strong>
                        <span style="color:#6b7280;">(≈ {{ number_format($salaireAutoBaseFc ?? 0, 0) }} FC)</span>
                    </p>
                    <p>Taux horaire actif : {{ $tauxHoraireUsd ?? '—' }} $/h</p>
                </div>

            @else

                <div class="fiche-alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <p>Ce personnel n'a aucun cours assigné. Son salaire est probablement fixé manuellement.</p>
                </div>

                @if($user->type_salaire === 'manuel' && $user->salaire_mensuel_usd)
                    <p style="margin-top: 0.75rem; font-size: 0.95rem; color: #374151;">
                        Salaire manuel actuel :
                        <strong>{{ number_format($user->salaire_mensuel_usd, 0) }} $</strong>
                    </p>
                @endif

            @endif
        </div>

        {{-- Pied de fiche avec actions --}}
        <div class="fiche-footer no-print">
            <div class="fiche-footer-buttons">
                <a href="{{ route('admin.salaires.edit', $user) }}" class="fiche-btn fiche-btn-success">
                    <i class="bi bi-cash-coin"></i> Fixer le salaire
                </a>
                <a href="{{ route('admin.users.edit', $user) }}" class="fiche-btn fiche-btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                      onsubmit="return confirm('Supprimer ce personnel ?')"
                      style="display: contents;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="fiche-btn fiche-btn-danger">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </form>
            </div>
            <p class="fiche-footer-date">
                Document généré le {{ now()->format('d/m/Y à H:i') }}
            </p>
        </div>

    </div>
</div>
@endsection