@extends('layouts.admin')

@section('page_title', 'Permissions du rôle')
@section('page_subtitle', $role->label ?? $role->name)

@section('content')
@php
    // ============================================================
    // Normalisation des permissions (groupées par scope + resource)
    // ============================================================
    $normalize = function ($permissions): array {
        if (!is_array($permissions)) {
            return ['admin' => [], 'member' => []];
        }

        $isGrouped = !array_is_list($permissions)
            && (array_key_exists('admin', $permissions) || array_key_exists('member', $permissions));

        if ($isGrouped) {
            return [
                'admin'  => $permissions['admin']  ?? [],
                'member' => $permissions['member'] ?? [],
            ];
        }

        $grouped = ['admin' => [], 'member' => []];

        foreach ($permissions as $perm) {
            $name     = is_array($perm) ? ($perm['name']     ?? '') : ($perm->name     ?? '');
            $resource = is_array($perm) ? ($perm['resource'] ?? '') : ($perm->resource ?? '');
            $scope    = str_contains($name, 'member.') ? 'member' : 'admin';

            $grouped[$scope][$resource][] = $perm;
        }

        return $grouped;
    };

    $groupedPermissions = $normalize($permissions);

    $totalPermissions = 0;
    foreach ($groupedPermissions as $scopes) {
        foreach ($scopes as $perms) {
            $totalPermissions += count($perms);
        }
    }

    // ✅ Cast en int + array propre
    $initialSelected = array_map('intval', (array) old('permissions', $rolePermissions ?? []));
@endphp

<div class="role-form-page"
     x-data="roleForm({
         initialSelected: {{ Js::from($initialSelected) }},
         permissionsData: {{ Js::from($groupedPermissions) }}
     })"
     x-init="init()">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-key title-icon" aria-hidden="true"></i>
                <span>Permissions du rôle</span>
                <span class="role-badge">{{ $role->label ?? ucfirst($role->name) }}</span>
            </h1>
            <p class="page-subtitle">Attribuez ou retirez des permissions à ce rôle</p>
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

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FORMULAIRE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form action="{{ route('admin.roles.sync-permissions', $role) }}"
          method="POST"
          @submit="onSubmit($event)"
          class="content-card"
          novalidate>

        @csrf

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- SECTION : PERMISSIONS --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <section class="form-section form-section-perms">
            <header class="section-header">
                <div class="section-icon section-icon-perm">
                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                </div>
                <div class="section-header-text">
                    <h2 class="section-title">Permissions du rôle</h2>
                    <p class="section-subtitle">
                        <span x-text="selectedIds.length"></span>
                        <span x-text="selectedIds.length > 1 ? ' permissions sélectionnées' : ' permission sélectionnée'"></span>
                        <span class="text-muted"> sur {{ $totalPermissions }}</span>
                    </p>
                </div>
            </header>

            {{-- Tabs Admin / Member --}}
            <nav class="tabs" role="tablist" aria-label="Scopes de permissions">
                <button type="button" role="tab"
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
                <button type="button" role="tab"
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

            {{-- Toolbar --}}
            <div class="perm-toolbar">
                <div class="search-field">
                    <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="search"
                           x-model.debounce.200ms="search"
                           placeholder="Rechercher une permission…"
                           aria-label="Rechercher une permission"
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

                <div class="toolbar-buttons">
                    <button type="button"
                            class="link-btn link-success"
                            @click="selectAllVisible()"
                            :disabled="totalVisible === 0">
                        <i class="fa-solid fa-check-double" aria-hidden="true"></i>
                        <span>Tout cocher</span>
                    </button>
                    <button type="button"
                            class="link-btn link-muted"
                            @click="deselectAllVisible()"
                            :disabled="totalVisible === 0">
                        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                        <span>Tout décocher</span>
                    </button>
                </div>
            </div>

            {{-- Grille --}}
            <div class="permissions-body">
                <template x-if="totalVisible === 0 && search.length > 0">
                    <div class="empty-perm">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <p>Aucune permission ne correspond à « <strong x-text="search"></strong> ».</p>
                    </div>
                </template>

                <template x-if="totalVisible === 0 && search.length === 0">
                    <div class="empty-perm">
                        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                        <p>Aucune permission dans ce scope.</p>
                    </div>
                </template>

                <div class="permissions-grid"
                     x-show="totalVisible > 0"
                     x-cloak>
                    <template x-for="(perms, resource) in filteredPermissions" :key="resource">
                        <article class="resource-card"
                                 :class="{ 'has-selected': (selectedCountByResource[resource] || 0) > 0 }">
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
        </section>

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- FOOTER STICKY --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <footer class="content-footer">
            <div class="footer-info">
                <span class="footer-count" aria-live="polite">
                    <i class="fa-solid fa-key" aria-hidden="true"></i>
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

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- STYLES PARTAGÉS --}}
{{-- ══════════════════════════════════════════════════════════ --}}
@include('admin.roles._form-styles')

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ALPINE --}}
{{-- ══════════════════════════════════════════════════════════ --}}
@include('admin.roles._form-script')