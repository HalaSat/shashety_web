<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscoverCategory extends Model
{
    public function category()
    {
        return $this->hasOne(Categorie::class, 'categorie_id');
    }
}
