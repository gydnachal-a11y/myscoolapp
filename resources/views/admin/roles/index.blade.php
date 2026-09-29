@extends('layouts.admin')

@section('page_title', 'Gestion des rôles')
@section('page_subtitle', 'Liste des rôles et leurs permissions')

@section('content')
@php
    $hasFilters = request()->hasAny(['search', 'type', 'sort']);
    $routeIndex = route('admin.roles.index');

    $currentType = request('type');
    $currentSort = request('sort', 'name_asc');

    // ✅ Rôles système : seuls les rôles protégés (super_admin)
    $systemRoles = ['super_admin'];
@endphp

<div class="page">

    {{-- ════════════════════════════════════════════════════════
         HEADER
         ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-shield-halved title-icon" aria-hidden="true"></i>
                <span>Gestion des rôles</span>
                <span class="count-badge">{{ $roles->total() }}</span>
            </h1>
            <p class="page-subtitle">Gérez les rôles et leurs permissions associées</p>
        </div>

        <div class="header-actions">
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                <span>Permissions</span>
            </a>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Nouveau rôle</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════
         FILTRES
         ════════════════════════════════════════════════════════ --}}
    <section class="filter-card">
        <div class="filter-header">
            <div class="filter-header-left">
                <i class="fa-solid fa-sliders filter-icon" aria-hidden="true"></i>
                <span class="filter-title">Filtres</span>
                @if($hasFilters)
                    <span class="filter-count">actifs</span>
                @endif
            </div>
        </div>

        <div class="filter-body">
            <form method="GET" action="{{ $routeIndex }}" class="filter-form">
                <div class="filter-grid">

                    {{-- Recherche --}}
                    <div class="filter-field filter-field-wide">
                        <label for="f-search" class="filter-label">Rechercher</label>
                        <div class="search-field">
                            <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                            <input type="search"
                                   name="search"
                                   id="f-search"
                                   value="{{ request('search') }}"
                                   placeholder="Nom ou libellé…"
                                   class="filter-input"
                                   data-auto-submit>
                        </div>
                    </div>

                    {{-- Type --}}
                    <div class="filter-field">
                        <label for="f-type" class="filter-label">Type</label>
                        <select name="type" id="f-type" class="filter-select" data-auto-submit>
                            <option value="">Tous</option>
                            <option value="system" @selected($currentType === 'system')>Système uniquement</option>
                            <option value="custom" @selected($currentType === 'custom')>Personnalisés</option>
                        </select>
                    </div>

                    {{-- Tri --}}
                    <div class="filter-field">
                        <label for="f-sort" class="filter-label">Trier par</label>
                        <select name="sort" id="f-sort" class="filter-select" data-auto-submit>
                            <option value="name_asc"         @selected($currentSort === 'name_asc')>Nom (A → Z)</option>
                            <option value="name_desc"        @selected($currentSort === 'name_desc')>Nom (Z → A)</option>
                            <option value="permissions_desc" @selected($currentSort === 'permissions_desc')>Permissions (↓)</option>
                            <option value="users_desc"       @selected($currentSort === 'users_desc')>Utilisateurs (↓)</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    @if($hasFilters)
                        <a href="{{ $routeIndex }}" class="btn btn-ghost">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                            <span>Réinitialiser</span>
                        </a>
                    @endif
                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span>Appliquer</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════
         CONTENU — TABLEAU (≥ 768px) + CARTES (< 768px)
         ════════════════════════════════════════════════════════ --}}
    <section class="content-card">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fa-solid fa-list title-icon-sm" aria-hidden="true"></i>
                <span>Liste des rôles</span>
                <span class="count-badge">{{ $roles->total() }}</span>
            </h2>
            @if($roles->total() > 0)
                <div class="content-meta">
                    <span class="meta-item">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        {{ $roles->firstItem() }}–{{ $roles->lastItem() }} sur {{ $roles->total() }}
                    </span>
                </div>
            @endif
        </div>

        @if($roles->count())

            {{-- ──────────────────────────────────────────────
                 VUE TABLEAU (Desktop / Tablette)
                 ────────────────────────────────────────────── --}}
            <div class="view-table table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Rôle</th>
                            <th scope="col">Libellé</th>
                            <th scope="col" class="text-center">Permissions</th>
                            <th scope="col" class="text-center">Utilisateurs</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            @php
                                $isSystem   = in_array($role->name, $systemRoles, true);
                                $initiale   = mb_strtoupper(mb_substr($role->label ?? $role->name, 0, 1));
                                $permCount  = (int) ($role->permissions_count ?? 0);
                                $usersCount = (int) ($role->users_count ?? 0);
                            @endphp
                            <tr>
                                {{-- Rôle --}}
                                <td class="cell-role">
                                    <div class="role-cell">
                                        <div class="role-avatar {{ $isSystem ? 'is-system' : '' }}">
                                            {{ $initiale }}
                                        </div>
                                        <div class="role-meta">
                                            <span class="cell-primary">{{ $role->name }}</span>
                                            @if($isSystem)
                                                <span class="badge-system">
                                                    <i class="fa-solid fa-shield" aria-hidden="true"></i>
                                                    Système
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Libellé --}}
                                <td class="cell-label">
                                    {{ $role->label ?? '—' }}
                                </td>

                                {{-- Permissions --}}
                                <td class="text-center">
                                    <a href="{{ route('admin.roles.permissions', $role) }}"
                                       class="count-pill count-pill-indigo"
                                       title="{{ $permCount }} permission(s)">
                                        <i class="fa-solid fa-key" aria-hidden="true"></i>
                                        <span>{{ $permCount }}</span>
                                    </a>
                                </td>

                                {{-- Utilisateurs --}}
                                <td class="text-center">
                                    <a href="{{ route('admin.roles.assign-users', $role) }}"
                                       class="count-pill count-pill-emerald"
                                       title="{{ $usersCount }} utilisateur(s)">
                                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                                        <span>{{ $usersCount }}</span>
                                    </a>
                                </td>

                                {{-- Actions --}}
                                <td class="text-right">
                                    <div class="action-bar">
                                        <a href="{{ route('admin.roles.permissions', $role) }}"
                                           class="action-btn"
                                           aria-label="Gérer les permissions de {{ $role->name }}"
                                           title="Permissions">
                                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                                        </a>
                                        <a href="{{ route('admin.roles.assign-users', $role) }}"
                                           class="action-btn"
                                           aria-label="Assigner des utilisateurs à {{ $role->name }}"
                                           title="Utilisateurs">
                                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                        </a>
                                        <a href="{{ route('admin.roles.edit', $role) }}"
                                           class="action-btn"
                                           aria-label="Modifier le rôle {{ $role->name }}"
                                           title="Modifier">
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                        </a>

                                        @if($isSystem)
                                            <button type="button"
                                                    class="action-btn is-locked"
                                                    disabled
                                                    aria-label="Rôle système non supprimable"
                                                    title="Rôle système protégé">
                                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.roles.destroy', $role) }}"
                                                  method="POST"
                                                  class="inline-form"
                                                  onsubmit="return confirm('⚠️ Supprimer définitivement le rôle « {{ $role->name }} » ?\n\nCette action est irréversible.')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="action-btn action-danger"
                                                        aria-label="Supprimer le rôle {{ $role->name }}"
                                                        title="Supprimer">
                                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ──────────────────────────────────────────────
                 VUE CARTES (Mobile)
                 ────────────────────────────────────────────── --}}
            <div class="view-cards cards-grid">
                @foreach($roles as $role)
                    @php
                        $isSystem   = in_array($role->name, $systemRoles, true);
                        $initiale   = mb_strtoupper(mb_substr($role->label ?? $role->name, 0, 1));
                        $permCount  = (int) ($role->permissions_count ?? 0);
                        $usersCount = (int) ($role->users_count ?? 0);
                    @endphp
                    <article class="role-card {{ $isSystem ? 'is-system' : '' }}">

                        {{-- Header --}}
                        <header class="role-card-header">
                            <div class="role-avatar {{ $isSystem ? 'is-system' : '' }}">
                                {{ $initiale }}
                            </div>
                            <div class="role-card-title">
                                <h3 class="role-card-name">{{ $role->name }}</h3>
                                @if($role->label)
                                    <p class="role-card-label">{{ $role->label }}</p>
                                @endif
                            </div>
                            @if($isSystem)
                                <span class="badge-system" title="Rôle protégé">
                                    <i class="fa-solid fa-shield" aria-hidden="true"></i>
                                    Système
                                </span>
                            @endif
                        </header>

                        {{-- Stats --}}
                        <div class="role-card-stats">
                            <a href="{{ route('admin.roles.permissions', $role) }}"
                               class="stat-block stat-indigo">
                                <i class="fa-solid fa-key" aria-hidden="true"></i>
                                <div class="stat-info">
                                    <span class="stat-value">{{ $permCount }}</span>
                                    <span class="stat-label">Permission{{ $permCount > 1 ? 's' : '' }}</span>
                                </div>
                            </a>

                            <a href="{{ route('admin.roles.assign-users', $role) }}"
                               class="stat-block stat-emerald">
                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                                <div class="stat-info">
                                    <span class="stat-value">{{ $usersCount }}</span>
                                    <span class="stat-label">Utilisateur{{ $usersCount > 1 ? 's' : '' }}</span>
                                </div>
                            </a>
                        </div>

                        {{-- Actions --}}
                        <footer class="role-card-actions">
                            <a href="{{ route('admin.roles.permissions', $role) }}"
                               class="card-action"
                               aria-label="Permissions">
                                <i class="fa-solid fa-key" aria-hidden="true"></i>
                                <span>Permissions</span>
                            </a>

                            <a href="{{ route('admin.roles.assign-users', $role) }}"
                               class="card-action"
                               aria-label="Utilisateurs">
                                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                <span>Utilisateurs</span>
                            </a>

                            <a href="{{ route('admin.roles.edit', $role) }}"
                               class="card-action"
                               aria-label="Modifier">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                <span>Modifier</span>
                            </a>

                            @if($isSystem)
                                <button type="button"
                                        class="card-action is-locked"
                                        disabled
                                        title="Rôle système protégé">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <span>Protégé</span>
                                </button>
                            @else
                                <form action="{{ route('admin.roles.destroy', $role) }}"
                                      method="POST"
                                      class="inline-form"
                                      onsubmit="return confirm('⚠️ Supprimer définitivement le rôle « {{ $role->name }} » ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="card-action is-danger"
                                            aria-label="Supprimer">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        <span>Supprimer</span>
                                    </button>
                                </form>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($roles->hasPages())
                <div class="pagination-wrapper">{{ $roles->links() }}</div>
            @endif

        @else
            {{-- État vide --}}
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-regular {{ $hasFilters ? 'fa-magnifying-glass' : 'fa-shield' }}" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">
                    {{ $hasFilters ? 'Aucun résultat' : 'Aucun rôle enregistré' }}
                </h3>
                <p class="empty-text">
                    @if($hasFilters)
                        Aucun rôle ne correspond à vos critères. Essayez d'ajuster les filtres.
                    @else
                        Commencez par créer votre premier rôle pour organiser les permissions.
                    @endif
                </p>
                <div class="empty-actions">
                    @if($hasFilters)
                        <a href="{{ $routeIndex }}" class="btn btn-ghost">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                            <span>Effacer les filtres</span>
                        </a>
                    @endif
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Nouveau rôle</span>
                    </a>
                </div>
            </div>
        @endif
    </section>
</div>

<style>
    /* ════════════════════════════════════════════════════════
       DESIGN TOKENS
       ════════════════════════════════════════════════════════ */
    :root {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;
        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;
        --c-emerald-mid:  #a7f3d0;
        --c-amber:        #d97706;
        --c-amber-soft:   #fffbeb;
        --c-red:          #dc2626;
        --c-red-soft:     #fef2f2;
        --c-red-mid:      #fecaca;

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
        --shadow-sm: 0 4px 12px rgba(15,23,42,0.06);
        --shadow-md: 0 8px 20px rgba(79,70,229,0.10);

        --t: 0.2s cubic-bezier(.4,0,.2,1);
    }

    * { box-sizing: border-box; }

    .page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--c-slate-800);
    }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .page-header {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .page-header-text { min-width: 0; }

    .page-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--c-slate-900);
        letter-spacing: -0.5px;
        margin: 0 0 0.25rem;
    }
    .title-icon    { color: #6366f1; font-size: 1.5rem; }
    .title-icon-sm { color: #6366f1; font-size: 1rem; }
    .page-subtitle { color: var(--c-slate-500); font-size: 0.9rem; margin: 0; }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 26px;
        padding: 0 0.6rem;
        background: var(--c-primary-soft);
        color: var(--c-primary);
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .header-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    @media (max-width: 767px) {
        .header-actions { width: 100%; }
        .header-actions .btn { flex: 1 1 calc(50% - 0.3rem); }
    }
    @media (max-width: 420px) {
        .header-actions .btn { flex: 1 1 100%; }
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.7rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.9rem;
        font-family: inherit;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all var(--t);
        white-space: nowrap;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }

    .btn-secondary {
        background: var(--c-slate-800);
        color: #fff;
    }
    .btn-secondary:hover {
        background: var(--c-slate-700);
        transform: translateY(-1px);
    }

    .btn-ghost {
        background: #fff;
        color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .btn-ghost:hover {
        border-color: #6366f1;
        color: var(--c-primary);
        background: var(--c-slate-50);
    }

    .btn:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    /* ════════════════════════════════════════════════════════
       FILTRES
       ════════════════════════════════════════════════════════ */
    .filter-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        box-shadow: var(--shadow-xs);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .filter-header { padding: 1rem 1.25rem 0; }
    .filter-header-left {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .filter-icon  { color: #6366f1; }
    .filter-title { font-weight: 700; color: var(--c-slate-900); font-size: 0.95rem; }
    .filter-count {
        background: var(--c-primary-soft);
        color: var(--c-primary);
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .filter-body { padding: 1rem 1.25rem 1.25rem; }
    .filter-form { display: flex; flex-direction: column; gap: 1rem; }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px) {
        .filter-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-field-wide { grid-column: span 2; }
    }
    @media (min-width: 1024px) {
        .filter-grid { grid-template-columns: 2fr 1fr 1fr; }
        .filter-field-wide { grid-column: span 1; }
    }

    .filter-field { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; }

    .filter-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--c-slate-600);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .filter-select,
    .filter-input {
        width: 100%;
        padding: 0.65rem 0.9rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.9rem;
        font-family: inherit;
        color: var(--c-slate-900);
        transition: all var(--t);
        outline: none;
    }
    .filter-select {
        padding-right: 2.5rem;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
        background-size: 1.1rem;
    }
    .filter-select:focus,
    .filter-input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }

    .search-field { position: relative; }
    .search-icon {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400);
        font-size: 0.85rem;
        pointer-events: none;
    }
    .search-field .filter-input { padding-left: 2.4rem; }

    .filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px dashed var(--c-slate-200);
        flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .filter-actions { flex-direction: column-reverse; }
        .filter-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .content-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--c-slate-100);
        flex-wrap: wrap;
    }
    .content-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--c-slate-900);
        margin: 0;
    }
    .content-meta { font-size: 0.8rem; color: var(--c-slate-500); }
    .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .meta-item i { color: var(--c-slate-400); }

    /* ════════════════════════════════════════════════════════
       VUE TABLEAU / CARTES — BASCULE
       ════════════════════════════════════════════════════════ */
    .view-table { display: none !important; }
    .view-cards { display: block !important; }

    @media (min-width: 768px) {
        .view-table { display: block !important; }
        .view-cards { display: none !important; }
    }

    /* ════════════════════════════════════════════════════════
       TABLEAU (Desktop / Tablette)
       ════════════════════════════════════════════════════════ */
    .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: var(--c-slate-600);
    }
    .data-table thead th {
        text-align: left;
        padding: 0.8rem 1.25rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--c-slate-500);
        background: var(--c-slate-50);
        border-bottom: 1px solid var(--c-slate-200);
        white-space: nowrap;
    }
    .data-table thead th.text-center { text-align: center; }
    .data-table thead th.text-right  { text-align: right; }

    .data-table tbody td {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid var(--c-slate-100);
        vertical-align: middle;
    }
    .data-table tbody tr:hover { background: var(--c-slate-50); }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .text-center { text-align: center !important; }
    .text-right  { text-align: right !important; }

    .cell-primary { font-weight: 700; color: var(--c-slate-900); font-size: 0.92rem; }
    .cell-label   { color: var(--c-slate-600); }

    /* Cellule rôle */
    .role-cell {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
    }
    .role-avatar {
        width: 38px;
        height: 38px;
        border-radius: var(--radius-sm);
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(99,102,241,0.2);
    }
    .role-avatar.is-system {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 2px 8px rgba(245,158,11,0.3);
    }

    .role-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        min-width: 0;
    }

    .badge-system {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.5rem;
        background: var(--c-amber-soft);
        color: #b45309;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        flex-shrink: 0;
    }
    .badge-system i { font-size: 0.6rem; }

    /* Pills de comptage */
    .count-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.7rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        transition: all var(--t);
    }
    .count-pill-indigo {
        background: var(--c-primary-soft);
        color: #4338ca;
    }
    .count-pill-indigo:hover {
        background: var(--c-primary-mid);
        color: #3730a3;
    }
    .count-pill-emerald {
        background: var(--c-emerald-soft);
        color: #047857;
    }
    .count-pill-emerald:hover {
        background: var(--c-emerald-mid);
        color: #065f46;
    }
    .count-pill i { font-size: 0.7rem; }
    .count-pill:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    /* Actions */
    .action-bar {
        display: inline-flex;
        gap: 0.25rem;
        justify-content: flex-end;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 9px;
        color: var(--c-slate-500);
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all var(--t);
        font-size: 0.9rem;
        text-decoration: none;
    }
    .action-btn:hover { background: var(--c-slate-100); color: var(--c-slate-900); }
    .action-btn.action-danger:hover { background: var(--c-red-soft); color: var(--c-red); }
    .action-btn.is-locked {
        color: var(--c-slate-300);
        cursor: not-allowed;
    }
    .action-btn.is-locked:hover {
        background: transparent;
        color: var(--c-slate-300);
    }
    .action-btn:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }
    .inline-form { display: inline; }

    /* ════════════════════════════════════════════════════════
       VUE CARTES (Mobile)
       ════════════════════════════════════════════════════════ */
    .cards-grid {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        padding: 1rem;
    }

    .role-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-md);
        padding: 1rem;
        transition: all var(--t);
        animation: fadeIn 0.3s ease-out both;
    }
    .role-card:nth-child(1)  { animation-delay: 0.02s; }
    .role-card:nth-child(2)  { animation-delay: 0.04s; }
    .role-card:nth-child(3)  { animation-delay: 0.06s; }
    .role-card:nth-child(n+4){ animation-delay: 0.08s; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .role-card.is-system {
        border-color: var(--c-amber-soft);
        background: linear-gradient(180deg, #fff 0%, #fffbf5 100%);
    }

    @media (hover: hover) {
        .role-card:hover {
            border-color: var(--c-primary-mid);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
    }

    /* Header de carte */
    .role-card-header {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px dashed var(--c-slate-200);
        margin-bottom: 0.85rem;
    }
    .role-card-header .role-avatar {
        width: 44px;
        height: 44px;
        font-size: 1rem;
        border-radius: 12px;
    }

    .role-card-title {
        flex: 1;
        min-width: 0;
    }
    .role-card-name {
        font-size: 1rem;
        font-weight: 800;
        color: var(--c-slate-900);
        margin: 0;
        word-break: break-word;
        line-height: 1.25;
    }
    .role-card-label {
        font-size: 0.78rem;
        color: var(--c-slate-500);
        margin: 0.15rem 0 0;
        word-break: break-word;
    }

    /* Stats côte à côte */
    .role-card-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.65rem;
        margin-bottom: 0.85rem;
    }

    .stat-block {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.65rem 0.75rem;
        border-radius: var(--radius-sm);
        text-decoration: none;
        transition: all var(--t);
    }
    .stat-block > i {
        font-size: 1rem;
        flex-shrink: 0;
    }

    .stat-indigo {
        background: var(--c-primary-soft);
        color: #4338ca;
    }
    .stat-indigo > i { color: var(--c-primary); }
    .stat-indigo:hover { background: var(--c-primary-mid); }

    .stat-emerald {
        background: var(--c-emerald-soft);
        color: #047857;
    }
    .stat-emerald > i { color: var(--c-emerald); }
    .stat-emerald:hover { background: var(--c-emerald-mid); }

    .stat-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .stat-value {
        font-size: 1.1rem;
        font-weight: 800;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .stat-label {
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        opacity: 0.85;
        margin-top: 0.15rem;
    }

    /* Actions côte à côte */
    .role-card-actions {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.4rem;
    }

    .card-action {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        padding: 0.55rem 0.4rem;
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        border: 1px solid var(--c-slate-200);
        color: var(--c-slate-600);
        font-size: 0.68rem;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        cursor: pointer;
        transition: all var(--t);
        min-height: 52px;
    }
    .card-action i { font-size: 0.9rem; }
    .card-action span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    @media (hover: hover) {
        .card-action:hover {
            background: #fff;
            border-color: var(--c-primary-mid);
            color: var(--c-primary);
            transform: translateY(-1px);
        }
    }

    .card-action.is-danger:hover {
        background: var(--c-red-soft);
        border-color: var(--c-red-mid);
        color: var(--c-red);
    }

    .card-action.is-locked {
        background: var(--c-slate-50);
        color: var(--c-slate-300);
        cursor: not-allowed;
        border-style: dashed;
    }
    .card-action.is-locked:hover {
        background: var(--c-slate-50);
        border-color: var(--c-slate-200);
        color: var(--c-slate-300);
        transform: none;
    }

    .card-action:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    /* ════════════════════════════════════════════════════════
       PAGINATION
       ════════════════════════════════════════════════════════ */
    .pagination-wrapper {
        padding: 0.9rem 1.25rem;
        border-top: 1px solid var(--c-slate-100);
    }
    @media (max-width: 767px) {
        .pagination-wrapper { padding: 1rem; text-align: center; }
    }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center;
        gap: 0.5rem;
    }
    .empty-icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--c-slate-50);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--c-slate-300);
        margin-bottom: 0.75rem;
    }
    .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--c-slate-700);
        margin: 0;
    }
    .empty-text {
        font-size: 0.9rem;
        color: var(--c-slate-400);
        max-width: 420px;
        margin: 0;
        line-height: 1.5;
    }
    .empty-actions {
        display: flex;
        gap: 0.6rem;
        margin-top: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — ≤ 992px
       ════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .page { padding: 1.5rem 1rem; }
        .data-table thead th,
        .data-table tbody td { padding: 0.75rem 1rem; }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — ≤ 768px (Mobile)
       ════════════════════════════════════════════════════════ */
    @media (max-width: 768px) {
        .page { padding: 1.25rem 0.85rem; }
        .page-title { font-size: 1.4rem; }
        .page-subtitle { font-size: 0.85rem; }

        .filter-card,
        .content-card { border-radius: var(--radius-md); }
        .filter-header { padding: 0.85rem 1rem 0; }
        .filter-body { padding: 0.85rem 1rem 1rem; }

        .content-header { padding: 0.85rem 1rem; }
        .content-title { font-size: 0.95rem; }

        .cards-grid { padding: 0.85rem; gap: 0.75rem; }

        /* Anti-zoom iOS */
        .filter-input,
        .filter-select { font-size: 16px; }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — ≤ 480px (Petit mobile)
       ════════════════════════════════════════════════════════ */
    @media (max-width: 480px) {
        .page { padding: 1rem 0.6rem; }
        .page-title { font-size: 1.2rem; }
        .page-subtitle { font-size: 0.78rem; }

        .role-card { padding: 0.85rem; }
        .role-card-header { gap: 0.7rem; }
        .role-card-header .role-avatar {
            width: 40px;
            height: 40px;
            font-size: 0.95rem;
        }
        .role-card-name { font-size: 0.95rem; }
        .role-card-label { font-size: 0.72rem; }

        .badge-system {
            font-size: 0.6rem;
            padding: 0.12rem 0.4rem;
        }

        .stat-block { padding: 0.55rem 0.65rem; gap: 0.5rem; }
        .stat-block > i { font-size: 0.9rem; }
        .stat-value { font-size: 1rem; }
        .stat-label { font-size: 0.62rem; }

        .role-card-actions { grid-template-columns: repeat(2, 1fr); }
        .card-action { min-height: 48px; font-size: 0.65rem; }
        .card-action i { font-size: 0.85rem; }

        .empty-icon-wrapper { width: 64px; height: 64px; font-size: 1.6rem; }
        .empty-title { font-size: 1rem; }
        .empty-text { font-size: 0.85rem; }
        .empty-actions { flex-direction: column; width: 100%; }
        .empty-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — ≤ 360px (Très petit)
       ════════════════════════════════════════════════════════ */
    @media (max-width: 360px) {
        .page { padding: 0.85rem 0.5rem; }
        .page-title { font-size: 1.1rem; }
        .title-icon { font-size: 1.2rem; }
        .count-badge { font-size: 0.7rem; min-width: 26px; height: 22px; }

        .role-card-name { font-size: 0.9rem; }
        .stat-label { display: none; }

        .card-action span { font-size: 0.62rem; }
    }

    /* ════════════════════════════════════════════════════════
       ACCESSIBILITÉ
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
        .role-card:hover,
        .card-action:hover { transform: none; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let timer = null;

        // Auto-submit des filtres
        document.querySelectorAll('[data-auto-submit]').forEach(el => {
            el.addEventListener('change', () => {
                clearTimeout(timer);
                timer = setTimeout(() => el.form?.submit(), 250);
            });
        });
    });
</script>
@endsection