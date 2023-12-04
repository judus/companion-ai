<?php

namespace App\Http\Requests;

use App\Models\Character;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateCharacterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $character = $this->route('character'); // Get character ID from the route

        // Allow if the user is the owner of the character
        return $this->user()->id === $character->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
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
