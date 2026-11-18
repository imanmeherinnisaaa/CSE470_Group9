<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubstituteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('manage', $this->route('team'));
    }

    public function rules(): array
    {
        return [
            'starter_slot_id' => ['required', 'integer'],
            'substitute_slot_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'starter_slot_id.required' => 'Choose the starter who is coming off.',
            'substitute_slot_id.required' => 'Choose the substitute who is coming on.',
        ];
    }
}
