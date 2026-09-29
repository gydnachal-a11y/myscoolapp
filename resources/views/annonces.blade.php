@extends('layouts.contact')

@section('title', 'Annonces')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Annonces</h1>
        <p class="lead text-muted">
            {{ auth('contact')->check() ? 'Toutes les annonces qui vous concernent' : 'Découvrez les dernières nouvelles de l\'établissement' }}
        </p>
    </div>

    @if($annonces->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="fa-regular fa-face-smile fa-4x mb-3 opacity-50"></i>
            <p>Aucune annonce pour le moment.</p>
        </div>
    @else
        <div class="row g-4">
            @foreach($annonces as $annonce)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        @if($annonce->image_url)
                            <img src="{{ $annonce->image_url }}" alt="{{ $annonce->titre }}"
                                 class="card-img-top object-fit-cover" style="height: 200px;" loading="lazy" decoding="async">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fa-solid fa-bullhorn fa-3x text-muted opacity-50"></i>
                            </div>
                        @endif
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="fw-bold mb-0">{{ $annonce->titre }}</h5>
                                @if($annonce->type == 'prive')
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Privé</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill">Public</span>
                                @endif
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="fa-regular fa-clock me-1"></i>
                                {{ $annonce->date_debut?->format('d/m/Y') ?? 'Date indéfinie' }}
                                @if($annonce->date_fin)
                                    - {{ $annonce->date_fin->format('d/m/Y') }}
                                @endif
                            </p>
                            <p class="flex-grow-1">{{ Str::limit($annonce->contenu, 120) }}</p>
                            @if(strlen($annonce->contenu) > 120)
                                <button class="btn btn-link p-0 text-decoration-none" data-bs-toggle="collapse"
                                        data-bs-target="#annonce{{ $annonce->id }}">
                                    Lire la suite
                                </button>
                                <div class="collapse mt-2" id="annonce{{ $annonce->id }}">
                                    <p>{{ $annonce->contenu }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-5">
            {{ $annonces->links() }}
        </div>
    @endif
</div>
@endsection