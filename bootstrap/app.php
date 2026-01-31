<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use App\Exceptions\ApiException;
use App\Exceptions\BusinessException;
use App\Exceptions\DatabaseException;
use App\Exceptions\IntegrationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware (tum isteklere uygulanir)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Web middleware
        $middleware->web(append: [
            \App\Http\Middleware\PanelMiddleware::class,
        ]);

        // Exclude webhook ve OAuth endpoint'lerini CSRF verification'dan
        $middleware->validateCsrfTokens(except: [
            'oauth/token',
            'webhooks/*',
            'voip/webhook',
            'voip/connect/*',
        ]);

        // Middleware aliases
        $middleware->alias([
            'webhook.validate' => \App\Http\Middleware\ValidateWebhookSignature::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Kurye guard için özel yönlendirme
        $middleware->redirectGuestsTo(function (Request $request) {
            // Kurye rotalarına erişmeye çalışan giriş yapmamış kullanıcıları kurye login'e yönlendir
            if ($request->is('kurye', 'kurye/*')) {
                return route('kurye.login');
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Kurye authentication exception'ları için özel yönlendirme
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('kurye', 'kurye/*') || in_array('courier', $e->guards())) {
                return redirect()->guest(route('kurye.login'));
            }

            // API istekleri için JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Oturum açmanız gerekiyor',
                    ],
                ], 401);
            }
        });

        // Model bulunamadı hatası
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());
                $modelNames = [
                    'Order' => 'Sipariş',
                    'Courier' => 'Kurye',
                    'Customer' => 'Müşteri',
                    'Branch' => 'Şube',
                    'User' => 'Kullanıcı',
                    'Product' => 'Ürün',
                    'Integration' => 'Entegrasyon',
                ];
                $name = $modelNames[$model] ?? $model;

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => "{$name} bulunamadı",
                    ],
                ], 404);
            }
        });

        // 404 Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'İstenen kaynak bulunamadı',
                    ],
                ], 404);
            }
        });

        // Method not allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'METHOD_NOT_ALLOWED',
                        'message' => 'HTTP metodu desteklenmiyor',
                    ],
                ], 405);
            }
        });

        // Rate limiting
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'TOO_MANY_REQUESTS',
                        'message' => "Çok fazla istek. {$retryAfter} saniye sonra tekrar deneyin.",
                    ],
                    'meta' => [
                        'retry_after' => (int) $retryAfter,
                    ],
                ], 429);
            }
        });

        // Validation hatası
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_FAILED',
                        'message' => 'Doğrulama hatası',
                        'details' => $e->errors(),
                    ],
                ], 422);
            }
        });

        // Database query hatası
        $exceptions->render(function (QueryException $e, Request $request) {
            \Log::error('Database Query Exception', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                // Duplicate entry (MySQL error code 1062)
                if (str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == 23000) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'DUPLICATE_ENTRY',
                            'message' => 'Bu kayıt zaten mevcut',
                        ],
                    ], 422);
                }

                // Foreign key constraint (MySQL error code 1451, 1452)
                if (str_contains($e->getMessage(), 'foreign key constraint')) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'FOREIGN_KEY_VIOLATION',
                            'message' => 'Bu kayıt başka kayıtlarla ilişkili olduğu için işlem yapılamaz',
                        ],
                    ], 422);
                }

                // Generic database error (hide details in production)
                $message = app()->environment('production')
                    ? 'Veritabanı hatası oluştu'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'DATABASE_ERROR',
                        'message' => $message,
                    ],
                ], 500);
            }
        });

        // Generic exception handler for production
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Custom exception'lar kendi render metodlarını kullanır
            if ($e instanceof ApiException || $e instanceof BusinessException ||
                $e instanceof DatabaseException || $e instanceof IntegrationException) {
                return null; // Let them handle themselves
            }

            if (($request->expectsJson() || $request->is('api/*')) && app()->environment('production')) {
                \Log::error('Unhandled Exception', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SERVER_ERROR',
                        'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
                    ],
                ], 500);
            }
        });
    })->create();
