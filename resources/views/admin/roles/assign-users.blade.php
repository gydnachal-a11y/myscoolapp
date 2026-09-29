@extends('layouts.admin')

@section('page_title', 'Utilisateurs du rôle')
@section('page_subtitle', 'Assignez ou retirez des utilisateurs à ce rôle')

@section('content')
@php
    // ✅ Charge TOUS les users (pagination désactivée côté controller)
    $allUsers = $users->map(fn($u) => [
        'id'    => (int) $u->id,
        'name'  => (string) $u->name,
        'email' => (string) $u->email,
    ])->values()->all();

    $assignedIdsInt = array_map('intval', $assignedUsers ?? []);
@endphp

<div class="role-users-page"
     x-data="usersRoleManager({
         usersData: {{ Js::from($allUsers) }},
         assignedIds: {{ Js::from($assignedIdsInt) }}
     })"
     x-init="init()">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">
                <i class="fa-solid fa-users-gear title-icon" aria-hidden="true"></i>
                <span>Utilisateurs du rôle</span>
                <span class="role-badge">{{ $role->label ?? ucfirst($role->name) }}</span>
            </h1>
            <p class="page-subtitle">Assignez ou retirez des utilisateurs à ce rôle</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour aux rôles</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FORMULAIRE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form id="roleUsersForm"
          action="{{ route('admin.roles.sync-users', $role) }}"
          method="POST"
          @submit="onSubmit($event)"
          class="content-card"
          novalidate>

        @csrf

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- TOOLBAR --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="toolbar">

            <div class="toolbar-row">
                <nav class="tabs" role="tablist" aria-label="Filtres utilisateurs">
                    <button type="button" role="tab"
                            class="tab"
                            :class="{ 'is-active': activeFilter === 'all' }"
                            :aria-selected="activeFilter === 'all'"
                            @click="setFilter('all')">
                        <i class="fa-solid fa-users" aria-hidden="true"></i>
                        <span>Tous</span>
                        <span class="tab-badge" x-text="usersData.length"></span>
                    </button>
                    <button type="button" role="tab"
                            class="tab"
                            :class="{ 'is-active': activeFilter === 'assigned' }"
                            :aria-selected="activeFilter === 'assigned'"
                            @click="setFilter('assigned')">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>Assignés</span>
                        <span class="tab-badge" x-text="selectedUsers.length"></span>
                    </button>
                    <button type="button" role="tab"
                            class="tab"
                            :class="{ 'is-active': activeFilter === 'unassigned' }"
                            :aria-selected="activeFilter === 'unassigned'"
                            @click="setFilter('unassigned')">
                        <i class="fa-regular fa-circle" aria-hidden="true"></i>
                        <span>Non assignés</span>
                        <span class="tab-badge" x-text="usersData.length - selectedUsers.length"></span>
                    </button>
                </nav>

                <div class="search-field">
                    <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="search"
                           x-model.debounce.200ms="search"
                           placeholder="Rechercher un utilisateur…"
                           aria-label="Rechercher un utilisateur"
                           autocomplete="off">
                    <button type="button"
                            class="search-clear"
                            x-show="search.length > 0"
                            x-cloak
                            @click="search = ''"
                            aria-label="Effacer la recherche">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="toolbar-row toolbar-actions">
                <div class="toolbar-info" aria-live="polite">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>
                        <strong x-text="filteredUsers.length"></strong>
                        <span x-text="filteredUsers.length > 1 ? ' utilisateurs affichés' : ' utilisateur affiché'"></span>
                    </span>
                </div>

                <div class="toolbar-buttons">
                    <button type="button" class="link-btn link-success"
                            @click="selectAllVisible()"
                            :disabled="filteredUsers.length === 0">
                        <i class="fa-solid fa-check-double" aria-hidden="true"></i>
                        <span>Tout assigner (visibles)</span>
                    </button>
                    <button type="button" class="link-btn link-muted"
                            @click="deselectAllVisible()"
                            :disabled="filteredUsers.length === 0">
                        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                        <span>Tout retirer (visibles)</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- CORPS SCROLLABLE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="users-body">

            {{-- Vide --}}
            <template x-if="filteredUsers.length === 0">
                <div class="empty-state">
                    <div class="empty-icon-wrapper">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </div>
                    <h3 class="empty-title">Aucun utilisateur</h3>
                    <p class="empty-text">
                        <template x-if="search.length > 0">
                            <span>Aucun résultat pour « <strong x-text="search"></strong> ».</span>
                        </template>
                        <template x-if="search.length === 0">
                            <span>Aucun utilisateur dans cette catégorie.</span>
                        </template>
                    </p>
                    <button type="button"
                            class="btn btn-ghost"
                            x-show="search.length > 0"
                            x-cloak
                            @click="search = ''">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        <span>Effacer la recherche</span>
                    </button>
                </div>
            </template>

            {{-- Grille --}}
            <div class="users-grid"
                 x-show="filteredUsers.length > 0"
                 x-cloak
                 role="group"
                 aria-label="Liste des utilisateurs">
                <template x-for="user in filteredUsers" :key="user.id">
                    <label class="user-item"
                           :class="{ 'is-assigned': isSelected(user.id) }">

                        {{-- ✅ CORRECTION : x-model.number au lieu de :checked + @change --}}
                        <input type="checkbox"
                               name="users[]"
                               :value="user.id"
                               x-model.number="selectedUsers"
                               class="user-checkbox">

                        <span class="user-checkmark" aria-hidden="true"></span>

                        <div class="user-avatar" x-text="initials(user.name)"></div>

                        <div class="user-info">
                            <span class="user-name" x-text="user.name"></span>
                            <span class="user-email" x-text="user.email"></span>
                        </div>

                        <span class="user-status-pill"
                              x-show="isSelected(user.id)"
                              x-cloak>
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        </span>
                    </label>
                </template>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- FOOTER --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <footer class="content-footer">
            <div class="footer-info">
                <span class="footer-count" aria-live="polite">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <strong x-text="selectedUsers.length"></strong>
                    <span x-text="selectedUsers.length > 1 ? 'utilisateurs sélectionnés' : 'utilisateur sélectionné'"></span>
                    <span class="footer-total">sur <span x-text="usersData.length"></span></span>
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
       BASE
       ════════════════════════════════════════════════════════ */
    .role-users-page {
        --admin-header-height: 70px;

        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        /* ✅ NOUVEAU : flex column borné pour avoir un scroll interne */
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    @media (min-width: 1024px) {
        .role-users-page { --admin-header-height: 72px; }
    }
    @media (max-width: 768px) {
        .role-users-page {
            padding: 1.25rem 0.85rem;
            --admin-header-height: 60px;
        }
    }
    @media (max-width: 640px) {
        .role-users-page { padding: 1rem 0.75rem; }
    }

    .role-users-page *,
    .role-users-page *::before,
    .role-users-page *::after { box-sizing: border-box; }

    .role-users-page [x-cloak] { display: none !important; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .role-users-page .page-header {
        display: flex; flex-direction: column; align-items: flex-start;
        gap: 1.25rem; margin-bottom: 2rem;
        flex-shrink: 0;
    }
    @media (min-width: 768px) {
        .role-users-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .role-users-page .page-header-left { min-width: 0; }

    .role-users-page .page-title {
        display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem;
        font-size: 1.6rem; font-weight: 800; color: #0f172a;
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    @media (max-width: 640px) {
        .role-users-page .page-title { font-size: 1.25rem; }
    }
    .role-users-page .title-icon { color: #6366f1; }

    .role-users-page .role-badge {
        display: inline-flex; align-items: center;
        padding: 0.25rem 0.75rem;
        background: #eef2ff; color: #4f46e5;
        border-radius: 9999px;
        font-size: 0.8rem; font-weight: 700;
        max-width: 100%;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    .role-users-page .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }
    .role-users-page .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
    @media (max-width: 767px) {
        .role-users-page .header-actions { width: 100%; }
        .role-users-page .header-actions .btn { flex: 1 1 100%; }
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .role-users-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.25rem; border-radius: 12px;
        font-weight: 600; font-size: 0.9rem; text-decoration: none;
        border: none; cursor: pointer; transition: all 0.2s ease;
        white-space: nowrap;
        font-family: inherit;
        min-height: 44px;
    }
    .role-users-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .role-users-page .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
    }
    .role-users-page .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
    .role-users-page .btn-ghost {
        background: #fff; color: #64748b;
        border: 1.5px solid #e2e8f0;
    }
    .role-users-page .btn-ghost:hover {
        border-color: #6366f1; color: #4f46e5; background: #f8fafc;
    }
    .role-users-page .btn-content { display: inline-flex; align-items: center; gap: 0.5rem; }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD — ✅ NOUVEAU : flex column avec hauteur bornée
       ════════════════════════════════════════════════════════ */
    .role-users-page .content-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        overflow: hidden;

        /* ✅ Le secret : flex column + max-height pour scroller en interne */
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - var(--admin-header-height) - 220px);
        min-height: 480px;
    }

    @media (max-width: 768px) {
        .role-users-page .content-card {
            max-height: calc(100vh - var(--admin-header-height) - 180px);
            min-height: 400px;
        }
    }

    /* ════════════════════════════════════════════════════════
       TOOLBAR — ✅ FIXE (flex-shrink: 0, plus de sticky)
       ════════════════════════════════════════════════════════ */
    .role-users-page .toolbar {
        flex-shrink: 0;
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        z-index: 2;
    }
    @media (max-width: 640px) {
        .role-users-page .toolbar { padding: 0.85rem 1rem; }
    }

    .role-users-page .toolbar-row {
        display: flex; flex-direction: column; gap: 0.75rem;
    }
    @media (min-width: 768px) {
        .role-users-page .toolbar-row {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }

    /* Tabs */
    .role-users-page .tabs { display: flex; gap: 0.4rem; flex-wrap: wrap; }
    .role-users-page .tab {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.55rem 1rem;
        background: transparent;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-weight: 600; font-size: 0.85rem;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
        min-height: 40px;
    }
    .role-users-page .tab:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .role-users-page .tab.is-active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
    }
    .role-users-page .tab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 26px; height: 20px; padding: 0 0.4rem;
        background: rgba(255,255,255,0.25);
        border-radius: 9999px;
        font-size: 0.7rem; font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .role-users-page .tab:not(.is-active) .tab-badge {
        background: #f1f5f9; color: #64748b;
    }

    /* Recherche */
    .role-users-page .search-field { position: relative; width: 100%; max-width: 400px; }
    @media (min-width: 768px) {
        .role-users-page .search-field { width: 340px; }
    }
    .role-users-page .search-icon {
        position: absolute; left: 0.9rem; top: 50%;
        transform: translateY(-50%);
        color: #94a3b8; font-size: 0.85rem;
        pointer-events: none;
    }
    .role-users-page .search-field input {
        width: 100%;
        padding: 0.6rem 2.5rem 0.6rem 2.4rem;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        background: #f8fafc;
        font-size: 0.9rem; color: #0f172a;
        outline: none; transition: all 0.2s;
        font-family: inherit;
        min-height: 44px;
    }
    .role-users-page .search-field input:focus {
        border-color: #6366f1; background: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .role-users-page .search-clear {
        position: absolute; right: 0.5rem; top: 50%;
        transform: translateY(-50%);
        width: 26px; height: 26px;
        background: #f1f5f9; border: none;
        border-radius: 6px;
        color: #64748b; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.15s;
    }
    .role-users-page .search-clear:hover { background: #e2e8f0; color: #0f172a; }

    /* Actions groupées */
    .role-users-page .toolbar-actions { padding-top: 0.5rem; border-top: 1px dashed #e2e8f0; }
    .role-users-page .toolbar-info {
        display: inline-flex; align-items: center; gap: 0.5rem;
        font-size: 0.85rem; color: #64748b;
    }
    .role-users-page .toolbar-info i { color: #6366f1; }
    .role-users-page .toolbar-info strong { color: #0f172a; font-weight: 700; }

    .role-users-page .toolbar-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }

    .role-users-page .link-btn {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.4rem 0.75rem;
        background: transparent; border: none;
        border-radius: 8px;
        font-weight: 600; font-size: 0.8rem;
        cursor: pointer; transition: all 0.15s;
        font-family: inherit;
        min-height: 38px;
    }
    .role-users-page .link-btn:hover:not(:disabled) { background: #f1f5f9; }
    .role-users-page .link-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .role-users-page .link-success { color: #059669; }
    .role-users-page .link-success:hover:not(:disabled) { background: #ecfdf5; }
    .role-users-page .link-muted { color: #64748b; }
    .role-users-page .link-muted:hover:not(:disabled) { background: #f1f5f9; color: #0f172a; }

    /* ════════════════════════════════════════════════════════
       CORPS — ✅ SEUL ÉLÉMENT SCROLLABLE
       ════════════════════════════════════════════════════════ */
    .role-users-page .users-body {
        flex: 1;
        min-height: 0;                    /* ⚠️ CRITIQUE : permet à flex:1 de scroller */
        overflow-y: auto;
        overflow-x: hidden;
        padding: 1.25rem;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    .role-users-page .users-body::-webkit-scrollbar { width: 8px; }
    .role-users-page .users-body::-webkit-scrollbar-track { background: transparent; }
    .role-users-page .users-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .role-users-page .users-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    @media (max-width: 640px) {
        .role-users-page .users-body { padding: 1rem; }
    }

    .role-users-page .users-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.6rem;
    }
    @media (min-width: 640px)  { .role-users-page .users-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1200px) { .role-users-page .users-grid { grid-template-columns: repeat(3, 1fr); } }

    /* ════════════════════════════════════════════════════════
       ITEM UTILISATEUR
       ════════════════════════════════════════════════════════ */
    .role-users-page .user-item {
        display: flex; align-items: center;
        gap: 0.65rem;
        padding: 0.75rem 1rem;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.15s ease;
        position: relative;
        user-select: none;
        min-width: 0;
    }
    .role-users-page .user-item:hover {
        border-color: #c7d2fe;
        background: #f8fafc;
    }
    .role-users-page .user-item.is-assigned {
        border-color: #6366f1;
        background: #f5f7ff;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08);
    }

    /* Checkbox custom */
    .role-users-page .user-checkbox {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 0;
        height: 0;
    }
    .role-users-page .user-checkmark {
        width: 20px; height: 20px;
        flex-shrink: 0;
        border: 2px solid #cbd5e1;
        border-radius: 6px;
        background: #fff;
        position: relative;
        transition: all 0.15s;
    }
    .role-users-page .user-checkmark::after {
        content: '';
        position: absolute;
        left: 4px; top: 1px;
        width: 5px; height: 10px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg) scale(0);
        transition: transform 0.15s;
    }
    .role-users-page .user-item.is-assigned .user-checkmark {
        background: #4f46e5;
        border-color: #4f46e5;
    }
    .role-users-page .user-item.is-assigned .user-checkmark::after {
        transform: rotate(45deg) scale(1);
    }
    .role-users-page .user-checkbox:focus-visible + .user-checkmark {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
    }

    /* Avatar */
    .role-users-page .user-avatar {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.75rem;
        flex-shrink: 0;
        letter-spacing: 0.3px;
    }
    .role-users-page .user-item.is-assigned .user-avatar {
        box-shadow: 0 2px 6px rgba(99, 102, 241, 0.35);
    }

    /* Info */
    .role-users-page .user-info {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column; gap: 0.1rem;
    }
    .role-users-page .user-name {
        font-size: 0.9rem; font-weight: 600; color: #0f172a;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .role-users-page .user-email {
        font-size: 0.72rem; color: #94a3b8;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    /* Pill "assigné" */
    .role-users-page .user-status-pill {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.2rem 0.5rem;
        background: #ecfdf5; color: #059669;
        border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700;
        flex-shrink: 0;
    }

    /* ════════════════════════════════════════════════════════
       FOOTER — ✅ FIXE (flex-shrink: 0, plus de sticky)
       ════════════════════════════════════════════════════════ */
    .role-users-page .content-footer {
        flex-shrink: 0;
        display: flex; flex-direction: column;
        gap: 1rem;
        padding: 1rem 1.25rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
        background: #fff;
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
        z-index: 2;
    }
    @media (min-width: 768px) {
        .role-users-page .content-footer {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }

    .role-users-page .footer-info {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;
        font-size: 0.9rem; color: #64748b;
    }
    .role-users-page .footer-count { display: inline-flex; align-items: center; gap: 0.4rem; }
    .role-users-page .footer-count i { color: #6366f1; }
    .role-users-page .footer-count strong {
        color: #4f46e5; font-size: 1rem; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .role-users-page .footer-total { color: #94a3b8; font-size: 0.8rem; margin-left: 0.25rem; }

    .role-users-page .pending-badge {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        background: #fffbeb; color: #b45309;
        border-radius: 9999px;
        font-size: 0.75rem; font-weight: 600;
    }

    .role-users-page .footer-actions {
        display: flex; gap: 0.5rem; flex-wrap: wrap;
        justify-content: flex-end;
    }
    @media (max-width: 640px) {
        .role-users-page .footer-actions { width: 100%; flex-direction: column-reverse; }
        .role-users-page .footer-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .role-users-page .empty-state {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center; gap: 0.5rem;
    }
    .role-users-page .empty-icon-wrapper {
        width: 80px; height: 80px; border-radius: 50%;
        background: #f8fafc;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #cbd5e1;
        margin-bottom: 0.75rem;
    }
    .role-users-page .empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin: 0; }
    .role-users-page .empty-text { font-size: 0.9rem; color: #94a3b8; max-width: 420px; margin: 0; line-height: 1.5; }
    .role-users-page .empty-text strong { color: #475569; }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 640px) {
        .role-users-page .user-item { padding: 0.65rem 0.85rem; }
        .role-users-page .user-avatar { width: 32px; height: 32px; font-size: 0.7rem; }
        .role-users-page .search-field { max-width: 100%; }
        .role-users-page .tabs { width: 100%; }
        .role-users-page .tab { flex: 1; justify-content: center; }
        .role-users-page .tab span:not(.tab-badge) { display: none; }
        .role-users-page .tab-badge { display: inline-flex; }

        /* Anti-zoom iOS */
        .role-users-page .search-field input { font-size: 16px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .role-users-page *, .role-users-page *::before, .role-users-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        // ✅ CORRECTION #3 : factory unique + enregistrement idempotent
        const usersRoleManagerFactory = (config) => ({
            /* ============================================================
               ÉTAT
               ============================================================ */
            usersData: config.usersData || [],
            assignedIds: (config.assignedIds || []).map(Number),  // snapshot initial
            selectedUsers: [],   // ✅ Source de vérité (liée via x-model)
            search: '',
            activeFilter: 'all',
            submitting: false,

            /* ============================================================
               INIT
               ============================================================ */
            init() {
                // ✅ Copie du snapshot initial
                this.selectedUsers = [...this.assignedIds];

                // ✅ Reset du flag submitting au retour navigateur
                window.addEventListener('pageshow', () => {
                    this.submitting = false;
                });

                // ✅ Warning si modifications non sauvegardées
                window.addEventListener('beforeunload', (e) => {
                    if (this.hasChanges && !this.submitting) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
            },

            /* ============================================================
               GETTERS
               ============================================================ */

            /**
             * ✅ Liste filtrée + triée.
             * Utilise selectedUsers.includes() (O(n)) — parfait pour 2000 users.
             * Pas de cache : Alpine re-render déjà à chaque changement.
             */
            get filteredUsers() {
                const query = (this.search || '').toLowerCase().trim();
                let users = this.usersData;

                if (this.activeFilter === 'assigned') {
                    users = users.filter(u => this.selectedUsers.includes(u.id));
                } else if (this.activeFilter === 'unassigned') {
                    users = users.filter(u => !this.selectedUsers.includes(u.id));
                }

                if (query) {
                    users = users.filter(u =>
                        u.name.toLowerCase().includes(query)
                        || u.email.toLowerCase().includes(query)
                    );
                }

                // Tri : assignés d'abord, puis alphabétique
                return [...users].sort((a, b) => {
                    const aA = this.selectedUsers.includes(a.id);
                    const bA = this.selectedUsers.includes(b.id);
                    if (aA !== bA) return aA ? -1 : 1;
                    return a.name.localeCompare(b.name);
                });
            },

            /**
             * ✅ Détecte les changements par rapport au snapshot initial.
             */
            get hasChanges() {
                if (this.selectedUsers.length !== this.assignedIds.length) {
                    return true;
                }

                // Compare les 2 tableaux sans se soucier de l'ordre
                const sortedCurrent  = [...this.selectedUsers].sort((a, b) => a - b);
                const sortedOriginal = [...this.assignedIds].sort((a, b) => a - b);

                for (let i = 0; i < sortedCurrent.length; i++) {
                    if (sortedCurrent[i] !== sortedOriginal[i]) return true;
                }

                return false;
            },

            /* ============================================================
               HELPERS
               ============================================================ */
            isSelected(id) {
                return this.selectedUsers.includes(Number(id));
            },

            initials(name) {
                return (name || '?')
                    .split(' ')
                    .filter(Boolean)
                    .slice(0, 2)
                    .map(w => w[0].toUpperCase())
                    .join('');
            },

            /* ============================================================
               ACTIONS
               ============================================================ */
            setFilter(filter) {
                this.activeFilter = filter;
            },

            selectAllVisible() {
                const visibleIds = this.filteredUsers.map(u => u.id);
                const set = new Set([...this.selectedUsers, ...visibleIds]);
                this.selectedUsers = Array.from(set);
            },

            deselectAllVisible() {
                const visibleSet = new Set(this.filteredUsers.map(u => u.id));
                this.selectedUsers = this.selectedUsers.filter(id => !visibleSet.has(id));
            },

            /* ============================================================
               SOUMISSION — ✅ CORRECTION DU BUG #1
               ============================================================ */
            onSubmit(event) {
                // Guard anti double-soumission
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }

                // Confirmation si suppression massive
                const removed = this.assignedIds.filter(id => !this.selectedUsers.includes(id));
                if (removed.length > 5) {
                    const plural = removed.length > 1 ? 's' : '';
                    if (!confirm(`Vous allez retirer ${removed.length} utilisateur${plural} de ce rôle.\n\nContinuer ?`)) {
                        event.preventDefault();
                        return;
                    }
                }

                // ✅ On laisse le submit natif se produire (pas de requestSubmit !)
                this.submitting = true;
            },

            onCancelClick(event) {
                if (this.hasChanges && !this.submitting) {
                    if (!confirm('Vous avez des modifications non enregistrées. Quitter ?')) {
                        event.preventDefault();
                    }
                }
            },
        });

        // ✅ Enregistrement idempotent
        const register = () => {
            if (window.Alpine && !window._usersRoleManagerRegistered) {
                window._usersRoleManagerRegistered = true;
                window.Alpine.data('usersRoleManager', usersRoleManagerFactory);
            }
        };

        if (window.Alpine) {
            register();
        } else {
            document.addEventListener('alpine:init', register);
        }
    })();
</script>
@endpush