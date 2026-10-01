<?php

namespace App\Services;

use ZipArchive;
use DOMDocument;
use DOMXPath;

/**
 * Parses a "แบบรายงานการดำเนินงาน" (SDA0902 - ร้อยละเครือข่ายเป้าหมายที่
 * ดำเนินการลดการบริโภคเกลือโซเดียมตามแนวทางที่กำหนด) Word (.docx) report
 * into the same field shape the admin.report-progress form uses
 * (ans_1_detail..ans_4_detail, ans_5_1_detail..ans_5_5_detail, problems,
 * suggestions), so a historical report can be dropped in, previewed, and
 * edited in that form before saving through the normal store endpoint -
 * no separate import/save path, no new database writes here. Mirrors
 * App\Services\KidneyDhbWordParser's approach for the พชอ.ไต form.
 *
 * A .docx is just a zip of XML parts; this reads word/document.xml
 * directly with ZipArchive + DOMDocument rather than pulling in a full
 * Word-processing library, since the one template this targets is fixed
 * and small (two tables, a handful of labelled paragraphs, three
 * Wingdings checkboxes for the reporting round).
 */
class SaltAssessmentWordParser
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * A short prefix of the form's own header title - used only to warn
     * when an uploaded file doesn't look like this template at all,
     * rather than silently returning a mostly-empty parse.
     */
    private const TEMPLATE_SIGNATURE = 'SDA0902';

    /**
     * This template checkboxes 6/9/12-month rounds only (no 3-month
     * option) - position in that checkbox row maps to the system's
     * quarter values in this fixed order.
     */
    private const QUARTER_CHECKBOX_ORDER = ['2', '3', '4'];

    private const WINGDINGS_CHECKED = 'F0FE';

    /**
     * The 9 form fields this template's two tables carry, keyed exactly
     * as resources/views/pages/partials/report-progress-content.blade.php
     * names its textareas (ans_{key}_detail). Table 1 (items 1-4) matches
     * a leading "N." on the row's FIRST column; table 2 (items 5.1-5.5)
     * matches the distinctive "5.N" substring anywhere in that same
     * column, since the template's own "5." row header and its "5.1" sub
     * item share one multi-line cell.
     */
    private const SUB5_NEEDLES = [
        '5.1' => '5_1',
        '5.2' => '5_2',
        '5.3' => '5_3',
        '5.4' => '5_4',
        '5.5' => '5_5',
    ];

    /**
     * @param string $path Local filesystem path to the uploaded .docx
     * @return array{
     *   is_valid_template: bool,
     *   quarter: ?string,
     *   quarter_detected: bool,
     *   fields: array<string, string>,
     *   unmatched: array<int, array{heading: string, text: string}>,
     *   reporter_summary: ?string,
     *   reporter_name: ?string,
     *   reporter_position: ?string,
     *   reporter_phone: ?string,
     *   reporter_email: ?string,
     *   agency_name: ?string,
     *   warnings: array<int, string>,
     * }
     */
    public function parse(string $path): array
    {
        $warnings = [];

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('ไม่สามารถเปิดไฟล์นี้ได้ - อาจไม่ใช่ไฟล์ .docx ที่สมบูรณ์');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('ไม่พบเนื้อหาเอกสารในไฟล์นี้ (word/document.xml หายไป) - อาจไม่ใช่ไฟล์ Word ที่ถูกต้อง');
        }

        $dom = new DOMDocument();
        $prevErrorSetting = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_use_internal_errors($prevErrorSetting);

        if (!$loaded) {
            throw new \RuntimeException('ไม่สามารถอ่านโครงสร้างไฟล์ Word นี้ได้ (XML ผิดรูปแบบ)');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);

        // --- Whole-body plain text (paragraph by paragraph), and a
        //     parallel per-paragraph plain-text list for section slicing. ---
        $bodyParagraphs = [];
        foreach ($xpath->query('//w:body/w:p') as $p) {
            $bodyParagraphs[] = $this->paragraphText($p, $xpath);
        }
        $bodyText = implode("\n", $bodyParagraphs);

        $isValidTemplate = mb_strpos($bodyText, self::TEMPLATE_SIGNATURE) !== false;
        if (!$isValidTemplate) {
            $warnings[] = 'ไม่พบข้อความ "SDA0902" ในไฟล์นี้ - อาจเป็นเอกสารคนละแบบฟอร์ม กรุณาตรวจสอบข้อมูลที่อ่านได้อย่างละเอียดก่อนนำเข้า';
        }

        // --- Quarter checkbox: find the paragraph mentioning "รอบ" that
        //     also carries the three Wingdings symbol runs, then read
        //     which one is the "checked" glyph (F0FE). ---
        [$quarter, $quarterDetected] = $this->detectQuarter($xpath, $warnings);

        // --- Reporter block (for the admin to eyeball against whichever
        //     agency they picked - never saved to any field). ---
        $reporterInfo = $this->extractReporterSummary($bodyParagraphs);

        // --- Tables: every data row's 1st column carries the item number
        //     (either "N." for items 1-4, or the distinctive "5.N"
        //     substring for the 5.1-5.5 sub-items); the 2nd column is
        //     that item's actual answer text. ---
        $fields = [];
        $unmatched = [];
        $tables = $xpath->query('//w:body/w:tbl');

        if ($tables->length === 0) {
            $warnings[] = 'ไม่พบตารางข้อมูลในไฟล์นี้ - ไม่มีข้อมูลหัวข้อ 1-4 และ 5.1-5.5 ให้นำเข้า';
        } else {
            foreach ($tables as $table) {
                $rows = $xpath->query('w:tr', $table);
                foreach ($rows as $rowIndex => $row) {
                    $cells = $xpath->query('w:tc', $row);
                    if ($cells->length < 2) {
                        continue;
                    }
                    $labelText = trim($this->cellText($cells->item(0), $xpath));
                    $valueText = trim($this->cellText($cells->item(1), $xpath));

                    if ($labelText === '' && $valueText === '') {
                        continue;
                    }

                    // Skip the header row ("ขั้นตอนการดำเนินงาน" / "ผลการดำเนินงาน").
                    if ($rowIndex === 0 && (mb_strpos($labelText, 'ขั้นตอนการดำเนินงาน') !== false || mb_strpos($valueText, 'ผลการดำเนินงาน') !== false)) {
                        continue;
                    }

                    $key = $this->detectItemKey($labelText);

                    if ($key !== null && !isset($fields["ans_{$key}_detail"])) {
                        $fields["ans_{$key}_detail"] = $valueText;
                    } elseif ($valueText !== '') {
                        $lines = preg_split('/\R/u', $labelText);
                        $unmatched[] = [
                            'heading' => trim($lines[0] ?? ''),
                            'text' => $valueText,
                        ];
                    }
                }
            }
        }

        $allKeys = ['1', '2', '3', '4', '5_1', '5_2', '5_3', '5_4', '5_5'];
        $missingKeys = [];
        foreach ($allKeys as $k) {
            if (!isset($fields["ans_{$k}_detail"])) {
                $missingKeys[] = $k;
            }
        }
        if (!empty($missingKeys) && $tables->length > 0) {
            $warnings[] = 'อ่านข้อมูลหัวข้อไม่ครบ ' . count($missingKeys) . ' จาก 9 หัวข้อ (' . implode(', ', $missingKeys) . ') - ข้อความที่จับคู่ไม่เกิดลอกไปวางเอง';
        }

        // --- "ปัญหา/อุปสรรค" and "ข้อเสนอแนะการพัฒนา" free-text blocks:
        //     everything between one heading paragraph and the next. ---
        $fields['problems'] = $this->extractSection(
            $bodyParagraphs,
            ['ปัญหา/อุปสรรค', 'ปัญหาอุปสรรค', 'ปัญหา อุปสรรค'],
            ['ข้อเสนอแนะ'],
            ['ข้อเสนอแนะ']
        );
        $fields['suggestions'] = $this->extractSection(
            $bodyParagraphs,
            ['ข้อเสนอแนะ'],
            ['หมายเหตุ'],
            ['ปัญหา']
        );

        return [
            'is_valid_template' => $isValidTemplate,
            'quarter' => $quarter,
            'quarter_detected' => $quarterDetected,
            'fields' => $fields,
            'unmatched' => $unmatched,
            'reporter_summary' => $reporterInfo['summary'],
            'reporter_name' => $reporterInfo['name'],
            'reporter_position' => $reporterInfo['position'],
            'reporter_phone' => $reporterInfo['phone'],
            'reporter_email' => $reporterInfo['email'],
            'agency_name' => $reporterInfo['agency'],
            'warnings' => $warnings,
        ];
    }

    /**
     * Item 1-4 rows have their number as the very first character(s) of
     * the cell ("1. จัดทำ..."); the 5.1 row's cell also opens with a
     * parent "5." header line above the actual "5.1 ..." sub-heading, so
     * a leading-character match would misfire there - checking for the
     * distinctive "5.N" substring anywhere in the cell first sidesteps
     * that entirely, and is tried before the leading-digit check so it
     * always wins for every 5.x row regardless of position.
     */
    private function detectItemKey(string $labelText): ?string
    {
        foreach (self::SUB5_NEEDLES as $needle => $key) {
            if (mb_strpos($labelText, $needle) !== false) {
                return $key;
            }
        }
        if (preg_match('/^\s*([1-4])[.\)]/u', $labelText, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Plain text of one <w:p>, honoring <w:tab/> and <w:br/> the same way
     * Word itself would visually space them, and returning empty string
     * runs unchanged (never throws on an empty/graphic-only paragraph).
     */
    private function paragraphText(\DOMNode $p, DOMXPath $xpath): string
    {
        $out = '';
        foreach ($xpath->query('.//w:t|.//w:tab|.//w:br', $p) as $node) {
            if ($node->localName === 't') {
                $out .= $node->textContent;
            } elseif ($node->localName === 'tab') {
                $out .= "\t";
            } elseif ($node->localName === 'br') {
                $out .= "\n";
            }
        }
        return $out;
    }

    private function cellText(\DOMNode $cell, DOMXPath $xpath): string
    {
        $paragraphs = [];
        foreach ($xpath->query('.//w:p', $cell) as $p) {
            $paragraphs[] = $this->paragraphText($p, $xpath);
        }
        return implode("\n", array_filter($paragraphs, fn($t) => trim($t) !== ''));
    }

    /**
     * Finds the checkbox row containing "รอบ" and reads which of its three
     * Wingdings glyphs is the checked one (F0FE), mapping its position to
     * the system's quarter values via QUARTER_CHECKBOX_ORDER. Returns
     * [quarterValueOrNull, wasDetectedCleanly].
     */
    private function detectQuarter(DOMXPath $xpath, array &$warnings): array
    {
        foreach ($xpath->query('//w:body/w:p') as $p) {
            $text = $this->paragraphText($p, $xpath);
            if (mb_strpos($text, 'รอบ') === false) {
                continue;
            }

            $syms = $xpath->query('.//w:sym[@w:font="Wingdings"]', $p);
            if ($syms->length < 2) {
                continue; // not the checkbox paragraph, just some other "รอบ" mention
            }

            $codes = [];
            foreach ($syms as $sym) {
                $codes[] = strtoupper((string) $sym->getAttribute('w:char'));
            }

            $checkedPositions = array_keys($codes, self::WINGDINGS_CHECKED, true);

            if (count($checkedPositions) === 1) {
                $pos = $checkedPositions[0];
                if (isset(self::QUARTER_CHECKBOX_ORDER[$pos])) {
                    return [self::QUARTER_CHECKBOX_ORDER[$pos], true];
                }
            } elseif (count($checkedPositions) > 1) {
                $warnings[] = 'พบเครื่องหมายติ๊กมากกว่า 1 ช่องในบรรทัด "รอบ..." - กรุณาเลือกไตรมาสเอง';
            } else {
                $warnings[] = 'ไม่พบเครื่องหมายติ๊กในช่อง "รอบ..." ของไฟล์นี้ - กรุณาเลือกไตรมาสเอง';
            }

            return [null, false];
        }

        $warnings[] = 'ไม่พบบรรทัดเลือกรอบการรายงานในไฟล์นี้ - กรุณาเลือกไตรมาสเอง';
        return [null, false];
    }

    /**
     * Best-effort "ผู้รายงาน / ตำแหน่ง / เบอร์ติดต่อ / E-mail / หน่วยงาน"
     * block. Returns both the structured fields (name/position/phone/
     * email - used to auto-match the target user account by email, and
     * saved as that record's reporter info) and a combined display
     * summary line for the admin to eyeball against whichever agency they
     * picked in the import dropdown.
     *
     * @return array{summary: ?string, name: ?string, position: ?string, phone: ?string, email: ?string, agency: ?string}
     */
    private function extractReporterSummary(array $paragraphs): array
    {
        $name = null;
        $position = null;
        $phone = null;
        $email = null;
        $agency = null;

        foreach ($paragraphs as $line) {
            if (mb_strpos($line, 'ผู้รายงาน') !== false) {
                $rawName = $this->extractBetween($line, 'ชื่อ-สกุล', 'ตำแหน่ง');
                $rawPosition = $this->extractAfter($line, 'ตำแหน่ง');
                if ($rawName) {
                    $name = $this->cleanDots($rawName);
                }
                if ($rawPosition) {
                    $position = $this->cleanDots($rawPosition);
                }
            } elseif (mb_strpos($line, 'เบอร์ติดต่อ') !== false) {
                $emailLabel = null;
                foreach (['E-mail', 'e-mail', 'Email', 'email', 'อีเมล'] as $needle) {
                    if (mb_strpos($line, $needle) !== false) {
                        $emailLabel = $needle;
                        break;
                    }
                }
                $rawPhone = $emailLabel !== null
                    ? $this->extractBetween($line, 'เบอร์ติดต่อ', $emailLabel)
                    : $this->extractAfter($line, 'เบอร์ติดต่อ');
                $rawEmail = $emailLabel !== null ? $this->extractAfter($line, $emailLabel) : null;
                if ($rawPhone) {
                    $phone = $this->cleanDots($rawPhone);
                }
                if ($rawEmail) {
                    $email = trim($this->cleanDots($rawEmail), " :\t");
                }
            } elseif (mb_strpos($line, 'หน่วยงาน') !== false) {
                $rawAgency = $this->extractAfter($line, 'หน่วยงาน');
                if ($rawAgency) {
                    $agency = $this->cleanDots($rawAgency);
                }
            }
        }

        $parts = [];
        if ($name) {
            $parts[] = 'ผู้รายงาน: ' . $name;
        }
        if ($position) {
            $parts[] = 'ตำแหน่ง: ' . $position;
        }
        if ($phone) {
            $parts[] = 'เบอร์ติดต่อ: ' . $phone;
        }
        if ($email) {
            $parts[] = 'E-mail: ' . $email;
        }
        if ($agency) {
            $parts[] = 'หน่วยงาน: ' . $agency;
        }

        return [
            'summary' => empty($parts) ? null : implode(' | ', $parts),
            'name' => $name,
            'position' => $position,
            'phone' => $phone,
            'email' => $email,
            'agency' => $agency,
        ];
    }

    private function extractBetween(string $line, string $startLabel, string $endLabel): ?string
    {
        $startPos = mb_strpos($line, $startLabel);
        if ($startPos === false) {
            return null;
        }
        $startPos += mb_strlen($startLabel);
        $endPos = mb_strpos($line, $endLabel, $startPos);
        $segment = $endPos === false
            ? mb_substr($line, $startPos)
            : mb_substr($line, $startPos, $endPos - $startPos);
        return trim($segment) !== '' ? $segment : null;
    }

    private function extractAfter(string $line, string $label): ?string
    {
        $pos = mb_strpos($line, $label);
        if ($pos === false) {
            return null;
        }
        $segment = mb_substr($line, $pos + mb_strlen($label));
        return trim($segment) !== '' ? $segment : null;
    }

    /**
     * Strips the "......" leader-dot placeholders the paper template uses
     * for fill-in-the-blank fields, plus stray whitespace.
     */
    private function cleanDots(string $s): string
    {
        $s = preg_replace('/[.…]{2,}/u', ' ', $s);
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /**
     * Joins every paragraph strictly between a heading matching one of
     * $startNeedles and the next heading matching one of $stopNeedles (or
     * the end of the document when $stopNeedles is empty), dropping the
     * heading line itself.
     */
    private function extractSection(array $paragraphs, array $startNeedles, array $stopNeedles, array $skipIfAlsoContains = []): string
    {
        $collecting = false;
        $collected = [];

        foreach ($paragraphs as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (!$collecting) {
                // A combined section-title paragraph like "ปัญหา/อุปสรรค
                // และข้อเสนอแนะการพัฒนา" contains BOTH sub-headings'
                // text as substrings, one paragraph before either
                // sub-heading actually appears on its own line. Left
                // unguarded, that title satisfies this loop's own
                // start-needle check a paragraph early, which then makes
                // the real sub-heading (reached later, once $collected is
                // no longer empty) fail the "don't re-collect my own
                // heading" check further below and get appended as
                // ordinary content instead of being recognized and
                // skipped - visibly duplicating the other section's text
                // into this one. Rejecting the combined title as a start
                // candidate here keeps both extractions anchored to the
                // paragraph that is actually just their own heading.
                foreach ($skipIfAlsoContains as $skipNeedle) {
                    if (mb_strpos($trimmed, $skipNeedle) !== false) {
                        continue 2;
                    }
                }
                foreach ($startNeedles as $needle) {
                    if (mb_strpos($trimmed, $needle) !== false) {
                        $collecting = true;
                        continue 2;
                    }
                }
                continue;
            }

            foreach ($stopNeedles as $needle) {
                if (mb_strpos($trimmed, $needle) !== false) {
                    return trim(implode("\n", $collected));
                }
            }

            // Never re-trigger on the same heading text appearing again.
            $isOwnHeading = false;
            foreach ($startNeedles as $needle) {
                if (mb_strpos($trimmed, $needle) !== false && count($collected) === 0) {
                    $isOwnHeading = true;
                }
            }
            if (!$isOwnHeading) {
                $collected[] = $trimmed;
            }
        }

        return trim(implode("\n", $collected));
    }
}
