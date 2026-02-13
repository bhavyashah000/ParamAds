<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
            'avatar' => 'nullable|string|max:500',
            'preferences' => 'nullable|array',
        ];
    }
}
