<?php

namespace Hamadou\Fundry\Tests\Helpers;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Hamadou\Fundry\Traits\HasWallets;

class TestUser extends Authenticatable
{
    use HasWallets;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
