<?php

namespace Modules\WsopFantasy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WsopFantasy\Database\Factories\TeamFactory;

/**
 * Команда пользователя в конкурсе WSOP Fantasy.
 *
 * @property int $id
 * @property int $gipsyteam_user_id
 * @property string|null $name
 * @property int $total_score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read GipsyteamUser $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TeamPlayer> $teamPlayers
 */
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wsop_teams';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gipsyteam_user_id',
        'name',
        'total_score',
    ];

    /**
     * Пользователь, которому принадлежит команда.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<GipsyteamUser, Team>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GipsyteamUser::class, 'gipsyteam_user_id');
    }

    /**
     * Игроки в команде.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<TeamPlayer>
     */
    public function teamPlayers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeamPlayer::class, 'team_id');
    }
}
