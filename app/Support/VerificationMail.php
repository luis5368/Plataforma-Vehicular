<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class VerificationMail
{
    public static function dispatch(callable $send, int $userId): bool
    {
        try {
            $send();

            return true;
        } catch (Throwable $exception) {
            // Never log transport messages: they can include credentials or signed links.
            Log::warning('No se pudo enviar la notificación de verificación.', [
                'user_id' => $userId,
                'exception_type' => $exception::class,
            ]);

            return false;
        }
    }
}
