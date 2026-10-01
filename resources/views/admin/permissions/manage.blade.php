@extends('layouts.admin')

@section('page_title', 'Gestion avancée des permissions')
@section('page_subtitle', 'Attribuez des permissions groupées par page et par action')

@section('content')
<div class="pm-wrapper" x-data="permissionManager()" x-init="init()">

    {{-- ═══════════════════════════════════════════════════════════
         EN-TÊTE
         ═══════════════════════════════════════════════════════════ --}}
    <div class="pm-header">
        <div class="pm-header-left">
            <div class="pm-icon-wrap">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="pm-header-text">
                <h1 class="pm-title">Gestion avancée des permissions</h1>
                <p class="pm-subtitle">Sélectionnez un rôle ou un utilisateur, puis cochez les permissions autorisées</p>
            </div>
        </div>

        <div class="pm-header-actions">
            <a href="{{ route('admin.permissions.sync-all') }}"
               class="pm-btn pm-btn-ghost"
               onclick="return confirm('Synchroniser les permissions depuis les routes ?')">
                <i class="fa-solid fa-rotate"></i>
                <span class="pm-btn-label">Synchroniser</span>
            </a>
            <a href="{{ route('admin.permissions.create') }}" class="pm-btn pm-btn-primary">
                <i class="fa-solid fa-plus"></i>
                <span class="pm-btn-label">Nouvelle permission</span>
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         SÉLECTEUR DE CIBLE
         ═══════════════════════════════════════════════════════════ --}}
    <div class="pm-card pm-target-card">
        <div class="pm-target-grid">
            <div class="pm-target-field">
                <label class="pm-label">
                    <i class="fa-solid fa-crosshairs"></i>
                    Cible (rôle ou utilisateur)
                </label>
                <div class="pm-select-wrap">
                    <select x-model="selectedTarget"
                            @change="loadPermissions()"
                            class="pm-select">
                        <option value="">— Choisir une cible —</option>
                        <optgroup label="🎭 Rôles">
                            @foreach($roles as $role)
                                <option value="role:{{ $role->id }}">
                                    {{ $role->label ?? $role->name }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="👤 Utilisateurs">
                            @foreach($users as $user)
                                <option value="user:{{ $user->id }}">
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <i class="fa-solid fa-chevron-down pm-select-icon"></i>
                </div>
            </div>

            {{-- Compteur animé --}}
            <div class="pm-target-counter" :class="{ 'is-active': selectedCount > 0 }">
                <div class="pm-counter-value" x-text="selectedCount"></div>
                <div class="pm-counter-label">
                    <span x-text="selectedCount <= 1 ? 'permission' : 'permissions'"></span>
                    <span>sélectionnée<span x-show="selectedCount > 1">s</span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         LOADING
         ═══════════════════════════════════════════════════════════ --}}
    <div x-show="loading" x-cloak class="pm-loading">
        <div class="pm-spinner"></div>
        <p class="pm-loading-text">Chargement des permissions...</p>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PERMISSIONS
         ═══════════════════════════════════════════════════════════ --}}
    <template x-if="!loading && selectedTarget">
        <div class="pm-content">

            {{-- ─── Barre de contrôle ─── --}}
            <div class="pm-toolbar">
                {{-- Onglets Admin / Membre --}}
                <div class="pm-tabs">
                    <button type="button"
                            @click="setScope('admin')"
                            :class="activeScope === 'admin' ? 'is-active' : ''"
                            class="pm-tab">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Administration</span>
                        <span class="pm-tab-count" x-text="countInScope('admin')"></span>
                    </button>
                    <button type="button"
                            @click="setScope('member')"
                            :class="activeScope === 'member' ? 'is-active' : ''"
                            class="pm-tab">
                        <i class="fa-solid fa-users"></i>
                        <span>Espace membre</span>
                        <span class="pm-tab-count" x-text="countInScope('member')"></span>
                    </button>
                </div>

                {{-- Actions globales --}}
                <div class="pm-toolbar-actions">
                    <button type="button" @click="setAll(true)" class="pm-action-link is-primary">
                        <i class="fa-solid fa-check-double"></i>
                        <span>Tout cocher</span>
                    </button>
                    <button type="button" @click="setAll(false)" class="pm-action-link is-muted">
                        <i class="fa-regular fa-square"></i>
                        <span>Tout décocher</span>
                    </button>
                </div>
            </div>

            {{-- ─── Barre de recherche ─── --}}
            <div class="pm-search-wrap">
                <i class="fa-solid fa-magnifying-glass pm-search-icon"></i>
                <input type="search"
                       x-model="searchQuery"
                       placeholder="Rechercher une permission..."
                       class="pm-search-input">
                <button type="button"
                        x-show="searchQuery.length > 0"
                        @click="searchQuery = ''"
                        class="pm-search-clear"
                        aria-label="Effacer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- ─── Grille de permissions ─── --}}
            <div class="pm-grid">
                <template x-for="(permissions, resource) in filteredPermissions" :key="resource">
                    <div class="pm-card pm-resource-card"
                         :class="{ 'is-fully-checked': isResourceFullyChecked(resource) }">

                        {{-- En-tête ressource --}}
                        <div class="pm-resource-header">
                            <div class="pm-resource-info">
                                <div class="pm-resource-icon">
                                    <i class="fa-solid" :class="getResourceIcon(resource)"></i>
                                </div>
                                <div class="pm-resource-text">
                                    <h3 class="pm-resource-title" x-text="resource"></h3>
                                    <p class="pm-resource-count">
                                        <span x-text="countCheckedInResource(resource)"></span>
                                        /
                                        <span x-text="permissions.length"></span>
                                    </p>
                                </div>
                            </div>

                            <div class="pm-resource-actions">
                                <button type="button"
                                        @click="setResourceAll(resource, true)"
                                        class="pm-mini-btn"
                                        title="Tout cocher">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button"
                                        @click="setResourceAll(resource, false)"
                                        class="pm-mini-btn is-muted"
                                        title="Tout décocher">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Liste des permissions --}}
                        <div class="pm-permission-list">
                            <template x-for="perm in permissions" :key="perm.name">
                                <label class="pm-permission-item"
                                       :class="{ 'is-checked': isChecked(perm.name) }">
                                    <input type="checkbox"
                                           :value="perm.name"
                                           x-model="selectedPermissions"
                                           class="pm-checkbox-input">
                                    <span class="pm-checkbox">
                                        <i class="fa-solid fa-check pm-checkbox-mark"></i>
                                    </span>
                                    <span class="pm-permission-label" x-text="perm.label || perm.action"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Aucun résultat de recherche --}}
                <div x-show="Object.keys(filteredPermissions).length === 0 && searchQuery.length > 0"
                     class="pm-empty-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <p>Aucune permission ne correspond à « <strong x-text="searchQuery"></strong> »</p>
                    <button type="button" @click="searchQuery = ''" class="pm-btn pm-btn-ghost">
                        <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                    </button>
                </div>

                {{-- Aucune permission dans ce scope --}}
                <div x-show="Object.keys(filteredPermissions).length === 0 && searchQuery.length === 0"
                     class="pm-empty-search">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>Aucune permission dans cet espace</p>
                </div>
            </div>

            {{-- ─── Barre d'action sticky ─── --}}
            <div class="pm-action-bar">
                <div class="pm-action-bar-info">
                    <div class="pm-action-bar-count" x-text="selectedCount"></div>
                    <div class="pm-action-bar-text">
                        permission<span x-show="selectedCount > 1">s</span>
                        sélectionnée<span x-show="selectedCount > 1">s</span>
                    </div>
                </div>

                <div class="pm-action-bar-buttons">
                    <button type="button"
                            @click="resetForm()"
                            class="pm-btn pm-btn-ghost"
                            :disabled="saving">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span class="pm-btn-label">Réinitialiser</span>
                    </button>
                    <button type="button"
                            @click="savePermissions()"
                            class="pm-btn pm-btn-primary pm-btn-save"
                            :disabled="saving || !hasChanges">
                        <template x-if="!saving">
                            <span class="pm-btn-content">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span class="pm-btn-label">Enregistrer</span>
                            </span>
                        </template>
                        <template x-if="saving">
                            <span class="pm-btn-content">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                <span class="pm-btn-label">Enregistrement...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ═══════════════════════════════════════════════════════════
         ÉTAT VIDE — Aucune cible sélectionnée
         ═══════════════════════════════════════════════════════════ --}}
    <template x-if="!loading && !selectedTarget">
        <div class="pm-empty">
            <div class="pm-empty-icon">
                <i class="fa-regular fa-hand-pointer"></i>
            </div>
            <h3 class="pm-empty-title">Sélectionnez une cible pour commencer</h3>
            <p class="pm-empty-text">
                Choisissez un rôle ou un utilisateur dans le menu ci-dessus
                pour afficher et configurer ses permissions.
            </p>
        </div>
    </template>

    {{-- ═══════════════════════════════════════════════════════════
         TOASTS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="pm-toast-wrap">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="pm-toast" :class="`is-${toast.type}`" x-transition>
                <i class="fa-solid"
                   :class="{
                       'fa-circle-check': toast.type === 'success',
                       'fa-circle-exclamation': toast.type === 'error',
                       'fa-circle-info': toast.type === 'info'
                   }"></i>
                <span x-text="toast.message"></span>
                <button type="button"
                        @click="removeToast(toast.id)"
                        class="pm-toast-close"
                        aria-label="Fermer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </template>
    </div>
</div>

<style>
    /* ═══════════════════════════════════════════════════════════
       DESIGN TOKENS
       ═══════════════════════════════════════════════════════════ */
    .pm-wrapper {
        --pm-primary: #6366f1;
        --pm-primary-dark: #4f46e5;
        --pm-primary-soft: #eef2ff;
        --pm-success: #10b981;
        --pm-success-soft: #ecfdf5;
        --pm-danger: #ef4444;
        --pm-danger-soft: #fef2f2;
        --pm-warning: #f59e0b;
        --pm-warning-soft: #fffbeb;
        --pm-text: #1e293b;
        --pm-text-muted: #64748b;
        --pm-text-dim: #94a3b8;
        --pm-border: #e2e8f0;
        --pm-border-strong: #cbd5e1;
        --pm-bg: #f8fafc;
        --pm-surface: #ffffff;
        --pm-radius: 14px;
        --pm-radius-sm: 10px;
        --pm-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
        --pm-shadow-md: 0 4px 12px rgba(15, 23, 42, 0.06);
        --pm-shadow-lg: 0 12px 32px rgba(15, 23, 42, 0.08);
        --pm-transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);

        max-width: 1400px;
        margin-inline: auto;
        padding: 1rem;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    @media (min-width: 640px)  { .pm-wrapper { padding: 1.5rem; } }
    @media (min-width: 1024px) { .pm-wrapper { padding: 2rem; } }

    .pm-wrapper [x-cloak] { display: none !important; }

    /* ═══════════════════════════════════════════════════════════
       EN-TÊTE
       ═══════════════════════════════════════════════════════════ */
    .pm-header {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    @media (min-width: 768px) {
        .pm-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
        }
    }

    .pm-header-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .pm-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
    }

    .pm-header-text { min-width: 0; }

    .pm-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--pm-text);
        margin: 0 0 0.15rem;
        line-height: 1.2;
        letter-spacing: -0.01em;
    }
    @media (min-width: 640px) { .pm-title { font-size: 1.35rem; } }

    .pm-subtitle {
        font-size: 0.8rem;
        color: var(--pm-text-muted);
        margin: 0;
        line-height: 1.4;
    }
    @media (min-width: 640px) { .pm-subtitle { font-size: 0.875rem; } }

    .pm-header-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    @media (max-width: 767.98px) {
        .pm-header-actions { width: 100%; }
        .pm-header-actions .pm-btn { flex: 1; justify-content: center; }
    }

    /* ═══════════════════════════════════════════════════════════
       BOUTONS
       ═══════════════════════════════════════════════════════════ */
    .pm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.65rem 1.15rem;
        border-radius: var(--pm-radius-sm);
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all var(--pm-transition);
        text-decoration: none;
        white-space: nowrap;
        line-height: 1.2;
    }

    .pm-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none !important;
    }

    .pm-btn-primary {
        background: var(--pm-text);
        color: #fff;
    }
    .pm-btn-primary:hover:not(:disabled) {
        background: var(--pm-primary);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
    }

    .pm-btn-ghost {
        background: #fff;
        border-color: var(--pm-border);
        color: var(--pm-text-muted);
    }
    .pm-btn-ghost:hover:not(:disabled) {
        border-color: var(--pm-primary);
        color: var(--pm-primary);
        background: var(--pm-primary-soft);
    }

    .pm-btn-label {
        display: inline;
    }
    @media (max-width: 480px) {
        .pm-btn { padding: 0.6rem 0.85rem; font-size: 0.8rem; }
    }

    /* ═══════════════════════════════════════════════════════════
       CARTES
       ═══════════════════════════════════════════════════════════ */
    .pm-card {
        background: var(--pm-surface);
        border: 1px solid var(--pm-border);
        border-radius: var(--pm-radius);
        box-shadow: var(--pm-shadow-sm);
    }

    /* ═══════════════════════════════════════════════════════════
       CARTE CIBLE
       ═══════════════════════════════════════════════════════════ */
    .pm-target-card {
        padding: 1.15rem;
        margin-bottom: 1.25rem;
    }
    @media (min-width: 640px) {
        .pm-target-card { padding: 1.5rem; margin-bottom: 1.5rem; }
    }

    .pm-target-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .pm-target-grid {
            flex-direction: row;
            align-items: flex-end;
            gap: 1.5rem;
        }
        .pm-target-field { flex: 1; min-width: 0; }
    }

    .pm-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--pm-text-muted);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .pm-label i { color: var(--pm-primary); font-size: 0.85rem; }

    .pm-select-wrap {
        position: relative;
    }

    .pm-select {
        width: 100%;
        padding: 0.75rem 2.5rem 0.75rem 1rem;
        border: 2px solid var(--pm-border);
        border-radius: var(--pm-radius-sm);
        background: var(--pm-bg);
        font-size: 0.9rem;
        color: var(--pm-text);
        font-weight: 500;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
        transition: all var(--pm-transition);
        outline: none;
    }
    .pm-select:focus {
        border-color: var(--pm-primary);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .pm-select-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--pm-text-dim);
        font-size: 0.85rem;
        pointer-events: none;
    }

    /* Compteur */
    .pm-target-counter {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.75rem 1.15rem;
        background: var(--pm-bg);
        border: 2px solid var(--pm-border);
        border-radius: var(--pm-radius-sm);
        transition: all var(--pm-transition);
    }
    .pm-target-counter.is-active {
        background: var(--pm-primary-soft);
        border-color: #c7d2fe;
    }

    .pm-counter-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--pm-text);
        line-height: 1;
        font-variant-numeric: tabular-nums;
        transition: color var(--pm-transition);
    }
    .pm-target-counter.is-active .pm-counter-value {
        color: var(--pm-primary-dark);
    }

    .pm-counter-label {
        display: flex;
        flex-direction: column;
        font-size: 0.7rem;
        color: var(--pm-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        line-height: 1.3;
    }
    .pm-target-counter.is-active .pm-counter-label {
        color: var(--pm-primary-dark);
    }

    /* ═══════════════════════════════════════════════════════════
       LOADING
       ═══════════════════════════════════════════════════════════ */
    .pm-loading {
        padding: 3rem 1rem;
        text-align: center;
    }

    .pm-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--pm-primary-soft);
        border-top-color: var(--pm-primary);
        border-radius: 50%;
        margin: 0 auto 1rem;
        animation: pm-spin 0.8s linear infinite;
    }
    @keyframes pm-spin { to { transform: rotate(360deg); } }

    .pm-loading-text {
        font-size: 0.9rem;
        color: var(--pm-text-muted);
        margin: 0;
    }

    /* ═══════════════════════════════════════════════════════════
       TOOLBAR (onglets + actions)
       ═══════════════════════════════════════════════════════════ */
    .pm-toolbar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    @media (min-width: 640px) {
        .pm-toolbar {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .pm-tabs {
        display: flex;
        gap: 0.35rem;
        padding: 0.3rem;
        background: var(--pm-bg);
        border-radius: var(--pm-radius-sm);
        border: 1px solid var(--pm-border);
        width: 100%;
    }
    @media (min-width: 640px) {
        .pm-tabs { width: auto; }
    }

    .pm-tab {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        background: transparent;
        border: none;
        color: var(--pm-text-muted);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--pm-transition);
        white-space: nowrap;
    }
    .pm-tab:hover:not(.is-active) {
        background: rgba(99, 102, 241, 0.05);
        color: var(--pm-primary);
    }
    .pm-tab.is-active {
        background: #fff;
        color: var(--pm-primary-dark);
        box-shadow: var(--pm-shadow-sm);
    }
    .pm-tab i { font-size: 0.85rem; }

    .pm-tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--pm-border);
        color: var(--pm-text-muted);
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1;
    }
    .pm-tab.is-active .pm-tab-count {
        background: var(--pm-primary-soft);
        color: var(--pm-primary-dark);
    }

    @media (max-width: 480px) {
        .pm-tab span:not(.pm-tab-count) { display: none; }
        .pm-tab { padding: 0.6rem; }
        .pm-tab i { font-size: 1rem; }
    }

    .pm-toolbar-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .pm-action-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.85rem;
        border-radius: var(--pm-radius-sm);
        background: transparent;
        border: 1px solid transparent;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--pm-transition);
        white-space: nowrap;
    }
    .pm-action-link.is-primary {
        color: var(--pm-primary-dark);
    }
    .pm-action-link.is-primary:hover {
        background: var(--pm-primary-soft);
        border-color: #c7d2fe;
    }
    .pm-action-link.is-muted {
        color: var(--pm-text-muted);
    }
    .pm-action-link.is-muted:hover {
        background: var(--pm-bg);
        border-color: var(--pm-border);
    }

    /* ═══════════════════════════════════════════════════════════
       RECHERCHE
       ═══════════════════════════════════════════════════════════ */
    .pm-search-wrap {
        position: relative;
        margin-bottom: 1.25rem;
    }

    .pm-search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--pm-text-dim);
        font-size: 0.9rem;
        pointer-events: none;
    }

    .pm-search-input {
        width: 100%;
        padding: 0.75rem 2.75rem 0.75rem 2.75rem;
        border: 2px solid var(--pm-border);
        border-radius: var(--pm-radius-sm);
        background: var(--pm-surface);
        font-size: 0.9rem;
        color: var(--pm-text);
        outline: none;
        transition: all var(--pm-transition);
    }
    .pm-search-input:focus {
        border-color: var(--pm-primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    .pm-search-input::-webkit-search-cancel-button { display: none; }

    .pm-search-clear {
        position: absolute;
        right: 0.6rem;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--pm-bg);
        border: none;
        color: var(--pm-text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all var(--pm-transition);
    }
    .pm-search-clear:hover {
        background: var(--pm-danger-soft);
        color: var(--pm-danger);
    }

    /* ═══════════════════════════════════════════════════════════
       GRILLE DES PERMISSIONS
       ═══════════════════════════════════════════════════════════ */
    .pm-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        margin-bottom: 6rem;
    }
    @media (min-width: 640px) {
        .pm-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    }
    @media (min-width: 1280px) {
        .pm-grid { grid-template-columns: repeat(3, 1fr); }
    }

    /* ═══════════════════════════════════════════════════════════
       CARTE RESSOURCE
       ═══════════════════════════════════════════════════════════ */
    .pm-resource-card {
        padding: 1rem;
        transition: all var(--pm-transition);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .pm-resource-card.is-fully-checked {
        border-color: #a7f3d0;
        background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
    }
    .pm-resource-card.is-fully-checked::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #10b981, #059669);
    }

    /* En-tête ressource */
    .pm-resource-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--pm-border);
        margin-bottom: 0.75rem;
    }

    .pm-resource-info {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
        flex: 1;
    }

    .pm-resource-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--pm-primary-soft);
        color: var(--pm-primary-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
        transition: all var(--pm-transition);
    }
    .pm-resource-card.is-fully-checked .pm-resource-icon {
        background: var(--pm-success-soft);
        color: var(--pm-success);
    }

    .pm-resource-text { min-width: 0; flex: 1; }

    .pm-resource-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--pm-text);
        margin: 0 0 0.1rem;
        text-transform: capitalize;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }

    .pm-resource-count {
        font-size: 0.7rem;
        color: var(--pm-text-dim);
        margin: 0;
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        line-height: 1.3;
    }

    .pm-resource-actions {
        display: flex;
        gap: 0.25rem;
        flex-shrink: 0;
    }

    .pm-mini-btn {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        border: 1px solid var(--pm-border);
        background: #fff;
        color: var(--pm-text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        transition: all var(--pm-transition);
        padding: 0;
    }
    .pm-mini-btn:hover {
        background: var(--pm-success-soft);
        border-color: #a7f3d0;
        color: var(--pm-success);
        transform: scale(1.08);
    }
    .pm-mini-btn.is-muted:hover {
        background: var(--pm-danger-soft);
        border-color: #fecaca;
        color: var(--pm-danger);
    }

    /* Liste permissions */
    .pm-permission-list {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .pm-permission-item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.5rem 0.6rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
        min-width: 0;
    }
    .pm-permission-item:hover {
        background: var(--pm-bg);
    }
    .pm-permission-item.is-checked {
        background: var(--pm-primary-soft);
    }
    .pm-permission-item.is-checked:hover {
        background: #e0e7ff;
    }

    /* Checkbox custom */
    .pm-checkbox-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }

    .pm-checkbox {
        position: relative;
        width: 20px;
        height: 20px;
        border: 2px solid var(--pm-border-strong);
        border-radius: 6px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all var(--pm-transition);
    }
    .pm-permission-item:hover .pm-checkbox {
        border-color: var(--pm-primary);
    }

    .pm-checkbox-mark {
        color: #fff;
        font-size: 0.65rem;
        opacity: 0;
        transform: scale(0.5);
        transition: all var(--pm-transition);
    }

    .pm-permission-item.is-checked .pm-checkbox {
        background: var(--pm-primary);
        border-color: var(--pm-primary);
        box-shadow: 0 2px 6px rgba(99, 102, 241, 0.35);
    }
    .pm-permission-item.is-checked .pm-checkbox-mark {
        opacity: 1;
        transform: scale(1);
    }

    .pm-checkbox-input:focus-visible + .pm-checkbox {
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    }

    .pm-permission-label {
        font-size: 0.82rem;
        color: var(--pm-text);
        font-weight: 500;
        line-height: 1.35;
        word-break: break-word;
        min-width: 0;
        transition: color var(--pm-transition);
    }
    .pm-permission-item.is-checked .pm-permission-label {
        color: var(--pm-primary-dark);
        font-weight: 600;
    }

    /* ═══════════════════════════════════════════════════════════
       ÉTATS VIDES
       ═══════════════════════════════════════════════════════════ */
    .pm-empty,
    .pm-empty-search {
        text-align: center;
        padding: 3rem 1rem;
        background: var(--pm-surface);
        border: 1px dashed var(--pm-border);
        border-radius: var(--pm-radius);
        grid-column: 1 / -1;
    }

    .pm-empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: var(--pm-primary-soft);
        color: var(--pm-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin: 0 auto 1rem;
    }

    .pm-empty-search > i {
        font-size: 2rem;
        color: var(--pm-text-dim);
        display: block;
        margin-bottom: 0.75rem;
    }

    .pm-empty-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--pm-text);
        margin: 0 0 0.4rem;
    }

    .pm-empty-text,
    .pm-empty-search p {
        font-size: 0.875rem;
        color: var(--pm-text-muted);
        max-width: 380px;
        margin: 0 auto 1rem;
        line-height: 1.55;
    }

    /* ═══════════════════════════════════════════════════════════
       BARRE D'ACTION STICKY
       ═══════════════════════════════════════════════════════════ */
    .pm-action-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 40;
        padding: 0.85rem 1rem;
        padding-bottom: calc(0.85rem + env(safe-area-inset-bottom, 0));
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-top: 1px solid var(--pm-border);
        box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.06);
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    @media (min-width: 640px) {
        .pm-action-bar {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
        }
    }

    /* Sur desktop, la barre reste dans le flux (pas fixe) */
    @media (min-width: 1024px) {
        .pm-action-bar {
            position: sticky;
            bottom: 1rem;
            border-radius: var(--pm-radius);
            border: 1px solid var(--pm-border);
            margin-top: 1.5rem;
            padding: 1rem 1.25rem;
            left: auto;
            right: auto;
            box-shadow: var(--pm-shadow-lg);
        }
    }

    .pm-action-bar-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        justify-content: center;
    }
    @media (min-width: 640px) {
        .pm-action-bar-info { justify-content: flex-start; }
    }

    .pm-action-bar-count {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--pm-primary-dark);
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .pm-action-bar-text {
        font-size: 0.78rem;
        color: var(--pm-text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .pm-action-bar-buttons {
        display: flex;
        gap: 0.5rem;
    }
    @media (max-width: 639.98px) {
        .pm-action-bar-buttons { width: 100%; }
        .pm-action-bar-buttons .pm-btn { flex: 1; }
    }

    .pm-btn-save {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #fff;
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
    }
    .pm-btn-save:hover:not(:disabled) {
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
        transform: translateY(-1px);
    }

    .pm-btn-content {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ═══════════════════════════════════════════════════════════
       TOASTS
       ═══════════════════════════════════════════════════════════ */
    .pm-toast-wrap {
        position: fixed;
        top: 1rem;
        right: 1rem;
        left: 1rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        pointer-events: none;
    }
    @media (min-width: 480px) {
        .pm-toast-wrap {
            left: auto;
            min-width: 320px;
            max-width: 420px;
        }
    }

    .pm-toast {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: var(--pm-radius-sm);
        background: #fff;
        border: 1px solid var(--pm-border);
        box-shadow: var(--pm-shadow-lg);
        font-size: 0.875rem;
        font-weight: 500;
        animation: pm-toast-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    @keyframes pm-toast-in {
        from { opacity: 0; transform: translateY(-12px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .pm-toast.is-success { border-left: 4px solid var(--pm-success); }
    .pm-toast.is-success > i { color: var(--pm-success); }
    .pm-toast.is-error   { border-left: 4px solid var(--pm-danger); }
    .pm-toast.is-error > i { color: var(--pm-danger); }
    .pm-toast.is-info    { border-left: 4px solid var(--pm-primary); }
    .pm-toast.is-info > i { color: var(--pm-primary); }

    .pm-toast > span {
        flex: 1;
        min-width: 0;
        word-break: break-word;
        color: var(--pm-text);
    }

    .pm-toast-close {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: var(--pm-text-dim);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
        transition: all var(--pm-transition);
    }
    .pm-toast-close:hover {
        background: var(--pm-bg);
        color: var(--pm-text);
    }

    /* ═══════════════════════════════════════════════════════════
       ACCESSIBILITÉ
       ═══════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .pm-wrapper *,
        .pm-wrapper *::before,
        .pm-wrapper *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    .pm-wrapper :focus-visible {
        outline: 2px solid var(--pm-primary);
        outline-offset: 2px;
        border-radius: 4px;
    }
</style>

<script>
    function permissionManager() {
        return {
            /* ═══════════════════════════════════════════════════
               ÉTAT
               ═══════════════════════════════════════════════════ */
            selectedTarget: '',
            selectedPermissions: [],
            originalPermissions: [],   // pour détecter les changements
            loading: false,
            saving: false,
            activeScope: 'admin',
            searchQuery: '',
            toasts: [],
            toastIdCounter: 0,

            groupedData: @json($grouped),

            /* ═══════════════════════════════════════════════════
               INIT
               ═══════════════════════════════════════════════════ */
            init() {
                const urlParams = new URLSearchParams(window.location.search);
                const target = urlParams.get('target');
                if (target) {
                    this.selectedTarget = target;
                    this.loadPermissions();
                }
            },

            /* ═══════════════════════════════════════════════════
               GETTERS
               ═══════════════════════════════════════════════════ */
            get selectedCount() {
                return this.selectedPermissions.length;
            },

            get hasChanges() {
                if (this.originalPermissions.length !== this.selectedPermissions.length) {
                    return true;
                }
                const orig = [...this.originalPermissions].sort();
                const curr = [...this.selectedPermissions].sort();
                return orig.some((v, i) => v !== curr[i]);
            },

            /* ═══════════════════════════════════════════════════
               PERMISSIONS GROUPÉES (avec recherche)
               ═══════════════════════════════════════════════════ */
            get groupedPermissions() {
                const scopeData = this.groupedData[this.activeScope] || {};

                // Pas de recherche → tout
                if (!this.searchQuery.trim()) return scopeData;

                const query = this.searchQuery.toLowerCase().trim();
                const filtered = {};

                Object.entries(scopeData).forEach(([resource, perms]) => {
                    const matches = perms.filter(p =>
                        (p.label || '').toLowerCase().includes(query)
                        || (p.action || '').toLowerCase().includes(query)
                        || (p.name || '').toLowerCase().includes(query)
                    );
                    if (matches.length > 0) {
                        filtered[resource] = matches;
                    }
                });

                return filtered;
            },

            /* Alias pour le template */
            get filteredPermissions() {
                return this.groupedPermissions;
            },

            /* ═══════════════════════════════════════════════════
               COMPTEURS / ÉTATS
               ═══════════════════════════════════════════════════ */
            countInScope(scope) {
                const data = this.groupedData[scope] || {};
                return Object.values(data).reduce((sum, perms) => sum + perms.length, 0);
            },

            isChecked(name) {
                return this.selectedPermissions.includes(name);
            },

            countCheckedInResource(resource) {
                const perms = this.groupedPermissions[resource] || [];
                return perms.filter(p => this.isChecked(p.name)).length;
            },

            isResourceFullyChecked(resource) {
                const perms = this.groupedPermissions[resource] || [];
                if (perms.length === 0) return false;
                return perms.every(p => this.isChecked(p.name));
            },

            /* Icône par ressource (heuristique) */
            getResourceIcon(resource) {
                const r = resource.toLowerCase();
                const map = {
                    'eleves': 'fa-user-graduate',
                    'élèves': 'fa-user-graduate',
                    'inscriptions': 'fa-clipboard-list',
                    'paiements': 'fa-money-bill-wave',
                    'notes': 'fa-pencil',
                    'cours': 'fa-book-open',
                    'salaires': 'fa-hand-holding-dollar',
                    'avances': 'fa-hand-holding-usd',
                    'roles': 'fa-shield-halved',
                    'rôles': 'fa-shield-halved',
                    'permissions': 'fa-lock',
                    'users': 'fa-users-cog',
                    'utilisateurs': 'fa-users-cog',
                    'statistiques': 'fa-chart-pie',
                    'annonces': 'fa-bullhorn',
                    'contacts': 'fa-users',
                    'messages': 'fa-envelope-open-text',
                    'reglements': 'fa-gavel',
                    'règlements': 'fa-gavel',
                    'salles': 'fa-door-open',
                    'salles-de-classe': 'fa-door-open',
                    'sections': 'fa-th-large',
                    'options': 'fa-sliders',
                    'sessions': 'fa-layer-group',
                    'fonctions': 'fa-briefcase',
                    'devises': 'fa-coins',
                    'echeances': 'fa-hourglass-half',
                    'échéances': 'fa-hourglass-half',
                    'categories': 'fa-tags',
                    'catégories': 'fa-tags',
                    'references': 'fa-tag',
                    'références': 'fa-tag',
                    'annees-scolaires': 'fa-calendar-alt',
                    'mois-scolaires': 'fa-calendar-day',
                    'tranches-scolaires': 'fa-calendar-week',
                    'periode-notes': 'fa-calendar-check',
                    'salaire-horaires': 'fa-business-time',
                    'frais-supplementaires': 'fa-wallet',
                    'paiement-salaires': 'fa-money-check-dollar',
                    'paiement-frais-supplementaires': 'fa-receipt',
                    'info-eleves': 'fa-users-viewfinder',
                    'info-paiements': 'fa-file-invoice-dollar',
                    'demandes-avance': 'fa-hand-holding-dollar',
                    'session-avances': 'fa-calendar-check',
                    'settings': 'fa-gear',
                    'site-settings': 'fa-globe',
                    'planification-paiements': 'fa-calendar-plus',
                    'taux': 'fa-arrow-right-arrow-left',
                    'emails': 'fa-paper-plane',
                };
                return map[r] || 'fa-key';
            },

            /* ═══════════════════════════════════════════════════
               ACTIONS
               ═══════════════════════════════════════════════════ */
            setScope(scope) {
                this.activeScope = scope;
            },

            async loadPermissions() {
                if (!this.selectedTarget) {
                    this.selectedPermissions = [];
                    this.originalPermissions = [];
                    return;
                }

                const [type, id] = this.selectedTarget.split(':');
                this.loading = true;

                try {
                    const url = `{{ route('admin.permissions.target.permissions', ['type' => '__type__', 'id' => '__id__']) }}`
                        .replace('__type__', type)
                        .replace('__id__', id);

                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Erreur réseau');

                    const data = await response.json();
                    this.selectedPermissions = data.permissions || [];
                    this.originalPermissions = [...this.selectedPermissions];

                } catch (error) {
                    console.error(error);
                    this.toast('Erreur lors du chargement des permissions', 'error');
                } finally {
                    this.loading = false;
                }
            },

            setAll(checked) {
                const current = this.groupedPermissions;

                if (checked) {
                    const toAdd = [];
                    Object.values(current).forEach(perms => {
                        perms.forEach(p => {
                            if (!this.selectedPermissions.includes(p.name)) {
                                toAdd.push(p.name);
                            }
                        });
                    });
                    this.selectedPermissions = [...this.selectedPermissions, ...toAdd];
                } else {
                    const toRemove = new Set();
                    Object.values(current).forEach(perms => {
                        perms.forEach(p => toRemove.add(p.name));
                    });
                    this.selectedPermissions = this.selectedPermissions
                        .filter(name => !toRemove.has(name));
                }
            },

            setResourceAll(resource, checked) {
                const perms = this.groupedPermissions[resource] || [];

                if (checked) {
                    const toAdd = perms
                        .map(p => p.name)
                        .filter(name => !this.selectedPermissions.includes(name));
                    this.selectedPermissions = [...this.selectedPermissions, ...toAdd];
                } else {
                    const toRemove = new Set(perms.map(p => p.name));
                    this.selectedPermissions = this.selectedPermissions
                        .filter(name => !toRemove.has(name));
                }
            },

            resetForm() {
                this.loadPermissions();
            },

            async savePermissions() {
                if (!this.selectedTarget || this.saving) return;

                const [type, id] = this.selectedTarget.split(':');
                this.saving = true;

                try {
                    const url = `{{ route('admin.permissions.target.sync', ['type' => '__type__', 'id' => '__id__']) }}`
                        .replace('__type__', type)
                        .replace('__id__', id);

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ permissions: this.selectedPermissions })
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.error || errorData.message || 'Erreur lors de l\'enregistrement');
                    }

                    this.originalPermissions = [...this.selectedPermissions];
                    this.toast('Permissions enregistrées avec succès', 'success');

                } catch (error) {
                    console.error(error);
                    this.toast(error.message, 'error');
                } finally {
                    this.saving = false;
                }
            },

            /* ═══════════════════════════════════════════════════
               TOASTS
               ═══════════════════════════════════════════════════ */
            toast(message, type = 'info', duration = 4000) {
                const id = ++this.toastIdCounter;
                this.toasts.push({ id, message, type });

                setTimeout(() => {
                    this.removeToast(id);
                }, duration);
            },

            removeToast(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            },
        };
    }
</script>
@endsection