@extends('layouts.admin.admin')

@section('title', 'Modifier — ' . Str::limit($equipement->nom, 30))

@section('content')
<div class="page-header">
  <div>
    <nav aria-label="breadcrumb" class="mb-2">
      <a href="{{ route('admin.equipements-sensibles.index') }}" class="text-decoration-none text-muted" style="font-size:.85rem;">
        <i class="fa-solid fa-arrow-left me-1"></i>Retour à la liste des équipements
      </a>
    </nav>
    <h1><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Modifier l'Équipement Sensible</h1>
    <p class="subtitle">{{ $equipement->nom }}</p>
  </div>
</div>

<form method="POST" action="{{ route('admin.equipements-sensibles.update', $equipement) }}" id="equipement-form" novalidate>
  @csrf
  @method('PUT')
  @include('admin.equipements_sensibles._form')
</form>
@endsection
