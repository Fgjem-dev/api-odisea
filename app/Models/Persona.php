<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Persona extends Model
{
    protected $table = 't5fotos';
    protected $primaryKey = 'kcvefoto';

    public function odisea(): BelongsTo {
        return $this->belongsTo(Odisea::class, 'rcvealerta', 'ID');
    }
}
