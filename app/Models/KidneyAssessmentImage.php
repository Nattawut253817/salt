<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KidneyAssessmentImage extends Model
{
    protected $fillable = [
        'kidney_assessment_id',
        'category',
        'slot',
        'path',
        'original_name',
        'mime',
        'size',
        'width',
        'height',
        'uploaded_by',
    ];

    public function assessment()
    {
        return $this->belongsTo(KidneyAssessment::class, 'kidney_assessment_id');
    }

    /** URL สาธารณะ (ผ่าน public/storage symlink) */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . ltrim($this->path, '/'));
    }
}
