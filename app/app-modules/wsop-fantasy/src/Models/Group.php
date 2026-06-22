<?php

namespace Modules\WsopFantasy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WsopFantasy\Database\Factories\GroupFactory;

/**
 * Группа игроков WSOP.
 *
 * @property int $id
 * @property string $name
 * @property int $max_players_per_team
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Player> $players
 */
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wsop_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'max_players_per_team',
    ];

    /**
     * Игроки в группе.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Player>
     */
    public function players(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Player::class, 'group_id');
    }
}
