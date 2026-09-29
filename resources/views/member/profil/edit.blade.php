@extends('layouts.admin')

@section('page_title', 'Mon profil')
@section('page_subtitle', 'Modifier vos informations personnelles')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .profil-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    .profil-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        border: 1px solid #f1f5f9;
    }

    .profil-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .profil-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
    }
    .profil-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .profil-title h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .profil-title p {
        color: #94a3b8;
        margin: 0.2rem 0 0;
        font-size: 0.95rem;
    }

    .form-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .form-field {
        position: relative;
    }

    .field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .input-wrap {
        position: relative;
    }
    .input-wrap input, .input-wrap select, .input-wrap textarea {
        width: 100%;
        padding: 0.75rem 2.8rem 0.75rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
    }
    .input-wrap input:focus, .input-wrap select:focus, .input-wrap textarea:focus {
        border-bottom-color: #4f46e5;
    }
    .input-wrap input.is-invalid, .input-wrap select.is-invalid, .input-wrap textarea.is-invalid {
        border-bottom-color: #ef4444;
    }
    .input-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.75rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label,
    .input-wrap select:focus ~ .float-label,
    .input-wrap select:not(:placeholder-shown) ~ .float-label,
    .input-wrap textarea:focus ~ .float-label,
    .input-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #4f46e5;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.2rem;
        pointer-events: none;
        transition: all 0.3s;
    }
    .input-wrap input:focus ~ i.icon,
    .input-wrap select:focus ~ i.icon,
    .input-wrap textarea:focus ~ i.icon {
        color: #4f46e5;
    }
    .input-wrap .line-focus {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #4f46e5, #7c3aed);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .input-wrap input:focus ~ .line-focus,
    .input-wrap select:focus ~ .line-focus,
    .input-wrap textarea:focus ~ .line-focus {
        width: 100%;
    }

    .select-wrap {
        position: relative;
    }
    .select-wrap select {
        width: 100%;
        padding: 0.75rem 2.8rem 0.75rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
        -webkit-appearance: none;
        appearance: none;
        cursor: pointer;
    }
    .select-wrap select:focus { border-bottom-color: #4f46e5; }
    .select-wrap .select-icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.2rem;
        pointer-events: none;
        transition: color 0.3s;
    }
    .select-wrap select:focus ~ .select-icon { color: #4f46e5; }
    .select-wrap .line-focus {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #4f46e5, #7c3aed);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .select-wrap select:focus ~ .line-focus { width: 100%; }

    .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.3rem;
    }

    .file-upload-wrap {
        position: relative;
        margin-top: 0.5rem;
    }
    .file-input {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .file-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.75rem 1.25rem;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        color: #475569;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    .file-input:hover + .file-label {
        border-color: #4f46e5;
        color: #4f46e5;
        background: #f1f5f9;
    }

    .btn-submit {
        padding: 0.8rem 2.5rem;
        background: #4f46e5;
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .btn-submit:hover {
        background: #3730a3;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
    }
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
    }
    .btn-cancel:hover {
        border-color: #4f46e5;
        color: #4f46e5;
        background: #f8fafc;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
    }

    @media (max-width: 768px) {
        .profil-card { padding: 1.5rem; }
        .form-row { grid-template-columns: 1fr; }
        .profil-header { flex-direction: column; text-align: center; }
        .form-actions { flex-direction: column; }
        .btn-submit, .btn-cancel { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="profil-container">
    <div class="profil-card">
        <div class="profil-header">
            <div class="profil-avatar">
                @if($user->photo_url)
                    <img src="{{ $user->photo_url }}" alt="{{ $user->name }}">
                @else
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                @endif
            </div>
            <div class="profil-title">
                <h1>Mon profil</h1>
                <p>Modifiez vos informations personnelles</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
                <i class="bi bi-check-circle-fill mt-0.5"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                <i class="bi bi-exclamation-circle-fill mt-0.5"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('member.profil.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-grid">
                {{-- Nom complet --}}
                <div class="form-row">
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" placeholder=" " required
                                   class="@error('name') is-invalid @enderror">
                            <label for="name" class="float-label">Nom complet <span class="text-red-500">*</span></label>
                            <i class="fas fa-user icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    {{-- Sexe --}}
                    <div class="form-field">
                        <label for="sexe" class="field-label">Sexe</label>
                        <div class="select-wrap">
                            <select name="sexe" id="sexe" class="@error('sexe') is-invalid @enderror">
                                <option value="">--</option>
                                <option value="M" {{ old('sexe', $user->sexe) == 'M' ? 'selected' : '' }}>Masculin</option>
                                <option value="F" {{ old('sexe', $user->sexe) == 'F' ? 'selected' : '' }}>Féminin</option>
                            </select>
                            <i class="fas fa-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('sexe')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Téléphone et date de naissance --}}
                <div class="form-row">
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="telephone" id="telephone" value="{{ old('telephone', $user->telephone) }}" placeholder=" "
                                   class="@error('telephone') is-invalid @enderror">
                            <label for="telephone" class="float-label">Téléphone</label>
                            <i class="fas fa-phone icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('telephone')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="date" name="date_naissance" id="date_naissance" value="{{ old('date_naissance', $user->date_naissance?->format('Y-m-d')) }}" placeholder=" "
                                   class="@error('date_naissance') is-invalid @enderror">
                            <label for="date_naissance" class="float-label">Date de naissance</label>
                            <i class="fas fa-calendar icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('date_naissance')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Adresse --}}
                <div class="form-row">
                    <div class="form-field">
                        <div class="input-wrap">
                            <textarea name="adresse" id="adresse" rows="2" placeholder=" "
                                      class="@error('adresse') is-invalid @enderror">{{ old('adresse', $user->adresse) }}</textarea>
                            <label for="adresse" class="float-label">Adresse</label>
                            <i class="fas fa-home icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('adresse')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Photo --}}
                <div class="form-row">
                    <div class="form-field">
                        <label for="photo" class="field-label">Photo</label>
                        <div class="file-upload-wrap">
                            <input type="file" name="photo" id="photo" accept="image/*" class="file-input">
                            <label for="photo" class="file-label">
                                <i class="fas fa-cloud-upload-alt"></i> Choisir une image
                            </label>
                        </div>
                        @error('photo')<p class="error-text">{{ $message }}</p>@enderror
                        @if($user->photo_url)
                            <div class="mt-2">
                                <img src="{{ $user->photo_url }}" alt="Photo actuelle" class="h-16 w-16 rounded-full object-cover border">
                                <span class="text-sm text-gray-500 ml-2">Photo actuelle</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Boutons d'action --}}
            <div class="form-actions">
                <a href="{{ route('member.profil') }}" class="btn-cancel">Annuler</a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection