<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Posts;
use App\Models\Comment;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes; // Add SoftDeletes trait

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'account_expiration_date',
        'status',
        'type',
        'image',
        'profile_image', // Add profile_image to the fillable array
        'password',
        'role_as',
        'email_verified_at',
        'is_active', // Add is_active to the fillable attributes
    ];

    protected $dates = ['account_expiration_date']; // Add account_expiration_date to dates array

    // Define a boot method to listen for updated events
    public static function boot()
    {
        parent::boot();

        // Listen for the updated event
        static::updated(function ($user) {
            // If the status of the user changes, update the is_active field of related categories
            if ($user->isDirty('status')) {
                $user->categories()->update(['is_active' => $user->status]);
            }
        });
    }

    // Define the relationship with categories
    public function categories()
    {
        return $this->hasMany(Posts::class);
    }

    // Define an accessor for the 'type' attribute
    public function getTypeAttribute($value)
    {
        $userTypes = ["user", "admin", "business"];

        if ($value !== null && isset($userTypes[$value])) {
            return $userTypes[$value];
        } else {
            return null;
        }
    }

    // Define a mutator for the 'type' attribute
    public function setTypeAttribute($value)
    {
        if ($value === 'business') {
            $this->attributes['type'] = 2; // Set type to 2 if the user is a business
        } else {
            $this->attributes['type'] = $value;
        }
    }

    public function setStatusAttribute($value)
    {
        // Set the status attribute
        $this->attributes['status'] = $value;
    }

    public function setAccountExpirationDateAttribute($value)
    {
        if ($this->type === 'business') {
            $this->attributes['account_expiration_date'] = $value ? Carbon::parse($value) : null;
        } else {
            $this->attributes['account_expiration_date'] = $value;
        }
    }

    // Define an accessor for the 'role_as' attribute
    public function getRoleAsAttribute($value)
    {
        $roles = ['user' => 0, 'admin' => 1, 'business' => 2];

        foreach ($roles as $role => $type) {
            if ($this->type == $role) {
                return $role;
            }
        }

        return null;
    }

    // Define a mutator for the 'role_as' attribute
    public function setRoleAsAttribute($value)
    {
        $this->attributes['role_as'] = $value;
    }

    // Define the relationship with comments
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }


    public function posts()
    {
        return $this->hasMany(Posts::class, 'user_id');
    }
}
