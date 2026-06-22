<?php

namespace Modules\WsopFantasy\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\WsopFantasy\Models\Team;
use Modules\WsopFantasy\Services\TeamCreationService;
use Illuminate\Http\Request;

/**
 * Контроллер для управления командами WSOP Fantasy.
 */
class TeamController extends Controller
{
    /**
     * Отображает форму создания/редактирования команды.
     *
     * @param Request $request
     * @param TeamCreationService $teamCreationService
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create(Request $request, TeamCreationService $teamCreationService)
    {
        $user = $request->user();

        if (!$teamCreationService->canParticipate($user)) {
            return redirect()
                ->route('wsop-fantasy.leaderboard')
                ->withErrors( 'Вы зарегистрировались слишком поздно, поэтому вы не можете участвовать в конкурсе.');
        }

        $formData = $teamCreationService->getFormData($request->user()->id);

        return view('wsop-fantasy::team.form', $formData);
    }

    /**
     * Подает команду на участие.
     *
     * @param Request $request
     * @param TeamCreationService $teamCreationService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submit(Request $request, TeamCreationService $teamCreationService)
    {
        $user = $request->user();

        if (!$teamCreationService->canParticipate($user)) {
            return redirect()
                ->route('wsop-fantasy.leaderboard')
                ->withErrors( 'Вы зарегистрировались слишком поздно, поэтому вы не можете участвовать в конкурсе.');
        }

        // Проверяем, что команда ещё не создана
        if (Team::where('gipsyteam_user_id', $user->id)->exists()) {
            return redirect()
                ->route('wsop-fantasy.team.create')
                ->withErrors('Вы уже подали команду ранее.');
        }

        $data = $request->validate([
            'players' => ['required', 'array'],
            'captain_id' => ['required', 'integer', 'exists:wsop_players,id'],
        ]);

        $teamCreationService->createTeam($user->id, $data);

        return redirect()
            ->route('wsop-fantasy.leaderboard')
            ->with('success', 'Команда успешно подана на участие!');
    }
}
