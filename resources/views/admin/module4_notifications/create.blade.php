@extends('layouts.admin.admin')

@section('content')
<h2 class="mb-4">Créer une notification de prévention</h2>
<form method="POST" action="{{ route('admin.module4_notifications.store') }}" class="card"><div class="card-body row g-3">@csrf
<div class="col-md-6"><label class="form-label">Utilisateur</label><select name="user_id" class="form-select" required><option value="">Sélectionner</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->nom }} {{ $user->prenom }} — {{ $user->email }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Équipement associé</label><select name="equipement_sensible_id" class="form-select"><option value="">Aucun équipement</option>@foreach($equipements as $equipement)<option value="{{ $equipement->id }}">{{ $equipement->nom }} — {{ $equipement->user?->email }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Canal</label><select name="canal" class="form-select" required>@foreach($canaux as $canal)<option value="{{ $canal }}">{{ strtoupper($canal) }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="4" required>{{ old('message') }}</textarea></div>
<div class="col-12"><button class="btn btn-primary">Créer la notification</button> <a href="{{ route('admin.module4_notifications.index') }}" class="btn btn-outline-secondary">Annuler</a></div>
</div></form>
@endsection
