<?php

namespace Modules\WsopFantasy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WsopFantasy\Database\Factories\PoyScoreFactory;

/**
 * POY очки игрока.
 *
 * @property int $id
 * @property int $player_id
 * @property int $score
 * @property \Illuminate\Support\Carbon $scored_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Player $player
 */
class PoyScore extends Model
{
    /** @use HasFactory<PoyScoreFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wsop_poy_scores';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'player_id',
        'score',
        'scored_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scored_at' => 'timestamp',
        ];
    }

    /**
     * Игрок, которому принадлежат очки.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Player, PoyScore>
     */
    public function player(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
