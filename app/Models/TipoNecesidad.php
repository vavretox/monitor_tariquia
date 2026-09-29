<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoNecesidad extends Model
{
    protected $table = 'tipos_necesidad';

    protected $fillable = ['nombre'];

    public function necesidades(): HasMany
    {
        return $this->hasMany(Necesidad::class, 'tipo_necesidad_id');
    }
}