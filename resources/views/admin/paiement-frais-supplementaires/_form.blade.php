@php
    $paiementExistant = $paiement ?? null;
    $action = $paiementExistant
        ? route('admin.paiement-frais-supplementaires.update', $paiementExistant)
        : route('admin.paiement-frais-supplementaires.store');
    $method = $paiementExistant ? 'PUT' : null;
    $titre = $paiementExistant ? 'Modifier' : 'Nouveau';
@endphp

<div class="max-w-6xl mx-auto" x-data="paiementFraisSupplementaireForm(@json($paiementExistant))" x-init="init()">
    <div class="header-container">
        <div>
            <h1 class="form-title">{{ $titre }} <strong>paiement frais supplémentaire</strong></h1>
            <p class="form-subtitle">
                {{ $paiementExistant ? 'Mettez à jour le paiement d’un frais ponctuel' : 'Enregistrez le paiement d’un frais ponctuel' }}
            </p>
        </div>
        <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour aux paiements
        </a>
    </div>

    @if ($errors->any())
        <div class="alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Sélection du frais et de la salle --}}
    <div class="form-card mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-field">
                <label for="frais_supplementaire_id" class="field-label">Frais supplémentaire <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="frais_supplementaire_id" id="frais_supplementaire_id"
                            x-model="selectedFraisId" @change="onFraisChange"
                            class="@error('frais_supplementaire_id') is-invalid @enderror" required>
                        <option value="">Sélectionner un frais</option>
                        @foreach($frais as $f)
                            <option value="{{ $f['id'] }}"
                                    {{ old('frais_supplementaire_id', $paiementExistant?->frais_supplementaire_id) == $f['id'] ? 'selected' : '' }}>
                                {{ $f['libelle'] }} - {{ number_format($f['montant'], 0, ',', ' ') }} $
                            </option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('frais_supplementaire_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label for="salle_classe_id" class="field-label">Salle de classe <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="salle_classe_id" id="salle_classe_id"
                            x-model="selectedSalleId" @change="onSalleChange"
                            :disabled="!selectedFraisId"
                            class="@error('salle_classe_id') is-invalid @enderror" required>
                        <option value="">Sélectionner une salle</option>
                        <template x-for="salle in sallesDisponibles" :key="salle.id">
                            <option :value="salle.id" x-text="salle.nom"></option>
                        </template>
                    </select>
                    <i class="fa-solid fa-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('salle_classe_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- Informations du frais sélectionné --}}
    <div class="form-card mb-6" x-show="selectedFraisId">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="info-box">
                <div class="info-label">Montant du frais</div>
                <div class="info-value" x-text="montantFrais + ' $'"></div>
                <div class="info-sub" x-text="montantFraisFC"></div>
            </div>
            <div class="info-box">
                <div class="info-label">Salle concernée</div>
                <div class="info-value font-bold" x-text="libelleSalle"></div>
                <div class="info-sub" x-text="sallesAutoriseesLabel"></div>
            </div>
            <div class="info-box">
                <div class="info-label">Élève sélectionné</div>
                <div class="info-value font-bold" x-text="nomEleve"></div>
                <div class="info-sub" x-text="eleveSalle"></div>
            </div>
        </div>
    </div>

    {{-- Formulaire --}}
    <form action="{{ $action }}" method="POST" class="form-container" novalidate>
        @csrf
        @if($method)
            @method($method)
        @endif

        <input type="hidden" name="frais_supplementaire_id" x-model="selectedFraisId">
        <input type="hidden" name="salle_classe_id" x-model="selectedSalleId">
        <input type="hidden" name="eleve_id" x-model="selectedEleveId">
        <input type="hidden" name="montant_paye_usd" x-model="montantPaye">
        <input type="hidden" name="montant_paye_fc" x-model="montantPayeFC">

        <div class="form-grid">
            {{-- Élève --}}
            <div class="form-field">
                <label for="eleve_id" class="field-label">Élève <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="eleve_id" id="eleve_id"
                            x-model="selectedEleveId" @change="onEleveChange"
                            :disabled="!selectedSalleId"
                            class="@error('eleve_id') is-invalid @enderror" required>
                        <option value="">Sélectionner un élève</option>
                        <template x-for="eleve in elevesDisponibles" :key="eleve.id">
                            <option :value="eleve.id" x-text="eleve.nom_complet"></option>
                        </template>
                    </select>
                    <i class="fa-solid fa-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('eleve_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Montant payé (USD + FC synchronisés) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="number" step="1" min="0" x-model="montantPaye" @input="syncUSDToFC"
                               placeholder=" " required
                               :max="montantFrais"
                               :class="{'is-invalid': montantPaye > montantFrais}"
                               class="@error('montant_paye_usd') is-invalid @enderror">
                        <label class="float-label">Montant payé (USD) <span style="color:#ef4444;">*</span></label>
                        <i class="fa-solid fa-dollar-sign icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    <template x-if="montantPaye > montantFrais">
                        <p class="error-text" style="color:#ef4444;">Le montant payé ne peut pas dépasser le montant du frais.</p>
                    </template>
                    <small class="text-muted">Maximum : <span x-text="montantFrais"></span> $</small>
                    <button type="button" @click="payerTout" class="btn-small mt-1">Payer tout</button>
                </div>

                <div class="form-field">
                    <div class="input-wrap">
                        <input type="number" step="1" x-model="montantPayeFC" @input="syncFCToUSD"
                               placeholder=" " class="bg-gray-50">
                        <label class="float-label">Montant payé (FC)</label>
                        <i class="fa-solid fa-franc-sign icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    <small class="text-muted">Taux : <span x-text="tauxChange.toFixed(2)"></span> FC/USD</small>
                </div>
            </div>

            {{-- Commentaire --}}
            <div class="form-field">
                <label for="commentaire" class="field-label">Commentaire</label>
                <div class="textarea-wrap">
                    <textarea name="commentaire" id="commentaire" rows="2" placeholder=" "
                              x-model="commentaire"
                              class="@error('commentaire') is-invalid @enderror">{{ old('commentaire', $paiementExistant?->commentaire) }}</textarea>
                    <label for="commentaire" class="float-label">Commentaire (optionnel)</label>
                    <i class="fa-regular fa-comment-dots icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('commentaire')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="mt-2" x-show="estPret">
                <div class="badge badge-green"><i class="fa-solid fa-circle"></i> Prêt à {{ $paiementExistant ? 'mettre à jour' : 'enregistrer' }}</div>
            </div>
        </div>

        <div class="button-group">
            <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-submit"
                    :disabled="!estPret">
                {{ $paiementExistant ? 'Mettre à jour' : 'Enregistrer' }} <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Styles optimisés (identiques à la version précédente, mais consolidés) */
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; animation: fadeUp 0.5s ease forwards; opacity: 0; }
    .form-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: #667eea; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: color 0.2s; }
    .back-link:hover { color: #4f46e5; }

    .alert-danger { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 10px; font-size: 0.9rem; animation: fadeUp 0.5s 0.1s ease forwards; opacity: 0; }
    .alert-danger i { font-size: 1.2rem; margin-top: 0.2rem; }
    .alert-danger ul { margin: 0; padding-left: 1rem; }

    .form-card { background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); animation: cardIn 0.5s ease forwards; opacity: 0; }
    .form-container { background: white; padding: 2rem 2.5rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); animation: cardIn 0.5s 0.2s ease forwards; opacity: 0; }

    .form-grid { display: flex; flex-direction: column; gap: 1.5rem; }
    .form-field { position: relative; animation: fieldSlide 0.4s ease forwards; opacity: 0; }
    .form-field:nth-of-type(1) { animation-delay: 0.3s; }
    .form-field:nth-of-type(2) { animation-delay: 0.4s; }
    .form-field:nth-of-type(3) { animation-delay: 0.5s; }
    .field-label { display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; }

    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes cardIn { from { opacity: 0; transform: translateY(25px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fieldSlide { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

    .select-wrap { position: relative; }
    .select-wrap select { width: 100%; padding: 0.8rem 2.5rem 0.8rem 0; border: none; border-bottom: 2px solid #e2e8f0; background: transparent; font-size: 1rem; color: #1e293b; font-weight: 500; transition: border-color 0.3s; outline: none !important; -webkit-appearance: none; -moz-appearance: none; appearance: none; cursor: pointer; }
    .select-wrap select:focus { border-bottom-color: #667eea; }
    .select-wrap select.is-invalid { border-bottom-color: #ef4444; }
    .select-wrap select:disabled { background: #f8fafc; cursor: not-allowed; }
    .select-wrap .select-icon { position: absolute; right: 0; top: 50%; transform: translateY(-50%); color: #cbd5e1; font-size: 1.1rem; pointer-events: none; transition: color 0.3s; }
    .select-wrap select:focus ~ .select-icon { color: #667eea; }
    .select-wrap .line-focus { position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #667eea, #764ba2); transition: all 0.4s cubic-bezier(0.4,0,0.2,1); transform: translateX(-50%); pointer-events: none; }
    .select-wrap select:focus ~ .line-focus { width: 100%; }

    .input-wrap { position: relative; }
    .input-wrap input { width: 100%; padding: 0.8rem 2.5rem 0.8rem 0; border: none; border-bottom: 2px solid #e2e8f0; background: transparent; font-size: 1rem; color: #1e293b; font-weight: 500; transition: border-color 0.3s; outline: none !important; -webkit-appearance: none; -moz-appearance: none; appearance: none; }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid { border-bottom-color: #ef4444; }
    .input-wrap input:disabled { background: #f8fafc; cursor: not-allowed; }
    .input-wrap .float-label { position: absolute; left: 0; top: 0.8rem; color: #94a3b8; font-size: 1rem; pointer-events: none; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label { top: -0.6rem; font-size: 0.72rem; font-weight: 700; color: #667eea; letter-spacing: 0.5px; text-transform: uppercase; }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }
    .input-wrap i.icon { position: absolute; right: 0; top: 50%; transform: translateY(-50%); color: #cbd5e1; font-size: 1.1rem; transition: all 0.3s; pointer-events: none; }
    .input-wrap input:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
    .input-wrap input.is-invalid ~ i.icon { color: #ef4444; }
    .input-wrap .line-focus { position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #667eea, #764ba2); transition: all 0.4s cubic-bezier(0.4,0,0.2,1); transform: translateX(-50%); pointer-events: none; }
    .input-wrap input:focus ~ .line-focus { width: 100%; }

    .textarea-wrap { position: relative; }
    .textarea-wrap textarea { width: 100%; padding: 0.8rem 2.5rem 0.8rem 0; border: none; border-bottom: 2px solid #e2e8f0; background: transparent; font-size: 1rem; color: #1e293b; font-weight: 500; transition: border-color 0.3s; outline: none !important; resize: vertical; min-height: 40px; }
    .textarea-wrap textarea::placeholder { color: transparent; }
    .textarea-wrap textarea:focus { border-bottom-color: #667eea; }
    .textarea-wrap textarea.is-invalid { border-bottom-color: #ef4444; }
    .textarea-wrap .float-label { position: absolute; left: 0; top: 0.8rem; color: #94a3b8; font-size: 1rem; pointer-events: none; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label { top: -0.6rem; font-size: 0.72rem; font-weight: 700; color: #667eea; letter-spacing: 0.5px; text-transform: uppercase; }
    .textarea-wrap textarea.is-invalid:focus ~ .float-label,
    .textarea-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }
    .textarea-wrap i.icon { position: absolute; right: 0; top: 0.8rem; transform: translateY(-50%); color: #cbd5e1; font-size: 1.1rem; transition: all 0.3s; pointer-events: none; }
    .textarea-wrap textarea:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
    .textarea-wrap textarea.is-invalid ~ i.icon { color: #ef4444; }
    .textarea-wrap .line-focus { position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #667eea, #764ba2); transition: all 0.4s cubic-bezier(0.4,0,0.2,1); transform: translateX(-50%); pointer-events: none; }
    .textarea-wrap textarea:focus ~ .line-focus { width: 100%; }

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }
    .text-muted { font-size: 0.8rem; color: #64748b; }
    .bg-gray-50 { background: #f8fafc; }

    .info-box { background: #f8fafc; border-radius: 12px; padding: 1.25rem; text-align: center; }
    .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600; margin-bottom: 0.5rem; }
    .info-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
    .info-value.font-bold { font-weight: 800; }
    .info-sub { font-size: 0.8rem; color: #64748b; }

    .btn-small { padding: 0.2rem 0.8rem; background: #e2e8f0; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; color: #475569; cursor: pointer; transition: background 0.2s; }
    .btn-small:hover { background: #cbd5e1; }

    .button-group { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; margin-top: 1.5rem; }
    .btn-submit { padding: 0.8rem 2rem; background: #1e293b; color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); outline: none !important; }
    .btn-submit:hover:not(:disabled) { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-cancel { padding: 0.8rem 1.5rem; border: 1.5px solid #e2e8f0; border-radius: 12px; color: #64748b; background: white; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-green { background: #dcfce7; color: #16a34a; }

    @media (max-width: 768px) {
        .header-container { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .form-container { padding: 1.5rem; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
    }
</style>
@endpush

@push('scripts')
<script>
    function paiementFraisSupplementaireForm(paiementExistant = null) {
        // Si en édition, on initialise le montant payé avec la valeur existante
        const initialMontantPaye = paiementExistant ? {{ $paiementExistant?->montant_paye_usd ?? 0 }} : 0;

        return {
            // Données
            fraisData: @json($frais),
            sallesData: @json($salles),
            elevesParSalle: @json($elevesParSalle),
            tauxChange: {{ $tauxChange ?? 2800 }},

            // Sélections (pré-remplies)
            selectedFraisId: @json(old('frais_supplementaire_id', $paiementExistant?->frais_supplementaire_id ?? '')),
            selectedSalleId: @json(old('salle_classe_id', $paiementExistant?->salle_classe_id ?? '')),
            selectedEleveId: @json(old('eleve_id', $paiementExistant?->eleve_id ?? '')),
            commentaire: @json(old('commentaire', $paiementExistant?->commentaire ?? '')),

            // Montants
            montantFrais: 0,
            montantPaye: initialMontantPaye,
            montantPayeFC: 0,

            // Affichage
            libelleSalle: '',
            nomEleve: '',
            eleveSalle: '',

            init() {
                // Initialiser les données au démarrage
                this.rafraichirInfosFrais();
                this.rafraichirInfosSalle();
                this.rafraichirInfosEleve();
                // Si le montant payé n'est pas encore défini (cas création), on le met au montant du frais
                if (!this.montantPaye) {
                    this.montantPaye = this.montantFrais;
                }
                this.syncUSDToFC();
            },

            // Getters
            get fraisSelectionne() {
                return this.fraisData.find(f => f.id == this.selectedFraisId);
            },

            get sallesDisponibles() {
                if (!this.fraisSelectionne) return [];
                if (this.fraisSelectionne.est_pour_toutes_salles) return this.sallesData;
                return this.sallesData.filter(s => this.fraisSelectionne.salles_ids.includes(s.id));
            },

            get elevesDisponibles() {
                if (!this.selectedSalleId) return [];
                return this.elevesParSalle[this.selectedSalleId] || [];
            },

            get montantFraisFC() {
                return this.montantFrais ? Math.round(this.montantFrais * this.tauxChange).toLocaleString('fr-FR') + ' FC' : '';
            },

            get sallesAutoriseesLabel() {
                if (!this.fraisSelectionne) return '';
                return this.fraisSelectionne.est_pour_toutes_salles ? 'Toutes les salles' : this.sallesDisponibles.length + ' salle(s) autorisée(s)';
            },

            get estPret() {
                return this.selectedFraisId &&
                       this.selectedSalleId &&
                       this.selectedEleveId &&
                       this.montantPaye <= this.montantFrais &&
                       this.montantPaye > 0;
            },

            // Méthodes de rafraîchissement
            rafraichirInfosFrais() {
                this.montantFrais = this.fraisSelectionne ? parseFloat(this.fraisSelectionne.montant) : 0;
                // Si le montant payé dépasse le montant du frais, on le réduit
                if (this.montantPaye > this.montantFrais) {
                    this.montantPaye = this.montantFrais;
                    this.syncUSDToFC();
                }
            },

            rafraichirInfosSalle() {
                const salle = this.sallesData.find(s => s.id == this.selectedSalleId);
                this.libelleSalle = salle ? salle.nom : '';
            },

            rafraichirInfosEleve() {
                const eleve = this.elevesDisponibles.find(e => e.id == this.selectedEleveId);
                this.nomEleve = eleve ? eleve.nom_complet : '';
                const salle = this.sallesData.find(s => s.id == this.selectedSalleId);
                this.eleveSalle = salle ? salle.nom : '';
            },

            // Gestionnaires d'événements
            onFraisChange() {
                this.selectedSalleId = '';
                this.selectedEleveId = '';
                this.rafraichirInfosFrais();
                this.rafraichirInfosSalle();
                this.rafraichirInfosEleve();
            },

            onSalleChange() {
                this.selectedEleveId = '';
                this.rafraichirInfosSalle();
                this.rafraichirInfosEleve();
            },

            onEleveChange() {
                this.rafraichirInfosEleve();
            },

            // Synchronisation des montants
            syncUSDToFC() {
                if (this.montantPaye && this.tauxChange) {
                    this.montantPayeFC = Math.round(parseFloat(this.montantPaye) * this.tauxChange);
                } else {
                    this.montantPayeFC = 0;
                }
            },

            syncFCToUSD() {
                if (this.montantPayeFC && this.tauxChange) {
                    let usd = Math.round(parseFloat(this.montantPayeFC) / this.tauxChange);
                    if (usd > this.montantFrais) {
                        usd = this.montantFrais;
                        this.montantPaye = usd;
                        this.syncUSDToFC();
                    } else {
                        this.montantPaye = usd;
                    }
                } else {
                    this.montantPaye = 0;
                }
            },

            payerTout() {
                this.montantPaye = this.montantFrais;
                this.syncUSDToFC();
            }
        }
    }
</script>
@endpush