<?php

namespace App\Services;

use ZipArchive;
use DOMDocument;
use DOMXPath;

/**
 * Parses a legacy "แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรค
 * ไม่ติดต่อเรื้อรัง/โรคไตในชุมชนผ่านกลไก พชอ." Word (.docx) report into the
 * same field shape the admin.kidney-dhb form uses (category_1..7,
 * category_8_1..3, problems_obstacles, recommendations_opportunities),
 * so a historical report can be dropped in, previewed, and edited in that
 * form before saving through the normal store endpoint - no separate
 * import/save path, no new database writes here.
 *
 * A .docx is just a zip of XML parts; this reads word/document.xml
 * directly with ZipArchive + DOMDocument rather than pulling in a full
 * Word-processing library, since the one template this targets is fixed
 * and small (one table, a handful of labelled paragraphs, three Wingdings
 * checkboxes for the reporting round).
 */
class KidneyDhbWordParser
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * The 10 canonical topic labels this template's table rows are
     * matched against, in the exact wording used by
     * resources/views/pages/partials/kidney-dhb-content.blade.php - a
     * table row is recognized by its second column STARTING WITH one of
     * these labels (the rest of that cell is the field's value).
     */
    private const CATEGORY_LABELS = [
        'category_1' => 'การขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง(ระดับอำเภอ)',
        'category_2' => 'การจัดการข้อมูลเฝ้าระวัง',
        'category_3' => 'การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม',
        'category_4' => 'สนับสนุนการสร้างนโยบายสาธารณะที่เกี่ยวข้องกับการป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/ โรคไตในชุมชน',
        'category_5' => 'การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ',
        'category_6' => 'การสร้างความเข้มแข็งของชุมชนในการลดปัจจัยเสี่ยงของการเกิดโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน',
        'category_7' => 'การจัดบริการเชิงรุกในชุมชน',
        'category_8_1' => 'ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง',
        'category_8_2' => 'การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่',
        'category_8_3' => 'นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง',
    ];

    /**
     * A short prefix of the form's own header title - used only to warn
     * when an uploaded file doesn't look like this template at all,
     * rather than silently returning a mostly-empty parse.
     */
    private const TEMPLATE_SIGNATURE = 'แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง';

    /**
     * This template only checkboxes 3/9/12-month rounds (skipping the
     * system's 6-month quarter 2 entirely) - position in that checkbox
     * row maps to the system's quarter values in this fixed order.
     */
    private const QUARTER_CHECKBOX_ORDER = ['1', '3', '4'];

    private const WINGDINGS_CHECKED = 'F0FE';
    private const WINGDINGS_UNCHECKED_CANDIDATES = ['F06F', 'F0A8', 'F0A3'];

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
            $warnings[] = 'ไม่พบข้อความหัวฟอร์ม "พชอ.ไต" ในไฟล์นี้ - อาจเป็นเอกสารคนละแบบฟอร์ม กรุณาตรวจสอบข้อมูลที่อ่านได้อย่างละเอียดก่อนบันทึก';
        }

        // --- Quarter checkbox: find the paragraph mentioning "รอบ" that
        //     also carries the three Wingdings symbol runs, then read
        //     which one is the "checked" glyph (F0FE). ---
        [$quarter, $quarterDetected] = $this->detectQuarter($xpath, $warnings);

        // --- Reporter block (for the admin to eyeball against whichever
        //     agency they picked - never saved to any field). ---
        $reporterInfo = $this->extractReporterSummary($bodyParagraphs);

        // --- Table: each data row's 2nd column starts with one of the 10
        //     canonical labels; whatever's left in that cell is the value. ---
        $fields = [];
        $unmatched = [];
        $tableRows = $xpath->query('//w:body/w:tbl[1]/w:tr');

        if ($tableRows->length === 0) {
            $warnings[] = 'ไม่พบตารางข้อมูลในไฟล์นี้ - ไม่มีข้อมูลหัวข้อ 1-7 และ 8.1-8.3 ให้นำเข้า';
        } else {
            foreach ($tableRows as $rowIndex => $row) {
                $cells = $xpath->query('w:tc', $row);
                if ($cells->length < 2) {
                    continue;
                }
                $cellText = trim($this->cellText($cells->item(1), $xpath));
                if ($cellText === '') {
                    continue;
                }

                // Skip the header row ("กิจกรรม" / "ผลการดำเนินงาน").
                if ($rowIndex === 0 && (mb_strpos($cellText, 'ผลการดำเนินงาน') === 0 || mb_strpos($cellText, 'ขั้นตอนการดำเนินงาน') === 0)) {
                    continue;
                }

                // Matched against the WHOLE cell (not just its first line):
                // a label can itself be wrapped across a mid-heading
                // <w:br/> in the original template (paragraphText() above
                // turns that into a plain "\n", same as any other line
                // break), so cutting the cell at its first newline before
                // matching would truncate the label and never match at all.
                $matchedField = null;
                $matchedRest = null;
                foreach (self::CATEGORY_LABELS as $field => $label) {
                    if (isset($fields[$field])) {
                        continue; // already filled by an earlier row
                    }
                    $rest = $this->matchLabelPrefix($cellText, $label);
                    if ($rest !== null) {
                        $matchedField = $field;
                        $matchedRest = $rest;
                        break;
                    }
                }

                if ($matchedField !== null) {
                    $fields[$matchedField] = $matchedRest !== '' ? $matchedRest : $cellText;
                } else {
                    $lines = preg_split('/\R/u', $cellText);
                    $unmatched[] = [
                        'heading' => trim($lines[0]),
                        'text' => $cellText,
                    ];
                }
            }
        }

        $missingLabels = array_diff(array_keys(self::CATEGORY_LABELS), array_keys($fields));
        if (!empty($missingLabels) && $tableRows->length > 0) {
            $warnings[] = 'อ่านข้อมูลหัวข้อไม่ครบ ' . count($missingLabels) . ' จาก 10 หัวข้อ (' . implode(', ', $missingLabels) . ') - ข้อความที่จับคู่ไม่ได้จะแสดงไว้ให้คัดลอกไปวางเอง';
        }

        // --- "ปัญหา อุปสรรค" and "ข้อเสนอแนะ/โอกาสพัฒนา" free-text blocks:
        //     everything between one heading paragraph and the next. ---
        $fields['problems_obstacles'] = $this->extractSection(
            $bodyParagraphs,
            ['ปัญหา อุปสรรค', 'ปัญหาอุปสรรค'],
            ['ข้อเสนอแนะ']
        );
        $fields['recommendations_opportunities'] = $this->extractSection(
            $bodyParagraphs,
            ['ข้อเสนอแนะ'],
            [] // runs to the end of the document
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
            'warnings' => $warnings,
        ];
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
     * Matches $label against the START of $cellText, character by
     * character, ignoring ALL whitespace on both sides (spaces, tabs, and
     * the "\n" that paragraphText()/cellText() insert for both real
     * paragraph breaks AND a mid-heading <w:br/> line-wrap - Word authors
     * routinely wrap a long label onto a second visual line without that
     * being a new paragraph, and the label text in CATEGORY_LABELS is
     * written as one unbroken string). On a match, returns everything in
     * $cellText immediately after the matched label, trimmed - i.e. the
     * field's actual value. Returns null when $cellText does not start
     * with $label once whitespace differences are ignored.
     */
    private function matchLabelPrefix(string $cellText, string $label): ?string
    {
        $textChars = preg_split('//u', $cellText, -1, PREG_SPLIT_NO_EMPTY);
        $labelChars = preg_split('//u', $label, -1, PREG_SPLIT_NO_EMPTY);
        $textLen = count($textChars);
        $labelLen = count($labelChars);
        $isSpace = fn($ch) => preg_match('/\s/u', $ch) === 1;

        $ti = 0;
        $li = 0;
        while ($li < $labelLen) {
            while ($ti < $textLen && $isSpace($textChars[$ti])) {
                $ti++;
            }
            while ($li < $labelLen && $isSpace($labelChars[$li])) {
                $li++;
            }
            if ($li >= $labelLen) {
                break;
            }
            if ($ti >= $textLen || $textChars[$ti] !== $labelChars[$li]) {
                return null;
            }
            $ti++;
            $li++;
        }

        return trim(implode('', array_slice($textChars, $ti)));
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
     * Best-effort "ผู้รายงาน / ตำแหน่ง / เบอร์ติดต่อ / E-mail / หน่วยงาน /
     * อำเภอ" block. Returns both the structured fields (name/position/
     * phone/email - used to auto-match the target user account by email,
     * and saved as that record's reporter info) and a combined display
     * summary line for the admin to eyeball against whichever agency they
     * picked in the import dropdown.
     *
     * @return array{summary: ?string, name: ?string, position: ?string, phone: ?string, email: ?string}
     */
    private function extractReporterSummary(array $paragraphs): array
    {
        $name = null;
        $position = null;
        $phone = null;
        $email = null;
        $agency = null;
        $district = null;

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
                    $email = trim($this->cleanDots($rawEmail));
                }
            } elseif (mb_strpos($line, 'หน่วยงาน') !== false) {
                $rawAgency = $this->extractBetween($line, 'หน่วยงาน', 'อำเภอ');
                $rawDistrict = $this->extractAfter($line, 'อำเภอ');
                if ($rawAgency) {
                    $agency = $this->cleanDots($rawAgency);
                }
                if ($rawDistrict) {
                    $district = $this->cleanDots($rawDistrict);
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
        if ($district) {
            $parts[] = 'อำเภอ: ' . $district;
        }

        return [
            'summary' => empty($parts) ? null : implode(' | ', $parts),
            'name' => $name,
            'position' => $position,
            'phone' => $phone,
            'email' => $email,
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
    private function extractSection(array $paragraphs, array $startNeedles, array $stopNeedles): string
    {
        $collecting = false;
        $collected = [];

        foreach ($paragraphs as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (!$collecting) {
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
