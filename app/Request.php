<?php

namespace App;

use App\Traits\UsesUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use UsesUUID, HasFactory;

    const CREATED_AT = 'requested_at';
    const UPDATED_AT = 'played_at';

    public function media()
    {
        return $this->belongsTo('App\Media');
    }

    public function users()
    {
        return $this->belongsToMany('App\User');
    }
}
