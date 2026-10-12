<?php

namespace App\Support;

use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ส่งออก Excel แบบ "ไอคอนดาวน์โหลดของเบราว์เซอร์หมุนทันทีที่กดปุ่ม"
 *
 * ปัญหาเดิม: Excel::download() สร้างไฟล์ให้เสร็จก่อนแล้วค่อยส่งอะไรกลับไป ระหว่างนั้นเบราว์เซอร์ไม่มีอะไรให้แสดง
 * (แท็บหมุนรอ แต่ไอคอนดาวน์โหลดมุมบนขวานิ่ง) และ Chrome จะเริ่มแสดงการดาวน์โหลดก็ต่อเมื่อได้รับ "เนื้อไฟล์ก้อนแรก"
 * (แค่ส่ง header ไม่พอ)
 *
 * วิธีแก้: ไฟล์ .xlsx คือไฟล์ ZIP จึงส่ง "รายการโฟลเดอร์ว่าง" (directory entry ขนาด 0 ไบต์ ซึ่งโปรแกรมที่เปิด
 * ZIP/Excel ปล่อยผ่านตามปกติ) ออกไปเป็นก้อนแรกทันทีที่กดปุ่ม เบราว์เซอร์จึงเริ่มดาวน์โหลดและไอคอนหมุนทันที
 * จากนั้นค่อยสร้างไฟล์จริง แล้วส่งต่อโดยปรับ offset ใน central directory ให้ตรง ข้อมูลทุกไฟล์ในแพ็กเกจคงเดิมทุกไบต์
 */
class EarlyDownload
{
    /** ชื่อโฟลเดอร์ว่างที่ส่งเป็นก้อนแรก (ไม่ซ้ำกับสิ่งที่ PhpSpreadsheet สร้าง) */
    private const DECOY_DIR = 'customXml/';

    /**
     * @param  string   $fileName    ชื่อไฟล์ .xlsx
     * @param  \Closure $makeExport  คืนค่า export object (ทำงานหลังส่งก้อนแรกไปแล้ว)
     */
    public static function excel(string $fileName, \Closure $makeExport): StreamedResponse
    {
        return response()->streamDownload(function () use ($fileName, $makeExport) {
            @set_time_limit(0);
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            [$localHeader, $centralRecord] = self::decoyRecords(self::DECOY_DIR);

            // 1) ก้อนแรก -> เบราว์เซอร์เริ่มแสดงสถานะ "กำลังดาวน์โหลด" ทันที
            echo $localHeader;
            @flush();

            // 2) สร้างไฟล์จริง (ขั้นตอนที่ใช้เวลานาน)
            $path = Excel::download($makeExport(), $fileName)->getFile()->getPathname();

            // 3) ส่งไฟล์ที่เหลือ พร้อมแก้ central directory
            try {
                self::emitWithPrefix($path, strlen($localHeader), $centralRecord, function (string $bytes) {
                    echo $bytes;
                    @flush();
                });
            } finally {
                @unlink($path);
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no', // กัน nginx พักข้อมูลไว้ก่อนส่ง
        ]);
    }

    /**
     * local header + central directory record ของ "โฟลเดอร์ว่าง" (stored, ขนาด 0)
     * central record ชี้ offset 0 เพราะ local header อยู่ต้นไฟล์
     *
     * @return array{0:string,1:string}
     */
    public static function decoyRecords(string $dirName): array
    {
        $time = 0;
        $date = (1980 - 1980) << 9 | 1 << 5 | 1; // 1980-01-01 ให้ผลลัพธ์คงที่
        $nameLen = strlen($dirName);

        $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $time, $date, 0, 0, 0, $nameLen, 0) . $dirName;
        $central = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $time, $date, 0, 0, 0, $nameLen, 0, 0, 0, 0, 0x10, 0) . $dirName;

        return [$local, $central];
    }

    /**
     * ส่งไฟล์ ZIP ที่สร้างเสร็จแล้ว โดยถือว่ามี $prefixLen ไบต์ (local header ของโฟลเดอร์ว่าง) ถูกส่งไปก่อนแล้ว
     * ส่วนข้อมูลไฟล์ในแพ็กเกจคงเดิม ปรับเฉพาะ central directory + end record
     */
    public static function emitWithPrefix(string $path, int $prefixLen, string $decoyCentral, callable $emit): void
    {
        $size = filesize($path);
        $fh = fopen($path, 'rb');
        if (!$fh || $size < 22) {
            throw new \RuntimeException('ไม่พบไฟล์ส่งออก');
        }

        // หา End Of Central Directory (ท้ายไฟล์, อาจมี comment ได้สูงสุด 65535 ไบต์)
        $tailLen = (int) min($size, 65557);
        fseek($fh, $size - $tailLen);
        $tail = fread($fh, $tailLen);
        $pos = strrpos($tail, "PK\x05\x06");
        if ($pos === false) {
            throw new \RuntimeException('ไฟล์ ZIP ไม่ถูกต้อง');
        }
        $eocd = unpack('Vsig/vdisk/vcdDisk/ventriesDisk/ventries/VcdSize/VcdOffset/vcommentLen', substr($tail, $pos, 22));
        $comment = substr($tail, $pos + 22, $eocd['commentLen']);
        if ($eocd['cdOffset'] === 0xFFFFFFFF || $eocd['entries'] === 0xFFFF) {
            throw new \RuntimeException('ZIP64 ไม่รองรับ');
        }

        // 1) เนื้อไฟล์ทั้งหมดก่อน central directory (ไม่แตะต้อง)
        fseek($fh, 0);
        $remaining = $eocd['cdOffset'];
        while ($remaining > 0) {
            $chunk = fread($fh, (int) min(1048576, $remaining));
            if ($chunk === false || $chunk === '') {
                throw new \RuntimeException('อ่านไฟล์ไม่ครบ');
            }
            $emit($chunk);
            $remaining -= strlen($chunk);
        }

        // 2) central directory: เลื่อน offset ของทุกรายการเดิมไป +$prefixLen
        fseek($fh, $eocd['cdOffset']);
        $cd = fread($fh, $eocd['cdSize']);
        $newCd = $decoyCentral;
        $p = 0;
        $n = strlen($cd);
        while ($p < $n) {
            if (substr($cd, $p, 4) !== "PK\x01\x02") {
                throw new \RuntimeException('central directory ไม่ถูกต้อง');
            }
            $h = unpack('vnameLen/vextraLen/vcommentLen', substr($cd, $p + 28, 6));
            $recLen = 46 + $h['nameLen'] + $h['extraLen'] + $h['commentLen'];
            $rec = substr($cd, $p, $recLen);
            $off = unpack('V', substr($rec, 42, 4))[1] + $prefixLen;
            $newCd .= substr($rec, 0, 42) . pack('V', $off) . substr($rec, 46);
            $p += $recLen;
        }
        fclose($fh);

        // 3) End Of Central Directory ใหม่
        $newEocd = pack('VvvvvVVv', 0x06054b50, 0, 0, $eocd['entries'] + 1, $eocd['entries'] + 1, strlen($newCd), $eocd['cdOffset'] + $prefixLen, strlen($comment)) . $comment;

        $emit($newCd . $newEocd);
    }
}
