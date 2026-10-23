@extends('layouts.front.front')

@section('title', 'Prévention liée à mes équipements')

@section('content')
<section class="container py-5" style="padding-top: 170px !important;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-shield-check text-danger me-2"></i>Prévention personnelle</h1>
            <p class="text-muted mb-0">Conseils adaptés à vos équipements sensibles. Ils sont aussi envoyés à l’adresse e-mail de votre compte.</p>
        </div>
        <form method="POST" action="{{ route('front.module4_notifications.generate') }}">
            @csrf
            <button class="btn btn-danger rounded-pill"><i class="bi bi-arrow-repeat me-1"></i>Actualiser mes conseils</button>
        </form>
    </div>
    @if(session('success'))<div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>@endif
    <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body row g-3 align-items-end">
        <div class="col-md-5"><label class="form-label">Canal</label><select name="canal" class="form-select"><option value="">Tous les canaux</option>@foreach($canaux as $canal)<option value="{{ $canal }}" @selected(($filters['canal'] ?? '') === $canal)>{{ strtoupper($canal) }}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-label">État</label><select name="lue" class="form-select"><option value="">Toutes</option><option value="0" @selected(($filters['lue'] ?? '') === '0')>Non lues</option><option value="1" @selected(($filters['lue'] ?? '') === '1')>Lues</option></select></div>
        <div class="col-md-2"><button class="btn btn-outline-danger w-100">Filtrer</button></div>
    </div></form>
    @forelse($notifications as $notification)
        <div class="card border-0 shadow-sm rounded-4 mb-3 {{ $notification->lue ? '' : 'border-start border-danger border-4' }}">
            <div class="card-body d-flex justify-content-between gap-3">
                <div><h2 class="h6 mb-2">{{ $notification->equipement?->nom ?? 'Préparation personnelle' }}</h2><p class="mb-2">{{ $notification->message }}</p><small class="text-muted">{{ $notification->date_envoi->diffForHumans() }} · {{ strtoupper($notification->canal) }}</small></div>
                @if(!$notification->lue)<form method="POST" action="{{ route('front.module4_notifications.read', $notification) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger rounded-pill">Marquer comme lue</button></form>@endif
            </div>
        </div>
    @empty
        <div class="alert alert-light border text-center text-muted">Aucune notification de prévention ne correspond à vos critères.</div>
    @endforelse
    {{ $notifications->links() }}
</section>
@endsection
