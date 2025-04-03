<?php

declare(strict_types=1);

namespace Forge\Traits;

use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Response;

trait SecurityHelper
{
    /**
     * @return array<<missing>,string>
     */
    private static function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }

    public static function isLoggedIn(ForgeAuthService $forgeAuthService): bool|Response
    {
        if ($forgeAuthService->user()) {
            return true;
        } else {
            return Redirect::to('/');
        }
    }
}
