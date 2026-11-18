<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, ['player', 'team_captain'], true);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50', 'unique:teams,name'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'That team name is already taken.'];
    }
}
