<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\WsopFantasy\Http\Controllers\TeamController;
use Modules\WsopFantasy\Http\Controllers\LeaderboardController;
use Modules\WsopFantasy\Http\Middleware\GipsyteamAuthMiddleware;
use Modules\WsopFantasy\Models\GipsyteamUser;

Route::middleware(['web', GipsyteamAuthMiddleware::class])->group(function () {
    Route::get('/wsop-2026-fantasy', [TeamController::class, 'create'])->name('wsop-fantasy.team.create');
    Route::post('/wsop-2026-fantasy/submit', [TeamController::class, 'submit'])->name('wsop-fantasy.team.submit');
});

Route::get('/wsop-2026-fantasy/leaderboard', [LeaderboardController::class, 'index'])->name('wsop-fantasy.leaderboard');

if (app()->environment('local') && config('app.debug')) {
    Route::get('/__wsop-fantasy-login/{login}', function (Request $request, string $login) {
        $login = trim($login);

        if ($login === '' || mb_strlen($login) > 50) {
            abort(404);
        }

        $user = GipsyteamUser::firstOrCreate(
            ['login' => $login],
            ['insert_datetime' => now()]
        );

        $request->session()->put('gipsyteam_user_id', $user->id);

        return redirect()->route('wsop-fantasy.team.create');
    })->middleware('web')->name('wsop-fantasy.debug.login');
}
