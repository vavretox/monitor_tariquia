<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['nombre'];

    public function responsables()
    {
        return $this->belongsToMany(Responsable::class, 'area_responsable')->withTimestamps();
    }
}
