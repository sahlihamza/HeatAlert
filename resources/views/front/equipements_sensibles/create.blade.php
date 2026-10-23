@extends('layouts.front.front')

@section('title', 'Ajouter un équipement — HeatAlert')

@section('content')
<div class="page-title position-relative overflow-hidden"
     style="background:linear-gradient(135deg,rgba(127,29,29,.91),rgba(220,38,38,.78)),url('{{ asset('assets/front/img/health/facilities-9.webp') }}') center/cover no-repeat;padding-top:135px;padding-bottom:40px;">
  <div class="container text-center text-white">
    <h1 class="heading-title text-white"><i class="bi bi-plus-circle me-2"></i>Ajouter un équipement</h1>
    <p class="mb-0">Ajoutez un élément à surveiller pendant les fortes chaleurs et coupures de courant.</p>
  </div>
  <nav class="breadcrumbs mt-4">
    <div class="container">
      <ol class="mb-0">
        <li><a href="{{ route('front.home') }}" class="text-white-50">Accueil</a></li>
        <li><a href="{{ route('front.equipements_sensibles.index') }}" class="text-white-50">Ma préparation</a></li>
        <li class="current text-white">Ajouter</li>
      </ol>
    </div>
  </nav>
</div>

<section class="py-5" style="background:#f8fafc">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('front.equipements_sensibles.store') }}" class="js-front-equipement-form" novalidate>
              @csrf
              @include('front.equipements_sensibles._form', ['equipement' => null])
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
