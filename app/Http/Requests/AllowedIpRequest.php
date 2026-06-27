<?php

namespace App\Http\Requests;

use App\Models\AllowedIp;
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
                // Accept a single IP (IPv4/IPv6) OR a CIDR range (e.g. 203.0.113.0/24).
                function ($attribute, $value, $fail) {
                    if (! AllowedIp::isValidIpOrCidr($value)) {
                        $fail('Must be a valid IP address (e.g. 192.168.1.1) or CIDR range (e.g. 203.0.113.0/24).');
                    }
                },
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
