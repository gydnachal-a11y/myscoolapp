@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto" x-data="salaireForm()" x-init="init()">
    <div class="header-container">
        <div>
            <h1 class="form-title">Fixation du salaire – <strong>{{ $user->name }}</strong></h1>
            <p class="form-subtitle">Configurez le salaire manuel ou automatique</p>
        </div>
        <a href="{{ route('admin.salaires.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Retour aux salaires
        </a>
    </div>

    {{-- Alerte si pas d'heures --}}
    @if($user->type_salaire === 'automatique' && $heuresHebdo == 0)
        <div class="alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Ce personnel n'a actuellement aucune heure de cours assignée. Le salaire automatique sera de 0 $.</span>
        </div>
    @endif

    @if(!$tauxHoraire)
        <div class="alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Aucun taux horaire actif configuré. Le salaire automatique ne peut pas être calculé.</span>
        </div>
    @endif

    {{-- Informations sur les cours --}}
    <div class="form-card mb-6">
        <h3 class="section-title"><i class="bi bi-book"></i> Cours dispensés</h3>

        @if($assignations->isNotEmpty())
            @foreach($assignations->groupBy('salle.nom') as $salleNom => $assigns)
                @php
                    $totalHeuresSalle = 0;
                    foreach ($assigns as $assign) {
                        if ($assign->creneauHoraire) {
                            $duree = $assign->creneauHoraire->heure_debut->diffInMinutes($assign->creneauHoraire->heure_fin) / 60;
                            $totalHeuresSalle += $duree * ($assign->nombre_seances ?? 0);
                        }
                    }
                @endphp
                <div class="course-row">
                    <div>
                        <span class="course-salle">{{ $salleNom }}</span>
                        <span class="course-cours"> – {{ $assigns->first()->cour->nom ?? 'Cours' }}</span>
                    </div>
                    <span class="course-heures">{{ $totalHeuresSalle }} h/sem</span>
                </div>
            @endforeach

            <div class="summary-box">
                <p>
                    <strong>{{ $user->name }}</strong> est titulaire de cours dans
                    <strong>{{ $assignations->groupBy('salle.nom')->count() }}</strong> salle(s)
                    pour un total hebdomadaire de <strong>{{ $heuresHebdo }} heures</strong>.
                </p>
                <p>
                    Salaire de base calculé : <strong>{{ number_format($salaireAutoBaseUsd, 0) }} $</strong>
                    (≈ {{ number_format($salaireAutoBaseFc, 0) }} FC)
                    @if($tauxHoraire)
                        <span class="taux-horaire">Taux horaire : {{ $tauxHoraire }} $/h</span>
                    @endif
                </p>
            </div>
        @else
            <p class="empty-text">Aucun cours assigné pour ce personnel.</p>
        @endif
    </div>

    {{-- Formulaire de salaire --}}
    <form action="{{ route('admin.salaires.update', $user) }}" method="POST" class="form-container" novalidate>
        @csrf
        @method('PUT')

        <div class="form-grid">
            {{-- Type de salaire --}}
            <div class="form-field">
                <label class="field-label">Type de salaire</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="type_salaire" value="manuel" x-model="typeSalaire" @change="onTypeChange()" class="form-radio">
                        <span>Manuel (arbitraire)</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="type_salaire" value="automatique" x-model="typeSalaire" @change="onTypeChange()" class="form-radio">
                        <span>Automatique (selon heures)</span>
                    </label>
                </div>
            </div>

            {{-- Mode manuel --}}
            <template x-if="typeSalaire === 'manuel'">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="number" step="1" name="salaire_mensuel_usd" x-model="salaireManuelUsd" @input="syncManuelToFC()"
                                   placeholder=" " required class="@error('salaire_mensuel_usd') is-invalid @enderror">
                            <label class="float-label">Salaire mensuel (USD) <span style="color:#ef4444;">*</span></label>
                            <i class="bi bi-currency-dollar icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('salaire_mensuel_usd')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="number" step="1" x-model="salaireManuelFc" @input="syncManuelToUSD()"
                                   placeholder=" " class="bg-gray-50">
                            <label class="float-label">Salaire mensuel (FC)</label>
                            <i class="bi bi-currency-exchange icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        <small class="text-muted">Taux : <span x-text="tauxChange.toFixed(2)"></span> FC/USD</small>
                        <input type="hidden" name="salaire_mensuel_fc" :value="salaireManuelFc">
                    </div>
                </div>
            </template>

            {{-- Mode automatique --}}
            <template x-if="typeSalaire === 'automatique'">
                <div class="form-field">
                    <div class="auto-salaire-box">
                        <div class="auto-row">
                            <span>Total heures par semaine :</span>
                            <strong>{{ $heuresHebdo }} h</strong>
                        </div>
                        <div class="auto-row">
                            <span>Taux horaire :</span>
                            <strong>{{ $tauxHoraire ?? 'Non défini' }} $/h</strong>
                        </div>
                        <div class="auto-row">
                            <span>Salaire de base calculé :</span>
                            <strong>{{ number_format($salaireAutoBaseUsd, 0) }} $</strong>
                            <span class="text-muted">≈ {{ number_format($salaireAutoBaseFc, 0) }} FC</span>
                        </div>

                        <hr class="divider">

                        {{-- Salaire ajusté (USD + FC synchronisés) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="input-wrap">
                                    <input type="number" step="1" name="salaire_ajuste_usd" x-model="salaireAjusteUsd" @input="syncAjusteToFC()"
                                           placeholder=" " class="@error('salaire_ajuste_usd') is-invalid @enderror">
                                    <label class="float-label">Salaire ajusté (USD)</label>
                                    <i class="bi bi-sliders icon"></i>
                                    <div class="line-focus"></div>
                                </div>
                                @error('salaire_ajuste_usd')<p class="error-text">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <div class="input-wrap">
                                    <input type="number" step="1" x-model="salaireAjusteFc" @input="syncAjusteToUSD()"
                                           placeholder=" " class="bg-gray-50">
                                    <label class="float-label">Salaire ajusté (FC)</label>
                                    <i class="bi bi-currency-exchange icon"></i>
                                    <div class="line-focus"></div>
                                </div>
                                <small class="text-muted">Taux : <span x-text="tauxChange.toFixed(2)"></span> FC/USD</small>
                                <input type="hidden" name="salaire_ajuste_fc" :value="salaireAjusteFc">
                            </div>
                        </div>

                        <p class="hint-text mt-2">Laissez vide pour conserver le salaire de base calculé.</p>

                        <input type="hidden" name="salaire_auto_base_usd" value="{{ $salaireAutoBaseUsd }}">
                    </div>
                </div>
            </template>
        </div>

        {{-- Affichage du taux de change historisé (supprimé car non fourni) --}}
        {{-- @if($historiqueTaux) ... @endif --}}

        {{-- Boutons --}}
        <div class="button-group">
            <a href="{{ route('admin.salaires.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-submit">Enregistrer le salaire <i class="bi bi-arrow-right"></i></button>
        </div>
    </form>
</div>

<style>
    /* ==== Styles premium ==== */
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.1s ease forwards;
        opacity: 0;
    }
    .form-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #667eea;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    .back-link:hover { color: #4f46e5; }

    .alert-warning {
        background: #fffbeb;
        border-left: 4px solid #f59e0b;
        color: #92400e;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
        animation: cardIn 0.5s ease forwards;
    }
    .alert-warning i { font-size: 1.2rem; }

    .form-card {
        background: white;
        padding: 1.5rem 2rem;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }
    .form-container {
        background: white;
        padding: 2.5rem;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }
    @keyframes cardIn { from { opacity: 0; transform: translateY(25px); } to { opacity: 1; transform: translateY(0); } }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: #667eea; }

    .course-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .course-salle { font-weight: 600; color: #1e293b; }
    .course-cours { color: #64748b; font-size: 0.9rem; }
    .course-heures { font-weight: 600; color: #667eea; }

    .summary-box {
        margin-top: 1.5rem;
        background: #f5f7ff;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        color: #1e293b;
        font-size: 0.9rem;
        line-height: 1.6;
    }
    .summary-box strong { font-weight: 600; }
    .taux-horaire {
        display: inline-block;
        margin-left: 0.5rem;
        font-weight: 400;
        color: #94a3b8;
        font-size: 0.8rem;
    }

    .empty-text { color: #94a3b8; }

    .form-grid { display: flex; flex-direction: column; gap: 1.5rem; }

    .form-field { position: relative; animation: fieldSlide 0.5s ease forwards; opacity: 0; }
    .form-field:nth-of-type(1) { animation-delay: 0.35s; }
    .form-field:nth-of-type(2) { animation-delay: 0.45s; }
    .form-field:nth-of-type(3) { animation-delay: 0.55s; }
    @keyframes fieldSlide { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

    .field-label { display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; }

    .radio-group {
        display: flex;
        gap: 1.5rem;
    }
    .radio-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: #475569;
        cursor: pointer;
    }
    .form-radio {
        outline: none !important;
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border: 1.5px solid #cbd5e1;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .form-radio:checked {
        border-color: #667eea;
        border-width: 5px;
    }
    .form-radio:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 8px;
        height: 8px;
        background: #667eea;
        border-radius: 50%;
        transform: translate(-50%, -50%);
    }

    .input-wrap { position: relative; }
    .input-wrap input {
        width: 100%;
        padding: 0.8rem 2.5rem 0.8rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid { border-bottom-color: #ef4444; }
    .input-wrap input:disabled { background: #f8fafc; cursor: not-allowed; }
    .input-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.8rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }
    .input-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.1rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
    .input-wrap input.is-invalid ~ i.icon { color: #ef4444; }
    .input-wrap .line-focus {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .input-wrap input:focus ~ .line-focus { width: 100%; }

    .auto-salaire-box {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.25rem;
        border: 1px solid #f1f5f9;
    }
    .auto-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        font-size: 0.9rem;
        color: #475569;
    }
    .auto-row strong { color: #1e293b; }
    .divider { border-color: #e2e8f0; margin: 1rem 0; }

    .hint-text { color: #94a3b8; font-size: 0.8rem; margin-top: 0.3rem; }
    .text-muted { color: #94a3b8; font-size: 0.8rem; }
    .bg-gray-50 { background-color: #f8fafc; }

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }

    .button-group {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
        margin-top: 1.5rem;
    }
    .btn-submit {
        padding: 0.8rem 2rem;
        background: #1e293b;
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none !important;
        animation: fadeUp 0.5s 0.9s ease forwards;
        opacity: 0;
    }
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit:active { transform: translateY(0) scale(0.98); box-shadow: none; }
    .btn-submit i { transition: transform 0.3s; }
    .btn-submit:hover i { transform: translateX(4px); }
    .btn-cancel {
        padding: 0.8rem 1.5rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        background: white;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        animation: fadeUp 0.5s 0.85s ease forwards;
        opacity: 0;
    }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    @media (max-width: 768px) {
        .header-container { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .form-container { padding: 1.5rem; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
        .radio-group { flex-direction: column; gap: 0.75rem; }
    }
</style>

<script>
    function salaireForm() {
        return {
            typeSalaire: @json(old('type_salaire', $user->type_salaire)),
            tauxChange: {{ $tauxChange }},

            // Manuel
            salaireManuelUsd: {{ old('salaire_mensuel_usd', $user->salaire_mensuel_usd ?? 0) }},
            salaireManuelFc: 0,

            // Automatique
            salaireAjusteUsd: {{ old('salaire_ajuste_usd', $user->salaire_ajuste_usd ?? $salaireAutoBaseUsd) }},
            salaireAjusteFc: 0,

            init() {
                this.syncManuelToFC();
                this.syncAjusteToFC();
            },

            onTypeChange() {
                // Rien de spécial, les champs se masquent/affichent via x-if
            },

            // Synchronisation manuel USD -> FC
            syncManuelToFC() {
                if (this.salaireManuelUsd && this.tauxChange) {
                    this.salaireManuelFc = Math.round(parseFloat(this.salaireManuelUsd) * this.tauxChange);
                } else {
                    this.salaireManuelFc = 0;
                }
            },

            // Synchronisation manuel FC -> USD
            syncManuelToUSD() {
                if (this.salaireManuelFc && this.tauxChange) {
                    this.salaireManuelUsd = Math.round(parseFloat(this.salaireManuelFc) / this.tauxChange);
                } else {
                    this.salaireManuelUsd = 0;
                }
            },

            // Synchronisation ajusté USD -> FC
            syncAjusteToFC() {
                if (this.salaireAjusteUsd && this.tauxChange) {
                    this.salaireAjusteFc = Math.round(parseFloat(this.salaireAjusteUsd) * this.tauxChange);
                } else {
                    this.salaireAjusteFc = 0;
                }
            },

            // Synchronisation ajusté FC -> USD
            syncAjusteToUSD() {
                if (this.salaireAjusteFc && this.tauxChange) {
                    this.salaireAjusteUsd = Math.round(parseFloat(this.salaireAjusteFc) / this.tauxChange);
                } else {
                    this.salaireAjusteUsd = 0;
                }
            }
        }
    }
</script>
@endsection