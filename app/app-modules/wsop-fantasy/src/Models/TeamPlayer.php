<?php

namespace Modules\WsopFantasy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\WsopFantasy\Database\Factories\TeamPlayerFactory;

/**
 * Связь игрока и команды.
 *
 * @property int $id
 * @property int $team_id
 * @property int $player_id
 * @property bool $is_captain
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Player $player
 */
class TeamPlayer extends Model
{
    /** @use HasFactory<TeamPlayerFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wsop_team_players';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'player_id',
        'is_captain',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_captain' => 'boolean',
        ];
    }

    /**
     * Команда.
     *
     * @return BelongsTo<Team, TeamPlayer>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Игрок.
     *
     * @return BelongsTo<Player, TeamPlayer>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
