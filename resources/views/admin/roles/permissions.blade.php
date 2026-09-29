@extends('layouts.admin')

@section('page_title', 'Permissions du rôle')
@section('page_subtitle', 'Attribuez des permissions groupées par page et action')

@section('content')
<div class="role-permissions-page"
     x-data="rolePermissionsManager({
         initialSelected: {{ Js::from($rolePermissions) }},
         permissionsData: {{ Js::from($permissions) }}
     })"
     x-init="init()">

    {{-- ════════════════════════════════════════════════════════
         HEADER
         ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-key title-icon" aria-hidden="true"></i>
                <span>Permissions du rôle</span>
                <span class="role-badge">{{ $role->label ?? $role->name }}</span>
            </h1>
            <p class="page-subtitle">
                Sélectionnez les permissions attribuées à ce rôle
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.roles.index') }}"
               class="btn btn-ghost"
               @click="onCancelClick($event)">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour aux rôles</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════
         FORMULAIRE
         ════════════════════════════════════════════════════════ --}}
    <form action="{{ route('admin.roles.sync-permissions', $role) }}"
          method="POST"
          @submit="onSubmit($event)"
          class="content-card"
          novalidate>

        @csrf

        {{-- ════════════════════════════════════════════════════
             TOOLBAR STICKY
             ════════════════════════════════════════════════════ --}}
        <div class="toolbar">

            <div class="toolbar-row">
                <nav class="tabs" role="tablist" aria-label="Scopes de permissions">
                    <button type="button"
                            role="tab"
                            class="tab"
                            :class="{ 'is-active': activeScope === 'admin' }"
                            :aria-selected="activeScope === 'admin'"
                            :tabindex="activeScope === 'admin' ? 0 : -1"
                            @click="setScope('admin')">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        <span>Administration</span>
                        <span class="tab-badge"
                              x-text="selectedCountByScope.admin + '/' + totalCountByScope.admin"></span>
                    </button>
                    <button type="button"
                            role="tab"
                            class="tab"
                            :class="{ 'is-active': activeScope === 'member' }"
                            :aria-selected="activeScope === 'member'"
                            :tabindex="activeScope === 'member' ? 0 : -1"
                            @click="setScope('member')">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <span>Espace membre</span>
                        <span class="tab-badge"
                              x-text="selectedCountByScope.member + '/' + totalCountByScope.member"></span>
                    </button>
                </nav>

                <div class="search-field">
                    <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="search"
                           x-model.debounce.200ms="searchQuery"
                           placeholder="Rechercher une permission…"
                           aria-label="Rechercher une permission"
                           autocomplete="off">
                    <button type="button"
                            class="search-clear"
                            x-show="searchQuery.length > 0"
                            x-cloak
                            @click="searchQuery = ''"
                            aria-label="Effacer la recherche">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="toolbar-row toolbar-actions">
                <div class="toolbar-info" aria-live="polite">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>
                        <strong x-text="totalVisible"></strong>
                        <span x-text="totalVisible > 1 ? ' permissions affichées' : ' permission affichée'"></span>
                    </span>
                </div>

                <div class="toolbar-buttons">
                    <button type="button" class="link-btn link-success"
                            @click="setAllVisible(true)"
                            :disabled="totalVisible === 0">
                        <i class="fa-solid fa-check-double" aria-hidden="true"></i>
                        <span>Tout cocher</span>
                    </button>
                    <button type="button" class="link-btn link-muted"
                            @click="setAllVisible(false)"
                            :disabled="totalVisible === 0">
                        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                        <span>Tout décocher</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════
             CORPS — GRILLE DE PERMISSIONS
             ════════════════════════════════════════════════════ --}}
        <div class="permissions-body">

            {{-- Vide : recherche sans résultat --}}
            <template x-if="totalVisible === 0 && searchQuery.length > 0">
                <div class="empty-state">
                    <div class="empty-icon-wrapper">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </div>
                    <h3 class="empty-title">Aucun résultat</h3>
                    <p class="empty-text">
                        Aucune permission ne correspond à «&nbsp;<strong x-text="searchQuery"></strong>&nbsp;».
                    </p>
                    <button type="button" class="btn btn-ghost" @click="searchQuery = ''">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        <span>Effacer la recherche</span>
                    </button>
                </div>
            </template>

            {{-- Vide : scope sans permissions --}}
            <template x-if="totalVisible === 0 && searchQuery.length === 0">
                <div class="empty-state">
                    <div class="empty-icon-wrapper">
                        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                    </div>
                    <h3 class="empty-title">Aucune permission</h3>
                    <p class="empty-text">
                        Ce scope ne contient encore aucune permission.
                    </p>
                </div>
            </template>

            {{-- Grille --}}
            <div class="permissions-grid"
                 x-show="totalVisible > 0"
                 x-cloak>
                <template x-for="(perms, resource) in filteredPermissions" :key="resource">
                    <article class="resource-card"
                             :class="{ 'has-selected': selectedCountByResource[resource] > 0 }">

                        <header class="resource-header">
                            <div class="resource-title">
                                <h3 x-text="prettyResource(resource)"></h3>
                                <span class="resource-count"
                                      x-text="(selectedCountByResource[resource] || 0) + '/' + perms.length"></span>
                            </div>
                            <div class="resource-actions">
                                <button type="button"
                                        class="link-mini"
                                        @click="setResourceAll(resource, true)"
                                        :disabled="(selectedCountByResource[resource] || 0) === perms.length"
                                        :aria-label="`Tout cocher pour ${resource}`"
                                        title="Tout cocher">
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                </button>
                                <button type="button"
                                        class="link-mini muted"
                                        @click="setResourceAll(resource, false)"
                                        :disabled="(selectedCountByResource[resource] || 0) === 0"
                                        :aria-label="`Tout décocher pour ${resource}`"
                                        title="Tout décocher">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                        </header>

                        <div class="resource-list" role="group" :aria-label="`Permissions pour ${resource}`">
                            <template x-for="perm in perms" :key="perm.id">
                                <label class="perm-item"
                                       :class="{ 'is-selected': selectedSet.has(Number(perm.id)) }">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           :value="perm.id"
                                           :checked="selectedSet.has(Number(perm.id))"
                                           @change="toggle(perm.id, $event.target.checked)"
                                           class="perm-checkbox">
                                    <span class="perm-checkmark" aria-hidden="true"></span>
                                    <span class="perm-label"
                                          x-text="perm.label || perm.action || perm.name"></span>
                                </label>
                            </template>
                        </div>
                    </article>
                </template>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════
             FOOTER STICKY
             ════════════════════════════════════════════════════ --}}
        <footer class="content-footer">
            <div class="footer-info">
                <span class="footer-count" aria-live="polite">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <strong x-text="selectedIds.length"></strong>
                    <span x-text="selectedIds.length > 1 ? 'permissions sélectionnées' : 'permission sélectionnée'"></span>
                </span>
                <span class="pending-badge" x-show="hasChanges" x-cloak>
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    Modifications en attente
                </span>
            </div>

            <div class="footer-actions">
                <a href="{{ route('admin.roles.index') }}"
                   class="btn btn-ghost"
                   @click="onCancelClick($event)">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    <span>Annuler</span>
                </a>
                <button type="submit"
                        class="btn btn-primary"
                        :disabled="submitting || !hasChanges"
                        :aria-busy="submitting">
                    <template x-if="!submitting">
                        <span class="btn-content">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            <span>Enregistrer</span>
                        </span>
                    </template>
                    <template x-if="submitting">
                        <span class="btn-content">
                            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                            <span>Enregistrement…</span>
                        </span>
                    </template>
                </button>
            </div>
        </footer>
    </form>
</div>
@endsection

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       DESIGN TOKENS — ✅ scopés sous .role-permissions-page
       ════════════════════════════════════════════════════════ */
    .role-permissions-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;
        --c-emerald-mid:  #a7f3d0;

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
        --shadow-md: 0 8px 20px rgba(79,70,229,0.10);

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        /* ✅ CORRECTION #4 : hauteur header admin adaptative */
        --admin-header-height: 70px;
        --toolbar-gap: 8px;
        --footer-space: 90px;  /* espace pour le footer sticky */

        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--c-slate-800);
    }

    @media (min-width: 1024px) {
        .role-permissions-page { --admin-header-height: 72px; }
    }
    @media (max-width: 768px) {
        .role-permissions-page {
            --admin-header-height: 60px;
            --footer-space: 140px;  /* footer 2 lignes en mobile */
        }
    }
    @media (max-width: 480px) {
        .role-permissions-page { --footer-space: 150px; }
    }

    .role-permissions-page *,
    .role-permissions-page *::before,
    .role-permissions-page *::after { box-sizing: border-box; }

    [x-cloak] { display: none !important; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .page-header {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .role-permissions-page .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .role-permissions-page .page-header-text { min-width: 0; }

    .role-permissions-page .page-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--c-slate-900);
        letter-spacing: -0.5px;
        margin: 0 0 0.25rem;
    }
    .role-permissions-page .title-icon { color: #6366f1; font-size: 1.4rem; }

    .role-permissions-page .role-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background: var(--c-primary-soft);
        color: var(--c-primary);
        border-radius: 9999px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .role-permissions-page .page-subtitle {
        color: var(--c-slate-500);
        font-size: 0.9rem;
        margin: 0;
    }

    .role-permissions-page .header-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    @media (max-width: 767px) {
        .role-permissions-page .header-actions { width: 100%; }
        .role-permissions-page .header-actions .btn { flex: 1 1 100%; }
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .btn {
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
        min-height: 44px;
    }

    .role-permissions-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .role-permissions-page .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .role-permissions-page .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .role-permissions-page .btn-ghost {
        background: #fff;
        color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .role-permissions-page .btn-ghost:hover {
        border-color: #6366f1;
        color: var(--c-primary);
        background: var(--c-slate-50);
    }

    .role-permissions-page .btn:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    .role-permissions-page .btn-content {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .content-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }

    /* ════════════════════════════════════════════════════════
       TOOLBAR STICKY — ✅ CORRECTION #4
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .toolbar {
        position: sticky;
        top: calc(var(--admin-header-height) + var(--toolbar-gap));
        z-index: 20;
        background: rgba(255,255,255,0.98);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--c-slate-100);
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .role-permissions-page .toolbar-row {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    @media (min-width: 768px) {
        .role-permissions-page .toolbar-row {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    /* Tabs */
    .role-permissions-page .tabs {
        display: flex;
        gap: 0.4rem;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-bottom: 2px;
    }
    .role-permissions-page .tabs::-webkit-scrollbar { display: none; }

    .role-permissions-page .tab {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1rem;
        background: transparent;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 0.85rem;
        font-family: inherit;
        color: var(--c-slate-500);
        cursor: pointer;
        transition: all var(--t);
        white-space: nowrap;
        flex-shrink: 0;
        min-height: 40px;
    }
    .role-permissions-page .tab:hover {
        background: var(--c-slate-50);
        color: var(--c-slate-900);
        border-color: var(--c-slate-300);
    }
    .role-permissions-page .tab.is-active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 4px 10px rgba(79,70,229,0.25);
    }
    .role-permissions-page .tab:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    .role-permissions-page .tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        padding: 0.1rem 0.4rem;
        background: rgba(255,255,255,0.25);
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .role-permissions-page .tab:not(.is-active) .tab-badge {
        background: var(--c-slate-100);
        color: var(--c-slate-500);
    }

    /* Recherche */
    .role-permissions-page .search-field {
        position: relative;
        width: 100%;
        max-width: 400px;
    }
    @media (min-width: 768px) {
        .role-permissions-page .search-field { width: 340px; }
    }

    .role-permissions-page .search-icon {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400);
        font-size: 0.85rem;
        pointer-events: none;
    }
    .role-permissions-page .search-field input {
        width: 100%;
        padding: 0.65rem 2.5rem 0.65rem 2.4rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.9rem;
        font-family: inherit;
        color: var(--c-slate-900);
        outline: none;
        transition: all var(--t);
        min-height: 44px;
    }
    .role-permissions-page .search-field input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }

    .role-permissions-page .search-clear {
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        background: var(--c-slate-100);
        border: none;
        border-radius: 6px;
        color: var(--c-slate-500);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--t);
    }
    .role-permissions-page .search-clear:hover {
        background: var(--c-slate-200);
        color: var(--c-slate-900);
    }

    /* Toolbar actions */
    .role-permissions-page .toolbar-actions {
        padding-top: 0.75rem;
        border-top: 1px dashed var(--c-slate-200);
    }
    .role-permissions-page .toolbar-info {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--c-slate-500);
        min-width: 0;
    }
    .role-permissions-page .toolbar-info i { color: #6366f1; flex-shrink: 0; }
    .role-permissions-page .toolbar-info strong { color: var(--c-slate-900); font-weight: 700; }

    .role-permissions-page .toolbar-buttons {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .role-permissions-page .link-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.75rem;
        background: transparent;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        font-family: inherit;
        cursor: pointer;
        transition: all var(--t);
        min-height: 38px;
    }
    .role-permissions-page .link-btn:hover:not(:disabled) { background: var(--c-slate-100); }
    .role-permissions-page .link-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .role-permissions-page .link-success { color: var(--c-emerald); }
    .role-permissions-page .link-success:hover:not(:disabled) { background: var(--c-emerald-soft); }
    .role-permissions-page .link-muted { color: var(--c-slate-500); }
    .role-permissions-page .link-muted:hover:not(:disabled) {
        background: var(--c-slate-100);
        color: var(--c-slate-900);
    }

    /* ════════════════════════════════════════════════════════
       CORPS — ✅ CORRECTION #5 : padding-bottom compensatoire
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .permissions-body {
        padding: 1.25rem;
        padding-bottom: calc(1.25rem + var(--footer-space));
    }

    .role-permissions-page .permissions-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px) {
        .role-permissions-page .permissions-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1200px) {
        .role-permissions-page .permissions-grid { grid-template-columns: repeat(3, 1fr); }
    }

    /* ════════════════════════════════════════════════════════
       CARTE RESSOURCE
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .resource-card {
        background: var(--c-slate-50);
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-md);
        padding: 1rem;
        transition: all var(--t);
        animation: rpFadeIn 0.3s ease-out both;
        min-width: 0;
    }

    @keyframes rpFadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .role-permissions-page .resource-card.has-selected {
        background: #f5f7ff;
        border-color: var(--c-primary-mid);
        box-shadow: 0 2px 8px rgba(99,102,241,0.08);
    }

    .role-permissions-page .resource-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .role-permissions-page .resource-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
        flex: 1;
    }
    .role-permissions-page .resource-title h3 {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--c-slate-900);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .role-permissions-page .resource-count {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.5rem;
        background: var(--c-slate-200);
        color: var(--c-slate-600);
        border-radius: 9999px;
        font-size: 0.68rem;
        font-weight: 700;
        flex-shrink: 0;
        font-variant-numeric: tabular-nums;
    }
    .role-permissions-page .has-selected .resource-count {
        background: var(--c-primary-mid);
        color: #4338ca;
    }

    .role-permissions-page .resource-actions {
        display: inline-flex;
        gap: 0.25rem;
        flex-shrink: 0;
    }
    .role-permissions-page .link-mini {
        width: 30px;
        height: 30px;
        background: transparent;
        border: 1px solid var(--c-slate-200);
        border-radius: 6px;
        color: var(--c-emerald);
        cursor: pointer;
        transition: all var(--t);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
    }
    .role-permissions-page .link-mini:hover:not(:disabled) {
        background: var(--c-emerald-soft);
        border-color: var(--c-emerald-mid);
    }
    .role-permissions-page .link-mini.muted { color: var(--c-slate-500); }
    .role-permissions-page .link-mini.muted:hover:not(:disabled) {
        background: var(--c-slate-100);
        border-color: var(--c-slate-300);
    }
    .role-permissions-page .link-mini:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .role-permissions-page .link-mini:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    /* ════════════════════════════════════════════════════════
       ITEMS PERMISSION
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .resource-list {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        max-height: 260px;
        overflow-y: auto;
        padding-right: 0.25rem;
        scrollbar-width: thin;
    }
    .role-permissions-page .resource-list::-webkit-scrollbar { width: 6px; }
    .role-permissions-page .resource-list::-webkit-scrollbar-thumb {
        background: var(--c-slate-300);
        border-radius: 3px;
    }

    .role-permissions-page .perm-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.55rem 0.65rem;
        border-radius: 8px;
        font-size: 0.85rem;
        color: var(--c-slate-600);
        cursor: pointer;
        transition: background var(--t);
        user-select: none;
        min-height: 40px;
    }
    .role-permissions-page .perm-item:hover { background: #fff; }
    .role-permissions-page .perm-item.is-selected {
        background: var(--c-primary-soft);
        color: var(--c-slate-900);
        font-weight: 600;
    }

    .role-permissions-page .perm-checkbox {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 0;
        height: 0;
    }
    .role-permissions-page .perm-checkmark {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        border: 2px solid var(--c-slate-300);
        border-radius: 5px;
        background: #fff;
        position: relative;
        transition: all var(--t);
    }
    .role-permissions-page .perm-checkmark::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 1px;
        width: 5px;
        height: 9px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg) scale(0);
        transition: transform var(--t);
    }
    .role-permissions-page .perm-item.is-selected .perm-checkmark {
        background: var(--c-primary);
        border-color: var(--c-primary);
    }
    .role-permissions-page .perm-item.is-selected .perm-checkmark::after {
        transform: rotate(45deg) scale(1);
    }
    .role-permissions-page .perm-checkbox:focus-visible + .perm-checkmark {
        box-shadow: 0 0 0 3px rgba(99,102,241,0.3);
        border-color: var(--c-primary);
    }

    .role-permissions-page .perm-label {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ════════════════════════════════════════════════════════
       FOOTER STICKY
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .content-footer {
        position: sticky;
        bottom: 0;
        z-index: 20;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem 1.25rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
        background: #fff;
        border-top: 1px solid var(--c-slate-200);
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }
    @media (min-width: 768px) {
        .role-permissions-page .content-footer {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .role-permissions-page .footer-info {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
        color: var(--c-slate-500);
    }
    .role-permissions-page .footer-count {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .role-permissions-page .footer-count i { color: #6366f1; }
    .role-permissions-page .footer-count strong {
        color: var(--c-primary);
        font-size: 1rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .role-permissions-page .pending-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        background: #fffbeb;
        color: #b45309;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .role-permissions-page .footer-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    @media (max-width: 640px) {
        .role-permissions-page .footer-actions {
            width: 100%;
            flex-direction: column-reverse;
        }
        .role-permissions-page .footer-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .role-permissions-page .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center;
        gap: 0.5rem;
    }
    .role-permissions-page .empty-icon-wrapper {
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
    .role-permissions-page .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--c-slate-700);
        margin: 0;
    }
    .role-permissions-page .empty-text {
        font-size: 0.9rem;
        color: var(--c-slate-400);
        max-width: 420px;
        margin: 0;
        line-height: 1.5;
    }
    .role-permissions-page .empty-text strong { color: var(--c-slate-600); }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .role-permissions-page { padding: 1.5rem 1rem; }
    }

    @media (max-width: 768px) {
        .role-permissions-page { padding: 1.25rem 0.85rem; }
        .role-permissions-page .page-title { font-size: 1.35rem; }
        .role-permissions-page .title-icon { font-size: 1.2rem; }
        .role-permissions-page .page-subtitle { font-size: 0.85rem; }

        .role-permissions-page .content-card { border-radius: var(--radius-md); }

        .role-permissions-page .toolbar { padding: 0.85rem 1rem; }

        .role-permissions-page .permissions-body { padding: 1rem; }
        .role-permissions-page .permissions-grid { gap: 0.75rem; }

        .role-permissions-page .resource-card { padding: 0.85rem; }
        .role-permissions-page .resource-list { max-height: 220px; }

        .role-permissions-page .content-footer {
            padding: 0.85rem 1rem;
            padding-bottom: calc(0.85rem + env(safe-area-inset-bottom, 0px));
        }

        /* Anti-zoom iOS */
        .role-permissions-page .search-field input { font-size: 16px; }
    }

    @media (max-width: 480px) {
        .role-permissions-page { padding: 1rem 0.6rem; }
        .role-permissions-page .page-title { font-size: 1.15rem; }
        .role-permissions-page .role-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
        }

        .role-permissions-page .toolbar { padding: 0.75rem 0.85rem; }
        .role-permissions-page .tabs { gap: 0.3rem; }
        .role-permissions-page .tab {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }
        .role-permissions-page .tab-badge {
            min-width: 40px;
            font-size: 0.65rem;
        }

        .role-permissions-page .toolbar-info { font-size: 0.8rem; }
        .role-permissions-page .link-btn { font-size: 0.75rem; padding: 0.45rem 0.6rem; }

        .role-permissions-page .permissions-body { padding: 0.85rem; }
        .role-permissions-page .resource-card {
            padding: 0.75rem;
            border-radius: var(--radius-sm);
        }
        .role-permissions-page .resource-title h3 { font-size: 0.85rem; }
        .role-permissions-page .resource-count { font-size: 0.62rem; }

        .role-permissions-page .perm-item {
            padding: 0.5rem 0.55rem;
            font-size: 0.82rem;
            min-height: 42px;
        }
        .role-permissions-page .perm-checkmark { width: 17px; height: 17px; }

        .role-permissions-page .footer-info { font-size: 0.85rem; }

        .role-permissions-page .empty-icon-wrapper { width: 64px; height: 64px; font-size: 1.6rem; }
        .role-permissions-page .empty-title { font-size: 1rem; }
        .role-permissions-page .empty-text { font-size: 0.85rem; }
    }

    @media (max-width: 360px) {
        .role-permissions-page { padding: 0.85rem 0.5rem; }
        .role-permissions-page .page-title { font-size: 1.05rem; }
        .role-permissions-page .title-icon { font-size: 1rem; }

        .role-permissions-page .tab {
            padding: 0.45rem 0.6rem;
            font-size: 0.75rem;
            gap: 0.35rem;
        }
        .role-permissions-page .tab-badge { min-width: 36px; font-size: 0.6rem; }

        .role-permissions-page .resource-card { padding: 0.65rem; }
        .role-permissions-page .perm-item {
            padding: 0.45rem 0.5rem;
            font-size: 0.78rem;
        }

        .role-permissions-page .footer-count span { display: none; }
    }

    /* ════════════════════════════════════════════════════════
       IMPRESSION
       ════════════════════════════════════════════════════════ */
    @media print {
        .role-permissions-page .toolbar,
        .role-permissions-page .content-footer,
        .role-permissions-page .header-actions { display: none !important; }
        .role-permissions-page .content-card {
            box-shadow: none;
            border: 1px solid #ccc;
        }
        .role-permissions-page .resource-card { break-inside: avoid; }
        .role-permissions-page .permissions-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    /* ════════════════════════════════════════════════════════
       ACCESSIBILITÉ
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .role-permissions-page *,
        .role-permissions-page *::before,
        .role-permissions-page *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
        .role-permissions-page .resource-card { animation: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    // ✅ CORRECTION #2 : enregistrement via Alpine.data() avec fallback.
    function registerRolePermissionsManager() {
        const factory = (config) => ({
            /* ============================================================
               ÉTAT
               ============================================================ */
            activeScope: 'admin',
            searchQuery: '',
            selectedIds: [],
            selectedSet: new Set(),
            initialIds: [],
            initialSet: new Set(),
            permissionsData: config.permissionsData || { admin: {}, member: {} },
            submitting: false,

            // Caches internes (invalidés par clé)
            _filteredCache: {},
            _filteredKey: '',
            selectedCountByScope: { admin: 0, member: 0 },
            totalCountByScope: { admin: 0, member: 0 },
            selectedCountByResource: {},

            /* ============================================================
               INIT
               ============================================================ */
            init() {
                // ✅ CORRECTION : Set pour lookups O(1)
                this.initialIds = (config.initialSelected || []).map(Number);
                this.initialSet = new Set(this.initialIds);

                this.selectedIds = [...this.initialIds];
                this.selectedSet = new Set(this.initialIds);

                // ✅ CORRECTION #8 : re-init sur retour arrière
                window.addEventListener('pageshow', () => {
                    this.submitting = false;
                });

                // ✅ CORRECTION #7 : warning si modifications non sauvegardées
                window.addEventListener('beforeunload', (e) => {
                    if (this.hasChanges && !this.submitting) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                this.recomputeAll();

                // Auto-sélectionne le scope dominant
                if (this.selectedCountByScope.member > this.selectedCountByScope.admin) {
                    this.activeScope = 'member';
                }
            },

            /* ============================================================
               GETTERS
               ============================================================ */

            get currentScopePermissions() {
                return this.permissionsData[this.activeScope] || {};
            },

            get filteredPermissions() {
                // ✅ CORRECTION #6 : cache invalidé par clé
                const key = this.activeScope + '||' + this.searchQuery;
                if (key !== this._filteredKey) {
                    this._filteredCache = this.computeFiltered();
                    this._filteredKey = key;
                }
                return this._filteredCache;
            },

            get totalVisible() {
                let count = 0;
                const perms = this.filteredPermissions;
                for (const resource in perms) {
                    count += perms[resource].length;
                }
                return count;
            },

            get hasChanges() {
                if (this.selectedSet.size !== this.initialSet.size) return true;
                for (const id of this.initialSet) {
                    if (!this.selectedSet.has(id)) return true;
                }
                return false;
            },

            /* ============================================================
               CALCULS INTERNES (memoized)
               ============================================================ */

            computeFiltered() {
                const query = (this.searchQuery || '').toLowerCase().trim();
                const scope = this.currentScopePermissions;

                if (!query) return scope;

                const result = {};
                for (const resource in scope) {
                    const filtered = scope[resource].filter(p => {
                        const label  = (p.label  || '').toLowerCase();
                        const action = (p.action || '').toLowerCase();
                        const name   = (p.name   || '').toLowerCase();
                        const res    = resource.toLowerCase();

                        return label.includes(query)
                            || action.includes(query)
                            || name.includes(query)
                            || res.includes(query);
                    });

                    if (filtered.length > 0) {
                        result[resource] = filtered;
                    }
                }
                return result;
            },

            recomputeAll() {
                // Compteurs par scope
                const counts = { admin: 0, member: 0 };
                const totals = { admin: 0, member: 0 };

                for (const scope in this.permissionsData) {
                    const byResource = this.permissionsData[scope] || {};
                    for (const res in byResource) {
                        const list = byResource[res];
                        totals[scope] += list.length;
                        for (const p of list) {
                            if (this.selectedSet.has(Number(p.id))) {
                                counts[scope]++;
                            }
                        }
                    }
                }

                this.selectedCountByScope = counts;
                this.totalCountByScope = totals;

                // Compteurs par ressource du scope actif
                const byResource = {};
                const currentScope = this.currentScopePermissions;
                for (const res in currentScope) {
                    let c = 0;
                    for (const p of currentScope[res]) {
                        if (this.selectedSet.has(Number(p.id))) c++;
                    }
                    byResource[res] = c;
                }
                this.selectedCountByResource = byResource;
            },

            /* ============================================================
               ACTIONS
               ============================================================ */

            setScope(scope) {
                this.activeScope = scope;
                this.searchQuery = '';
                this.recomputeAll();
            },

            toggle(id, checked) {
                const intId = Number(id);

                if (checked) {
                    if (!this.selectedSet.has(intId)) {
                        this.selectedSet.add(intId);
                        this.selectedIds = [...this.selectedIds, intId];
                    }
                } else {
                    if (this.selectedSet.delete(intId)) {
                        this.selectedIds = this.selectedIds.filter(x => x !== intId);
                    }
                }

                this.recomputeAll();
            },

            setAllVisible(checked) {
                const visibleIds = this._getVisibleIds();

                if (checked) {
                    let changed = false;
                    for (const id of visibleIds) {
                        if (!this.selectedSet.has(id)) {
                            this.selectedSet.add(id);
                            this.selectedIds.push(id);
                            changed = true;
                        }
                    }
                    if (changed) {
                        this.selectedIds = [...this.selectedIds];
                        this.recomputeAll();
                    }
                } else {
                    const toRemove = new Set(visibleIds);
                    const before = this.selectedIds.length;
                    this.selectedIds = this.selectedIds.filter(id => !toRemove.has(id));

                    if (this.selectedIds.length !== before) {
                        this.selectedSet = new Set(this.selectedIds);
                        this.recomputeAll();
                    }
                }
            },

            setResourceAll(resource, checked) {
                const perms = this.currentScopePermissions[resource] || [];
                const ids = perms.map(p => Number(p.id));

                if (checked) {
                    let changed = false;
                    for (const id of ids) {
                        if (!this.selectedSet.has(id)) {
                            this.selectedSet.add(id);
                            this.selectedIds.push(id);
                            changed = true;
                        }
                    }
                    if (changed) {
                        this.selectedIds = [...this.selectedIds];
                        this.recomputeAll();
                    }
                } else {
                    const toRemove = new Set(ids);
                    const before = this.selectedIds.length;
                    this.selectedIds = this.selectedIds.filter(id => !toRemove.has(id));

                    if (this.selectedIds.length !== before) {
                        this.selectedSet = new Set(this.selectedIds);
                        this.recomputeAll();
                    }
                }
            },

            /* ============================================================
               HELPERS
               ============================================================ */

            _getVisibleIds() {
                const ids = [];
                const perms = this.filteredPermissions;
                for (const res in perms) {
                    for (const p of perms[res]) {
                        ids.push(Number(p.id));
                    }
                }
                return ids;
            },

            prettyResource(resource) {
                return String(resource)
                    .replace(/[-_]/g, ' ')
                    .replace(/\b\w/g, c => c.toUpperCase());
            },

            /* ============================================================
               SOUMISSION
               ============================================================ */

            onSubmit(e) {
                if (this.submitting) {
                    e.preventDefault();
                    return;
                }
                this.submitting = true;
            },

            onCancelClick(e) {
                if (this.hasChanges && !this.submitting) {
                    if (!confirm('Vous avez des modifications non enregistrées. Quitter ?')) {
                        e.preventDefault();
                        return;
                    }
                }
            },
        });

        // ✅ Enregistrement auprès d'Alpine (avec fallback si déjà prêt)
        if (window.Alpine) {
            window.Alpine.data('rolePermissionsManager', factory);
        } else {
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('rolePermissionsManager', factory);
            });
        }
    }

    registerRolePermissionsManager();
</script>
@endpush