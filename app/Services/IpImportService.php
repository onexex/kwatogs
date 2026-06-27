<?php

namespace App\Services;

use App\Models\AllowedIp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class IpImportService
{
    /**
     * Expected CSV column names (case-insensitive header matching).
     * Required: ip_address
     * Optional: description
     */
    private const REQUIRED_HEADERS = ['ip_address'];

    /**
     * Process an uploaded CSV file.
     *
     * Returns a summary array:
     *   inserted  — new rows created
     *   updated   — existing rows updated (ip_address matched)
     *   skipped   — rows with validation errors
     *   errors    — human-readable error strings for each skipped row
     */
    public function import(UploadedFile $file, ?string $createdBy = null): array
    {
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            $result['errors'][] = 'Could not open the uploaded file.';
            return $result;
        }

        // ── Parse header row ──────────────────────────────────────────────────
        $rawHeaders = fgetcsv($handle);
        if (! $rawHeaders) {
            fclose($handle);
            $result['errors'][] = 'The CSV file is empty or has no header row.';
            return $result;
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), $rawHeaders);

        // Verify required columns are present
        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) {
                fclose($handle);
                $result['errors'][] = "Missing required column: \"{$required}\". "
                    . 'Expected headers: ip_address, description (optional).';
                return $result;
            }
        }

        $ipIdx   = array_search('ip_address',  $headers, true);
        $descIdx = array_search('description', $headers, true);

        // ── Process data rows ─────────────────────────────────────────────────
        $lineNo = 1; // header was line 1
        while (($row = fgetcsv($handle)) !== false) {
            $lineNo++;

            // Skip visually blank rows
            if (empty(array_filter($row, fn ($v) => trim($v) !== ''))) {
                continue;
            }

            $ip   = trim($row[$ipIdx] ?? '');
            $desc = $descIdx !== false ? trim($row[$descIdx] ?? '') : null;

            // ── Per-row validation ────────────────────────────────────────────
            $validator = Validator::make(
                ['ip_address' => $ip, 'description' => $desc],
                [
                    'ip_address'  => [
                        'required',
                        'max:45',
                        function ($attribute, $value, $fail) {
                            if (! AllowedIp::isValidIpOrCidr($value)) {
                                $fail("\"$value\" is not a valid IP address or CIDR range.");
                            }
                        },
                    ],
                    'description' => ['nullable', 'string', 'max:255'],
                ],
                [
                    'ip_address.required' => 'IP address is required.',
                    'ip_address.max'      => 'IP address exceeds 45 characters.',
                ]
            );

            if ($validator->fails()) {
                $result['skipped']++;
                $result['errors'][] = "Row {$lineNo}: " . $validator->errors()->first();
                continue;
            }

            // ── Upsert ────────────────────────────────────────────────────────
            $existing = AllowedIp::where('ip_address', $ip)->first();

            if ($existing) {
                $existing->update([
                    'description' => $desc ?: $existing->description,
                ]);
                $result['updated']++;
            } else {
                AllowedIp::create([
                    'ip_address'  => $ip,
                    'description' => $desc ?: null,
                    'status'      => true,
                    'created_by'  => $createdBy,
                ]);
                $result['inserted']++;
            }
        }

        fclose($handle);
        return $result;
    }

    /**
     * Generate a sample CSV template for download.
     */
    public static function templateContent(): string
    {
        return implode("\r\n", [
            'ip_address,description',
            '192.168.1.1,Main Office Gateway',
            '192.168.1.100,Branch 2 Workstation',
            '203.0.113.0/24,Office ISP block (CIDR range)',
            '2001:db8::1,IPv6 Example (optional)',
        ]);
    }
}
