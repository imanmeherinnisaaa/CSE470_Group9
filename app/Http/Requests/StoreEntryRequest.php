<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Let the EntryService perform the player-role
        // and tournament-eligibility checks.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'team_id.exists' => 'That team does not exist.',
        ];
    }
}
