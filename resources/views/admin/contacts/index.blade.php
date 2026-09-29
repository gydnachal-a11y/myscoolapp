@extends('layouts.admin')

@section('page_title', 'Abonnés')
@section('page_subtitle', 'Gérez les comptes de vos abonnés (parents/responsables)')

@section('content')
@php
    // Total affiché (fallback si pas passé par le controller)
    $totalAbonnes        = $totalAbonnes        ?? $contacts->total();
    $responsablesCount   = $responsablesCount   ?? null;
    $nonResponsablesCount = $nonResponsablesCount ?? null;
    $inscritsCeMois      = $inscritsCeMois      ?? null;

    $hasFilters = request()->filled('search') || request()->filled('est_responsable');
@endphp

<div class="contacts-index-page">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-users title-icon" aria-hidden="true"></i>
                <span>Abonnés</span>
                <span class="count-badge">{{ $totalAbonnes }}</span>
            </h1>
            <p class="page-subtitle">Gérez les comptes de vos abonnés (parents/responsables)</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.contacts.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Nouvel abonné</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- STATS --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="stats-grid">
        <div class="stat-card stat-indigo">
            <div class="stat-icon">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
            </div>
            <div class="stat-content">
                <p class="stat-label">Total abonnés</p>
                <p class="stat-value">{{ $totalAbonnes }}</p>
            </div>
        </div>

        <div class="stat-card stat-purple">
            <div class="stat-icon">
                <i class="fa-solid fa-user-check" aria-hidden="true"></i>
            </div>
            <div class="stat-content">
                <p class="stat-label">Responsables</p>
                <p class="stat-value">
                    {{ $responsablesCount ?? $contacts->where('est_responsable', true)->count() }}
                </p>
            </div>
        </div>

        <div class="stat-card stat-blue">
            <div class="stat-icon">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
            </div>
            <div class="stat-content">
                <p class="stat-label">Non responsables</p>
                <p class="stat-value">
                    {{ $nonResponsablesCount ?? $contacts->where('est_responsable', false)->count() }}
                </p>
            </div>
        </div>

        <div class="stat-card stat-emerald">
            <div class="stat-icon">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            </div>
            <div class="stat-content">
                <p class="stat-label">Ce mois</p>
                <p class="stat-value">
                    {{ $inscritsCeMois ?? $contacts->where('created_at', '>=', now()->startOfMonth())->count() }}
                </p>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FILTRES --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form method="GET"
          action="{{ route('admin.contacts.index') }}"
          class="filters-card">

        <div class="filters-grid">
            {{-- Recherche --}}
            <div class="filter-field filter-field-search">
                <label for="search" class="filter-label">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Recherche</span>
                </label>
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Nom, email, téléphone…"
                           autocomplete="off"
                           class="filter-input">
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.contacts.index', request()->except('search', 'page')) }}"
                           class="search-clear"
                           aria-label="Effacer la recherche">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Statut --}}
            <div class="filter-field">
                <label for="est_responsable" class="filter-label">
                    <i class="fa-solid fa-user-tag" aria-hidden="true"></i>
                    <span>Statut</span>
                </label>
                <select id="est_responsable" name="est_responsable" class="filter-input">
                    <option value="">Tous</option>
                    <option value="1" {{ request('est_responsable') === '1' ? 'selected' : '' }}>
                        Responsable
                    </option>
                    <option value="0" {{ request('est_responsable') === '0' ? 'selected' : '' }}>
                        Non responsable
                    </option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter" aria-hidden="true"></i>
                    <span>Filtrer</span>
                </button>
                @if($hasFilters)
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-ghost">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        <span>Réinitialiser</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- LISTE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="content-card">

        {{-- Info bar --}}
        <div class="content-info">
            <div class="content-info-left">
                <i class="fa-regular fa-address-book content-info-icon" aria-hidden="true"></i>
                <span class="content-info-title">Abonnés</span>
                <span class="count-pill">{{ $contacts->total() }}</span>
            </div>
            @if($contacts->count() > 0)
                <div class="content-info-right">
                    <span>
                        <strong>{{ $contacts->firstItem() ?? 0 }}</strong>–<strong>{{ $contacts->lastItem() ?? 0 }}</strong>
                        sur <strong>{{ $contacts->total() }}</strong>
                    </span>
                </div>
            @endif
        </div>

        @if($contacts->isEmpty())
            {{-- ════════════════════════════════════════════════════ --}}
            {{-- ÉTAT VIDE --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-solid fa-users-slash" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">Aucun abonné</h3>
                <p class="empty-text">
                    @if($hasFilters)
                        Aucun résultat avec ces critères. Essayez d'élargir votre recherche.
                    @else
                        Commencez par créer votre premier abonné (parent / responsable).
                    @endif
                </p>
                <div class="empty-actions">
                    @if($hasFilters)
                        <a href="{{ route('admin.contacts.index') }}" class="btn btn-primary">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                            <span>Réinitialiser les filtres</span>
                        </a>
                    @else
                        <a href="{{ route('admin.contacts.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Créer un abonné</span>
                        </a>
                    @endif
                </div>
            </div>
        @else

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- VUE DESKTOP : TABLE --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Nom</th>
                            <th scope="col">Email</th>
                            <th scope="col">Téléphone</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Inscrit le</th>
                            <th scope="col" class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $contact)
                            <tr>
                                <td>
                                    <div class="cell-user">
                                        <div class="avatar" aria-hidden="true">
                                            {{ strtoupper(mb_substr($contact->nom ?? '?', 0, 1)) }}
                                        </div>
                                        <div class="cell-user-info">
                                            <span class="cell-primary">{{ $contact->nom }}</span>
                                            @if($contact->est_responsable)
                                                <span class="cell-subtitle">Responsable légal</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:{{ $contact->email }}" class="cell-email">
                                        {{ $contact->email }}
                                    </a>
                                </td>
                                <td>
                                    @if($contact->telephone)
                                        <a href="tel:{{ $contact->telephone }}" class="cell-phone">
                                            {{ $contact->telephone }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($contact->est_responsable)
                                        <span class="badge badge-purple">
                                            <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                            Responsable
                                        </span>
                                    @else
                                        <span class="badge badge-slate">
                                            <i class="fa-regular fa-user" aria-hidden="true"></i>
                                            Contact
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <time datetime="{{ $contact->created_at->toIso8601String() }}"
                                          title="{{ $contact->created_at->format('d/m/Y à H:i') }}">
                                        {{ $contact->created_at->format('d/m/Y') }}
                                    </time>
                                </td>
                                <td class="col-actions">
                                    <div class="row-actions">
                                        <a href="{{ route('admin.contacts.show', $contact) }}"
                                           class="icon-btn icon-btn-view"
                                           title="Voir la fiche"
                                           aria-label="Voir la fiche de {{ $contact->nom }}">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="{{ route('admin.contacts.edit', $contact) }}"
                                           class="icon-btn icon-btn-edit"
                                           title="Modifier"
                                           aria-label="Modifier {{ $contact->nom }}">
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('admin.contacts.destroy', $contact) }}"
                                              method="POST"
                                              class="inline-form"
                                              onsubmit="return confirm('Supprimer définitivement l\'abonné « {{ addslashes($contact->nom) }} » ?\n\nCette action est irréversible.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="icon-btn icon-btn-delete"
                                                    title="Supprimer"
                                                    aria-label="Supprimer {{ $contact->nom }}">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- VUE MOBILE : CARDS --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div class="contacts-cards">
                @foreach($contacts as $contact)
                    <article class="contact-card">
                        {{-- Header : avatar + nom + statut --}}
                        <header class="contact-card-header">
                            <div class="contact-avatar" aria-hidden="true">
                                {{ strtoupper(mb_substr($contact->nom ?? '?', 0, 1)) }}
                            </div>
                            <div class="contact-identity">
                                <h3 class="contact-name">{{ $contact->nom }}</h3>
                                @if($contact->est_responsable)
                                    <span class="badge badge-purple badge-sm">
                                        <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                        Responsable
                                    </span>
                                @else
                                    <span class="badge badge-slate badge-sm">
                                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                                        Contact
                                    </span>
                                @endif
                            </div>
                        </header>

                        {{-- Coordonnées --}}
                        <div class="contact-card-body">
                            <a href="mailto:{{ $contact->email }}" class="contact-line">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                                <span>{{ $contact->email }}</span>
                            </a>

                            @if($contact->telephone)
                                <a href="tel:{{ $contact->telephone }}" class="contact-line">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                    <span>{{ $contact->telephone }}</span>
                                </a>
                            @endif

                            <div class="contact-line contact-line-muted">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                <span>Inscrit le {{ $contact->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <footer class="contact-card-actions">
                            <a href="{{ route('admin.contacts.show', $contact) }}"
                               class="card-action card-action-view">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                <span>Voir</span>
                            </a>
                            <a href="{{ route('admin.contacts.edit', $contact) }}"
                               class="card-action card-action-edit">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                <span>Modifier</span>
                            </a>
                            <form action="{{ route('admin.contacts.destroy', $contact) }}"
                                  method="POST"
                                  class="inline-form"
                                  onsubmit="return confirm('Supprimer définitivement l\'abonné « {{ addslashes($contact->nom) }} » ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="card-action card-action-delete"
                                        aria-label="Supprimer {{ $contact->nom }}">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    <span>Suppr.</span>
                                </button>
                            </form>
                        </footer>
                    </article>
                @endforeach
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- PAGINATION --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            @if($contacts->hasPages())
                <footer class="content-footer">
                    <nav aria-label="Pagination" class="pagination-nav">
                        {{ $contacts->appends(request()->query())->links() }}
                    </nav>
                </footer>
            @endif

        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       DESIGN TOKENS
       ════════════════════════════════════════════════════════ */
    .contacts-index-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;
        --c-primary-dark: #4338ca;

        --c-purple:      #7c3aed;
        --c-purple-soft: #f5f3ff;
        --c-purple-mid:  #ddd6fe;

        --c-blue:      #2563eb;
        --c-blue-soft: #eff6ff;
        --c-blue-mid:  #bfdbfe;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;
        --c-emerald-mid:  #a7f3d0;

        --c-rose:      #dc2626;
        --c-rose-soft: #fef2f2;

        --c-amber:      #d97706;
        --c-amber-soft: #fffbeb;

        --c-slate-50:  #f8fafc;
        --c-slate-100: #f1f5f9;
        --c-slate-200: #e2e8f0;
        --c-slate-300: #cbd5e1;
        --c-slate-400: #94a3b8;
        --c-slate-500: #64748b;
        --c-slate-600: #475569;
        --c-slate-700: #334155;
        --c-slate-800: #1e293b;
        --c-slate-900: #0f172a;

        --radius-sm: 10px;
        --radius-md: 14px;
        --radius-lg: 16px;

        --shadow-xs: 0 1px 3px rgba(0,0,0,0.04);
        --shadow-sm: 0 4px 12px rgba(0,0,0,0.05);

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        max-width: 1280px;
        margin: 0 auto;
        padding: 2rem 1rem;
        color: var(--c-slate-800);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .contacts-index-page *,
    .contacts-index-page *::before,
    .contacts-index-page *::after { box-sizing: border-box; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .page-header {
        display: flex; flex-direction: column; gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 768px) {
        .contacts-index-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .contacts-index-page .page-header-text { min-width: 0; }

    .contacts-index-page .page-title {
        display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem;
        font-size: 1.6rem; font-weight: 800; color: var(--c-slate-900);
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .contacts-index-page .title-icon { color: #6366f1; font-size: 1.35rem; }

    .contacts-index-page .count-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 24px; padding: 0 0.6rem;
        background: var(--c-primary-soft); color: var(--c-primary);
        border-radius: 9999px;
        font-size: 0.78rem; font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .contacts-index-page .page-subtitle {
        color: var(--c-slate-500); font-size: 0.9rem; margin: 0;
    }

    .contacts-index-page .header-actions {
        display: flex; gap: 0.6rem; flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .contacts-index-page .header-actions { width: 100%; }
        .contacts-index-page .header-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600; font-size: 0.875rem; font-family: inherit;
        text-decoration: none; border: none; cursor: pointer;
        transition: all var(--t); white-space: nowrap;
        min-height: 44px;
    }
    .contacts-index-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .contacts-index-page .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .contacts-index-page .btn-ghost {
        background: #fff; color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .contacts-index-page .btn-ghost:hover {
        border-color: #6366f1; color: var(--c-primary);
        background: var(--c-slate-50);
    }
    .contacts-index-page .btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }

    /* ════════════════════════════════════════════════════════
       STATS
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.85rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 1024px) {
        .contacts-index-page .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
    }

    .contacts-index-page .stat-card {
        background: #fff;
        padding: 1rem 1.1rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        border-left: 4px solid;
        box-shadow: var(--shadow-xs);
        display: flex; align-items: center; gap: 0.85rem;
        min-width: 0;
        transition: all var(--t);
    }
    .contacts-index-page .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    @media (min-width: 1024px) {
        .contacts-index-page .stat-card { padding: 1.25rem 1.35rem; gap: 1rem; }
    }

    .contacts-index-page .stat-indigo  { border-left-color: var(--c-primary); }
    .contacts-index-page .stat-purple  { border-left-color: var(--c-purple); }
    .contacts-index-page .stat-blue    { border-left-color: var(--c-blue); }
    .contacts-index-page .stat-emerald { border-left-color: var(--c-emerald); }

    .contacts-index-page .stat-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    @media (min-width: 1024px) {
        .contacts-index-page .stat-icon { width: 48px; height: 48px; font-size: 1.15rem; }
    }

    .contacts-index-page .stat-indigo  .stat-icon { background: var(--c-primary-soft); color: var(--c-primary); }
    .contacts-index-page .stat-purple  .stat-icon { background: var(--c-purple-soft);  color: var(--c-purple); }
    .contacts-index-page .stat-blue    .stat-icon { background: var(--c-blue-soft);    color: var(--c-blue); }
    .contacts-index-page .stat-emerald .stat-icon { background: var(--c-emerald-soft); color: var(--c-emerald); }

    .contacts-index-page .stat-content { min-width: 0; flex: 1; }

    .contacts-index-page .stat-label {
        font-size: 0.68rem; font-weight: 600;
        color: var(--c-slate-500);
        text-transform: uppercase; letter-spacing: 0.5px;
        margin: 0 0 0.15rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    @media (min-width: 1024px) {
        .contacts-index-page .stat-label { font-size: 0.72rem; }
    }

    .contacts-index-page .stat-value {
        font-size: 1.35rem; font-weight: 800;
        color: var(--c-slate-900);
        margin: 0;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }
    @media (min-width: 1024px) {
        .contacts-index-page .stat-value { font-size: 1.65rem; }
    }

    /* ════════════════════════════════════════════════════════
       FILTRES
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .filters-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        padding: 1.15rem 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-xs);
    }
    @media (max-width: 640px) {
        .contacts-index-page .filters-card { padding: 1rem; }
    }

    .contacts-index-page .filters-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        align-items: end;
    }
    @media (min-width: 768px) {
        .contacts-index-page .filters-grid {
            grid-template-columns: 2fr 1fr auto;
        }
    }

    .contacts-index-page .filter-field { min-width: 0; }

    .contacts-index-page .filter-label {
        display: flex; align-items: center; gap: 0.35rem;
        font-size: 0.75rem; font-weight: 600;
        color: var(--c-slate-600);
        margin-bottom: 0.35rem;
    }
    .contacts-index-page .filter-label i {
        color: var(--c-slate-400); font-size: 0.7rem;
    }

    .contacts-index-page .filter-input {
        width: 100%;
        padding: 0.65rem 0.9rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.875rem;
        font-family: inherit;
        color: var(--c-slate-900);
        outline: none;
        transition: all var(--t);
        min-height: 44px;
    }
    .contacts-index-page .filter-input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }

    .contacts-index-page .search-wrapper { position: relative; }
    .contacts-index-page .search-icon {
        position: absolute; left: 0.9rem; top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400); font-size: 0.8rem;
        pointer-events: none;
    }
    .contacts-index-page .search-wrapper .filter-input {
        padding-left: 2.3rem;
        padding-right: 2.4rem;
    }
    .contacts-index-page .search-clear {
        position: absolute; right: 0.5rem; top: 50%;
        transform: translateY(-50%);
        width: 26px; height: 26px;
        background: var(--c-slate-100);
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        color: var(--c-slate-500);
        text-decoration: none;
        transition: all var(--t);
    }
    .contacts-index-page .search-clear:hover {
        background: var(--c-slate-200); color: var(--c-slate-800);
    }

    .contacts-index-page .filter-actions {
        display: flex; gap: 0.5rem; flex-wrap: wrap;
    }
    @media (max-width: 767px) {
        .contacts-index-page .filter-actions { grid-column: 1 / -1; }
        .contacts-index-page .filter-actions .btn { flex: 1; }
    }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .content-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }

    .contacts-index-page .content-info {
        display: flex; flex-direction: column; gap: 0.5rem;
        padding: 0.85rem 1.15rem;
        background: var(--c-slate-50);
        border-bottom: 1px solid var(--c-slate-100);
    }
    @media (min-width: 640px) {
        .contacts-index-page .content-info {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .contacts-index-page .content-info-left {
        display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .contacts-index-page .content-info-icon {
        color: #6366f1; font-size: 0.95rem;
    }
    .contacts-index-page .content-info-title {
        font-weight: 600; color: var(--c-slate-800); font-size: 0.9rem;
    }
    .contacts-index-page .content-info-right {
        font-size: 0.82rem; color: var(--c-slate-500);
    }
    .contacts-index-page .content-info-right strong {
        color: var(--c-slate-700); font-weight: 700;
    }

    .contacts-index-page .count-pill {
        display: inline-flex; align-items: center;
        padding: 0.1rem 0.55rem;
        background: var(--c-primary-soft); color: var(--c-primary);
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    /* ════════════════════════════════════════════════════════
       TABLE — Desktop
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .table-wrapper {
        display: none;
    }
    @media (min-width: 768px) {
        .contacts-index-page .table-wrapper {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    .contacts-index-page .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .contacts-index-page .data-table thead th {
        padding: 0.75rem 1rem;
        background: var(--c-slate-50);
        text-align: left;
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--c-slate-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--c-slate-200);
        white-space: nowrap;
    }
    .contacts-index-page .data-table th.col-actions,
    .contacts-index-page .data-table td.col-actions {
        text-align: right;
        width: 120px;
    }

    .contacts-index-page .data-table tbody tr {
        border-bottom: 1px solid var(--c-slate-100);
        transition: background var(--t);
    }
    .contacts-index-page .data-table tbody tr:hover {
        background: var(--c-slate-50);
    }
    .contacts-index-page .data-table tbody tr:last-child {
        border-bottom: none;
    }
    .contacts-index-page .data-table td {
        padding: 0.85rem 1rem;
        color: var(--c-slate-600);
        vertical-align: middle;
    }

    /* Cell utilisateur */
    .contacts-index-page .cell-user {
        display: flex; align-items: center; gap: 0.65rem;
        min-width: 0;
    }
    .contacts-index-page .avatar {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.8rem;
        flex-shrink: 0;
    }
    .contacts-index-page .cell-user-info {
        min-width: 0;
        display: flex; flex-direction: column; gap: 0.1rem;
    }
    .contacts-index-page .cell-primary {
        font-weight: 600; color: var(--c-slate-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .contacts-index-page .cell-subtitle {
        font-size: 0.7rem; color: var(--c-slate-500);
    }

    /* Liens cellules */
    .contacts-index-page .cell-email,
    .contacts-index-page .cell-phone {
        color: var(--c-slate-600);
        text-decoration: none;
        transition: color var(--t);
        word-break: break-word;
    }
    .contacts-index-page .cell-email:hover,
    .contacts-index-page .cell-phone:hover {
        color: var(--c-primary);
        text-decoration: underline;
    }

    .contacts-index-page .text-muted { color: var(--c-slate-400); }

    /* ════════════════════════════════════════════════════════
       BADGES
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 600;
        white-space: nowrap;
        line-height: 1.3;
    }
    .contacts-index-page .badge-sm {
        font-size: 0.65rem;
        padding: 0.15rem 0.5rem;
    }
    .contacts-index-page .badge-purple {
        background: var(--c-purple-soft); color: var(--c-purple);
    }
    .contacts-index-page .badge-slate {
        background: var(--c-slate-100); color: var(--c-slate-600);
    }
    .contacts-index-page .badge i { font-size: 0.6rem; }

    /* ════════════════════════════════════════════════════════
       ICON BUTTONS (actions table)
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .row-actions {
        display: inline-flex; align-items: center; gap: 0.25rem;
        justify-content: flex-end;
    }
    .contacts-index-page .inline-form { display: inline; }

    .contacts-index-page .icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px;
        color: var(--c-slate-500);
        cursor: pointer;
        transition: all var(--t);
        font-size: 0.85rem;
        text-decoration: none;
    }
    .contacts-index-page .icon-btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }
    .contacts-index-page .icon-btn-view { color: var(--c-primary); }
    .contacts-index-page .icon-btn-view:hover {
        background: var(--c-primary-soft);
        border-color: var(--c-primary-mid);
    }
    .contacts-index-page .icon-btn-edit { color: var(--c-amber); }
    .contacts-index-page .icon-btn-edit:hover {
        background: var(--c-amber-soft);
        border-color: #fde68a;
    }
    .contacts-index-page .icon-btn-delete { color: var(--c-rose); }
    .contacts-index-page .icon-btn-delete:hover {
        background: var(--c-rose-soft);
        border-color: #fecaca;
    }

    /* ════════════════════════════════════════════════════════
       CARDS — Mobile
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .contacts-cards {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
    }
    @media (min-width: 768px) {
        .contacts-index-page .contacts-cards { display: none; }
    }

    .contacts-index-page .contact-card {
        background: #fff;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-md);
        padding: 1rem;
        transition: all var(--t);
    }
    .contacts-index-page .contact-card:hover {
        border-color: var(--c-primary-mid);
        box-shadow: 0 4px 12px rgba(99,102,241,0.08);
    }

    .contacts-index-page .contact-card-header {
        display: flex; align-items: center; gap: 0.85rem;
        margin-bottom: 0.85rem;
    }
    .contacts-index-page .contact-avatar {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.95rem;
        flex-shrink: 0;
    }
    .contacts-index-page .contact-identity {
        min-width: 0; flex: 1;
        display: flex; flex-direction: column; gap: 0.25rem;
    }
    .contacts-index-page .contact-name {
        font-size: 0.95rem; font-weight: 700;
        color: var(--c-slate-900);
        margin: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    .contacts-index-page .contact-card-body {
        display: flex; flex-direction: column; gap: 0.5rem;
        padding: 0.75rem 0;
        border-top: 1px dashed var(--c-slate-200);
        border-bottom: 1px dashed var(--c-slate-200);
        margin-bottom: 0.85rem;
    }
    .contacts-index-page .contact-line {
        display: flex; align-items: center; gap: 0.6rem;
        font-size: 0.82rem; color: var(--c-slate-600);
        text-decoration: none;
        transition: color var(--t);
        min-width: 0;
    }
    .contacts-index-page .contact-line i {
        width: 16px;
        color: var(--c-slate-400);
        flex-shrink: 0;
        font-size: 0.75rem;
        text-align: center;
    }
    .contacts-index-page .contact-line span {
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        min-width: 0;
        flex: 1;
    }
    a.contacts-index-page .contact-line:hover {
        color: var(--c-primary);
    }
    a.contacts-index-page .contact-line:hover i {
        color: var(--c-primary);
    }
    .contacts-index-page .contact-line-muted {
        color: var(--c-slate-400);
        font-size: 0.78rem;
    }

    .contacts-index-page .contact-card-actions {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.4rem;
    }
    .contacts-index-page .card-action {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.35rem;
        padding: 0.5rem 0.4rem;
        border: 1px solid var(--c-slate-200);
        border-radius: 8px;
        background: #fff;
        color: var(--c-slate-600);
        font-size: 0.75rem; font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        cursor: pointer;
        transition: all var(--t);
        min-height: 36px;
    }
    .contacts-index-page .card-action:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }
    .contacts-index-page .card-action-view { color: var(--c-primary); }
    .contacts-index-page .card-action-view:hover {
        background: var(--c-primary-soft);
        border-color: var(--c-primary-mid);
    }
    .contacts-index-page .card-action-edit { color: var(--c-amber); }
    .contacts-index-page .card-action-edit:hover {
        background: var(--c-amber-soft);
        border-color: #fde68a;
    }
    .contacts-index-page .card-action-delete { color: var(--c-rose); }
    .contacts-index-page .card-action-delete:hover {
        background: var(--c-rose-soft);
        border-color: #fecaca;
    }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .empty-state {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center;
        padding: 3.5rem 1.5rem;
        text-align: center;
        gap: 0.5rem;
    }
    .contacts-index-page .empty-icon-wrapper {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: var(--c-slate-50);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: var(--c-slate-300);
        margin-bottom: 0.75rem;
    }
    .contacts-index-page .empty-title {
        font-size: 1.1rem; font-weight: 700;
        color: var(--c-slate-700); margin: 0;
    }
    .contacts-index-page .empty-text {
        font-size: 0.875rem; color: var(--c-slate-400);
        max-width: 420px; margin: 0; line-height: 1.55;
    }
    .contacts-index-page .empty-actions { margin-top: 1rem; }

    /* ════════════════════════════════════════════════════════
       PAGINATION
       ════════════════════════════════════════════════════════ */
    .contacts-index-page .content-footer {
        display: flex; justify-content: center;
        padding: 1rem 1.15rem;
        border-top: 1px solid var(--c-slate-100);
        background: var(--c-slate-50);
    }
    .contacts-index-page .pagination-nav { width: 100%; }

    /* Override Laravel pagination (Tailwind-based) */
    .contacts-index-page .pagination-nav nav { display: flex; justify-content: center; }
    .contacts-index-page .pagination-nav nav > div { display: none; }  /* cache "Showing X to Y" */

    @media (max-width: 640px) {
        .contacts-index-page .pagination-nav nav span[aria-current="page"] > span,
        .contacts-index-page .pagination-nav nav a {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8rem !important;
        }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .contacts-index-page { padding: 1.5rem 1rem; }
    }

    @media (max-width: 768px) {
        .contacts-index-page { padding: 1.25rem 0.85rem; }
        .contacts-index-page .page-title { font-size: 1.35rem; }
        .contacts-index-page .title-icon { font-size: 1.15rem; }
        .contacts-index-page .page-subtitle { font-size: 0.85rem; }
    }

    @media (max-width: 480px) {
        .contacts-index-page { padding: 1rem 0.65rem; }
        .contacts-index-page .page-title { font-size: 1.15rem; }
        .contacts-index-page .count-badge {
            font-size: 0.7rem;
            min-width: 26px;
            height: 22px;
        }

        .contacts-index-page .stats-grid { gap: 0.6rem; }
        .contacts-index-page .stat-card { padding: 0.85rem 0.9rem; gap: 0.65rem; }
        .contacts-index-page .stat-icon { width: 36px; height: 36px; font-size: 0.9rem; }
        .contacts-index-page .stat-value { font-size: 1.2rem; }
        .contacts-index-page .stat-label { font-size: 0.62rem; }

        .contacts-index-page .filters-card { padding: 0.85rem; }
        .contacts-index-page .contacts-cards { padding: 0.75rem; }
        .contacts-index-page .contact-card { padding: 0.85rem; }
        .contacts-index-page .contact-avatar { width: 40px; height: 40px; font-size: 0.85rem; }
    }

    @media (max-width: 360px) {
        .contacts-index-page .stats-grid { grid-template-columns: 1fr; }
        .contacts-index-page .contact-card-actions {
            grid-template-columns: 1fr 1fr;
        }
        .contacts-index-page .contact-card-actions .card-action-delete {
            grid-column: 1 / -1;
        }
        .contacts-index-page .stat-value { font-size: 1.15rem; }
    }

    /* Anti-zoom iOS */
    @media (max-width: 640px) {
        .contacts-index-page .filter-input { font-size: 16px; }
    }

    /* ════════════════════════════════════════════════════════
       A11Y + PRINT
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .contacts-index-page *,
        .contacts-index-page *::before,
        .contacts-index-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }

    @media print {
        .contacts-index-page .filters-card,
        .contacts-index-page .header-actions,
        .contacts-index-page .content-footer,
        .contacts-index-page .col-actions,
        .contacts-index-page .contact-card-actions {
            display: none !important;
        }
        .contacts-index-page .content-card {
            box-shadow: none; border: 1px solid #ccc;
        }
        .contacts-index-page .table-wrapper { display: block !important; }
        .contacts-index-page .contacts-cards { display: none !important; }
    }
</style>
@endpush