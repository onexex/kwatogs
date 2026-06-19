<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllowedIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // On update, ignore the current record's ip_address in the unique check
        $ignoreId = $this->route('allowed_ip');

        return [
            'ip_address' => [
                'required',
                'string',
                'max:45',
                'ip',                                         // validates both IPv4 and IPv6
                Rule::unique('allowed_ips', 'ip_address')->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'status'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ip_address.required' => 'IP address is required.',
            'ip_address.ip'       => 'Must be a valid IPv4 or IPv6 address.',
            'ip_address.max'      => 'IP address must not exceed 45 characters.',
            'ip_address.unique'   => 'This IP address is already in the allowlist.',
            'description.max'     => 'Description must not exceed 255 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ip_address'  => 'IP Address',
            'description' => 'Description',
            'status'      => 'Status',
        ];
    }
}
