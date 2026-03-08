<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class ApplicationKey extends Model
{
    use HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'url',
        'status',
        'token',
    ];
}
