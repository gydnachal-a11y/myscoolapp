@extends('layouts.admin')

@section('page_title', 'Liste des permissions')
@section('page_subtitle', 'Toutes les permissions disponibles')

@section('content')
@php
    // ✅ Cast pour éviter des types incohérents
    $totalCount = (int) $permissions->total();
    $currentPerPage = (int) request('per_page', 25);
    $currentSort = (string) request('sort', 'name_asc');
    $currentScope = (string) request('scope', 'all');
@endphp

<div class="permissions-index-page">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-list-check title-icon" aria-hidden="true"></i>
                <span>Liste des permissions</span>
                <span class="count-badge">{{ $totalCount }}</span>
            </h1>
            <p class="page-subtitle">Consultez et gérez les permissions du système</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.permissions.manage') }}" class="btn btn-ghost">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                <span>Vue par ressources</span>
            </a>
            <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Nouvelle permission</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FILTRES --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form method="GET"
          action="{{ route('admin.permissions.index') }}"
          class="filters-card"
          x-data="permissionFilters()"
          x-init="init()">

        {{-- Ligne 1 : Tabs scope --}}
        <nav class="scope-tabs" role="tablist" aria-label="Scope des permissions">
            <a href="{{ route('admin.permissions.index', array_merge(request()->except('page', 'scope'), ['scope' => 'all'])) }}"
               class="scope-tab {{ $currentScope === 'all' ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $currentScope === 'all' ? 'true' : 'false' }}">
                <i class="fa-solid fa-globe" aria-hidden="true"></i>
                <span>Toutes</span>
            </a>
            <a href="{{ route('admin.permissions.index', array_merge(request()->except('page', 'scope'), ['scope' => 'admin'])) }}"
               class="scope-tab {{ $currentScope === 'admin' ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $currentScope === 'admin' ? 'true' : 'false' }}">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span>Administration</span>
            </a>
            <a href="{{ route('admin.permissions.index', array_merge(request()->except('page', 'scope'), ['scope' => 'member'])) }}"
               class="scope-tab {{ $currentScope === 'member' ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $currentScope === 'member' ? 'true' : 'false' }}">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span>Espace membre</span>
            </a>
        </nav>

        {{-- Ligne 2 : Champs --}}
        <div class="filters-grid">
            {{-- Recherche --}}
            <div class="filter-field filter-field-search">
                <label for="search" class="filter-label">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    Recherche
                </label>
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Nom, libellé ou description…"
                           autocomplete="off"
                           class="filter-input">
                    @if(request('search'))
                        <a href="{{ route('admin.permissions.index', request()->except(['search', 'page'])) }}"
                           class="search-clear"
                           aria-label="Effacer la recherche">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Ressource --}}
            <div class="filter-field">
                <label for="resource" class="filter-label">
                    <i class="fa-solid fa-folder" aria-hidden="true"></i>
                    Ressource
                </label>
                <select id="resource" name="resource" class="filter-input">
                    <option value="">Toutes les ressources</option>
                    @foreach($resources as $res)
                        <option value="{{ $res }}" {{ request('resource') == $res ? 'selected' : '' }}>
                            {{ $res }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Tri --}}
            <div class="filter-field">
                <label for="sort" class="filter-label">
                    <i class="fa-solid fa-arrow-down-a-z" aria-hidden="true"></i>
                    Trier par
                </label>
                <select id="sort" name="sort" class="filter-input">
                    <option value="name_asc"        {{ $currentSort === 'name_asc'        ? 'selected' : '' }}>Nom (A → Z)</option>
                    <option value="name_desc"       {{ $currentSort === 'name_desc'       ? 'selected' : '' }}>Nom (Z → A)</option>
                    <option value="resource_asc"    {{ $currentSort === 'resource_asc'    ? 'selected' : '' }}>Ressource (A → Z)</option>
                    <option value="resource_desc"   {{ $currentSort === 'resource_desc'   ? 'selected' : '' }}>Ressource (Z → A)</option>
                    <option value="roles_desc"      {{ $currentSort === 'roles_desc'      ? 'selected' : '' }}>Plus utilisées</option>
                    <option value="roles_asc"       {{ $currentSort === 'roles_asc'       ? 'selected' : '' }}>Moins utilisées</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter" aria-hidden="true"></i>
                    <span>Filtrer</span>
                </button>
                @if(request()->hasAny(['search', 'resource', 'sort', 'scope']))
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-ghost">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        <span>Réinitialiser</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- CONTENU --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="content-card">

        {{-- Barre d'info --}}
        <div class="content-info">
            <div class="content-info-left">
                <i class="fa-regular fa-list content-info-icon" aria-hidden="true"></i>
                <span class="content-info-title">Permissions</span>
                <span class="count-pill">{{ $totalCount }}</span>
            </div>
            <div class="content-info-right">
                @if($permissions->count() > 0)
                    <span>
                        <strong>{{ $permissions->firstItem() ?? 0 }}</strong>–<strong>{{ $permissions->lastItem() ?? 0 }}</strong>
                        sur <strong>{{ $totalCount }}</strong>
                    </span>
                @endif
            </div>
        </div>

        {{-- Vide --}}
        @if($permissions->isEmpty())
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">Aucune permission trouvée</h3>
                <p class="empty-text">
                    @if(request()->hasAny(['search', 'resource', 'scope']))
                        Essayez d'élargir votre recherche ou de réinitialiser les filtres.
                    @else
                        Commencez par synchroniser les permissions depuis vos routes.
                    @endif
                </p>
                <div class="empty-actions">
                    @if(request()->hasAny(['search', 'resource', 'scope']))
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-primary">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                            <span>Réinitialiser les filtres</span>
                        </a>
                    @else
                        <a href="{{ route('admin.permissions.sync-all') }}" class="btn btn-primary">
                            <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                            <span>Synchroniser les permissions</span>
                        </a>
                    @endif
                </div>
            </div>
        @else

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- VUE DESKTOP : Table --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div class="table-wrapper">
                <table class="permissions-table">
                    <thead>
                        <tr>
                            <th class="col-name">Nom</th>
                            <th class="col-resource">Ressource</th>
                            <th class="col-action">Action</th>
                            <th class="col-label">Libellé</th>
                            <th class="col-roles">Rôles</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $perm)
                            <tr class="table-row">
                                <td class="col-name">
                                    <code class="perm-name">{{ $perm->name }}</code>
                                </td>
                                <td class="col-resource">
                                    @if($perm->resource)
                                        <span class="badge badge-indigo">{{ $perm->resource }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="col-action">
                                    @if($perm->action)
                                        <span class="badge badge-blue">{{ $perm->action }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="col-label" title="{{ $perm->label ?? '' }}">
                                    <span class="truncate">{{ $perm->label ?? '—' }}</span>
                                </td>
                                <td class="col-roles">
                                    @php $rolesCount = (int) ($perm->roles_count ?? 0); @endphp
                                    @if($rolesCount > 0)
                                        <span class="badge badge-emerald" title="{{ $rolesCount }} rôle(s) utilise(nt) cette permission">
                                            {{ $rolesCount }}
                                        </span>
                                    @else
                                        <span class="badge badge-slate" title="Non utilisée">0</span>
                                    @endif
                                </td>
                                <td class="col-actions">
                                    <div class="row-actions">
                                        <a href="{{ route('admin.permissions.edit', $perm) }}"
                                           class="icon-btn icon-btn-edit"
                                           title="Modifier"
                                           aria-label="Modifier {{ $perm->name }}">
                                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                        </a>

                                        @if((int) ($perm->roles_count ?? 0) > 0)
                                            <button type="button"
                                                    class="icon-btn icon-btn-disabled"
                                                    disabled
                                                    title="Impossible : utilisée par {{ $perm->roles_count }} rôle(s)"
                                                    aria-label="Suppression impossible">
                                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.permissions.destroy', $perm) }}"
                                                  method="POST"
                                                  class="inline-form"
                                                  onsubmit="return confirm('Supprimer la permission « {{ $perm->name }} » ?\n\nCette action est irréversible.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="icon-btn icon-btn-delete"
                                                        title="Supprimer"
                                                        aria-label="Supprimer {{ $perm->name }}">
                                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
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

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- VUE MOBILE : Cards --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div class="permissions-cards">
                @foreach($permissions as $perm)
                    <article class="perm-card">
                        <header class="perm-card-header">
                            <code class="perm-card-name">{{ $perm->name }}</code>
                            <div class="perm-card-actions">
                                <a href="{{ route('admin.permissions.edit', $perm) }}"
                                   class="icon-btn icon-btn-edit"
                                   title="Modifier"
                                   aria-label="Modifier {{ $perm->name }}">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                </a>

                                @if((int) ($perm->roles_count ?? 0) > 0)
                                    <button type="button" class="icon-btn icon-btn-disabled" disabled
                                            title="Utilisée par {{ $perm->roles_count }} rôle(s)">
                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                @else
                                    <form action="{{ route('admin.permissions.destroy', $perm) }}"
                                          method="POST"
                                          class="inline-form"
                                          onsubmit="return confirm('Supprimer la permission « {{ $perm->name }} » ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn-delete"
                                                aria-label="Supprimer {{ $perm->name }}">
                                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </header>

                        @if($perm->label)
                            <p class="perm-card-label">{{ $perm->label }}</p>
                        @endif

                        @if($perm->description)
                            <p class="perm-card-description">{{ Str::limit($perm->description, 120) }}</p>
                        @endif

                        <div class="perm-card-meta">
                            @if($perm->resource)
                                <span class="badge badge-indigo">{{ $perm->resource }}</span>
                            @endif
                            @if($perm->action)
                                <span class="badge badge-blue">{{ $perm->action }}</span>
                            @endif

                            @php $rolesCount = (int) ($perm->roles_count ?? 0); @endphp
                            <span class="badge {{ $rolesCount > 0 ? 'badge-emerald' : 'badge-slate' }}"
                                  title="{{ $rolesCount }} rôle(s)">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                {{ $rolesCount }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- PAGINATION --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            @if($permissions->hasPages() || $totalCount > 25)
                <footer class="content-footer">

                    {{-- Info pagination --}}
                    <div class="pagination-info">
                        <span>Affichage de <strong>{{ $permissions->firstItem() ?? 0 }}</strong> à
                        <strong>{{ $permissions->lastItem() ?? 0 }}</strong> sur
                        <strong>{{ $totalCount }}</strong> permissions</span>
                    </div>

                    {{-- Per page + Pagination --}}
                    <div class="pagination-controls">

                        {{-- Per page --}}
                        <form method="GET" action="{{ route('admin.permissions.index') }}" class="per-page-form" id="perPageForm">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $k => $v)
                                        <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <label for="per_page" class="per-page-label">Par page :</label>
                            <select id="per_page" name="per_page" class="per-page-select"
                                    onchange="document.getElementById('perPageForm').submit()">
                                @foreach([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        {{-- Links --}}
                        @if($permissions->hasPages())
                            <nav class="pagination-links" aria-label="Pagination">
                                {{ $permissions->appends(request()->query())->links() }}
                            </nav>
                        @endif
                    </div>
                </footer>
            @endif

        @endif

    </div>
</div>
@endsection

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       BASE — scopé sous .permissions-index-page
       ════════════════════════════════════════════════════════ */
    .permissions-index-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;

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

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        max-width: 1280px;
        margin: 0 auto;
        padding: 2rem 1rem;
        color: var(--c-slate-800);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .permissions-index-page *,
    .permissions-index-page *::before,
    .permissions-index-page *::after { box-sizing: border-box; }

    .permissions-index-page [x-cloak] { display: none !important; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .page-header {
        display: flex; flex-direction: column; gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 768px) {
        .permissions-index-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .permissions-index-page .page-header-text { min-width: 0; }

    .permissions-index-page .page-title {
        display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem;
        font-size: 1.6rem; font-weight: 800; color: var(--c-slate-900);
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .permissions-index-page .title-icon { color: #6366f1; font-size: 1.4rem; }

    .permissions-index-page .count-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 24px; padding: 0 0.6rem;
        background: var(--c-primary-soft); color: var(--c-primary);
        border-radius: 9999px;
        font-size: 0.78rem; font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .permissions-index-page .page-subtitle {
        color: var(--c-slate-500); font-size: 0.9rem; margin: 0;
    }

    .permissions-index-page .header-actions {
        display: flex; gap: 0.6rem; flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .permissions-index-page .header-actions { width: 100%; flex-direction: column; }
        .permissions-index-page .header-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.65rem 1.15rem; border-radius: var(--radius-sm);
        font-weight: 600; font-size: 0.875rem; font-family: inherit;
        text-decoration: none; border: none; cursor: pointer;
        transition: all var(--t);
        white-space: nowrap; min-height: 42px;
    }
    .permissions-index-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .permissions-index-page .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .permissions-index-page .btn-ghost {
        background: #fff; color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .permissions-index-page .btn-ghost:hover {
        border-color: #6366f1; color: var(--c-primary); background: var(--c-slate-50);
    }
    .permissions-index-page .btn:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    /* ════════════════════════════════════════════════════════
       FILTRES
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .filters-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        padding: 1.15rem 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-xs);
    }
    @media (max-width: 640px) {
        .permissions-index-page .filters-card { padding: 1rem 0.9rem; }
    }

    /* Tabs scope */
    .permissions-index-page .scope-tabs {
        display: flex;
        gap: 0.35rem;
        margin-bottom: 1.1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px dashed var(--c-slate-200);
        overflow-x: auto;
        scrollbar-width: none;
    }
    .permissions-index-page .scope-tabs::-webkit-scrollbar { display: none; }

    .permissions-index-page .scope-tab {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        background: transparent;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        font-size: 0.82rem; font-weight: 600;
        color: var(--c-slate-500);
        text-decoration: none;
        transition: all var(--t);
        white-space: nowrap;
        flex-shrink: 0;
        min-height: 36px;
    }
    .permissions-index-page .scope-tab:hover {
        border-color: var(--c-slate-300);
        color: var(--c-slate-800);
        background: var(--c-slate-50);
    }
    .permissions-index-page .scope-tab.is-active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 3px 8px rgba(79,70,229,0.22);
    }

    /* Grille filtres */
    .permissions-index-page .filters-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        align-items: end;
    }
    @media (min-width: 640px) {
        .permissions-index-page .filters-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (min-width: 1024px) {
        .permissions-index-page .filters-grid {
            grid-template-columns: 2fr 1fr 1fr auto;
        }
    }

    .permissions-index-page .filter-field { min-width: 0; }
    .permissions-index-page .filter-field-search { grid-column: 1 / -1; }
    @media (min-width: 1024px) {
        .permissions-index-page .filter-field-search { grid-column: auto; }
    }

    .permissions-index-page .filter-label {
        display: flex; align-items: center; gap: 0.35rem;
        font-size: 0.78rem; font-weight: 600;
        color: var(--c-slate-600);
        margin-bottom: 0.35rem;
    }
    .permissions-index-page .filter-label i {
        color: var(--c-slate-400); font-size: 0.72rem;
    }

    .permissions-index-page .filter-input {
        width: 100%;
        padding: 0.6rem 0.9rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.875rem;
        font-family: inherit;
        color: var(--c-slate-900);
        outline: none;
        transition: all var(--t);
        min-height: 42px;
    }
    .permissions-index-page .filter-input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }

    .permissions-index-page .search-wrapper { position: relative; }
    .permissions-index-page .search-icon {
        position: absolute; left: 0.8rem; top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400); font-size: 0.8rem;
        pointer-events: none;
    }
    .permissions-index-page .search-wrapper .filter-input {
        padding-left: 2.2rem;
        padding-right: 2.4rem;
    }
    .permissions-index-page .search-clear {
        position: absolute; right: 0.45rem; top: 50%;
        transform: translateY(-50%);
        width: 26px; height: 26px;
        background: var(--c-slate-100);
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        color: var(--c-slate-500);
        text-decoration: none;
        transition: all var(--t);
    }
    .permissions-index-page .search-clear:hover {
        background: var(--c-slate-200); color: var(--c-slate-800);
    }

    .permissions-index-page .filter-actions {
        display: flex; gap: 0.5rem; flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .permissions-index-page .filter-actions { grid-column: 1 / -1; }
        .permissions-index-page .filter-actions .btn { flex: 1; }
    }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .content-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }

    /* Barre d'info */
    .permissions-index-page .content-info {
        display: flex; flex-direction: column; gap: 0.5rem;
        padding: 0.85rem 1.15rem;
        background: var(--c-slate-50);
        border-bottom: 1px solid var(--c-slate-100);
    }
    @media (min-width: 640px) {
        .permissions-index-page .content-info {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .permissions-index-page .content-info-left {
        display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .permissions-index-page .content-info-icon {
        color: #6366f1; font-size: 0.95rem;
    }
    .permissions-index-page .content-info-title {
        font-weight: 600; color: var(--c-slate-800); font-size: 0.9rem;
    }
    .permissions-index-page .content-info-right {
        font-size: 0.82rem; color: var(--c-slate-500);
    }
    .permissions-index-page .content-info-right strong {
        color: var(--c-slate-700); font-weight: 700;
    }

    .permissions-index-page .count-pill {
        display: inline-flex; align-items: center;
        padding: 0.1rem 0.55rem;
        background: var(--c-primary-soft);
        color: var(--c-primary);
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    /* ════════════════════════════════════════════════════════
       TABLE — Desktop uniquement
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .table-wrapper {
        display: none;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    @media (min-width: 768px) {
        .permissions-index-page .table-wrapper { display: block; }
    }

    .permissions-index-page .permissions-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .permissions-index-page .permissions-table thead th {
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

    .permissions-index-page .permissions-table th.col-actions,
    .permissions-index-page .permissions-table th.col-roles {
        text-align: center;
    }
    .permissions-index-page .permissions-table th.col-roles { width: 90px; }
    .permissions-index-page .permissions-table th.col-actions { width: 110px; }

    .permissions-index-page .permissions-table tbody tr {
        border-bottom: 1px solid var(--c-slate-100);
        transition: background var(--t);
    }
    .permissions-index-page .permissions-table tbody tr:hover {
        background: var(--c-slate-50);
    }
    .permissions-index-page .permissions-table tbody tr:last-child {
        border-bottom: none;
    }

    .permissions-index-page .permissions-table td {
        padding: 0.85rem 1rem;
        color: var(--c-slate-600);
        vertical-align: middle;
    }

    .permissions-index-page .perm-name {
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.8rem;
        color: var(--c-slate-800);
        font-weight: 600;
        background: var(--c-slate-100);
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        word-break: break-all;
    }

    .permissions-index-page .col-label { max-width: 200px; }
    .permissions-index-page .col-label .truncate {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .permissions-index-page .text-muted { color: var(--c-slate-400); }

    /* ════════════════════════════════════════════════════════
       CARDS — Mobile uniquement
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .permissions-cards {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
    }
    @media (min-width: 768px) {
        .permissions-index-page .permissions-cards { display: none; }
    }

    .permissions-index-page .perm-card {
        background: #fff;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-md);
        padding: 0.9rem 1rem;
        transition: all var(--t);
    }
    .permissions-index-page .perm-card:hover {
        border-color: var(--c-primary-mid);
        background: #fafbff;
    }

    .permissions-index-page .perm-card-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.6rem;
    }
    .permissions-index-page .perm-card-name {
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--c-slate-800);
        background: var(--c-slate-100);
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        word-break: break-all;
        flex: 1;
        min-width: 0;
    }
    .permissions-index-page .perm-card-actions {
        display: inline-flex; gap: 0.25rem;
        flex-shrink: 0;
    }

    .permissions-index-page .perm-card-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--c-slate-700);
        margin: 0 0 0.35rem;
    }

    .permissions-index-page .perm-card-description {
        font-size: 0.78rem;
        color: var(--c-slate-500);
        line-height: 1.5;
        margin: 0 0 0.65rem;
    }

    .permissions-index-page .perm-card-meta {
        display: flex; flex-wrap: wrap; gap: 0.35rem;
    }

    /* ════════════════════════════════════════════════════════
       BADGES
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.7rem; font-weight: 600;
        white-space: nowrap;
        line-height: 1.3;
    }
    .permissions-index-page .badge-indigo {
        background: var(--c-primary-soft); color: #4338ca;
    }
    .permissions-index-page .badge-blue {
        background: #eff6ff; color: #1d4ed8;
    }
    .permissions-index-page .badge-emerald {
        background: var(--c-emerald-soft); color: var(--c-emerald);
    }
    .permissions-index-page .badge-slate {
        background: var(--c-slate-100); color: var(--c-slate-500);
    }

    /* ════════════════════════════════════════════════════════
       ACTIONS DE LIGNE
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .row-actions {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.35rem;
    }

    .permissions-index-page .inline-form { display: inline; }

    .permissions-index-page .icon-btn {
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
    .permissions-index-page .icon-btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }
    .permissions-index-page .icon-btn-edit {
        color: #d97706;
    }
    .permissions-index-page .icon-btn-edit:hover {
        background: #fffbeb;
        border-color: #fde68a;
    }
    .permissions-index-page .icon-btn-delete {
        color: #dc2626;
    }
    .permissions-index-page .icon-btn-delete:hover {
        background: #fef2f2;
        border-color: #fecaca;
    }
    .permissions-index-page .icon-btn-disabled {
        color: var(--c-slate-300);
        cursor: not-allowed;
        opacity: 0.55;
    }

    /* ════════════════════════════════════════════════════════
       FOOTER / PAGINATION
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .content-footer {
        display: flex; flex-direction: column; gap: 1rem;
        padding: 1rem 1.15rem;
        border-top: 1px solid var(--c-slate-100);
        background: var(--c-slate-50);
    }
    @media (min-width: 768px) {
        .permissions-index-page .content-footer {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }

    .permissions-index-page .pagination-info {
        font-size: 0.8rem; color: var(--c-slate-500);
    }
    .permissions-index-page .pagination-info strong {
        color: var(--c-slate-700); font-weight: 700;
    }

    .permissions-index-page .pagination-controls {
        display: flex; flex-direction: column; gap: 0.75rem;
        align-items: stretch;
    }
    @media (min-width: 768px) {
        .permissions-index-page .pagination-controls {
            flex-direction: row; align-items: center; gap: 1.25rem;
        }
    }

    .permissions-index-page .per-page-form {
        display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .permissions-index-page .per-page-label {
        font-size: 0.78rem; color: var(--c-slate-500); font-weight: 500;
    }
    .permissions-index-page .per-page-select {
        padding: 0.4rem 0.7rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: 8px;
        background: #fff;
        font-size: 0.8rem;
        color: var(--c-slate-700);
        cursor: pointer;
        font-family: inherit;
        min-height: 34px;
    }
    .permissions-index-page .per-page-select:focus {
        outline: none; border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .permissions-index-page .empty-state {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center;
        gap: 0.6rem;
    }
    .permissions-index-page .empty-icon-wrapper {
        width: 88px; height: 88px;
        border-radius: 50%;
        background: var(--c-slate-50);
        display: flex; align-items: center; justify-content: center;
        font-size: 2.2rem;
        color: var(--c-slate-300);
        margin-bottom: 0.75rem;
    }
    .permissions-index-page .empty-title {
        font-size: 1.1rem; font-weight: 700;
        color: var(--c-slate-700); margin: 0;
    }
    .permissions-index-page .empty-text {
        font-size: 0.88rem; color: var(--c-slate-400);
        max-width: 420px; margin: 0; line-height: 1.55;
    }
    .permissions-index-page .empty-actions { margin-top: 1rem; }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .permissions-index-page { padding: 1.5rem 1rem; }
    }

    @media (max-width: 768px) {
        .permissions-index-page { padding: 1.25rem 0.85rem; }
        .permissions-index-page .page-title { font-size: 1.3rem; }
        .permissions-index-page .title-icon { font-size: 1.15rem; }
        .permissions-index-page .page-subtitle { font-size: 0.85rem; }
    }

    @media (max-width: 480px) {
        .permissions-index-page { padding: 1rem 0.65rem; }
        .permissions-index-page .page-title { font-size: 1.15rem; }
        .permissions-index-page .count-badge {
            font-size: 0.7rem; padding: 0 0.5rem;
        }

        .permissions-index-page .filters-card { padding: 0.85rem; }
        .permissions-index-page .scope-tab {
            padding: 0.45rem 0.7rem;
            font-size: 0.78rem;
        }

        .permissions-index-page .content-info { padding: 0.75rem 0.9rem; }
        .permissions-index-page .permissions-cards { padding: 0.75rem; }
        .permissions-index-page .perm-card { padding: 0.8rem 0.85rem; }
    }

    /* ════════════════════════════════════════════════════════
       A11Y + PRINT
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .permissions-index-page *,
        .permissions-index-page *::before,
        .permissions-index-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }

    @media print {
        .permissions-index-page .filters-card,
        .permissions-index-page .content-footer,
        .permissions-index-page .header-actions,
        .permissions-index-page .col-actions,
        .permissions-index-page .perm-card-actions {
            display: none !important;
        }
        .permissions-index-page .content-card {
            box-shadow: none; border: 1px solid #ccc;
        }
        .permissions-index-page .table-wrapper { display: block !important; }
        .permissions-index-page .permissions-cards { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    /**
     * ✅ Debounce auto-submit pour la recherche.
     * Soumet le formulaire après 400ms d'inactivité.
     */
    function permissionFilters() {
        return {
            init() {
                const searchInput = document.getElementById('search');
                if (!searchInput) return;

                let timer;
                searchInput.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        if (searchInput.value.length === 0 || searchInput.value.length >= 2) {
                            searchInput.form?.submit();
                        }
                    }, 400);
                });
            },
        };
    }
</script>
@endpush