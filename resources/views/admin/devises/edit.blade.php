@extends('layouts.admin')

@section('page_title', 'Modifier devise')
@section('page_subtitle', 'Modifier la devise ' . $devise->code)

@section('content')
<div class="page">

    {{-- HEADER --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-pen-to-square title-icon" aria-hidden="true"></i>
                <span>Modifier la devise</span>
                <span class="devise-code-badge">{{ $devise->code }}</span>
            </h1>
            <p class="page-subtitle">Modifiez les informations de la devise</p>
        </div>
        <a href="{{ route('admin.devises.index') }}" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Retour</span>
        </a>
    </header>

    {{-- FLASH --}}
    @if($errors->any())
        <div class="flash flash-error">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <div>
                <strong>Corrigez les erreurs suivantes :</strong>
                <ul>
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORM --}}
    <section class="content-card">
        <form action="{{ route('admin.devises.update', $devise) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.devises._form', ['devise' => $devise])

            <div class="form-actions">
                <a href="{{ route('admin.devises.index') }}" class="btn btn-ghost">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </section>
</div>

<style>
    .page {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 1rem;
        padding-left: max(1rem, env(safe-area-inset-left));
        padding-right: max(1rem, env(safe-area-inset-right));
    }

    /* HEADER */
    .page-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }
    .page-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem 0.6rem;
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.25rem;
        letter-spacing: -0.4px;
    }
    @media (min-width: 640px) { .page-title { font-size: 1.75rem; } }
    .title-icon { color: #6366f1; }

    .devise-code-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.7rem;
        background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
        color: #4338ca;
        font-weight: 800;
        border-radius: 8px;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border: 1px solid #c7d2fe;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.85rem;
        margin: 0;
    }
    @media (min-width: 640px) { .page-subtitle { font-size: 0.9rem; } }

    /* BOUTONS */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.7rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .page-header > .btn { width: 100%; justify-content: center; }
    @media (min-width: 768px) {
        .page-header > .btn { width: auto; }
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35); }
    .btn-primary:active { transform: translateY(0) scale(0.98); }
    .btn-ghost {
        background: #fff;
        color: #64748b;
        border: 1.5px solid #e2e8f0;
    }
    .btn-ghost:hover { border-color: #6366f1; color: #4f46e5; background: #f8fafc; }

    /* FLASH */
    .flash {
        display: flex;
        gap: 0.65rem;
        align-items: flex-start;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .flash i { flex-shrink: 0; margin-top: 2px; }
    .flash-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .flash strong { display: block; margin-bottom: 0.25rem; }
    .flash ul { margin: 0; padding-left: 1.25rem; }

    /* CONTENT CARD */
    .content-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        padding: 1.5rem;
    }
    @media (min-width: 640px) { .content-card { padding: 2rem; } }

    /* FORM ACTIONS */
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        padding-top: 1.5rem;
        margin-top: 1.75rem;
        border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .form-actions { flex-direction: column-reverse; }
        .form-actions .btn { width: 100%; }
    }

    /* RESPONSIVE */
    @media (max-width: 400px) {
        .page { padding: 1.25rem 0.75rem; }
        .page-title { font-size: 1.35rem; }
        .content-card { padding: 1.25rem; border-radius: 14px; }
        .devise-code-badge { font-size: 0.75rem; padding: 0.25rem 0.55rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
@endsection