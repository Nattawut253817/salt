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
        'Tambon_id',
        'hos_id',
        'sh_id',
        'Con_name',
        'is_approved',
        'first_login_at',
    ];

    /**
     * SuperAdmin = ผู้ใช้ สคร. (ระดับ 1) ที่อีเมลอยู่ในรายการ config('app.super_admin_emails')
     * เห็นและจัดการได้ทุกเมนู และเป็นผู้เดียวที่เข้าเมนู "จัดการระบบ" ได้
     */
    public function isSuperAdmin(): bool
    {
        if ((int) $this->User_rank_id !== 1) {
            return false;
        }

        return $this->isRootSuperAdmin() || $this->isGrantedSuperAdmin();
    }

    /**
     * "บัญชีตัวแทนหน่วยงาน" ที่ระบบสร้างให้อัตโนมัติตอนนำเข้า Word ของหน่วยงานที่ยังไม่มีบัญชี
     * (ดู AdminController::ensureKidneyDhbTargetAgency) - อีเมลขึ้นต้น import-r และลงท้าย @import.invalid
     * ล็อกอินไม่ได้ และเห็นได้เฉพาะ SuperAdmin
     */
    public const IMPORT_PLACEHOLDER_EMAIL_LIKE = 'import-r%@import.invalid';

    public function isImportPlaceholder(): bool
    {
        $mail = strtolower((string) $this->email);

        return str_starts_with($mail, 'import-r') && str_ends_with($mail, '@import.invalid');
    }

    public function scopeWithoutImportPlaceholders($query)
    {
        return $query->where('email', 'not like', self::IMPORT_PLACEHOLDER_EMAIL_LIKE);
    }

    /**
     * ตัด SuperAdmin และบัญชีตัวแทนหน่วยงาน (นำเข้า Word) ออกจากผลลัพธ์
     * - ใช้กับรายชื่อที่แสดงให้ผู้ที่ไม่ใช่ SuperAdmin เห็น
     */
    public function scopeWithoutSuperAdmins($query)
    {
        $query->withoutImportPlaceholders();

        $emails = array_values(array_unique(array_filter(array_map('strtolower', array_merge(
            (array) config('app.super_admin_emails', []),
            (array) \App\Support\SystemSettings::get('super_admin_granted', [])
        )))));

        if (empty($emails)) {
            return $query;
        }

        return $query->where(function ($q) use ($emails) {
            $q->where('User_rank_id', '!=', 1)
              ->orWhereNull('User_rank_id')
              ->orWhereNotIn(\DB::raw('LOWER(email)'), $emails);
        });
    }

    /**
     * SuperAdmin หลัก - อีเมลที่กำหนดใน config/.env (SUPER_ADMIN_EMAILS)
     * ถอดสิทธิ์จากหน้าเว็บไม่ได้
     */
    public function isRootSuperAdmin(): bool
    {
        if ((int) $this->User_rank_id !== 1) {
            return false;
        }

        $emails = array_map('strtolower', (array) config('app.super_admin_emails', []));

        return in_array(strtolower((string) $this->email), $emails, true);
    }

    /**
     * SuperAdmin ที่ SuperAdmin คนอื่นมอบสิทธิ์ให้ผ่านหน้า "จัดการผู้ใช้งาน"
     * (เก็บอีเมลใน SystemSettings คีย์ super_admin_granted)
     */
    public function isGrantedSuperAdmin(): bool
    {
        if ((int) $this->User_rank_id !== 1) {
            return false;
        }

        $emails = array_map('strtolower', (array) \App\Support\SystemSettings::get('super_admin_granted', []));

        return in_array(strtolower((string) $this->email), $emails, true);
    }

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
        'first_login_at' => 'datetime',
    ];
    public function province()
    {
        return $this->belongsTo(Province::class, 'Province_id', 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'District_id', 'district_id');
    }

    public function tambon()
    {
        return $this->belongsTo(Tambon::class, 'Tambon_id', 'tambon_id');
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
