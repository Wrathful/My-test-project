<?php

namespace Modules\WsopFantasy\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\WsopFantasy\Services\LeaderboardService;
use Modules\WsopFantasy\Services\TeamService;

/**
 * Контроллер для отображения лидерборда.
 */
class LeaderboardController extends Controller
{
    /**
     * Отображает лидерборд команд.
     *
     * @param LeaderboardService $leaderboardService
     * @return \Illuminate\Contracts\View\View
     */
    public function index(LeaderboardService $leaderboardService)
    {
        $data = $leaderboardService->getLeaderboardData();

        return view('wsop-fantasy::leaderboard.index', $data);
    }
}
