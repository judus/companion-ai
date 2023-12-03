<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCharacterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:0',
            'gender' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'occupation' => 'nullable|string|max:255',
            'traits' => 'nullable|string',
            'quirks' => 'nullable|string',
            'interests' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'dialogue_style' => 'nullable|string|max:255',
            'prompt' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_public' => 'sometimes|boolean',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        if (!array_key_exists('is_public', $validated)) {
            $validated['is_public'] = false;
        }

        return $validated;
    }
}
