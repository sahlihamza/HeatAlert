@extends('layouts.admin.admin')

@section('title', 'Ajouter un équipement sensible')

@section('content')
<div class="page-header">
  <div>
    <nav aria-label="breadcrumb" class="mb-2">
      <a href="{{ route('admin.equipements-sensibles.index') }}" class="text-decoration-none text-muted" style="font-size:.85rem;">
        <i class="fa-solid fa-arrow-left me-1"></i>Retour à la liste des équipements
      </a>
    </nav>
    <h1><i class="fa-solid fa-shield-heart me-2 text-danger"></i>Ajouter un Équipement Sensible</h1>
    <p class="subtitle">Référencer un équipement sensible ou un site vulnérable face aux alertes de canicule et aux coupures d'électricité.</p>
  </div>
</div>

<form method="POST" action="{{ route('admin.equipements-sensibles.store') }}" id="equipement-form" novalidate>
  @csrf
  @include('admin.equipements_sensibles._form')
</form>
@endsection
