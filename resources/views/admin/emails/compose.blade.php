@extends('layouts.admin')

@section('page_title', 'Envoyer un email groupé')
@section('page_subtitle', 'Rédigez et envoyez un message à tous les abonnés')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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
@if ($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="paiement-page" x-data="bulkEmailForm()">

    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Email <strong>groupé</strong></h1>
            <p class="form-subtitle">Envoyez un message à tous les abonnés en une seule fois</p>
        </div>
        <a href="{{ route('admin.messages.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour aux messages
        </a>
    </div>

    {{-- Formulaire --}}
    <form action="{{ route('admin.emails.send') }}" method="POST" id="bulkEmailForm">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Panneau principal --}}
            <div class="xl:col-span-2 space-y-6">
                <div class="form-card">
                    <h2 class="card-title"><i class="fa-solid fa-paper-plane"></i> Rédaction du message</h2>

                    <div class="form-row">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text" name="subject" id="subject" value="{{ old('subject') }}"
                                       placeholder=" " required autofocus
                                       class="@error('subject') is-invalid @enderror">
                                <label for="subject" class="float-label">Sujet <span class="text-red-500">*</span></label>
                                <i class="fa-solid fa-heading icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('subject')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-field mt-4">
                        <div class="textarea-wrap">
                            <textarea name="content" id="content" rows="8" placeholder=" " required
                                      class="@error('content') is-invalid @enderror">{{ old('content') }}</textarea>
                            <label for="content" class="float-label">Contenu du message <span class="text-red-500">*</span></label>
                            <i class="fa-regular fa-comment-dots icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('content')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('admin.messages.index') }}" class="btn-cancel">
                            <i class="fa-solid fa-arrow-left"></i> Annuler
                        </a>
                        <button type="submit" class="btn-submit" :disabled="isSending">
                            <span x-show="!isSending"><i class="fa-solid fa-paper-plane"></i> Envoyer à tous</span>
                            <span x-show="isSending"><i class="fa-solid fa-spinner fa-spin"></i> Envoi en cours...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Panneau latéral --}}
            <div class="space-y-6">
                <div class="selected-eleve-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <div class="selected-eleve-avatar">
                        <i class="fa-solid fa-envelope-open-text text-2xl"></i>
                    </div>
                    <div>
                        <p class="selected-eleve-name">Message groupé</p>
                        <p class="selected-eleve-age">Tous les abonnés recevront cet email</p>
                    </div>
                </div>

                <div class="financial-card">
                    <h3 class="financial-title"><i class="fa-solid fa-info-circle"></i> Informations</h3>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li>• Le message sera envoyé à tous les contacts enregistrés.</li>
                        <li>• Assurez-vous que le serveur d'emails est correctement configuré.</li>
                        <li>• Le sujet et le contenu sont obligatoires.</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    /* ==== Styles premium ==== */
    .paiement-page { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .form-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: #667eea; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: color 0.2s; }
    .back-link:hover { color: #4f46e5; }
    .form-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .card-title { font-size: 1.2rem; font-weight: 600; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
    .card-title i { color: #667eea; }
    .form-row { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
    @media (min-width: 768px) { .form-row { grid-template-columns: 1fr 1fr; } }
    .form-field { position: relative; }
    .input-wrap { position: relative; }
    .input-wrap input,
    .textarea-wrap textarea {
        width: 100%;
        padding: 0.8rem 2.5rem 0.8rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none;
    }
    .input-wrap input::placeholder,
    .textarea-wrap textarea::placeholder { color: transparent; }
    .input-wrap input:focus,
    .textarea-wrap textarea:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid,
    .textarea-wrap textarea.is-invalid { border-bottom-color: #ef4444; }
    .input-wrap .float-label,
    .textarea-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.8rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label,
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap i.icon,
    .textarea-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.1rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon,
    .textarea-wrap textarea:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
    .input-wrap .line-focus,
    .textarea-wrap .line-focus {
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
    .input-wrap input:focus ~ .line-focus,
    .textarea-wrap textarea:focus ~ .line-focus { width: 100%; }
    .textarea-wrap { position: relative; }
    .textarea-wrap textarea { min-height: 200px; resize: vertical; padding-right: 2.5rem; }
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .textarea-wrap i.icon { top: 0.8rem; transform: none; }
    .textarea-wrap textarea:focus ~ i.icon { color: #667eea; transform: scale(1.1); }
    .textarea-wrap .line-focus { bottom: 0; }
    .textarea-wrap textarea:focus ~ .line-focus { width: 100%; }
    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }
    .btn-submit, .btn-cancel {
        padding: 0.8rem 1.75rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none;
        border: none;
        text-decoration: none;
    }
    .btn-submit { background: #1e293b; color: white; }
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-cancel { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }
    .selected-eleve-card {
        border-radius: 20px;
        padding: 1.5rem;
        color: white;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 20px 40px rgba(102,126,234,0.3);
    }
    .selected-eleve-avatar {
        width: 48px;
        height: 48px;
        background: rgba(255,255,255,0.2);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .selected-eleve-name { font-weight: 700; font-size: 1.1rem; }
    .selected-eleve-age { color: #e0e7ff; font-size: 0.9rem; }
    .financial-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
    }
    .financial-title { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; }
    @media (max-width: 768px) {
        .paiement-page { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .header-container { flex-direction: column; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.75rem; }
        .form-title { font-size: 1.3rem; }
        .form-card { padding: 1rem; }
        .form-row { grid-template-columns: 1fr; }
        .btn-submit, .btn-cancel { width: 100%; }
    }
</style>

<script>
    function bulkEmailForm() {
        return {
            isSending: false,
            init() {
                const form = document.getElementById('bulkEmailForm');
                if (!form) return;
                form.addEventListener('submit', () => {
                    this.isSending = true;
                });
            }
        }
    }
</script>
@endsection