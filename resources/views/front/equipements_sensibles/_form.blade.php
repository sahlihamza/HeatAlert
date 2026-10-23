@php
  $formUid = isset($equipement) && $equipement?->id ? 'edit-' . $equipement->id : 'create';
  $typesList = $types ?? \App\Models\EquipementSensible::TYPES;
  $niveauxList = $niveaux ?? \App\Models\EquipementSensible::NIVEAUX;
@endphp

{{-- Bannière d'erreur dynamique (Client-side) --}}
<div id="front_form_alert_{{ $formUid }}" class="alert alert-danger d-none mb-3 py-2 px-3 small rounded-3 js-form-alert">
  <i class="bi bi-exclamation-triangle-fill me-1"></i>
  <span>Veuillez corriger les champs indiqués ci-dessous avant d'enregistrer.</span>
</div>

{{-- Bannière d'erreurs serveur --}}
@if($errors->any() && (!old('_modal') || old('_modal') === $formUid))
<div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
  <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i>Erreurs de validation :</div>
  <ul class="mb-0 ps-3">
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

<div class="row g-3">
  {{-- Nom de l'équipement --}}
  <div class="col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <label for="front_nom_{{ $formUid }}" class="form-label fw-semibold small mb-0">
        Nom de l’équipement <span class="text-danger">*</span>
      </label>
      <span class="small text-muted" id="front_nom_counter_{{ $formUid }}" style="font-size:11px;">0 / 150</span>
    </div>
    <div class="input-group">
      <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-tag"></i></span>
      <input type="text"
             id="front_nom_{{ $formUid }}"
             name="nom"
             class="form-control border-start-0 js-front-nom @error('nom') is-invalid @enderror"
             required
             minlength="2"
             maxlength="150"
             value="{{ old('nom', $equipement->nom ?? '') }}"
             placeholder="Ex: Réfrigérateur à médicaments"
             autocomplete="off">
    </div>
    <div class="invalid-feedback d-block" id="front_nom_error_{{ $formUid }}" style="display:none !important; font-size:12px;">
      <i class="bi bi-exclamation-circle me-1"></i>@error('nom'){{ $message }}@else{{ 'Le nom doit comporter entre 2 et 150 caractères.' }}@enderror
    </div>
  </div>

  {{-- Type d'équipement --}}
  <div class="col-md-6">
    <label for="front_type_{{ $formUid }}" class="form-label fw-semibold small mb-1">
      Type d'équipement <span class="text-danger">*</span>
    </label>
    <div class="input-group">
      <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-cpu"></i></span>
      <select id="front_type_{{ $formUid }}"
              name="type"
              class="form-select border-start-0 js-front-type @error('type') is-invalid @enderror"
              required>
        <option value="">— Sélectionner un type —</option>
        @foreach($typesList as $t)
          @php
            $typeLabel = \App\Models\EquipementSensible::TYPE_LABELS[$t] ?? ucfirst(str_replace('_', ' ', $t));
          @endphp
          <option value="{{ $t }}" @selected(old('type', $equipement->type ?? '') === $t)>
            {{ $typeLabel }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="invalid-feedback d-block" id="front_type_error_{{ $formUid }}" style="display:none !important; font-size:12px;">
      <i class="bi bi-exclamation-circle me-1"></i>@error('type'){{ $message }}@else{{ 'Veuillez sélectionner un type d\'équipement.' }}@enderror
    </div>
  </div>

  {{-- Niveau de sensibilité --}}
  <div class="col-md-6">
    <label for="front_niveau_{{ $formUid }}" class="form-label fw-semibold small mb-1">
      Niveau de risque <span class="text-danger">*</span>
    </label>
    <div class="input-group">
      <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-shield-exclamation"></i></span>
      <select id="front_niveau_{{ $formUid }}"
              name="niveau_sensibilite"
              class="form-select border-start-0 js-front-niveau @error('niveau_sensibilite') is-invalid @enderror"
              required>
        <option value="">— Sélectionner le niveau —</option>
        @foreach($niveauxList as $niveau)
          <option value="{{ $niveau }}" @selected(old('niveau_sensibilite', $equipement->niveau_sensibilite ?? 'moyen') === $niveau)>
            @if($niveau === 'eleve') 🔴 Élevé (Priorité maximale en cas de chaleur ou coupure)
            @elseif($niveau === 'moyen') 🟠 Moyen (Vigilance accrue)
            @else 🟢 Faible (Surveillance normale)
            @endif
          </option>
        @endforeach
      </select>
    </div>
    <div class="invalid-feedback d-block" id="front_niveau_error_{{ $formUid }}" style="display:none !important; font-size:12px;">
      <i class="bi bi-exclamation-circle me-1"></i>@error('niveau_sensibilite'){{ $message }}@else{{ 'Le niveau de risque est obligatoire.' }}@enderror
    </div>
  </div>

  {{-- Statut actif switch --}}
  <div class="col-md-6">
    <label class="form-label fw-semibold small mb-1">Statut de surveillance</label>
    <div class="p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between" style="min-height:38px;">
      <div>
        <span class="fw-semibold small d-block js-front-actif-label">
          {{ old('actif', $equipement->actif ?? true) ? 'À surveiller actuellement' : 'Surveillance suspendue' }}
        </span>
        <span class="text-muted" style="font-size:11px;">Recevoir les conseils et alertes pour cet équipement</span>
      </div>
      <div class="form-check form-switch mb-0">
        <input type="hidden" name="actif" value="0">
        <input class="form-check-input js-front-actif-input"
               type="checkbox"
               id="front_actif_{{ $formUid }}"
               name="actif"
               value="1"
               style="cursor:pointer;"
               @checked(old('actif', $equipement->actif ?? true))>
      </div>
    </div>
  </div>

  {{-- Description et précautions --}}
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <label for="front_desc_{{ $formUid }}" class="form-label fw-semibold small mb-0">
        Description et précautions utiles
      </label>
      <span class="small text-muted" id="front_desc_counter_{{ $formUid }}" style="font-size:11px;">0 / 2000</span>
    </div>
    <textarea id="front_desc_{{ $formUid }}"
              name="description"
              class="form-control js-front-description @error('description') is-invalid @enderror"
              rows="3"
              maxlength="2000"
              placeholder="Ex. Garder la porte fermée au maximum en cas de coupure de courant. Température critique : 8°C.">{{ old('description', $equipement->description ?? '') }}</textarea>
    <div class="invalid-feedback d-block" id="front_desc_error_{{ $formUid }}" style="display:none !important; font-size:12px;">
      <i class="bi bi-exclamation-circle me-1"></i>@error('description'){{ $message }}@else{{ 'La description ne peut pas dépasser 2 000 caractères.' }}@enderror
    </div>
    <div class="form-text text-muted" style="font-size:11.5px;">
      Mentionnez toute consigne pratique qui vous aidera ou aidera vos proches à réagir vite.
    </div>
  </div>
</div>

<div class="mt-4 d-flex align-items-center gap-2">
  <button type="submit" class="btn btn-danger rounded-pill px-4 js-submit-btn">
    <i class="bi bi-save me-1"></i>Enregistrer
  </button>
  <a href="{{ route('front.equipements_sensibles.index') }}" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
    Annuler
  </a>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  function initFrontEquipementValidation(form) {
    if (!form || form.dataset.validationBound) return;
    form.dataset.validationBound = 'true';

    const alertBanner = form.querySelector('.js-form-alert');
    const nomInput = form.querySelector('.js-front-nom');
    const typeSelect = form.querySelector('.js-front-type');
    const niveauSelect = form.querySelector('.js-front-niveau');
    const descTextarea = form.querySelector('.js-front-description');
    const actifInput = form.querySelector('.js-front-actif-input');
    const actifLabel = form.querySelector('.js-front-actif-label');

    // Counters
    const nomCounter = form.querySelector('[id^="front_nom_counter_"]');
    const descCounter = form.querySelector('[id^="front_desc_counter_"]');

    // Error elements
    const nomError = form.querySelector('[id^="front_nom_error_"]');
    const typeError = form.querySelector('[id^="front_type_error_"]');
    const niveauError = form.querySelector('[id^="front_niveau_error_"]');
    const descError = form.querySelector('[id^="front_desc_error_"]');

    function updateCounters() {
      if (nomInput && nomCounter) {
        nomCounter.textContent = `${nomInput.value.length} / 150`;
      }
      if (descTextarea && descCounter) {
        descCounter.textContent = `${descTextarea.value.length} / 2000`;
      }
    }

    function setFieldError(field, errorEl, message) {
      if (!field) return;
      field.classList.remove('is-valid');
      field.classList.add('is-invalid');
      if (errorEl) {
        errorEl.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${message}`;
        errorEl.style.display = 'block';
      }
    }

    function setFieldSuccess(field, errorEl) {
      if (!field) return;
      field.classList.remove('is-invalid');
      field.classList.add('is-valid');
      if (errorEl) {
        errorEl.style.display = 'none';
      }
    }

    function validateNom() {
      const val = (nomInput?.value || '').trim();
      if (!val) {
        setFieldError(nomInput, nomError, 'Le nom de l\'équipement est obligatoire.');
        return false;
      }
      if (val.length < 2) {
        setFieldError(nomInput, nomError, 'Le nom doit comporter au moins 2 caractères.');
        return false;
      }
      if (val.length > 150) {
        setFieldError(nomInput, nomError, 'Le nom ne peut pas dépasser 150 caractères.');
        return false;
      }
      setFieldSuccess(nomInput, nomError);
      return true;
    }

    function validateType() {
      if (!typeSelect?.value) {
        setFieldError(typeSelect, typeError, 'Veuillez sélectionner un type d\'équipement.');
        return false;
      }
      setFieldSuccess(typeSelect, typeError);
      return true;
    }

    function validateNiveau() {
      if (!niveauSelect?.value) {
        setFieldError(niveauSelect, niveauError, 'Veuillez choisir un niveau de risque.');
        return false;
      }
      setFieldSuccess(niveauSelect, niveauError);
      return true;
    }

    function validateDescription() {
      const val = (descTextarea?.value || '').trim();
      if (val.length > 2000) {
        setFieldError(descTextarea, descError, 'La description ne peut pas dépasser 2 000 caractères.');
        return false;
      }
      if (val.length > 0) {
        setFieldSuccess(descTextarea, descError);
      } else {
        descTextarea?.classList.remove('is-invalid', 'is-valid');
        if (descError) descError.style.display = 'none';
      }
      return true;
    }

    if (nomInput) {
      nomInput.addEventListener('input', function () { updateCounters(); validateNom(); });
      nomInput.addEventListener('blur', validateNom);
    }
    if (typeSelect) {
      typeSelect.addEventListener('change', validateType);
    }
    if (niveauSelect) {
      niveauSelect.addEventListener('change', validateNiveau);
    }
    if (descTextarea) {
      descTextarea.addEventListener('input', function () { updateCounters(); validateDescription(); });
      descTextarea.addEventListener('blur', validateDescription);
    }
    if (actifInput && actifLabel) {
      actifInput.addEventListener('change', function () {
        actifLabel.textContent = this.checked ? 'À surveiller actuellement' : 'Surveillance suspendue';
      });
    }

    updateCounters();

    form.addEventListener('submit', function (e) {
      const okNom = validateNom();
      const okType = validateType();
      const okNiveau = validateNiveau();
      const okDesc = validateDescription();

      const allOk = okNom && okType && okNiveau && okDesc;

      if (!allOk) {
        e.preventDefault();
        e.stopPropagation();

        if (alertBanner) {
          alertBanner.classList.remove('d-none');
          alertBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
          firstInvalid.focus();
        }
      } else {
        if (alertBanner) {
          alertBanner.classList.add('d-none');
        }
      }
    });
  }

  document.querySelectorAll('.js-front-equipement-form').forEach(initFrontEquipementValidation);
});
</script>
@endpush
@endonce
