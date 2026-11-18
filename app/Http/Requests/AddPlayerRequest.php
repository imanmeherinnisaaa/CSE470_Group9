<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('manage', $this->route('team'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'slot_type' => ['required', 'in:starter,substitute'],
        ];
    }
}
