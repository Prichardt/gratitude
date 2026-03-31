<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class ApplicationKey extends Authenticatable
{
    use HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'url',
        'status',
        'token',
    ];
}
