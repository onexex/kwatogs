<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllowedIp extends Model
{
    use HasFactory;

    protected $table = 'allowed_ips';

    protected $fillable = [
        'ip_address',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * The user who added this IP to the allowlist.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'empID');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only active (enabled) IP entries.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Check if a given IP address is in the active allowlist.
     *
     * Entries may be either a single IP (exact match) or a CIDR range
     * (e.g. "203.0.113.0/24", "49.144.0.0/16"). CIDR support exists because
     * most PH ISPs hand out dynamic public IPs that rotate daily within a
     * fixed block — an exact-only allowlist locks employees out overnight.
     */
    public static function isAllowed(string $ip): bool
    {
        // Fast path: exact match on the indexed column (covers single-IP rows).
        $exact = static::where('ip_address', $ip)
            ->where('status', true)
            ->exists();

        if ($exact) {
            return true;
        }

        // CIDR ranges can't be matched in SQL — evaluate them in PHP.
        // Only rows containing "/" are candidates, so this set stays small.
        $cidrs = static::where('status', true)
            ->where('ip_address', 'like', '%/%')
            ->pluck('ip_address');

        foreach ($cidrs as $cidr) {
            if (self::ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether $ip falls inside $cidr. Handles both IPv4 and IPv6.
     * If $cidr has no "/", it's treated as a plain exact-match IP.
     */
    public static function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        $ipBin     = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        // Bail on malformed input, or mismatched families (IPv4 vs IPv6).
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8; // 32 for IPv4, 128 for IPv6
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainder  = $bits % 8;

        // Compare the fully-masked leading bytes.
        if ($wholeBytes > 0 && strncmp($ipBin, $subnetBin, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        // Compare the partial trailing byte under its bit mask.
        $mask = (0xFF << (8 - $remainder)) & 0xFF;

        return (ord($ipBin[$wholeBytes]) & $mask) === (ord($subnetBin[$wholeBytes]) & $mask);
    }

    /**
     * Validate that a value is either a single IP or a valid CIDR range.
     * Shared by the form request and the CSV importer.
     */
    public static function isValidIpOrCidr(string $value): bool
    {
        if (! str_contains($value, '/')) {
            return filter_var($value, FILTER_VALIDATE_IP) !== false;
        }

        [$subnet, $bits] = explode('/', $value, 2);

        if (! ctype_digit($bits)) {
            return false;
        }

        $bits = (int) $bits;

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $bits >= 0 && $bits <= 32;
        }

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $bits >= 0 && $bits <= 128;
        }

        return false;
    }
}
