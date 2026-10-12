<?php

namespace App\Services;

use App\Models\KidneyAssessment;
use App\Models\KidneyAssessmentImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * รูปภาพประกอบแบบรายงาน พชอ.ไต: สูงสุด 3 รูปต่อข้อ ต่อรายงาน.
 *
 * ทำให้รองรับภาพทุกขนาด/ทุกอุปกรณ์: ฝั่งเบราว์เซอร์ย่อภาพก่อนอัปโหลดอยู่แล้ว
 * (ดู kidney-image-uploader) และฝั่งเซิร์ฟเวอร์ย่อซ้ำอีกครั้งเมื่อมี GD -
 * ด้านยาวสุดไม่เกิน MAX_EDGE px, หมุนตาม EXIF, แปลงเป็น JPEG - เพื่อให้ไฟล์
 * เล็กพอสำหรับหน้าเว็บ, PDF และ Word. ถ้าไม่มี GD จะเก็บไฟล์ต้นฉบับตามเดิม.
 */
class KidneyImageService
{
    public const CATEGORIES = ['1', '2', '3', '4', '5', '6', '7', '8_1', '8_2', '8_3'];
    public const MAX_SLOTS = 3;
    public const MAX_EDGE = 1600;       // px ด้านยาวสุดหลังย่อ
    public const JPEG_QUALITY = 82;
    public const MAX_UPLOAD_KB = 20480; // 20 MB ต่อรูป (ก่อนย่อ)
    public const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private static ?bool $ready = null;

    /**
     * ตารางเก็บรูปพร้อมใช้งานหรือไม่ - ถ้ายังไม่มี (ยังไม่ได้รัน php artisan migrate) จะสร้างให้เอง
     * เพื่อไม่ให้รูปถูกข้ามแบบเงียบ ๆ (เดิมคืนค่า false แล้วไม่บันทึกอะไรเลย)
     */
    public static function available(): bool
    {
        if (self::$ready !== null) {
            return self::$ready;
        }

        try {
            if (!\App\Support\SchemaProbe::hasTable('kidney_assessment_images')) {
                Schema::create('kidney_assessment_images', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('kidney_assessment_id');
                    $table->string('category', 10);
                    $table->unsignedTinyInteger('slot');
                    $table->string('path');
                    $table->string('original_name')->nullable();
                    $table->string('mime', 50)->nullable();
                    $table->unsignedInteger('size')->default(0);
                    $table->unsignedSmallInteger('width')->nullable();
                    $table->unsignedSmallInteger('height')->nullable();
                    $table->unsignedBigInteger('uploaded_by')->nullable();
                    $table->timestamps();

                    $table->unique(['kidney_assessment_id', 'category', 'slot'], 'kidney_img_assessment_cat_slot_unique');
                });
                Log::info('kidney_assessment_images table created on demand');
            }
            self::$ready = \App\Support\SchemaProbe::hasTable('kidney_assessment_images');
        } catch (\Throwable $e) {
            Log::error('kidney_assessment_images unavailable: ' . $e->getMessage());
            self::$ready = false;
        }

        return self::$ready;
    }

    /** กฎ validate สำหรับ request ที่ส่ง kidney_images[cat][slot] */
    public static function validationRules(): array
    {
        return [
            'kidney_images' => 'nullable|array',
            'kidney_images.*' => 'nullable|array',
            'kidney_images.*.*' => 'nullable|file|mimes:' . implode(',', self::ALLOWED_EXT) . '|max:' . self::MAX_UPLOAD_KB,
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'kidney_images.*.*.mimes' => 'รูปภาพต้องเป็นไฟล์ JPG, PNG, WEBP หรือ GIF เท่านั้น',
            'kidney_images.*.*.max' => 'รูปภาพต้องมีขนาดไม่เกิน ' . (self::MAX_UPLOAD_KB / 1024) . ' MB ต่อรูป',
            'kidney_images.*.*.file' => 'อัปโหลดรูปภาพไม่สำเร็จ (ไฟล์อาจใหญ่เกินที่เซิร์ฟเวอร์กำหนด)',
        ];
    }

    /**
     * บันทึก/ลบรูปตามที่ฟอร์มส่งมา:
     *   kidney_images[{cat}][{slot}]         = ไฟล์ใหม่ (แทนที่รูปเดิมในช่องนั้น)
     *   kidney_images_remove[{cat}][{slot}]  = "1" เพื่อลบรูปเดิมในช่องนั้น
     */
    /**
     * @return string[] ข้อความปัญหาที่เกิดขึ้น (ว่าง = สำเร็จทั้งหมด) - ผู้เรียกควรแจ้งผู้ใช้
     */
    public function sync(KidneyAssessment $assessment, Request $request, ?int $userId = null): array
    {
        $errors = [];

        $files = (array) $request->file('kidney_images', []);
        $removes = (array) $request->input('kidney_images_remove', []);
        $hasWork = false;
        array_walk_recursive($files, function ($f) use (&$hasWork) {
            if ($f instanceof UploadedFile) {
                $hasWork = true;
            }
        });
        array_walk_recursive($removes, function ($v) use (&$hasWork) {
            if ((string) $v === '1') {
                $hasWork = true;
            }
        });

        if (!$hasWork) {
            return $errors;
        }

        if (!self::available()) {
            return ['ระบบเก็บรูปภาพยังไม่พร้อมใช้งาน (สร้างตาราง kidney_assessment_images ไม่สำเร็จ - ดู storage/logs/laravel.log)'];
        }

        foreach (self::CATEGORIES as $cat) {
            for ($slot = 1; $slot <= self::MAX_SLOTS; $slot++) {
                $file = $files[$cat][$slot] ?? null;
                $remove = ($removes[$cat][$slot] ?? '0') === '1';
                $label = 'ข้อ ' . str_replace('_', '.', $cat) . ' รูปที่ ' . $slot;

                try {
                    if ($file instanceof UploadedFile) {
                        if (!$file->isValid()) {
                            $errors[] = $label . ': อัปโหลดไม่สำเร็จ (' . $file->getErrorMessage() . ')';
                        } elseif (!$this->put($assessment, $cat, $slot, $file, $userId)) {
                            $errors[] = $label . ': บันทึกไฟล์ไม่สำเร็จ';
                        }
                    } elseif ($remove) {
                        $this->forget($assessment, $cat, $slot);
                    }
                } catch (\Throwable $e) {
                    Log::error('kidney image save failed', ['assessment' => $assessment->id, 'cat' => $cat, 'slot' => $slot, 'error' => $e->getMessage()]);
                    $errors[] = $label . ': ' . $e->getMessage();
                }
            }
        }

        return $errors;
    }

    public function put(KidneyAssessment $assessment, string $cat, int $slot, UploadedFile $file, ?int $userId = null): ?KidneyAssessmentImage
    {
        if (!in_array($cat, self::CATEGORIES, true) || $slot < 1 || $slot > self::MAX_SLOTS) {
            return null;
        }

        $processed = $this->process($file);
        $dir = 'kidney_assessments/images/' . $assessment->id;
        $name = $cat . '_' . $slot . '_' . bin2hex(random_bytes(4)) . '.' . $processed['ext'];
        $path = $dir . '/' . $name;

        if (!Storage::disk('public')->put($path, $processed['binary'])) {
            return null;
        }

        $existing = KidneyAssessmentImage::where('kidney_assessment_id', $assessment->id)
            ->where('category', $cat)->where('slot', $slot)->first();
        if ($existing) {
            Storage::disk('public')->delete($existing->path);
        }

        return KidneyAssessmentImage::updateOrCreate(
            ['kidney_assessment_id' => $assessment->id, 'category' => $cat, 'slot' => $slot],
            [
                'path' => $path,
                'original_name' => mb_substr((string) $file->getClientOriginalName(), 0, 250),
                'mime' => $processed['mime'],
                'size' => strlen($processed['binary']),
                'width' => $processed['width'],
                'height' => $processed['height'],
                'uploaded_by' => $userId,
            ]
        );
    }

    public function forget(KidneyAssessment $assessment, string $cat, int $slot): void
    {
        $img = KidneyAssessmentImage::where('kidney_assessment_id', $assessment->id)
            ->where('category', $cat)->where('slot', $slot)->first();
        if ($img) {
            Storage::disk('public')->delete($img->path);
            $img->delete();
        }
    }

    /**
     * ย่อ/หมุน/แปลงเป็น JPEG ด้วย GD (ถ้ามี) - ไม่มี GD หรือถอดรหัสไม่ได้ก็เก็บไฟล์เดิม
     *
     * @return array{binary:string,ext:string,mime:string,width:?int,height:?int}
     */
    private function process(UploadedFile $file): array
    {
        $raw = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($raw);
        $origW = $info[0] ?? null;
        $origH = $info[1] ?? null;
        $origMime = $info['mime'] ?? ($file->getMimeType() ?: 'image/jpeg');
        $origExt = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($origExt, self::ALLOWED_EXT, true)) {
            $origExt = 'jpg';
        }

        $fallback = ['binary' => $raw, 'ext' => $origExt, 'mime' => $origMime, 'width' => $origW, 'height' => $origH];

        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg') || !$origW || !$origH) {
            return $fallback;
        }
        // กัน memory พอง: ภาพใหญ่มาก (> ~60 ล้านพิกเซล) ไม่ถอดรหัสที่ฝั่งเซิร์ฟเวอร์
        if ($origW * $origH > 60000000) {
            return $fallback;
        }

        $src = @imagecreatefromstring($raw);
        if (!$src) {
            return $fallback;
        }

        // หมุนตาม EXIF (ภาพถ่ายจากมือถือ)
        if ($origMime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
            $orientation = (int) ($exif['Orientation'] ?? 1);
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0 && function_exists('imagerotate')) {
                $rotated = @imagerotate($src, $angle, 0);
                if ($rotated) {
                    imagedestroy($src);
                    $src = $rotated;
                }
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::MAX_EDGE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // พื้นขาวสำหรับ PNG/GIF โปร่งใส
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, self::JPEG_QUALITY);
        $jpeg = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        if ($jpeg === '') {
            return $fallback;
        }

        return ['binary' => $jpeg, 'ext' => 'jpg', 'mime' => 'image/jpeg', 'width' => $nw, 'height' => $nh];
    }

    /**
     * รูปของรายงานหนึ่งฉบับ จัดกลุ่มตามข้อ: ['1' => [img, img], '8_1' => [...]]
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    public static function groupedFor($assessmentId): array
    {
        if (!self::available() || !$assessmentId) {
            return [];
        }

        return KidneyAssessmentImage::where('kidney_assessment_id', $assessmentId)
            ->orderBy('slot')
            ->get()
            ->groupBy('category')
            ->all();
    }

    /** ข้อมูลรูปสำหรับ JSON ของฟอร์ม: ['1' => [{slot,url,name,size,width,height}]] */
    public static function payloadFor($assessmentId): array
    {
        $out = [];
        foreach (self::groupedFor($assessmentId) as $cat => $items) {
            $out[$cat] = $items->map(fn($i) => [
                'slot' => (int) $i->slot,
                'url' => $i->url,
                'name' => $i->original_name,
                'size' => (int) $i->size,
                'width' => $i->width,
                'height' => $i->height,
            ])->values()->all();
        }

        return $out;
    }
}
