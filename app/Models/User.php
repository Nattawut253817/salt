<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'prefix',
        'User_firstname',
        'User_lastname',
        'phone',
        'User_position',
        'User_rank_id',
        'Province_id',
        'District_id',
        'hos_id',
        'sh_id',
        'Con_name',
        'is_approved',
    ];

    public function subdistrictHospital()
    {
        return $this->belongsTo(SubdistrictHospital::class, 'sh_id', 'hospital_code_5_digit');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function province()
    {
        return $this->belongsTo(Province::class, 'Province_id', 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'District_id', 'district_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hos_id', 'hos_id');
    }

    public function saltAssessments()
    {
        return $this->hasMany(SaltAssessment::class, 'user_id', 'id');
    }

    public function kidneyAssessments()
    {
        return $this->hasMany(KidneyAssessment::class, 'user_id', 'id');
    }
}
