<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use Generator;
use XMLReader;
use ZipArchive;

/**
 * อ่านชีตแรกของไฟล์ .xlsx แบบสตรีม (ทีละแถว) โดยไม่ผ่าน PhpSpreadsheet
 *
 * ไฟล์หลักหมื่นแถวผ่าน PhpSpreadsheet ใช้ทั้งเวลาและหน่วยความจำมาก (การอ่านแบบแบ่งก้อนต้องแกะ XML
 * ซ้ำทุกก้อน) ตัวนี้อ่านครั้งเดียวจบ เร็วกว่าหลายสิบเท่า และใช้หน่วยความจำเกือบคงที่
 *
 * คืนค่าแต่ละแถวเป็น array ที่ index ตามตำแหน่งคอลัมน์ (0 = A) ช่องว่างเป็น null
 * ตัวเลขคืนเป็น int/float (วันที่แบบตัวเลข Excel ก็เป็นตัวเลขเหมือนกัน - ผู้เรียกแปลงเอง)
 */
final class XlsxRowStream
{
    /** @return Generator<int, array<int, mixed>> */
    public static function rows(string $path): Generator
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('ไม่สามารถเปิดไฟล์ Excel ได้ (ไฟล์อาจเสียหรือไม่ใช่ .xlsx)');
        }

        try {
            $sheetEntry = self::firstSheetEntry($zip);
            $shared = self::sharedStrings($zip);
        } finally {
            $zip->close();
        }

        $reader = new XMLReader();
        if (!$reader->open('zip://' . $path . '#' . $sheetEntry)) {
            throw new \RuntimeException('ไม่พบชีตข้อมูลในไฟล์ Excel');
        }

        $doc = new DOMDocument();
        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $node = $reader->expand($doc);
                $row = [];
                $max = -1;
                foreach ($node->childNodes as $cell) {
                    if ($cell->nodeType !== XML_ELEMENT_NODE || $cell->localName !== 'c') {
                        continue;
                    }
                    $idx = self::columnIndex($cell->getAttribute('r'), $max + 1);
                    $value = self::cellValue($cell, $shared);
                    $row[$idx] = $value;
                    if ($idx > $max) {
                        $max = $idx;
                    }
                }

                // เติมช่องว่างให้ครบทุกตำแหน่ง แล้วเรียงตาม index
                $full = [];
                for ($i = 0; $i <= $max; $i++) {
                    $full[$i] = $row[$i] ?? null;
                }

                yield $full;
            }
        } finally {
            $reader->close();
        }
    }

    private static function firstSheetEntry(ZipArchive $zip): string
    {
        $default = 'xl/worksheets/sheet1.xml';
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb === false || $rels === false) {
            return $default;
        }

        if (!preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $wb, $m)) {
            return $default;
        }
        $rid = $m[1];

        if (!preg_match_all('/<Relationship\b[^>]*>/', $rels, $all)) {
            return $default;
        }
        foreach ($all[0] as $tag) {
            if (str_contains($tag, 'Id="' . $rid . '"') && preg_match('/Target="([^"]+)"/', $tag, $t)) {
                $target = ltrim($t[1], '/');

                return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }

        return $default;
    }

    /** @return array<int, string> */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        $reader = new XMLReader();
        $reader->XML($xml);
        $doc = new DOMDocument();
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $si = $reader->expand($doc);
                $text = '';
                foreach ($si->childNodes as $child) {
                    if ($child->nodeType !== XML_ELEMENT_NODE) {
                        continue;
                    }
                    if ($child->localName === 't') {
                        $text .= $child->textContent;
                    } elseif ($child->localName === 'r') {
                        foreach ($child->childNodes as $rc) {
                            if ($rc->nodeType === XML_ELEMENT_NODE && $rc->localName === 't') {
                                $text .= $rc->textContent;
                            }
                        }
                    }
                }
                $strings[] = $text;
            }
        }
        $reader->close();

        return $strings;
    }

    private static function columnIndex(string $ref, int $fallback): int
    {
        if ($ref === '' || !preg_match('/^([A-Z]+)/', $ref, $m)) {
            return $fallback;
        }
        $n = 0;
        foreach (str_split($m[1]) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    private static function cellValue(\DOMElement $cell, array $shared)
    {
        $type = $cell->getAttribute('t');

        if ($type === 'inlineStr') {
            return $cell->textContent === '' ? null : $cell->textContent;
        }

        $v = null;
        foreach ($cell->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'v') {
                $v = $child->textContent;
                break;
            }
        }
        if ($v === null || $v === '') {
            return null;
        }

        switch ($type) {
            case 's':
                return $shared[(int) $v] ?? null;
            case 'b':
                return $v === '1';
            case 'e':
                return null;
            case 'str':
            case 'd':
                return $v;
            default:
                if (!is_numeric($v)) {
                    return $v;
                }

                return (str_contains($v, '.') || stripos($v, 'e') !== false) ? (float) $v : (int) $v;
        }
    }
}
