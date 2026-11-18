<?php

namespace App\Http\Requests;

use App\Services\TournamentConfigurationService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'organizer';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'game' => ['required', 'string', 'max:80'],
            'format' => ['required', Rule::in(array_keys(TournamentConfigurationService::FORMATS))],
            'min_participants' => ['required', 'integer', 'min:2'],
            'max_participants' => ['required', 'integer', 'min:2'],
            'swiss_rounds' => ['nullable', 'integer', 'min:1', 'required_if:format,swiss'],
            'best_of' => ['required', Rule::in([1, 3, 5])],
            'rules' => ['nullable', 'string', 'max:5000'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return; // basic errors first, then feasibility
            }
            foreach (app(TournamentConfigurationService::class)->validate($v->validated()) as $field => $messages) {
                foreach ($messages as $message) {
                    $v->errors()->add($field, $message);
                }
            }
        });
    }
}