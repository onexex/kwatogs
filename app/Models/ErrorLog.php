<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class ErrorLog extends Model
{
    protected $table = 'error_logs';

    protected $fillable = [
        'level', 'type', 'message', 'exception_class', 'file', 'line', 'code',
        'trace', 'url', 'method', 'input', 'user_id', 'user_name', 'ip',
        'user_agent', 'resolved',
    ];

    protected $casts = [
        'input'    => 'array',
        'resolved' => 'boolean',
        'line'     => 'integer',
    ];

    /**
     * Persist a detailed record of an exception so developers can browse and copy
     * it from the Error Logs screen instead of digging through storage/logs.
     *
     * Called from the global exception handler. Logging must NEVER break the
     * request (or recurse into itself), so every failure is swallowed.
     *
     * NOTE: this model deliberately does NOT use the Auditable trait — we don't
     * want an audit entry each time an error is captured.
     */
    public static function capture(Throwable $e): void
    {
        try {
            $user = auth()->user();
            $name = $user
                ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'User'))
                : 'system';

            $request = request();
            $input   = null;
            $url     = null;
            $method  = null;
            $agent   = null;
            try {
                $input  = $request->except(['password', 'password_confirmation', 'current_password', '_token']);
                $url    = $request->fullUrl();
                $method = $request->method();
                $agent  = substr((string) $request->userAgent(), 0, 255);
            } catch (\Throwable $inner) {
                // request context may be unavailable (console/queue) — leave nulls
            }

            static::create([
                'level'           => 'error',
                'type'            => class_basename($e),
                'message'         => $e->getMessage(),
                'exception_class' => get_class($e),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'code'            => (string) $e->getCode(),
                'trace'           => $e->getTraceAsString(),
                'url'             => $url,
                'method'          => $method,
                'input'           => $input,
                'user_id'         => optional($user)->id,
                'user_name'       => $name,
                'ip'              => optional($request)->ip(),
                'user_agent'      => $agent,
                'resolved'        => false,
            ]);
        } catch (\Throwable $ignore) {
            // never let error-logging break the actual request
        }
    }
}
