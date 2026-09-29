@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="max-w-5xl mx-auto" x-data="avanceForm()" x-init="init()">
    <div class="header-container">
        <div>
            <h1 class="form-title">Nouvelle <strong>avance sur salaire</strong></h1>
            <p class="form-subtitle">Créez un bon de paiement anticipé pour un personnel</p>
        </div>
        <a href="{{ route('admin.avances.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour aux avances
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

    {{-- Sélection de l'employé + carte info --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="form-card">
            <label for="user_select" class="field-label">Employé <span style="color:#ef4444;">*</span></label>
            <div class="select-wrap">
                <select id="user_select" required class="@error('user_id') is-invalid @enderror"
                        x-model="selectedUserId" @change="updateEmploye">
                    <option value="">Choisir un employé</option>
                    @foreach($usersData as $userData)
                        <option value="{{ $userData['id'] }}"
                                data-salaire="{{ $userData['salaire'] }}"
                                data-dette="{{ $userData['dette'] }}"
                                data-limite="{{ $userData['limite'] }}"
                                data-bloque="{{ $userData['bloque'] ? '1' : '0' }}"
                                {{ $userData['bloque'] ? 'disabled' : '' }}
                                class="{{ $userData['bloque'] ? 'text-gray-400' : '' }}">
                            {{ $userData['name'] }} ({{ number_format($userData['salaire'], 0, ',', ' ') }} $)
                            @if($userData['bloque'])
                                — Bloqué (dette {{ number_format($userData['dette'], 0, ',', ' ') }} $)
                            @endif
                        </option>
                    @endforeach
                </select>
                <i class="fa-solid fa-chevron-down select-icon"></i>
                <div class="line-focus"></div>
            </div>
            @error('user_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <div class="lg:col-span-2">
            <div id="employe_info" class="form-card h-full">
                <p class="empty-text">Sélectionnez un employé pour afficher ses informations.</p>
            </div>
        </div>
    </div>

    {{-- Formulaire d'avance --}}
    <form action="{{ route('admin.avances.store') }}" method="POST" class="form-container" novalidate>
        @csrf

        <input type="hidden" name="user_id" x-model="selectedUserId">

        <div class="form-grid">
            <div class="form-row three-col">
                {{-- Salaire mensuel --}}
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="text" id="salaire_mensuel" x-model="salaireMensuel" placeholder=" " readonly>
                        <label for="salaire_mensuel" class="float-label">Salaire mensuel (USD)</label>
                        <i class="fa-solid fa-cash-register icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    <small class="text-muted" x-text="salaireMensuelFC"></small>
                </div>

                {{-- Mois scolaire (obligatoire) --}}
                <div class="form-field">
                    <label for="mois_scolaire_id" class="field-label">Mois scolaire <span style="color:#ef4444;">*</span></label>
                    <div class="select-wrap">
                        <select id="mois_scolaire_id" name="mois_scolaire_id" required
                                class="@error('mois_scolaire_id') is-invalid @enderror">
                            <option value="">-- Sélectionner un mois --</option>
                            @foreach($mois as $m)
                                <option value="{{ $m->id }}" {{ old('mois_scolaire_id') == $m->id ? 'selected' : '' }}>
                                    {{ $m->nom_mois ?? $m->mois }}
                                </option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down select-icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('mois_scolaire_id')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                {{-- Date de l'avance --}}
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="date" id="date_avance" name="date_avance" value="{{ old('date_avance', now()->toDateString()) }}" placeholder=" " required
                               class="@error('date_avance') is-invalid @enderror">
                        <label for="date_avance" class="float-label">Date de l'avance <span style="color:#ef4444;">*</span></label>
                        <i class="fa-solid fa-calendar icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('date_avance')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-row">
                {{-- Montant de l'avance --}}
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="number" step="1" min="0" id="montant_avance_usd" name="montant_avance_usd"
                               x-model="montant" @input="verifierDepassement" placeholder=" " required
                               class="@error('montant_avance_usd') is-invalid @enderror">
                        <label for="montant_avance_usd" class="float-label">Montant de l'avance (USD) <span style="color:#ef4444;">*</span></label>
                        <i class="fa-solid fa-dollar-sign icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    <small class="text-muted" x-text="montantFC"></small>
                    <p class="text-warning" x-show="avertissement" x-text="avertissement" style="margin-top:0.3rem;"></p>
                    @error('montant_avance_usd')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                {{-- Solde restant --}}
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="text" id="solde_restant" :value="soldeRestant" placeholder=" " readonly>
                        <label for="solde_restant" class="float-label">Solde après avance (USD)</label>
                        <i class="fa-solid fa-arrow-left-right icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    <small class="text-muted" x-text="soldeRestantFC"></small>
                </div>
            </div>

            <div class="form-row">
                {{-- Motif --}}
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="text" id="motif" name="motif" value="{{ old('motif') }}" placeholder=" "
                               class="@error('motif') is-invalid @enderror">
                        <label for="motif" class="float-label">Motif</label>
                        <i class="fa-solid fa-comment-dots icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('motif')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                {{-- Commentaire --}}
                <div class="form-field">
                    <div class="textarea-wrap">
                        <textarea name="commentaire" id="commentaire" rows="1" placeholder=" "
                                  class="@error('commentaire') is-invalid @enderror">{{ old('commentaire') }}</textarea>
                        <label for="commentaire" class="float-label">Commentaire</label>
                        <i class="fa-regular fa-comment-dots icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('commentaire')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="button-group">
            <a href="{{ route('admin.avances.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-submit"
                    :disabled="selectedUserId === '' || (detteActuelle + montant > limite)">
                Enregistrer l'avance <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<style>
    /* Styles identiques à ceux déjà présents dans votre vue, je les garde */
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
    .form-container { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); animation: cardIn 0.5s 0.2s ease forwards; opacity: 0; }
    .form-grid { display: flex; flex-direction: column; gap: 1.5rem; }
    .form-row { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
    @media (min-width: 768px) { .form-row { grid-template-columns: 1fr 1fr; } .form-row.three-col { grid-template-columns: repeat(3, 1fr); } }
    .form-field { position: relative; animation: fieldSlide 0.4s ease forwards; opacity: 0; }
    .form-field:nth-of-type(1) { animation-delay: 0.3s; }
    .form-field:nth-of-type(2) { animation-delay: 0.4s; }
    .form-field:nth-of-type(3) { animation-delay: 0.5s; }
    .form-field:nth-of-type(4) { animation-delay: 0.6s; }
    .form-field:nth-of-type(5) { animation-delay: 0.7s; }
    .form-field:nth-of-type(6) { animation-delay: 0.8s; }
    .form-field:nth-of-type(7) { animation-delay: 0.9s; }
    .form-field:nth-of-type(8) { animation-delay: 1.0s; }
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
    .empty-text { color: #94a3b8; }
    .text-muted { font-size: 0.8rem; color: #64748b; }
    .text-warning { color: #b45309; }

    .employe-info-card { display: flex; flex-direction: column; gap: 0.5rem; }
    .employe-info-card .employe-nom { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
    .employe-info-card .info-row { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #475569; }
    .employe-info-card .info-row i { color: #94a3b8; width: 20px; text-align: center; }

    .button-group { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; margin-top: 1.5rem; }
    .btn-submit { padding: 0.8rem 2rem; background: #1e293b; color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); outline: none !important; }
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-cancel { padding: 0.8rem 1.5rem; border: 1.5px solid #e2e8f0; border-radius: 12px; color: #64748b; background: white; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }
</style>

<script>
    function avanceForm() {
        return {
            usersData: @json($usersData),
            selectedUserId: '',
            salaireMensuel: 0,
            detteActuelle: 0,
            limite: 0,
            montant: 0,
            avertissement: '',
            tauxChange: {{ $tauxChange ?? 2800 }},
            init() {
                this.$watch('selectedUserId', (id) => this.updateEmploye(id));
                this.$watch('montant', () => this.verifierDepassement());
            },
            updateEmploye(userId) {
                const user = this.usersData.find(u => u.id == userId);
                const infoDiv = document.getElementById('employe_info');
                if (user) {
                    this.salaireMensuel = user.salaire;
                    this.detteActuelle = user.dette;
                    this.limite = user.limite;
                    infoDiv.innerHTML = `
                        <div class="employe-info-card">
                            <h3 class="employe-nom">${user.name}</h3>
                            <div class="info-row"><i class="fa-solid fa-user"></i><span>Employé</span></div>
                            <div class="info-row"><i class="fa-solid fa-dollar-sign"></i><span>Salaire mensuel : ${user.salaire} $</span></div>
                            <div class="info-row"><i class="fa-solid fa-hand-holding-dollar"></i><span>Dette actuelle : ${user.dette} $</span></div>
                            <div class="info-row"><i class="fa-solid fa-scale-balanced"></i><span>Limite d'emprunt (2 mois) : ${user.limite} $</span></div>
                            ${user.bloque ? '<div class="alert-warning"><i class="fa-solid fa-lock"></i> Employé bloqué : dette >= limite</div>' : ''}
                        </div>
                    `;
                    this.verifierDepassement();
                } else {
                    this.salaireMensuel = 0;
                    this.detteActuelle = 0;
                    this.limite = 0;
                    infoDiv.innerHTML = '<p class="empty-text">Sélectionnez un employé pour afficher ses informations.</p>';
                    this.avertissement = '';
                }
            },
            get salaireMensuelFC() {
                return this.salaireMensuel ? Math.round(this.salaireMensuel * this.tauxChange).toLocaleString('fr-FR') + ' FC' : '';
            },
            get montantFC() {
                return this.montant ? Math.round(this.montant * this.tauxChange).toLocaleString('fr-FR') + ' FC' : '';
            },
            get soldeRestant() {
                return Math.round(this.salaireMensuel - this.montant);
            },
            get soldeRestantFC() {
                const solde = this.soldeRestant;
                return solde ? Math.round(solde * this.tauxChange).toLocaleString('fr-FR') + ' FC' : '';
            },
            verifierDepassement() {
                if (!this.selectedUserId) return;
                const total = this.detteActuelle + this.montant;
                if (total > this.limite) {
                    this.avertissement = `Attention : dette totale (${total} $) dépassera la limite de ${this.limite} $. Réduisez le montant.`;
                } else if (this.montant > this.salaireMensuel) {
                    this.avertissement = `Attention : l'avance dépasse le salaire mensuel. La dette de ${this.montant - this.salaireMensuel} $ sera reportée.`;
                } else {
                    this.avertissement = '';
                }
            }
        }
    }
</script>
@endsection