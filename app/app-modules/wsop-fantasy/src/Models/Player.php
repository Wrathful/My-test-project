<?php

namespace Modules\WsopFantasy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WsopFantasy\Database\Factories\PlayerFactory;

/**
 * Игрок WSOP.
 *
 * @property int $id
 * @property string $name
 * @property int $group_id
 * @property int $cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Group $group
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TeamPlayer> $teamPlayers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PoyScore> $poyScores
 */
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wsop_players';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'group_id',
        'cost',
    ];

    /**
     * Группа игрока.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Group, Player>
     */
    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Связи с командами.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<TeamPlayer>
     */
    public function teamPlayers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeamPlayer::class, 'player_id');
    }

    /**
     * POY очки игрока.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<PoyScore>
     */
    public function poyScores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PoyScore::class, 'player_id');
    }

    /**
     * Последние POY очки.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<PoyScore>
     */
    public function latestPoyScore(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PoyScore::class, 'player_id')
            ->latestOfMany('scored_at');
    }
}
