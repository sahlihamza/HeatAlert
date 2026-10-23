{{-- Partial: Formulaire EquipementSensible --}}
<div class="row align-items-stretch">
  <div class="col-lg-8">
    {{-- Bannière d'erreur dynamique (Client-side) --}}
    <div id="equipement-form-alert" class="alert alert-danger d-none mb-3 py-2 px-3 small rounded-3" style="border-radius:10px;">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <span>Veuillez corriger les erreurs ci-dessous avant d'enregistrer l'équipement.</span>
    </div>

    {{-- Bannière d'erreur serveur (Laravel) --}}
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

    {{-- Section 1 : Informations principales --}}
    <section class="m-card mb-3">
      <header class="m-card__header">
        <h2 class="m-card__title">
          <i class="fa-solid fa-circle-info me-2 text-primary"></i>Informations principales
        </h2>
      </header>
      <div class="row g-3 p-3">
        {{-- Nom de l'équipement --}}
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="nom" class="form-label fw-semibold mb-0">Nom de l'équipement <span class="text-danger">*</span></label>
            <span class="small text-muted" id="nom-char-counter">0 / 150</span>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-tag"></i></span>
            <input type="text"
                   id="nom" name="nom"
                   class="form-control @error('nom') is-invalid @enderror"
                   value="{{ old('nom', $equipement->nom ?? '') }}"
                   placeholder="Ex: Réfrigérateur à vaccins dispensaire Nord"
                   minlength="3" maxlength="150" required autocomplete="off">
            <div class="invalid-feedback" id="nom-error">
              <i class="fa-solid fa-circle-exclamation me-1"></i>@error('nom'){{ $message }}@else{{ 'Le nom doit comporter entre 3 et 150 caractères.' }}@enderror
            </div>
          </div>
          <div class="form-text small text-muted">Désignation claire permettant d'identifier immédiatement le site ou l'équipement.</div>
        </div>

        {{-- Type d'équipement --}}
        <div class="col-md-6">
          <label for="type" class="form-label fw-semibold">Type d'équipement <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-shapes"></i></span>
            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
              <option value="">— Sélectionner le type d'équipement —</option>
              @foreach($types as $t)
                @php
                  $label = \App\Models\EquipementSensible::TYPE_LABELS[$t] ?? ucfirst(str_replace('_', ' ', $t));
                @endphp
                <option value="{{ $t }}" {{ old('type', $equipement->type ?? '') === $t ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            <div class="invalid-feedback" id="type-error">
              <i class="fa-solid fa-circle-exclamation me-1"></i>@error('type'){{ $message }}@else{{ 'Le type d\'équipement est obligatoire.' }}@enderror
            </div>
          </div>
        </div>

        {{-- Zone géographique --}}
        <div class="col-md-6">
          <label for="zone_id" class="form-label fw-semibold">Zone géographique</label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-location-dot"></i></span>
            <select id="zone_id" name="zone_id" class="form-select @error('zone_id') is-invalid @enderror">
              <option value="">— Aucune zone (Nationale / Indéterminée) —</option>
              @foreach($zones as $z)
                <option value="{{ $z->id }}" {{ old('zone_id', $equipement->zone_id ?? '') == $z->id ? 'selected' : '' }}>
                  {{ $z->nom }}{{ $z->ville ? ' — ' . $z->ville : '' }}{{ $z->gouvernorat ? ' (' . $z->gouvernorat . ')' : '' }}
                </option>
              @endforeach
            </select>
            <div class="invalid-feedback" id="zone_id-error">
              <i class="fa-solid fa-circle-exclamation me-1"></i>@error('zone_id'){{ $message }}@enderror
            </div>
          </div>
        </div>

        {{-- Adresse détaillée --}}
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="adresse" class="form-label fw-semibold mb-0">Adresse précise / Emplacement</label>
            <span class="small text-muted" id="adresse-char-counter">0 / 255</span>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-map-pin"></i></span>
            <input type="text"
                   id="adresse" name="adresse"
                   class="form-control @error('adresse') is-invalid @enderror"
                   value="{{ old('adresse', $equipement->adresse ?? '') }}"
                   placeholder="Ex: 12 Rue de la République, Bâtiment B, Salle 104"
                   maxlength="255">
            <div class="invalid-feedback" id="adresse-error">
              <i class="fa-solid fa-circle-exclamation me-1"></i>@error('adresse'){{ $message }}@else{{ 'L\'adresse ne peut pas dépasser 255 caractères.' }}@enderror
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- Section 2 : Sensibilité & Paramètres de surveillance --}}
    <section class="m-card mb-3">
      <header class="m-card__header">
        <h2 class="m-card__title">
          <i class="fa-solid fa-gauge-high me-2 text-warning"></i>Sensibilité & Surveillance
        </h2>
      </header>
      <div class="row g-3 p-3">
        {{-- Niveau de sensibilité --}}
        <div class="col-md-7">
          <label for="niveau_sensibilite" class="form-label fw-semibold">Niveau de sensibilité <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-shield-halved"></i></span>
            <select id="niveau_sensibilite" name="niveau_sensibilite" class="form-select @error('niveau_sensibilite') is-invalid @enderror" required>
              <option value="">— Choisir le niveau de sensibilité —</option>
              @foreach($niveaux as $n)
                <option value="{{ $n }}" {{ old('niveau_sensibilite', $equipement->niveau_sensibilite ?? 'moyen') === $n ? 'selected' : '' }}>
                  @if($n === 'eleve') 🔴 Élevé (Risque vital / rupture critique immédiate)
                  @elseif($n === 'moyen') 🟠 Moyen (Vigilance accrue en cas de coupure)
                  @else 🟢 Faible (Surveillance standard)
                  @endif
                </option>
              @endforeach
            </select>
            <div class="invalid-feedback" id="niveau_sensibilite-error">
              <i class="fa-solid fa-circle-exclamation me-1"></i>@error('niveau_sensibilite'){{ $message }}@else{{ 'Le niveau de sensibilité est obligatoire.' }}@enderror
            </div>
          </div>
          <div class="form-text small text-muted">Définit le seuil d'alerte et la priorité lors des vagues de chaleur ou coupures de courant.</div>
        </div>

        {{-- Statut actif --}}
        <div class="col-md-5">
          <label class="form-label fw-semibold d-block">Statut opérationnel</label>
          <div class="p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between" style="min-height:38px;">
            <div>
              <span class="fw-semibold small d-block" id="actif-label-text">
                {{ old('actif', $equipement->actif ?? true) ? 'Équipement actif' : 'Équipement inactif' }}
              </span>
              <span class="text-muted" style="font-size:11px;" id="actif-sub-text">
                {{ old('actif', $equipement->actif ?? true) ? 'Surveillance et alertes actives' : 'Surveillance suspendue' }}
              </span>
            </div>
            <div class="form-check form-switch mb-0">
              <input type="hidden" name="actif" value="0">
              <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1"
                     style="cursor:pointer;width:2.5em;height:1.3em;"
                     {{ old('actif', $equipement->actif ?? true) ? 'checked' : '' }}>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- Section 3 : Description & Consignes --}}
    <section class="m-card mb-3">
      <header class="m-card__header">
        <h2 class="m-card__title">
          <i class="fa-solid fa-file-lines me-2 text-secondary"></i>Description & Consignes de sécurité
        </h2>
      </header>
      <div class="row g-3 p-3">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="description" class="form-label fw-semibold mb-0">Description & Consignes d'urgence</label>
            <span class="small text-muted" id="description-char-counter">0 / 2000</span>
          </div>
          <textarea id="description" name="description"
                    class="form-control @error('description') is-invalid @enderror"
                    rows="4"
                    maxlength="2000"
                    placeholder="Préciser les consignes en cas de coupure (ex: température max tolérée +4°C, autonomie groupe électrogène 2h, contact astreinte 71 000 000)...">{{ old('description', $equipement->description ?? '') }}</textarea>
          <div class="invalid-feedback" id="description-error">
            <i class="fa-solid fa-circle-exclamation me-1"></i>@error('description'){{ $message }}@else{{ 'La description ne peut pas dépasser 2 000 caractères.' }}@enderror
          </div>
          <div class="form-text small text-muted">Indiquez toute consigne utile pour l'équipe d'intervention d'urgence.</div>
        </div>
      </div>
    </section>

    {{-- Boutons d'action --}}
    <div class="d-flex align-items-center gap-2 mb-4">
      <button type="submit" class="m-btn m-btn--primary" id="btn-submit-equipement">
        <i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer l'équipement
      </button>
      <a href="{{ route('admin.equipements-sensibles.index') }}" class="m-btn m-btn--ghost">
        Annuler
      </a>
    </div>
  </div>

  {{-- Colonne latérale : Aperçu & Conseils --}}
  <div class="col-lg-4">
    {{-- Carte d'aide / Consignes --}}
    <div class="m-card mb-3 p-3" style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">
      <h6 class="fw-bold text-dark mb-2">
        <i class="fa-solid fa-lightbulb text-warning me-1"></i>Règles de saisie
      </h6>
      <ul class="small text-muted ps-3 mb-0" style="line-height:1.6;">
        <li><strong>Nom :</strong> Doit être clair et unique (3 à 150 caractères).</li>
        <li><strong>Type :</strong> Choisissez la catégorie correspondante pour adapter les seuils d'alerte.</li>
        <li><strong>Niveau Élevé :</strong> Réservé aux équipements vitaux (chambres froides de médicaments, assistance respiratoire).</li>
        <li><strong>Zone :</strong> Permet de corréler automatiquement l'équipement aux alertes météo et coupures locales.</li>
        <li class="text-primary mt-1"><strong><i class="fa-solid fa-envelope me-1"></i>Notification e-mail :</strong> Les citoyens rattachés à cette zone reçoivent automatiquement un e-mail d'information lors de l'ajout ou de la modification.</li>
      </ul>
    </div>

    {{-- Carte Aperçu dynamique en direct --}}
    <div class="m-card p-3" style="border-radius:12px;border:1px solid #e2e8f0;">
      <h6 class="fw-bold text-dark mb-3">
        <i class="fa-solid fa-eye text-primary me-1"></i>Aperçu en direct
      </h6>
      <div class="p-3 rounded-3" style="background:#f1f5f9;border-left:4px solid #3b82f6;" id="preview-box">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge" id="preview-type-badge" style="background:#64748b;font-size:11px;">
            <i class="fa-solid fa-microchip me-1"></i><span id="preview-type-text">Type non défini</span>
          </span>
          <span class="badge" id="preview-niveau-badge" style="background:#eab308;color:#000;font-size:11px;">
            <span id="preview-niveau-text">Moyen</span>
          </span>
        </div>
        <h6 class="fw-bold mb-1 text-dark" id="preview-nom-text">Nom de l'équipement</h6>
        <p class="small text-muted mb-2" id="preview-zone-text">
          <i class="fa-solid fa-location-dot me-1 text-danger"></i>Zone : non spécifiée
        </p>
        <p class="small text-muted mb-0" id="preview-desc-text" style="font-style:italic;">
          Aucune consigne saisie.
        </p>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('equipement-form');
  const alertBanner = document.getElementById('equipement-form-alert');

  // Champs du formulaire
  const nomInput = document.getElementById('nom');
  const typeSelect = document.getElementById('type');
  const zoneSelect = document.getElementById('zone_id');
  const adresseInput = document.getElementById('adresse');
  const niveauSelect = document.getElementById('niveau_sensibilite');
  const actifCheckbox = document.getElementById('actif');
  const actifLabelText = document.getElementById('actif-label-text');
  const actifSubText = document.getElementById('actif-sub-text');
  const descTextarea = document.getElementById('description');

  // Compteurs
  const nomCounter = document.getElementById('nom-char-counter');
  const adresseCounter = document.getElementById('adresse-char-counter');
  const descCounter = document.getElementById('description-char-counter');

  // Aperçu
  const previewNom = document.getElementById('preview-nom-text');
  const previewTypeBadge = document.getElementById('preview-type-badge');
  const previewTypeText = document.getElementById('preview-type-text');
  const previewNiveauBadge = document.getElementById('preview-niveau-badge');
  const previewNiveauText = document.getElementById('preview-niveau-text');
  const previewZone = document.getElementById('preview-zone-text');
  const previewDesc = document.getElementById('preview-desc-text');

  const typeIcons = {
    frigo: 'fa-solid fa-snowflake',
    medicament: 'fa-solid fa-pills',
    ventilateur: 'fa-solid fa-fan',
    appareil_medical: 'fa-solid fa-heart-pulse'
  };

  const typeLabels = {
    frigo: 'Réfrigérateur',
    medicament: 'Médicament à conserver',
    ventilateur: 'Ventilateur / Climatiseur',
    appareil_medical: 'Appareil médical'
  };

  // ─── Mise à jour des compteurs et aperçu ──────────────────────────────────────────
  function updateCountersAndPreview() {
    if (nomInput && nomCounter) {
      nomCounter.textContent = `${nomInput.value.length} / 150`;
    }
    if (adresseInput && adresseCounter) {
      adresseCounter.textContent = `${adresseInput.value.length} / 255`;
    }
    if (descTextarea && descCounter) {
      descCounter.textContent = `${descTextarea.value.length} / 2000`;
    }

    // Aperçu Nom
    if (previewNom) {
      previewNom.textContent = nomInput?.value.trim() || 'Nom de l\'équipement';
    }

    // Aperçu Type
    if (previewTypeBadge && previewTypeText) {
      const typeVal = typeSelect?.value;
      if (typeVal && typeLabels[typeVal]) {
        previewTypeText.textContent = typeLabels[typeVal];
        const iconClass = typeIcons[typeVal] || 'fa-solid fa-microchip';
        previewTypeBadge.innerHTML = `<i class="${iconClass} me-1"></i>${typeLabels[typeVal]}`;
        previewTypeBadge.style.background = '#2563eb';
      } else {
        previewTypeBadge.innerHTML = '<i class="fa-solid fa-microchip me-1"></i>Type non défini';
        previewTypeBadge.style.background = '#64748b';
      }
    }

    // Aperçu Niveau
    if (previewNiveauBadge && previewNiveauText) {
      const nVal = niveauSelect?.value;
      if (nVal === 'eleve') {
        previewNiveauText.textContent = 'Élevé';
        previewNiveauBadge.className = 'badge bg-danger text-white';
      } else if (nVal === 'faible') {
        previewNiveauText.textContent = 'Faible';
        previewNiveauBadge.className = 'badge bg-success text-white';
      } else {
        previewNiveauText.textContent = 'Moyen';
        previewNiveauBadge.className = 'badge bg-warning text-dark';
      }
    }

    // Aperçu Zone
    if (previewZone && zoneSelect) {
      const selectedOption = zoneSelect.options[zoneSelect.selectedIndex];
      if (zoneSelect.value && selectedOption) {
        previewZone.innerHTML = `<i class="fa-solid fa-location-dot me-1 text-danger"></i>${selectedOption.textContent.trim()}`;
      } else {
        previewZone.innerHTML = `<i class="fa-solid fa-location-dot me-1 text-muted"></i>Zone non spécifiée`;
      }
    }

    // Aperçu Description
    if (previewDesc) {
      const dVal = descTextarea?.value.trim();
      previewDesc.textContent = dVal ? (dVal.length > 90 ? dVal.substring(0, 90) + '…' : dVal) : 'Aucune consigne saisie.';
      previewDesc.style.fontStyle = dVal ? 'normal' : 'italic';
    }
  }

  // ─── Helpers de validation visuelle ──────────────────────────────────────────
  function setFieldError(field, errorElId, message) {
    if (!field) return;
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    const errEl = document.getElementById(errorElId);
    if (errEl) {
      errEl.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i>${message}`;
      errEl.style.display = 'block';
    }
  }

  function setFieldSuccess(field, errorElId) {
    if (!field) return;
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    const errEl = document.getElementById(errorElId);
    if (errEl) {
      errEl.style.display = 'none';
    }
  }

  // ─── Fonctions de validation de chaque champ ─────────────────────────────────
  function validateNom() {
    const val = (nomInput?.value || '').trim();
    if (!val) {
      setFieldError(nomInput, 'nom-error', 'Le nom de l\'équipement est obligatoire.');
      return false;
    }
    if (val.length < 3) {
      setFieldError(nomInput, 'nom-error', 'Le nom doit comporter au moins 3 caractères.');
      return false;
    }
    if (val.length > 150) {
      setFieldError(nomInput, 'nom-error', 'Le nom ne peut pas dépasser 150 caractères.');
      return false;
    }
    setFieldSuccess(nomInput, 'nom-error');
    return true;
  }

  function validateType() {
    if (!typeSelect?.value) {
      setFieldError(typeSelect, 'type-error', 'Veuillez sélectionner un type d\'équipement.');
      return false;
    }
    setFieldSuccess(typeSelect, 'type-error');
    return true;
  }

  function validateNiveau() {
    if (!niveauSelect?.value) {
      setFieldError(niveauSelect, 'niveau_sensibilite-error', 'Le niveau de sensibilité est obligatoire.');
      return false;
    }
    setFieldSuccess(niveauSelect, 'niveau_sensibilite-error');
    return true;
  }

  function validateAdresse() {
    const val = (adresseInput?.value || '').trim();
    if (val.length > 255) {
      setFieldError(adresseInput, 'adresse-error', 'L\'adresse ne peut pas dépasser 255 caractères.');
      return false;
    }
    if (val.length > 0) {
      setFieldSuccess(adresseInput, 'adresse-error');
    } else {
      adresseInput?.classList.remove('is-invalid', 'is-valid');
      const errEl = document.getElementById('adresse-error');
      if (errEl) errEl.style.display = 'none';
    }
    return true;
  }

  function validateDescription() {
    const val = (descTextarea?.value || '').trim();
    if (val.length > 2000) {
      setFieldError(descTextarea, 'description-error', 'La description ne peut pas dépasser 2 000 caractères.');
      return false;
    }
    if (val.length > 0) {
      setFieldSuccess(descTextarea, 'description-error');
    } else {
      descTextarea?.classList.remove('is-invalid', 'is-valid');
      const errEl = document.getElementById('description-error');
      if (errEl) errEl.style.display = 'none';
    }
    return true;
  }

  // ─── Écouteurs d'événements interactifs ──────────────────────────────────────
  if (nomInput) {
    nomInput.addEventListener('input', function () { updateCountersAndPreview(); validateNom(); });
    nomInput.addEventListener('blur', validateNom);
  }

  if (typeSelect) {
    typeSelect.addEventListener('change', function () { updateCountersAndPreview(); validateType(); });
  }

  if (zoneSelect) {
    zoneSelect.addEventListener('change', updateCountersAndPreview);
  }

  if (niveauSelect) {
    niveauSelect.addEventListener('change', function () { updateCountersAndPreview(); validateNiveau(); });
  }

  if (adresseInput) {
    adresseInput.addEventListener('input', function () { updateCountersAndPreview(); validateAdresse(); });
    adresseInput.addEventListener('blur', validateAdresse);
  }

  if (descTextarea) {
    descTextarea.addEventListener('input', function () { updateCountersAndPreview(); validateDescription(); });
    descTextarea.addEventListener('blur', validateDescription);
  }

  if (actifCheckbox) {
    actifCheckbox.addEventListener('change', function () {
      if (this.checked) {
        if (actifLabelText) actifLabelText.textContent = 'Équipement actif';
        if (actifSubText) actifSubText.textContent = 'Surveillance et alertes actives';
      } else {
        if (actifLabelText) actifLabelText.textContent = 'Équipement inactif';
        if (actifSubText) actifSubText.textContent = 'Surveillance suspendue';
      }
    });
  }

  // Initialisation à l'ouverture de la page
  updateCountersAndPreview();

  // ─── Validation globale lors de la soumission du formulaire ──────────────────
  if (form) {
    form.addEventListener('submit', function (e) {
      const isNomValid = validateNom();
      const isTypeValid = validateType();
      const isNiveauValid = validateNiveau();
      const isAdresseValid = validateAdresse();
      const isDescValid = validateDescription();

      const allValid = isNomValid && isTypeValid && isNiveauValid && isAdresseValid && isDescValid;

      if (!allValid) {
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
});
</script>
@endpush
