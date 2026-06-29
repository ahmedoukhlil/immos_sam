<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBienRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'localisation_id' => ['sometimes', 'required', 'exists:localisations,id'],
            'numero_serie' => ['nullable', 'string', 'max:255', 'unique:biens,numero_serie,' . $this->route('bien')->id],
            'valeur' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

