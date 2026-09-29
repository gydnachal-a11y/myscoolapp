@push('scripts')
<script>
    (function () {
        // ✅ CORRECTION #1 : factory partagée + enregistrement idempotent
        const roleFormFactory = (config) => ({
            /* ============================================================
               ÉTAT
               ============================================================ */
            activeScope: 'admin',
            search: '',
            selectedIds: [],
            selectedSet: new Set(),
            initialIds: [],
            initialSet: new Set(),
            permissionsData: config.permissionsData || { admin: {}, member: {} },
            submitting: false,

            // Caches internes
            _filteredCache: {},
            _filteredKey: '',
            selectedCountByScope: { admin: 0, member: 0 },
            totalCountByScope:    { admin: 0, member: 0 },
            selectedCountByResource: {},

            /* ============================================================
               INIT
               ============================================================ */
            init() {
                const initial = (config.initialSelected || []).map(Number);

                // ✅ CORRECTION #2 : Set pour lookups O(1)
                this.initialIds = initial;
                this.initialSet = new Set(initial);
                this.selectedIds = [...initial];
                this.selectedSet = new Set(initial);

                // ✅ CORRECTION #4 : reset du flag submitting au retour navigateur
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

                // Auto-focus sur le scope dominant
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
                const key = this.activeScope + '||' + this.search;
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
               CALCULS
               ============================================================ */
            computeFiltered() {
                const query = (this.search || '').toLowerCase().trim();
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
                            if (this.selectedSet.has(Number(p.id))) counts[scope]++;
                        }
                    }
                }

                this.selectedCountByScope = counts;
                this.totalCountByScope    = totals;

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
                this.search = '';
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

            selectAllVisible() {
                const visibleIds = this._getVisibleIds();
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
            },

            deselectAllVisible() {
                const toRemove = new Set(this._getVisibleIds());
                const before = this.selectedIds.length;

                this.selectedIds = this.selectedIds.filter(id => !toRemove.has(id));

                if (this.selectedIds.length !== before) {
                    this.selectedSet = new Set(this.selectedIds);
                    this.recomputeAll();
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

            // ✅ CORRECTION #9 : "Salles de classe" au lieu de "Salles De Classe"
            prettyResource(resource) {
                const s = String(resource).replace(/[-_]/g, ' ');
                return s.charAt(0).toUpperCase() + s.slice(1);
            },

            /* ============================================================
               SOUMISSION
               ============================================================ */
            // ✅ CORRECTION #4 : guard anti double-soumission
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
                    }
                }
            },
        });

        // ✅ Enregistrement idempotent
        const register = () => {
            if (window.Alpine && !window._roleFormRegistered) {
                window._roleFormRegistered = true;
                window.Alpine.data('roleForm', roleFormFactory);
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