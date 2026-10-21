<?php

namespace App\Http\Requests;

use App\Models\ReplicationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization' => ['required', 'string', 'max:150'],
            'organization_type' => ['required', Rule::in(array_keys(ReplicationRequest::ORGANIZATION_TYPES))],
            'contact_name' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'municipality' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'trainees' => ['required', 'integer', 'min:1', 'max:'.config('replication.max_trainees')],
            'preferred_date' => ['nullable', 'date', 'after:today'],
            'message' => ['nullable', 'string', 'max:1000'],
            'payment' => ['required', Rule::in(['later', 'online'])],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trainees.max' => 'For more than :max trainees, please contact us so we can arrange more than one class.',
            'phone.regex' => 'Please enter a valid phone number.',
            'preferred_date.after' => 'Please choose a date after today.',
            'agree.accepted' => 'Please agree to the Terms & Conditions and the Privacy Policy.',
        ];
    }

    /**
     * Send people back to the form section (not the top of the long Replicate page).
     */
    protected function getRedirectUrl(): string
    {
        return route('replicate').'#inquire';
    }
}
