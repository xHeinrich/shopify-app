<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'token'
    ];

    protected $hidden = [
        'token'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user() {
        return $this->hasOne('App\User');
    }

    /**
     * Login the shop admin to manage dashboard
     */
    public function login() {
        $user = $this->user()->first();
        if(auth()->check()) {
            auth()->logout();
        }
        auth()->login($user);
    }
}
