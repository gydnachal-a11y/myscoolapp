@extends('layouts.admin')

@section('page_title', 'Modifier la permission')
@section('page_subtitle', $permission->name)

@section('content')
@php
    // ============================================================
    // Préparation des données (côté serveur)
    // ============================================================
    $permissionsByResource = $permissionsByResource ?? [];
    $allPermissions        = $allPermissions        ?? [];

    $standardActions = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    // Actions disponibles par ressource (union standard + existantes)
    $actionsByResource = [];
    foreach ($permissionsByResource as $resource => $perms) {
        $actionsByResource[$resource] = collect($standardActions)
            ->merge($perms)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
@endphp

<div class="permission-edit-page"
     x-data="permissionEditForm({
         resources: {{ Js::from($resources) }},
         actionsByResource: {{ Js::from($actionsByResource) }},
         permissionsByResource: {{ Js::from($permissionsByResource) }},
         allPermissions: {{ Js::from($allPermissions) }},
         standardActions: {{ Js::from($standardActions) }},
         permission: {
             id: {{ (int) $permission->id }},
             name: {{ Js::from($permission->name) }},
             label: {{ Js::from($permission->label) }},
             description: {{ Js::from($permission->description) }},
             resource: {{ Js::from($permission->resource) }},
             action: {{ Js::from($permission->action) }}
         },
         oldValues: {
             name: {{ Js::from(old('name')) }},
             label: {{ Js::from(old('label')) }},
             description: {{ Js::from(old('description')) }},
             resource: {{ Js::from(old('resource')) }},
             action: {{ Js::from(old('action')) }}
         }
     })"
     x-init="init()">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-pen-to-square title-icon" aria-hidden="true"></i>
                <span>Modifier la permission</span>
                <code class="perm-badge">{{ $permission->name }}</code>
            </h1>
            <p class="page-subtitle">Modifiez les informations de la permission</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.permissions.index') }}"
               class="btn btn-ghost"
               @click="onCancelClick($event)">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FORMULAIRE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form action="{{ route('admin.permissions.update', $permission) }}"
          method="POST"
          @submit="onSubmit($event)"
          class="form-card"
          novalidate>

        @csrf
        @method('PUT')

        <div class="form-grid">

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- RESSOURCE --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="resource" class="form-label">
                    Ressource <span class="req">*</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-folder input-icon" aria-hidden="true"></i>
                    <select id="resource"
                            name="resource"
                            x-model="selectedResource"
                            @change="onResourceChange()"
                            required
                            class="form-select @error('resource') is-invalid @enderror">
                        <option value="">— Sélectionner une ressource —</option>
                        <template x-for="res in resources" :key="res">
                            <option :value="res" x-text="prettyResource(res)"></option>
                        </template>
                    </select>
                </div>

                <p class="form-help">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                    Sélectionnez la ressource concernée
                </p>

                @error('resource')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- ACTION --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field"
                 x-show="selectedResource"
                 x-transition
                 x-cloak>
                <label for="action" class="form-label">
                    Action <span class="req">*</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-arrow-right input-icon" aria-hidden="true"></i>
                    <select id="action"
                            name="action"
                            x-model="selectedAction"
                            @change="onActionChange()"
                            required
                            class="form-select @error('action') is-invalid @enderror">
                        <option value="">— Sélectionner une action —</option>
                        <template x-for="act in actions" :key="act">
                            <option :value="act" x-text="act"></option>
                        </template>
                    </select>
                </div>

                <p class="form-help">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                    Actions disponibles pour la ressource sélectionnée
                </p>

                @error('action')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- NOM — éditable (contrairement à create) --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="name" class="form-label">
                    Nom technique <span class="req">*</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-tag input-icon" aria-hidden="true"></i>
                    <input type="text"
                           id="name"
                           name="name"
                           x-model="permissionName"
                           required
                           maxlength="255"
                           autocomplete="off"
                           placeholder="ressource.action"
                           class="form-input @error('name') is-invalid @enderror"
                           :class="{
                               'is-valid': permissionName && !permissionConflict,
                               'is-invalid': permissionConflict
                           }">
                </div>

                <p class="form-help">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    Généré automatiquement, mais modifiable manuellement
                </p>

                <p x-show="permissionConflict"
                   x-cloak
                   class="form-help form-help-warn">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    Ce nom existe déjà pour une autre permission
                </p>

                @error('name')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- LIBELLÉ --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="label" class="form-label">
                    Libellé
                    <span class="optional">(optionnel)</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-pen-to-square input-icon" aria-hidden="true"></i>
                    <input type="text"
                           id="label"
                           name="label"
                           x-model="label"
                           maxlength="255"
                           autocomplete="off"
                           placeholder="Ex : Voir les paiements"
                           class="form-input @error('label') is-invalid @enderror">
                </div>

                <p class="form-help">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                    Un libellé lisible pour cette permission
                </p>

                @error('label')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- DESCRIPTION --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="description" class="form-label">
                    Description
                    <span class="optional">(optionnel)</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-comment input-icon input-icon-top" aria-hidden="true"></i>
                    <textarea id="description"
                              name="description"
                              x-model="description"
                              rows="2"
                              maxlength="500"
                              placeholder="Description détaillée…"
                              class="form-textarea @error('description') is-invalid @enderror"></textarea>
                </div>

                @error('description')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- APERÇU --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="preview-card"
                 x-show="permissionName"
                 x-cloak>
                <div class="preview-icon">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>
                <div class="preview-content">
                    <p class="preview-title">Aperçu de la permission</p>
                    <code class="preview-name" x-text="permissionName"></code>
                    <p class="preview-meta">
                        <span x-text="'Ressource : ' + (selectedResource || '—')"></span>
                        <span class="dot">·</span>
                        <span x-text="'Action : ' + (selectedAction || '—')"></span>
                    </p>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- AUTRES PERMISSIONS DE LA RESSOURCE (référence) --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="bulk-card"
                 x-show="selectedResource && otherPermissions.length > 0"
                 x-cloak>
                <header class="bulk-header">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>Autres permissions pour <strong x-text="prettyResource(selectedResource)"></strong></span>
                </header>

                <div class="bulk-pills">
                    <template x-for="perm in otherPermissions" :key="perm">
                        <span class="bulk-pill"
                              :class="{ 'is-self': perm === selectedAction }">
                            <i class="fa-solid"
                               :class="perm === selectedAction ? 'fa-star' : 'fa-check'"
                               aria-hidden="true"></i>
                            <span x-text="perm"></span>
                            <span x-show="perm === selectedAction" class="self-badge">actuelle</span>
                        </span>
                    </template>
                </div>
            </div>

        </div>

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- ACTIONS --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <footer class="form-actions">
            <a href="{{ route('admin.permissions.index') }}"
               class="btn btn-ghost"
               @click="onCancelClick($event)">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span>Annuler</span>
            </a>
            <button type="submit"
                    class="btn btn-primary"
                    :disabled="submitting || !canSubmit"
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
        </footer>
    </form>
</div>
@endsection

@push('styles')
{{-- ✅ Réutilise les styles partagés avec create --}}
@include('admin.permissions._form-styles')
<style>
    /* ════════════════════════════════════════════════════════
       SPÉCIFIQUE À L'ÉDITION
       ════════════════════════════════════════════════════════ */

    /* Badge avec le nom de la permission dans le titre */
    .permission-edit-page .perm-badge {
        display: inline-block;
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.72rem;
        font-weight: 600;
        color: #4338ca;
        background: var(--c-primary-soft);
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
        margin-left: 0.35rem;
    }

    /* Pill "actuelle" sur la pastille de la permission en cours d'édition */
    .permission-edit-page .bulk-pill.is-self {
        background: var(--c-primary-soft);
        border-color: var(--c-primary-mid);
        color: #4338ca;
    }
    .permission-edit-page .bulk-pill.is-self i {
        color: #f59e0b;
    }
    .permission-edit-page .bulk-pill .self-badge {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #4338ca;
        background: rgba(99,102,241,0.15);
        padding: 0.05rem 0.35rem;
        border-radius: 4px;
        margin-left: 0.25rem;
    }

    @media (max-width: 640px) {
        .permission-edit-page .perm-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
        }
    }
</style>
@endpush

@push('scripts')
{{-- ✅ Réutilise la factory partagée --}}
@include('admin.permissions._form-script')

<script>
    // ============================================================
    // Factory spécifique à l'édition (hérite du comportement de create)
    // ============================================================
    (function () {
        const factory = (config) => {
            // Récupère la factory de base (créée par _form-script)
            const base = window.Alpine
                ? Alpine.$data(document.body) // fallback
                : null;

            // On redéfinit tout pour éviter les conflits
            return {
                /* ============================================================
                   ÉTAT
                   ============================================================ */
                resources: config.resources || [],
                actionsByResource: config.actionsByResource || {},
                permissionsByResource: config.permissionsByResource || {},
                standardActions: config.standardActions || [],
                allPermissions: new Set(config.allPermissions || []),

                // Original (pour détecter les conflits)
                permission: config.permission || {},
                originalName: config.permission?.name || '',

                // Formulaire — old() prioritaire sur les valeurs DB
                selectedResource: config.oldValues?.resource || config.permission?.resource || '',
                selectedAction: config.oldValues?.action || config.permission?.action || '',
                permissionName: config.oldValues?.name || config.permission?.name || '',
                label: config.oldValues?.label ?? config.permission?.label ?? '',
                description: config.oldValues?.description ?? config.permission?.description ?? '',

                actions: [],
                otherPermissions: [],

                // UI
                submitting: false,

                // Snapshot pour hasChanges
                initialSnapshot: '',

                /* ============================================================
                   INIT
                   ============================================================ */
                init() {
                    if (this.selectedResource) {
                        this.refreshActions();
                    }
                    this.refreshOtherPermissions();

                    // Snapshot initial
                    this.initialSnapshot = this.snapshot();

                    // ✅ Reset submitting au retour navigateur
                    window.addEventListener('pageshow', () => {
                        this.submitting = false;
                    });

                    // ✅ Warning si modifs non sauvegardées
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
                get permissionConflict() {
                    if (!this.permissionName) return false;

                    // Le nom existe déjà ET ce n'est pas le nom original
                    return this.allPermissions.has(this.permissionName)
                        && this.permissionName !== this.originalName;
                },

                get canSubmit() {
                    return this.selectedResource
                        && this.selectedAction
                        && this.permissionName
                        && !this.permissionConflict;
                },

                get hasChanges() {
                    return this.snapshot() !== this.initialSnapshot;
                },

                /* ============================================================
                   ACTIONS
                   ============================================================ */
                onResourceChange() {
                    this.selectedAction = '';
                    this.permissionName = '';
                    this.refreshActions();
                    this.refreshOtherPermissions();
                },

                onActionChange() {
                    if (this.selectedResource && this.selectedAction) {
                        this.permissionName = `${this.selectedResource}.${this.selectedAction}`;
                    }
                    this.refreshOtherPermissions();
                },

                refreshActions() {
                    if (!this.selectedResource) {
                        this.actions = [];
                        return;
                    }

                    const standard = this.standardActions;
                    const existing = this.permissionsByResource[this.selectedResource] || [];

                    this.actions = [...new Set([...standard, ...existing])].sort();
                },

                refreshOtherPermissions() {
                    if (!this.selectedResource) {
                        this.otherPermissions = [];
                        return;
                    }

                    const perms = this.permissionsByResource[this.selectedResource] || [];

                    // On exclut l'action actuelle (car c'est celle qu'on édite)
                    // sauf si l'action a changé
                    this.otherPermissions = perms.filter(p => p !== this.permission?.action);
                },

                /* ============================================================
                   HELPERS
                   ============================================================ */
                prettyResource(resource) {
                    if (!resource) return '';
                    const s = String(resource).replace(/[-_]/g, ' ');
                    return s.charAt(0).toUpperCase() + s.slice(1);
                },

                snapshot() {
                    return JSON.stringify({
                        r: this.selectedResource,
                        a: this.selectedAction,
                        n: this.permissionName,
                        l: this.label,
                        d: this.description,
                    });
                },

                /* ============================================================
                   SOUMISSION
                   ============================================================ */
                onSubmit(event) {
                    if (this.submitting) {
                        event.preventDefault();
                        return;
                    }
                    if (!this.canSubmit) {
                        event.preventDefault();
                        return;
                    }
                    this.submitting = true;
                },

                onCancelClick(event) {
                    if (this.hasChanges && !this.submitting) {
                        if (!confirm('Vous avez des modifications non enregistrées. Quitter ?')) {
                            event.preventDefault();
                        }
                    }
                },
            };
        };

        const register = () => {
            if (window.Alpine && !window._permissionEditFormRegistered) {
                window._permissionEditFormRegistered = true;
                window.Alpine.data('permissionEditForm', factory);
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