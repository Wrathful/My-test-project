<?php

namespace Modules\WsopFantasy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WsopFantasy\Database\Factories\GipsyteamUserFactory;

/**
 * Пользователь GipsyTeam для конкурса WSOP Fantasy.
 *
 * @property int $id
 * @property string $login
 * @property \Illuminate\Support\Carbon|null $insert_datetime
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Team> $teams
 */
class GipsyteamUser extends Model
{
    /** @use HasFactory<GipsyteamUserFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gipsyteam_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'login',
        'insert_datetime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'insert_datetime' => 'datetime',
        ];
    }

    /**
     * Команды пользователя.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Team>
     */
    public function teams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Team::class, 'gipsyteam_user_id');
    }
}
