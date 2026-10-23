<?php

namespace App\Http\Requests;

use App\Models\EquipementSensible;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFrontEquipementSensibleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'type' => ['required', Rule::in(EquipementSensible::TYPES)],
            'niveau_sensibilite' => ['required', Rule::in(EquipementSensible::NIVEAUX)],
            'description' => ['nullable', 'string', 'max:2000'],
            'actif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de l’équipement est obligatoire.',
            'nom.min' => 'Le nom doit contenir au moins 2 caractères.',
            'nom.max' => 'Le nom ne peut pas dépasser 150 caractères.',
            'type.required' => 'Le type d’équipement est obligatoire.',
            'type.in' => 'Le type d’équipement sélectionné est invalide.',
            'niveau_sensibilite.required' => 'Le niveau de risque est obligatoire.',
            'niveau_sensibilite.in' => 'Le niveau de risque sélectionné est invalide.',
            'description.max' => 'La description ne peut pas dépasser 2 000 caractères.',
            'actif.boolean' => 'Le statut sélectionné est invalide.',
        ];
    }
}
