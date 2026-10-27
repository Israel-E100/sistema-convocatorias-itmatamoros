<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plaza extends Model
{
    public const CODES_BY_TIME = [
        'Tiempo completo' => ['E3817', 'E3815', 'E3813', 'E3811', 'E3809', 'E3807'],
        '3/4 tiempo' => ['E3717', 'E3715', 'E3713', 'E3711', 'E3709', 'E3707'],
        '1/2 Tiempo' => ['E3617', 'E3615', 'E3613', 'E3611', 'E3609', 'E3607'],
    ];

    public const STATUS_OCCUPIED = 'ocupada';

    public const STATUS_AVAILABLE = 'disponible';

    public const STATUS_PROMOTION_PENDING = 'ascenso_pendiente';

    public const STATUS_RESERVED = 'reservada';

    protected $fillable = [
        'codigo',
        'tipo_tiempo',
        'orden',
        'user_id',
        'ofrecida_para_ascenso',
        'reservada_por',
    ];

    protected function casts(): array
    {
        return [
            'ofrecida_para_ascenso' => 'boolean',
        ];
    }

    public function ocupante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function interesadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reservada_por')->withTrashed();
    }
}
