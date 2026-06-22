<?php

namespace Modules\WsopFantasy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\WsopFantasy\Models\GipsyteamUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для авторизации через GipsyteamUser.
 */
class GipsyteamAuthMiddleware
{
    /**
     * Обрабатывает входящий запрос.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, что пользователь авторизован через GipsyteamUser
        $user = $request->user();

        if (!$user instanceof GipsyteamUser) {
            // Попробуем найти пользователя по ID в сессии или токену
            $userId = $request->hasSession() ? $request->session()->get('gipsyteam_user_id') : null;

            if ($userId) {
                $user = GipsyteamUser::find($userId);
                if ($user) {
                    $request->setUserResolver(fn() => $user);
                }
            }
        }

        if (!$user || !($user instanceof GipsyteamUser)) {
            if (app()->environment('local') && config('app.debug')) {
                return redirect()->route('wsop-fantasy.debug.login', ['login' => 'test']);
            }

            return redirect()->route('login')->with('error', 'Для участия в конкурсе необходимо авторизоваться.');
        }

        return $next($request);
    }
}
