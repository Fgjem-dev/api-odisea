<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Odisea extends Model
{
    protected $table = 'TOTALODISEA';
    protected $primaryKey = 'ID';

    public function persona(): HasOne {
        return $this->hasOne(Persona::class, 'rcvealerta', 'ID');
    }
}
