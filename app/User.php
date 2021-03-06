<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;




//use Illuminate\Database\Eloquent\SoftDeletes;



class User extends \TCG\Voyager\Models\User
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','api_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];





    /* check in vendor/laravel/src/Bidge/UserRepository */

    /**
     *
     * @param  string  $username
     * @return null|App\User
     */
  

    public function findForPassport($username)
    {
      
       return $this->where('email', $username)->first();;
    }

    public function validateForPassportPasswordGrant($password)
    {
        return $this->where('password', $password)->first();
    }

   

}
