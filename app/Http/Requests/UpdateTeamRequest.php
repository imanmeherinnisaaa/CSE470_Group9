<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('manage', $this->route('team'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50', Rule::unique('teams', 'name')->ignore($this->route('team')->id)],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'That team name is already taken.'];
    }
}
