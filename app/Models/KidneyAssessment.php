<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KidneyAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fiscal_year',
        'quarter',
        'operating_area',
        'operating_area_pin_id',
        'operating_area_province_id',
        'operating_area_district_id',
        'operating_area_tambon_id',
        'operating_area_village_id',
        'operating_area_village_name_snapshot',
        'operating_area_latitude',
        'operating_area_longitude',
        'category_1',
        'category_2',
        'category_3',
        'category_4',
        'category_5',
        'category_6',
        'category_7',
        'category_8_1',
        'category_8_2',
        'category_8_3',
        'category_1_file',
        'category_2_file',
        'category_3_file',
        'category_4_file',
        'category_5_file',
        'category_6_file',
        'category_7_file',
        'category_8_1_file',
        'category_8_2_file',
        'category_8_3_file',
        'problems_obstacles',
        'recommendations_opportunities',
        'reporter_metadata',
        'is_read',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'reporter_metadata' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** รูปภาพประกอบ (สูงสุด 3 รูปต่อข้อ) */
    public function images()
    {
        return $this->hasMany(KidneyAssessmentImage::class, 'kidney_assessment_id')
            ->orderBy('category')
            ->orderBy('slot');
    }

    protected static function booted()
    {
        // ลบรายงาน -> ลบไฟล์รูปและแถวรูปภาพของรายงานนั้นด้วย
        static::deleting(function (KidneyAssessment $assessment) {
            if (!\Illuminate\Support\Facades\Schema::hasTable('kidney_assessment_images')) {
                return;
            }
            foreach (KidneyAssessmentImage::where('kidney_assessment_id', $assessment->id)->get() as $img) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($img->path);
                $img->delete();
            }
        });
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * The registered operating-area pin this assessment was filed under,
     * when the reporter picked one of Level 1's existing pins rather than
     * placing an ad hoc one of their own. FK-less, like every other
     * location column on this model - see the migration that added these
     * columns.
     */
    public function operatingAreaPin()
    {
        return $this->belongsTo(OperatingAreaPin::class, 'operating_area_pin_id');
    }
}
