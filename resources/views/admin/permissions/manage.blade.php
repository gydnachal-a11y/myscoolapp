@extends('layouts.admin')

@section('page_title', 'Gestion avancée des permissions')
@section('page_subtitle', 'Attribuez des permissions groupées par page et par action')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" x-data="permissionManager()" x-init="init()">
    {{-- Messages flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
            <i class="fa-regular fa-check-circle mt-0.5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
            <i class="fa-regular fa-circle-exclamation mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- En-tête + actions --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-lock text-indigo-500"></i>
                Gestion avancée des permissions
            </h1>
            <p class="text-sm text-gray-500 mt-1">Sélectionnez un rôle ou un utilisateur, puis cochez les permissions autorisées</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.permissions.sync-all') }}" class="btn-secondary" onclick="return confirm('Synchroniser les permissions depuis les routes ?')">
                <i class="fa-solid fa-rotate"></i> Synchroniser
            </a>
            <a href="{{ route('admin.permissions.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nouvelle permission
            </a>
        </div>
    </div>

    {{-- Sélecteur de cible --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Cible (rôle ou utilisateur)</label>
        <select x-model="selectedTarget" @change="loadPermissions()" class="w-full md:w-1/2 rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Choisir une cible --</option>
            <optgroup label="Rôles">
                @foreach($roles as $role)
                    <option value="role:{{ $role->id }}">{{ $role->label ?? $role->name }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Utilisateurs">
                @foreach($users as $user)
                    <option value="user:{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </optgroup>
        </select>
        <p class="text-xs text-gray-400 mt-2">
            <span x-text="selectedCount"></span> permission(s) sélectionnée(s)
        </p>
    </div>

    {{-- Zone des permissions groupées --}}
    <div x-show="loading" class="text-center py-8">
        <i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-500"></i>
        <p class="text-sm text-gray-500 mt-2">Chargement des permissions...</p>
    </div>

    <div x-show="!loading && selectedTarget">
        {{-- Boutons tout cocher / décocher global --}}
        <div class="flex items-center gap-3 mb-4">
            <button type="button" @click="setAll(true)" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Tout cocher</button>
            <span class="text-gray-300">|</span>
            <button type="button" @click="setAll(false)" class="text-sm text-gray-500 hover:text-gray-700 font-medium">Tout décocher</button>
        </div>

        {{-- Onglets Admin / Membre --}}
        <div class="mb-4 flex gap-2">
            <button @click="activeScope = 'admin'"
                    :class="activeScope === 'admin' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">Administration</button>
            <button @click="activeScope = 'member'"
                    :class="activeScope === 'member' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
                    class="px-4 py-2 rounded-lg font-medium transition">Espace membre</button>
        </div>

        {{-- Liste groupée par ressource --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="(permissions, resource) in groupedPermissions" :key="resource">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-700 capitalize" x-text="resource"></h3>
                        <div class="flex items-center gap-2">
                            <button @click="setResourceAll(resource, true)" class="text-xs text-indigo-600 hover:text-indigo-800">Tout</button>
                            <button @click="setResourceAll(resource, false)" class="text-xs text-gray-500 hover:text-gray-700">Aucun</button>
                        </div>
                    </div>
                    <div class="space-y-1.5 max-h-60 overflow-y-auto">
                        <template x-for="perm in permissions" :key="perm.name">
                            <label class="flex items-center gap-2.5 text-sm p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                <input type="checkbox"
                                       :value="perm.name"
                                       x-model="selectedPermissions"
                                       class="permission-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-gray-600" x-text="perm.label || perm.action"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Bouton enregistrer --}}
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="resetForm()" class="btn-cancel">Réinitialiser</button>
            <button type="button" @click="savePermissions()" class="btn-primary" :disabled="saving">
                <i class="fa-regular fa-save"></i>
                <span x-text="saving ? 'Enregistrement...' : 'Enregistrer les permissions'"></span>
            </button>
        </div>
    </div>

    <div x-show="!loading && !selectedTarget" class="text-center py-12 text-gray-500 bg-white rounded-xl shadow-sm border border-gray-100">
        <i class="fa-regular fa-hand-pointer text-4xl block mb-3 text-gray-300"></i>
        <p class="text-lg font-medium text-gray-600">Sélectionnez une cible pour commencer</p>
        <p class="text-sm text-gray-400">Choisissez un rôle ou un utilisateur ci-dessus</p>
    </div>
</div>

<style>
    .btn-primary {
        padding: 0.65rem 1.25rem;
        background: #1e293b;
        color: white;
        border-radius: 10px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid transparent;
    }
    .btn-primary:hover:not(:disabled) {
        background: #667eea;
        border-color: #667eea;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102,126,234,0.3);
    }
    .btn-primary:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    .btn-secondary {
        padding: 0.65rem 1.25rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        color: #64748b;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-secondary:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }
    .btn-cancel {
        padding: 0.65rem 1.25rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: white;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-cancel:hover {
        border-color: #ef4444;
        color: #ef4444;
        background: #fef2f2;
    }
</style>

<script>
    function permissionManager() {
        return {
            // État
            selectedTarget: '',
            selectedPermissions: [],
            loading: false,
            saving: false,
            activeScope: 'admin',
            groupedData: @json($grouped), // Format: { admin: { resource: [ {name, label, action}, ... ] }, member: ... }
            groupedPermissions: {}, // Contiendra seulement le scope actif

            init() {
                // Si une cible est passée en query (facultatif), on la sélectionne
                const urlParams = new URLSearchParams(window.location.search);
                const target = urlParams.get('target');
                if (target) {
                    this.selectedTarget = target;
                    this.loadPermissions();
                }
                this.updateGroupedPermissions();
            },

            // Met à jour groupedPermissions selon activeScope
            updateGroupedPermissions() {
                this.groupedPermissions = this.groupedData[this.activeScope] || {};
            },

            // Charge les permissions actuelles de la cible sélectionnée
            async loadPermissions() {
                if (!this.selectedTarget) {
                    this.selectedPermissions = [];
                    this.updateGroupedPermissions();
                    return;
                }

                const [type, id] = this.selectedTarget.split(':');
                this.loading = true;
                try {
                    const response = await fetch(`{{ route('admin.permissions.target.permissions', ['type' => '__type__', 'id' => '__id__']) }}`
                        .replace('__type__', type).replace('__id__', id));
                    if (!response.ok) throw new Error('Erreur réseau');
                    const data = await response.json();
                    this.selectedPermissions = data.permissions || [];
                } catch (error) {
                    console.error(error);
                    alert('Erreur lors du chargement des permissions');
                } finally {
                    this.loading = false;
                    this.updateGroupedPermissions();
                }
            },

            // Sélectionner/désélectionner toutes les permissions visibles
            setAll(checked) {
                const current = this.groupedPermissions;
                Object.values(current).forEach(perms => {
                    perms.forEach(p => {
                        const index = this.selectedPermissions.indexOf(p.name);
                        if (checked && index === -1) {
                            this.selectedPermissions.push(p.name);
                        } else if (!checked && index !== -1) {
                            this.selectedPermissions.splice(index, 1);
                        }
                    });
                });
            },

            // Sélectionner/désélectionner toutes les permissions d'une ressource
            setResourceAll(resource, checked) {
                const perms = this.groupedPermissions[resource] || [];
                perms.forEach(p => {
                    const index = this.selectedPermissions.indexOf(p.name);
                    if (checked && index === -1) {
                        this.selectedPermissions.push(p.name);
                    } else if (!checked && index !== -1) {
                        this.selectedPermissions.splice(index, 1);
                    }
                });
            },

            // Réinitialiser la sélection
            resetForm() {
                this.loadPermissions();
            },

            // Enregistrer les permissions
            async savePermissions() {
                if (!this.selectedTarget) return;
                const [type, id] = this.selectedTarget.split(':');
                this.saving = true;
                try {
                    const response = await fetch(`{{ route('admin.permissions.target.sync', ['type' => '__type__', 'id' => '__id__']) }}`
                        .replace('__type__', type).replace('__id__', id), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ permissions: this.selectedPermissions })
                    });
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.error || 'Erreur lors de l\'enregistrement');
                    }
                    alert('Permissions enregistrées avec succès');
                } catch (error) {
                    console.error(error);
                    alert(error.message);
                } finally {
                    this.saving = false;
                }
            },

            // Compteur de permissions sélectionnées (getter)
            get selectedCount() {
                return this.selectedPermissions.length;
            },

            // Observateur pour activeScope
            watch: {
                activeScope(newVal) {
                    this.updateGroupedPermissions();
                }
            }
        }
    }
</script>
@endsection