{{-- Formulaire partagé des coupures électriques --}}
<div class="row align-items-stretch">
  <div class="col-lg-8">
    {{-- Bannière d'erreur dynamique --}}
    <div id="coupure-form-alert" class="alert alert-danger d-none mb-3 py-2 px-3 small rounded-3" style="border-radius:10px;">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <span>Veuillez corriger les erreurs ci-dessous avant d'enregistrer.</span>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3" style="border-radius:10px;">
        <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i>Des erreurs sont survenues :</div>
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <section class="m-card mb-3">
      <header class="m-card__header">
        <h2 class="m-card__title">Informations de la coupure</h2>
      </header>
      <div class="row g-3 p-3">
        {{-- Type --}}
        <div class="col-md-6">
          <label for="type" class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
          <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="">Sélectionner un type</option>
            @foreach($types as $type)
              <option value="{{ $type }}" {{ old('type', $coupure->type ?? request('type', '')) === $type ? 'selected' : '' }}>
                {{ ucfirst($type) }}
              </option>
            @endforeach
          </select>
          <div class="invalid-feedback" id="type-error">@error('type'){{ $message }}@else{{ 'Veuillez sélectionner un type de coupure valide.' }}@enderror</div>
        </div>

        {{-- Statut --}}
        <div class="col-md-6">
          <label for="statut" class="form-label fw-semibold">Statut <span class="text-danger">*</span></label>
          <select id="statut" name="statut" class="form-select @error('statut') is-invalid @enderror" required>
            @if(isset($coupure) && $coupure->statut === 'terminee')
              <option value="terminee" selected>Terminée (automatique)</option>
            @else
              @foreach($statuts as $statut)
                <option value="{{ $statut }}" {{ old('statut', $coupure->statut ?? request('statut', 'prevue')) === $statut ? 'selected' : '' }}>
                  {{ ucfirst(str_replace('_', ' ', $statut)) }}
                </option>
              @endforeach
            @endif
          </select>
          <div class="invalid-feedback" id="statut-error">@error('statut'){{ $message }}@else{{ 'Le statut est obligatoire.' }}@enderror</div>
          <small class="form-text text-muted">Le statut « Terminée » est calculé automatiquement selon la date de fin.</small>
        </div>

        {{-- Zone --}}
        <div class="col-12">
          <label for="zone_id" class="form-label fw-semibold">Zone géographique <span class="text-danger">*</span></label>
          <select id="zone_id" name="zone_id" class="form-select @error('zone_id') is-invalid @enderror" required>
            <option value="">Sélectionner une zone</option>
            @foreach($zones as $zone)
              <option value="{{ $zone->id }}" {{ old('zone_id', $coupure->zone_id ?? request('zone_id', '')) == $zone->id ? 'selected' : '' }}>
                {{ $zone->nom }} — {{ $zone->ville }} ({{ $zone->gouvernorat }})
              </option>
            @endforeach
          </select>
          <div class="invalid-feedback" id="zone_id-error">@error('zone_id'){{ $message }}@else{{ 'Veuillez sélectionner une zone géographique.' }}@enderror</div>
        </div>

        {{-- Date Début --}}
        <div class="col-md-6">
          <label for="date_debut" class="form-label fw-semibold">Date de début <span class="text-danger">*</span></label>
          <input type="datetime-local" id="date_debut" name="date_debut"
                 class="form-control @error('date_debut') is-invalid @enderror"
                 value="{{ old('date_debut', isset($coupure->date_debut) ? $coupure->date_debut->format('Y-m-d\TH:i') : request('date_debut', '')) }}"
                 step="60" required>
          <div class="invalid-feedback" id="date_debut-error">@error('date_debut'){{ $message }}@else{{ 'La date de début est obligatoire.' }}@enderror</div>
          <small class="form-text text-muted">Choisissez la date, l’heure et les minutes exactes.</small>
        </div>

        {{-- Date Fin --}}
        <div class="col-md-6">
          <label for="date_fin" class="form-label fw-semibold">Date de fin</label>
          <input type="datetime-local" id="date_fin" name="date_fin"
                 class="form-control @error('date_fin') is-invalid @enderror"
                 value="{{ old('date_fin', isset($coupure->date_fin) ? $coupure->date_fin->format('Y-m-d\TH:i') : request('date_fin', '')) }}"
                 step="60" {{ empty(old('date_debut', isset($coupure->date_debut) ? $coupure->date_debut->format('Y-m-d\TH:i') : request('date_debut', ''))) ? 'disabled' : '' }}>
          <div class="invalid-feedback" id="date_fin-error">@error('date_fin'){{ $message }}@else{{ 'La date de fin doit être postérieure à la date de début.' }}@enderror</div>
          <small class="form-text text-muted">La fin doit être ultérieure au début.</small>
        </div>

        {{-- Cause --}}
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="cause" class="form-label fw-semibold mb-0">Cause <span class="text-danger">*</span></label>
            <span class="small text-muted" id="cause-char-counter">0 / 500</span>
          </div>
          <textarea id="cause" name="cause" rows="4" maxlength="500"
                    class="form-control @error('cause') is-invalid @enderror"
                    placeholder="Décrire la cause détaillée de la coupure électrique..." required>{{ old('cause', $coupure->cause ?? request('cause', '')) }}</textarea>
          <div class="invalid-feedback" id="cause-error">@error('cause'){{ $message }}@else{{ 'La cause de la coupure est obligatoire (au moins 3 caractères).' }}@enderror</div>
        </div>
      </div>
    </section>
  </div>

  <div class="col-lg-4 d-flex flex-column">
    <section class="m-card mb-3">
      <header class="m-card__header">
        <h2 class="m-card__title">Actions</h2>
      </header>
      <div class="p-3 d-grid gap-2">
        <button type="submit" class="m-btn m-btn--primary" id="btn-submit-coupure">
          <i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer
        </button>
        <a href="{{ route('admin.coupures.index') }}" class="m-btn m-btn--ghost text-center">
          <i class="fa-solid fa-xmark me-2"></i>Annuler
        </a>
      </div>
    </section>

    {{-- Aide saisie --}}
    <section class="m-card">
      <header class="m-card__header">
        <h2 class="m-card__title" style="font-size:.92rem;"><i class="fa-solid fa-lightbulb text-warning me-2"></i>Règles métier</h2>
      </header>
      <div class="p-3 small text-muted">
        <ul class="mb-0 ps-3">
          <li class="mb-1"><strong>Type :</strong> Délestage, Surcharge ou Panne.</li>
          <li class="mb-1"><strong>Date de fin :</strong> Optionnelle au départ, mais si renseignée elle doit dépasser la date de début.</li>
          <li class="mb-1"><strong>Statut automatique :</strong> Lorsque la date de fin est dépassée, le système passe le statut à "Terminée".</li>
        </ul>
      </div>
    </section>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('coupure-form');
  const alertBanner = document.getElementById('coupure-form-alert');
  const typeSelect = document.getElementById('type');
  const statutSelect = document.getElementById('statut');
  const zoneSelect = document.getElementById('zone_id');
  const debutInput = document.getElementById('date_debut');
  const finInput = document.getElementById('date_fin');
  const causeTextarea = document.getElementById('cause');
  const causeCounter = document.getElementById('cause-char-counter');

  // Mise à jour compteur cause
  function updateCauseCounter() {
    if (!causeTextarea || !causeCounter) return;
    const len = causeTextarea.value.length;
    causeCounter.textContent = `${len} / 500`;
    causeCounter.style.color = len >= 480 ? '#dc2626' : (len > 0 ? '#16a34a' : '#64748b');
  }

  // Synchronisation des dates
  function synchroniserDates() {
    if (!debutInput || !finInput) return;
    finInput.disabled = !debutInput.value;
    finInput.min = debutInput.value || '';
    if (finInput.value && debutInput.value && finInput.value <= debutInput.value) {
      setFieldError(finInput, 'date_fin-error', 'La date de fin doit être postérieure à la date de début.');
    }
  }

  // Utilitaires validation champ
  function setFieldError(field, errorId, msg) {
    field.classList.remove('is-valid');
    field.classList.add('is-invalid');
    const errEl = document.getElementById(errorId);
    if (errEl) {
      errEl.textContent = msg;
      errEl.style.display = 'block';
    }
  }

  function setFieldSuccess(field, errorId) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    const errEl = document.getElementById(errorId);
    if (errEl) {
      errEl.style.display = 'none';
    }
  }

  // Validation individuelle des champs
  function validateType() {
    if (!typeSelect.value) {
      setFieldError(typeSelect, 'type-error', 'Le type de coupure est obligatoire.');
      return false;
    }
    setFieldSuccess(typeSelect, 'type-error');
    return true;
  }

  function validateStatut() {
    if (!statutSelect.value) {
      setFieldError(statutSelect, 'statut-error', 'Le statut est obligatoire.');
      return false;
    }
    setFieldSuccess(statutSelect, 'statut-error');
    return true;
  }

  function validateZone() {
    if (!zoneSelect.value) {
      setFieldError(zoneSelect, 'zone_id-error', 'La zone géographique est obligatoire.');
      return false;
    }
    setFieldSuccess(zoneSelect, 'zone_id-error');
    return true;
  }

  function validateDebut() {
    if (!debutInput.value) {
      setFieldError(debutInput, 'date_debut-error', 'La date de début est obligatoire.');
      return false;
    }
    setFieldSuccess(debutInput, 'date_debut-error');
    return true;
  }

  function validateFin() {
    if (!finInput.value) {
      finInput.classList.remove('is-invalid');
      const errEl = document.getElementById('date_fin-error');
      if (errEl) errEl.style.display = 'none';
      return true;
    }
    if (debutInput.value && finInput.value <= debutInput.value) {
      setFieldError(finInput, 'date_fin-error', 'La date de fin doit être postérieure à la date de début.');
      return false;
    }
    setFieldSuccess(finInput, 'date_fin-error');
    return true;
  }

  function validateCause() {
    const val = causeTextarea.value.trim();
    if (!val) {
      setFieldError(causeTextarea, 'cause-error', 'La cause de la coupure est obligatoire.');
      return false;
    }
    if (val.length < 3) {
      setFieldError(causeTextarea, 'cause-error', 'La cause doit comporter au moins 3 caractères.');
      return false;
    }
    if (val.length > 500) {
      setFieldError(causeTextarea, 'cause-error', 'La cause ne peut pas dépasser 500 caractères.');
      return false;
    }
    setFieldSuccess(causeTextarea, 'cause-error');
    return true;
  }

  // Événements live
  if (typeSelect) {
    typeSelect.addEventListener('change', validateType);
  }
  if (statutSelect) {
    statutSelect.addEventListener('change', validateStatut);
  }
  if (zoneSelect) {
    zoneSelect.addEventListener('change', validateZone);
  }
  if (debutInput) {
    debutInput.addEventListener('input', function() {
      validateDebut();
      synchroniserDates();
      validateFin();
    });
    debutInput.addEventListener('change', function() {
      validateDebut();
      synchroniserDates();
      validateFin();
    });
  }
  if (finInput) {
    finInput.addEventListener('input', validateFin);
    finInput.addEventListener('change', validateFin);
  }
  if (causeTextarea) {
    causeTextarea.addEventListener('input', function() {
      updateCauseCounter();
      validateCause();
    });
    causeTextarea.addEventListener('blur', validateCause);
    updateCauseCounter();
  }

  synchroniserDates();

  // Soumission contrôlée
  if (form) {
    form.addEventListener('submit', function (e) {
      const vType = validateType();
      const vStatut = validateStatut();
      const vZone = validateZone();
      const vDebut = validateDebut();
      const vFin = validateFin();
      const vCause = validateCause();

      const allValid = vType && vStatut && vZone && vDebut && vFin && vCause;

      if (!allValid) {
        e.preventDefault();
        e.stopPropagation();

        if (alertBanner) {
          alertBanner.classList.remove('d-none');
          alertBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Focus premier champ erroné
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
          firstInvalid.focus();
        }
      } else {
        if (alertBanner) alertBanner.classList.add('d-none');
      }
    });
  }
});
</script>
@endpush
