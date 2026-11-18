<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, ['player', 'team_captain'], true);
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
