@push('scripts')
<script>
    /**
     * ✅ Factory Alpine pour le formulaire de CRÉATION de permission.
     *    Utilisée par admin.permissions.create.
     */
    (function () {
        const factory = (config) => ({
            resources: config.resources || [],
            actionsByResource: config.actionsByResource || {},
            permissionsByResource: config.permissionsByResource || {},
            standardActions: config.standardActions || [],
            allPermissions: new Set(config.allPermissions || []),

            selectedResource: config.oldValues?.resource || '',
            selectedAction: config.oldValues?.action || '',
            permissionName: config.oldValues?.name || '',
            label: config.oldValues?.label || '',
            description: config.oldValues?.description || '',
            actions: [],
            existingPermissions: [],

            isBulkLoading: false,
            bulkMessage: '',
            bulkMessageType: null,
            submitting: false,

            bulkCreateUrl: config.bulkCreateUrl || '',
            csrfToken: config.csrfToken || '',

            initialSnapshot: '',

            init() {
                if (this.selectedResource) {
                    this.refreshActions();
                }

                if (this.permissionName) {
                    const parts = this.permissionName.split('.');
                    if (parts.length === 2) {
                        this.selectedResource = parts[0];
                        this.selectedAction = parts[1];
                        this.refreshActions();
                    }
                }

                this.refreshExisting();
                this.initialSnapshot = this.snapshot();

                window.addEventListener('pageshow', () => {
                    this.submitting = false;
                });

                window.addEventListener('beforeunload', (e) => {
                    if (this.hasChanges && !this.submitting) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
            },

            get permissionExists() {
                return this.permissionName && this.allPermissions.has(this.permissionName);
            },

            get missingActions() {
                if (!this.selectedResource) return [];
                const existing = new Set(this.existingPermissions);
                return this.standardActions.filter(a => !existing.has(a));
            },

            get canSubmit() {
                return this.selectedResource
                    && this.selectedAction
                    && this.permissionName
                    && !this.permissionExists;
            },

            get hasChanges() {
                return this.snapshot() !== this.initialSnapshot;
            },

            onResourceChange() {
                this.selectedAction = '';
                this.permissionName = '';
                this.refreshActions();
                this.refreshExisting();
                this.bulkMessage = '';
                this.bulkMessageType = null;
            },

            onActionChange() {
                this.permissionName = this.selectedResource && this.selectedAction
                    ? `${this.selectedResource}.${this.selectedAction}`
                    : '';
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

            refreshExisting() {
                if (!this.selectedResource) {
                    this.existingPermissions = [];
                    return;
                }
                this.existingPermissions = this.permissionsByResource[this.selectedResource] || [];
            },

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

            async bulkCreate() {
                if (!this.selectedResource || this.isBulkLoading) return;

                const missing = this.missingActions;
                if (missing.length === 0) {
                    this.bulkMessage = 'Toutes les actions existent déjà.';
                    this.bulkMessageType = 'error';
                    return;
                }

                if (!confirm(
                    `Créer les permissions manquantes pour « ${this.selectedResource} » ?\n\n`
                    + `- ${missing.join('\n- ')}`
                )) {
                    return;
                }

                this.isBulkLoading = true;
                this.bulkMessage = '';
                this.bulkMessageType = null;

                try {
                    const params = new URLSearchParams({
                        resource: this.selectedResource,
                        actions: missing.join(','),
                    });

                    const response = await fetch(`${this.bulkCreateUrl}?${params}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        const existing = new Set(this.permissionsByResource[this.selectedResource] || []);
                        (data.created || []).forEach(name => {
                            const parts = String(name).split('.');
                            const action = parts.slice(1).join('.');
                            existing.add(action);
                            this.allPermissions.add(name);
                        });
                        this.permissionsByResource[this.selectedResource] = [...existing].sort();
                        this.refreshExisting();

                        const count = (data.created || []).length;
                        this.bulkMessage = `${count} permission(s) créée(s) avec succès.`;
                        this.bulkMessageType = 'success';
                    } else {
                        this.bulkMessage = data.error || 'Une erreur est survenue.';
                        this.bulkMessageType = 'error';
                    }

                } catch (error) {
                    console.error('bulkCreate error:', error);
                    this.bulkMessage = 'Erreur réseau. Veuillez réessayer.';
                    this.bulkMessageType = 'error';
                } finally {
                    this.isBulkLoading = false;
                }
            },

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
        });

        const register = () => {
            if (window.Alpine && !window._permissionFormRegistered) {
                window._permissionFormRegistered = true;
                window.Alpine.data('permissionForm', factory);
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