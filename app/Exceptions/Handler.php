<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Record real server-side errors into the error_logs table so they can be
            // browsed/copied from the Error Logs screen. Falls through (no ->stop()),
            // so default file logging to storage/logs/laravel.log is unchanged.
            if ($this->shouldLogToDatabase($e)) {
                \App\Models\ErrorLog::capture($e);
            }
        });
    }

    /**
     * Filter out noise so only genuine bugs are stored: skip 404s, method-not-allowed,
     * auth/access denials, CSRF/token mismatches, validation errors, and any HTTP
     * exception below 500. Everything else (real crashes) is captured.
     */
    protected function shouldLogToDatabase(Throwable $e): bool
    {
        if ($this->shouldntReport($e)) {
            return false;
        }
        if ($e instanceof NotFoundHttpException
            || $e instanceof MethodNotAllowedHttpException
            || $e instanceof AccessDeniedHttpException
            || $e instanceof TokenMismatchException
            || $e instanceof ValidationException
            || $e instanceof AuthenticationException) {
            return false;
        }
        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return false;
        }
        return true;
    }
}
