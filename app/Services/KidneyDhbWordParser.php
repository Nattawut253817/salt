<?php

namespace App\Services;

use ZipArchive;
use DOMDocument;
use DOMXPath;

/**
 * Parses a historical "แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรค
 * ไม่ติดต่อเรื้อรัง/โรคไตในชุมชนผ่านกลไก พชอ." Word (.docx) report into the
 * same field shape the admin.kidney-dhb form uses (category_1..7,
 * category_8_1..3, problems_obstacles, recommendations_opportunities),
 * so a report can be dropped in, previewed, and edited in that form
 * before saving through the normal store endpoint - no separate
 * import/save path, no database writes here.
 *
 * Best-effort by design. The real files in circulation (FY2566-2569)
 * come in several layouts, so nothing here depends on one fixed template:
 *
 *   - "พื้นที่" layout (FY2567+): a 3-column table, the numbered topic
 *     ("1.มีคณะทำงาน...", "8. การประเมินผลลัพธ์...") in column 1, the
 *     reported result in column 2.
 *   - "สสจ." layout: column 1 is generic/empty and the topic heading is
 *     the first line of column 2 ("การขับเคลื่อน...", then its bullets).
 *   - FY2566 layout: 9 numbered topics (an extra "ให้ความรู้..." row) and
 *     different numbering from the form - so topics are recognised by
 *     their wording, never by their number.
 *   - the FY2569 สสอ. salt-plan form: unfamiliar topics. Whatever can be
 *     recognised is filled in; everything else is returned in `unmatched`
 *     so the admin can still copy it where it belongs.
 *
 * A .docx is a zip of XML parts; word/document.xml is read directly with
 * ZipArchive + DOMDocument rather than a full Word library.
 */
class KidneyDhbWordParser
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * Canonical topic labels exactly as the admin.kidney-dhb form words
     * them. Used only to strip a repeated heading off the front of a
     * value (see matchLabelPrefix()); topic recognition itself is done by
     * classifyHeading() below.
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

    /** Human-readable numbers for the "not found" warning. */
    private const FIELD_NUMBERS = [
        'category_1' => '1', 'category_2' => '2', 'category_3' => '3', 'category_4' => '4',
        'category_5' => '5', 'category_6' => '6', 'category_7' => '7',
        'category_8_1' => '8.1', 'category_8_2' => '8.2', 'category_8_3' => '8.3',
    ];

    /** Shared by every layout: the form title (with or without a leading "แบบ"). */
    private const TEMPLATE_SIGNATURE = 'รายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง';

    /** "รอบ N เดือน" -> the system's quarter value. */
    private const MONTH_TO_QUARTER = ['3' => '1', '6' => '2', '9' => '3', '12' => '4'];

    /**
     * Wingdings / Wingdings 2 glyph codes that draw a TICKED box. Anything
     * else seen in those fonts counts as an empty box.
     */
    private const CHECKED_SYMBOLS = [
        'wingdings' => ['F0FE', 'F0FD', 'F0FC', 'F078', 'F0FB', 'F0D6'],
        'wingdings 2' => ['F052', 'F053'],
    ];

    /** Plain-text tick marks authors type instead of a symbol. */
    private const TEXT_TICKS = ['√', '✓', '✔', '☑', '☒', '✅', '⌧'];

    /**
     * @param string      $path         Local filesystem path to the uploaded .docx
     * @param string|null $originalName Client-side file name, only used as a
     *                                  last-resort hint for the reporting round
     * @return array{
     *   is_valid_template: bool,
     *   layout: string,
     *   quarter: ?string,
     *   quarter_detected: bool,
     *   quarter_source: ?string,
     *   fields: array<string, string>,
     *   found_count: int,
     *   unmatched: array<int, array{heading: string, text: string}>,
     *   reporter_summary: ?string,
     *   reporter_name: ?string,
     *   reporter_position: ?string,
     *   reporter_phone: ?string,
     *   reporter_email: ?string,
     *   reporter_agency: ?string,
     *   reporter_district: ?string,
     *   reporter_province: ?string,
     *   suggested_rank: ?string,
     *   warnings: array<int, string>,
     * }
     */
    public function parse(string $path, ?string $originalName = null): array
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

        // Top-level paragraphs, in order (title, round checkboxes, reporter
        // block, the free-text sections after the table).
        $bodyParagraphs = [];
        $bodyParagraphNodes = [];
        foreach ($xpath->query('//w:body/w:p') as $p) {
            $bodyParagraphNodes[] = $p;
            $bodyParagraphs[] = $this->paragraphText($p, $xpath);
        }
        $bodyText = implode("\n", $bodyParagraphs);

        $isValidTemplate = mb_strpos($this->squash($bodyText), self::TEMPLATE_SIGNATURE) !== false;
        if (!$isValidTemplate) {
            $warnings[] = 'ไม่พบข้อความหัวฟอร์ม "รายงานความก้าวหน้า... พชอ.ไต" ในไฟล์นี้ - อาจเป็นเอกสารคนละแบบฟอร์ม ระบบจะดึงเท่าที่จับคู่ได้ กรุณาตรวจสอบก่อนนำเข้า';
        }

        [$quarter, $quarterSource] = $this->detectQuarter($bodyParagraphNodes, $xpath, $originalName, $warnings);

        $reporter = $this->extractReporter(array_slice($bodyParagraphs, 0, 20));

        // ---- Table rows -> topics -------------------------------------
        $fields = [];
        $unmatched = [];
        $layout = 'unknown';
        $tables = $xpath->query('//w:body/w:tbl');

        if ($tables->length === 0) {
            $warnings[] = 'ไม่พบตารางข้อมูลในไฟล์นี้ - ไม่มีหัวข้อ 1-7 และ 8.1-8.3 ให้นำเข้าอัตโนมัติ (ใช้ปุ่ม "นำเข้าด้วยตนเอง" เพื่อกรอกเองได้)';
        }

        $rowsSeen = 0;
        foreach ($tables as $table) {
            foreach ($xpath->query('w:tr', $table) as $row) {
                $cells = $xpath->query('w:tc', $row);
                $texts = [];
                foreach ($cells as $cell) {
                    $texts[] = trim($this->cellText($cell, $xpath));
                }
                if (count($texts) === 0 || implode('', $texts) === '') {
                    continue;
                }
                if ($this->isHeaderRow($texts)) {
                    continue;
                }
                $rowsSeen++;

                [$label, $value, $kind] = $this->splitRow($texts);
                if ($kind === 'numbered' && $layout === 'unknown') {
                    $layout = 'area';
                } elseif ($kind === 'merged' && $layout === 'unknown') {
                    $layout = 'province';
                }

                $headingFromValue = ($kind === 'merged' || $kind === 'single');
                $target = $this->classifyHeading($headingFromValue ? $this->firstLine($value) : $label);
                if ($target === null && $kind === 'numbered') {
                    // Label cell exists but is not a recognisable topic - still
                    // give the value's own first line one chance (some files
                    // put "คณะทำงาน…" as a heading inside the result cell).
                    $target = $this->classifyHeading($this->firstLine($value));
                }

                if ($target === null) {
                    $text = trim($label !== '' && $label !== $value ? $label . "\n" . $value : $value);
                    if ($text !== '') {
                        $unmatched[] = ['heading' => $this->firstLine($label !== '' ? $label : $value), 'text' => $text];
                    }
                    continue;
                }

                $body = $value;
                if ($headingFromValue) {
                    $body = $this->stripHeading($value, $target);
                }

                if ($target === 'outcome') {
                    foreach ($this->splitOutcome($body) as $field => $chunk) {
                        $this->assign($fields, $field, $chunk);
                    }
                } else {
                    $this->assign($fields, $target, $body);
                }
            }
        }
        if ($layout === 'unknown' && $rowsSeen > 0) {
            $layout = 'other';
        }

        // Free-text blocks after the table (a few forms keep them as table
        // rows instead - those were already routed above by classifyHeading).
        $problems = $this->extractSection($bodyParagraphs, '/^[\d๐-๙]*[.)]?ปัญหา(และ)?อุปสรรค/u', '/^[\d๐-๙]*[.)]?ข้อเสนอแนะ/u');
        $recommend = $this->extractSection($bodyParagraphs, '/^[\d๐-๙]*[.)]?ข้อเสนอแนะ/u', null);
        $this->assign($fields, 'problems_obstacles', $problems);
        $this->assign($fields, 'recommendations_opportunities', $recommend);

        foreach (['problems_obstacles', 'recommendations_opportunities'] as $k) {
            $fields[$k] = $fields[$k] ?? '';
        }

        $foundCount = 0;
        $missing = [];
        foreach (self::FIELD_NUMBERS as $field => $num) {
            if (isset($fields[$field]) && trim($fields[$field]) !== '') {
                $foundCount++;
            } else {
                $fields[$field] = '';
                $missing[] = $num;
            }
        }

        // No usable table at all (e.g. an appendix file): hand the visible
        // text back as one block so nothing the admin might need is hidden.
        if ($foundCount === 0 && $tables->length === 0) {
            $lines = array_values(array_filter(array_map('trim', $bodyParagraphs), fn($l) => $l !== ''));
            if (!empty($lines)) {
                $unmatched[] = ['heading' => $lines[0], 'text' => implode("\n", $lines)];
                $warnings[] = 'ไฟล์นี้ดูเหมือนไม่ใช่แบบรายงานหลัก (เช่น เป็นภาคผนวก) - แสดงข้อความทั้งหมดไว้ด้านล่างให้เลือกคัดลอกไปใส่เอง';
            }
        } elseif ($tables->length > 0) {
            if ($foundCount === 0) {
                $warnings[] = 'จับคู่หัวข้อไม่ได้เลย - ข้อความทั้งหมดอยู่ในส่วน "ข้อความอื่นที่พบ" ด้านล่าง เลือกเพิ่มเข้าหัวข้อที่ต้องการได้';
            } elseif (!empty($missing)) {
                $warnings[] = 'พบข้อมูล ' . $foundCount . ' จาก 10 หัวข้อ (ไม่พบหัวข้อ ' . implode(', ', $missing) . ') - หัวข้อที่ไม่พบสามารถกรอกเองในฟอร์มได้';
            }
        }

        return [
            'is_valid_template' => $isValidTemplate,
            'layout' => $layout,
            'quarter' => $quarter,
            'quarter_detected' => $quarter !== null,
            'quarter_source' => $quarterSource,
            'fields' => $fields,
            'found_count' => $foundCount,
            'unmatched' => $unmatched,
            'reporter_summary' => $reporter['summary'],
            'reporter_name' => $reporter['name'],
            'reporter_position' => $reporter['position'],
            'reporter_phone' => $reporter['phone'],
            'reporter_email' => $reporter['email'],
            'reporter_agency' => $reporter['agency'],
            'reporter_district' => $reporter['district'],
            'reporter_province' => $reporter['province'],
            'suggested_rank' => $reporter['rank'],
            'warnings' => $warnings,
        ];
    }

    // ------------------------------------------------------------------
    // Row handling
    // ------------------------------------------------------------------

    /** The "กิจกรรม | ผลการดำเนินงาน | ภาพกิจกรรม" header row. */
    private function isHeaderRow(array $texts): bool
    {
        $first = $this->squash($texts[0] ?? '');
        $second = $this->squash($texts[1] ?? '');
        return $first === 'กิจกรรม' && ($second === 'ผลการดำเนินงาน' || $second === '' || $second === 'ผลการดำเนินงานภาพกิจกรรม');
    }

    /**
     * @return array{0: string, 1: string, 2: string} [label, value, kind]
     *   kind: 'numbered' (own label cell), 'merged' (heading lives inside
     *   the value cell), 'single' (one-cell row)
     */
    private function splitRow(array $texts): array
    {
        if (count($texts) === 1) {
            return ['', $texts[0], 'single'];
        }

        $c0 = $texts[0];
        $c1 = $texts[1];

        // Column 1 empty or only a generic caption ("รายงานผลการดำเนินงาน") /
        // a bare ordinal: the topic heading is the first line of column 2.
        $c0Squashed = $this->squash($c0);
        $generic = $c0Squashed === '' || $c0Squashed === 'รายงานผลการดำเนินงาน' || preg_match('/^[\d๐-๙]{1,2}[.)]?$/u', $c0Squashed) === 1;
        if ($generic) {
            return ['', $c1, 'merged'];
        }

        return [$c0, $c1, 'numbered'];
    }

    /**
     * Recognises a topic from its heading wording. Order matters: the more
     * specific topics are tested first. Returns a field name, 'outcome' for
     * the combined "การประเมินผลลัพธ์" row (split into 8.1/8.2/8.3 by
     * splitOutcome()), or null when the heading is not a known topic.
     */
    private function classifyHeading(string $heading): ?string
    {
        $n = $this->squash($heading);
        $n = preg_replace('/^[\d๐-๙]+([.)][\d๐-๙]+)*[.)]?/u', '', $n);

        $has = fn(string ...$needles) => $this->containsAny($n, $needles);

        if ($has('ปัญหาอุปสรรค')) {
            return 'problems_obstacles';
        }
        if (mb_strpos($n, 'ข้อเสนอแนะ') === 0) {
            return 'recommendations_opportunities';
        }
        if ($has('ประเมินผลลัพธ์')) {
            return 'outcome';
        }
        if ($has('บริการเชิงรุก')) {
            return 'category_7';
        }
        if ($has('ความเข้มแข็งของชุมชน')) {
            return 'category_6';
        }
        if ($has('จัดการสิ่งแวดล้อม')) {
            return 'category_5';
        }
        if ($has('นโยบายสาธารณะ')) {
            return 'category_4';
        }
        if ($has('กำหนดประเด็นปัญหา')) {
            return 'category_3';
        }
        if ($has('วิเคราะห์ข้อมูลสุขภาพ', 'ข้อมูลเฝ้าระวัง')) {
            return 'category_2';
        }
        if ($has('คณะทำงานขับเคลื่อน', 'ขับเคลื่อนการดำเนินงาน')) {
            return 'category_1';
        }
        if ($has('นวัตกรรม', 'ภูมิปัญญา', 'บุคคลต้นแบบ')) {
            return 'category_8_3';
        }
        if ($has('ประเมินความตระหนัก', 'ความตระหนักรู้การลดการบริโภค')) {
            return 'category_8_2';
        }
        if (preg_match('/ร้อยละของผู้ป่วย.*คัดกรอง/u', $n) === 1) {
            return 'category_8_1';
        }

        return null;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Takes the topic heading off the front of a "merged" value cell. Tries
     * the canonical wording first (tolerating a heading wrapped over
     * several lines), then falls back to dropping the first line.
     */
    private function stripHeading(string $value, string $target): string
    {
        if (isset(self::CATEGORY_LABELS[$target])) {
            $rest = $this->matchLabelPrefix($value, self::CATEGORY_LABELS[$target]);
            if ($rest !== null && $rest !== '') {
                return $rest;
            }
        }

        $lines = preg_split('/\R/u', $value);
        if (count($lines) > 1) {
            array_shift($lines);
            $rest = trim(implode("\n", $lines));
            if ($rest !== '') {
                return $rest;
            }
        }

        // Heading-only cell: nothing but the heading text to report.
        return $target === 'outcome' ? '' : trim($value);
    }

    /**
     * The "การประเมินผลลัพธ์" row reports 8.1/8.2/8.3 together. Lines are
     * handed to the topic whose keywords they mention; a wrapped
     * continuation line stays with the line above it, and lines that
     * mention none of them default to 8.1 (the main screening metric).
     *
     * @return array<string, string> field => text
     */
    private function splitOutcome(string $text): array
    {
        $lines = array_values(array_filter(array_map('rtrim', preg_split('/\R/u', $text)), fn($l) => trim($l) !== ''));
        if (empty($lines)) {
            return [];
        }

        $bulletRe = '/^\s*(?:[-–—•·*]|[\d๐-๙]+\s*[.)])/u';
        $hasBullets = false;
        foreach ($lines as $l) {
            if (preg_match($bulletRe, $l) === 1) {
                $hasBullets = true;
                break;
            }
        }

        $buckets = ['category_8_1' => [], 'category_8_2' => [], 'category_8_3' => []];
        $current = 'category_8_1';
        foreach ($lines as $i => $line) {
            $isItemStart = !$hasBullets || preg_match($bulletRe, $line) === 1;
            if ($isItemStart || $i === 0) {
                $n = $this->squash($line);
                if (preg_match('/นวัตกรรม|ต้นแบบ|ภูมิปัญญา|งานวิจัย/u', $n) === 1) {
                    $current = 'category_8_3';
                } elseif (preg_match('/ความตระหนัก|ตระหนักรู้/u', $n) === 1 && mb_strpos($n, 'คัดกรอง') === false) {
                    $current = 'category_8_2';
                } elseif (preg_match('/คัดกรอง|ร้อยละ|ผู้ป่วย/u', $n) === 1) {
                    $current = 'category_8_1';
                }
            }
            $buckets[$current][] = trim($line);
        }

        $out = [];
        foreach ($buckets as $field => $items) {
            if (!empty($items)) {
                $out[$field] = implode("\n", $items);
            }
        }
        return $out;
    }

    /** Sets a field, appending when two rows land on the same topic. */
    private function assign(array &$fields, string $field, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }
        $fields[$field] = isset($fields[$field]) && trim($fields[$field]) !== ''
            ? $fields[$field] . "\n" . $text
            : $text;
    }

    // ------------------------------------------------------------------
    // Reporting round
    // ------------------------------------------------------------------

    /**
     * Which round the report covers. In order of trust:
     *   1. the one ticked "รอบ N เดือน" (Wingdings / Wingdings 2 box or a
     *      typed √ ✓ ☑)
     *   2. a title/line that mentions exactly one round and has no boxes
     *   3. the uploaded file's own name
     * Returns [quarterOrNull, sourceOrNull].
     */
    private function detectQuarter(array $paragraphNodes, DOMXPath $xpath, ?string $originalName, array &$warnings): array
    {
        $roundRe = '/([\x{E000}\x{E001}])?\s*รอบ\s*([\d๐-๙]{1,2})\s*เดือน/u';
        $singleMention = null;
        $ambiguous = false;
        $sawRoundLine = false;

        foreach (array_slice($paragraphNodes, 0, 14) as $p) {
            $line = $this->paragraphWithTicks($p, $xpath);
            if (mb_strpos($line, 'รอบ') === false || mb_strpos($line, 'เดือน') === false) {
                continue;
            }
            if (!preg_match_all($roundRe, $line, $m, PREG_SET_ORDER)) {
                continue;
            }
            $sawRoundLine = true;

            $checked = [];
            $hasBoxes = false;
            $months = [];
            foreach ($m as $hit) {
                $month = $this->thaiDigitsToAscii($hit[2]);
                $months[$month] = true;
                if (($hit[1] ?? '') !== '') {
                    $hasBoxes = true;
                    if ($hit[1] === "\u{E000}") {
                        $checked[] = $month;
                    }
                }
            }

            if ($hasBoxes) {
                $checked = array_values(array_unique($checked));
                if (count($checked) === 1 && isset(self::MONTH_TO_QUARTER[$checked[0]])) {
                    return [self::MONTH_TO_QUARTER[$checked[0]], 'checkbox'];
                }
                if (count($checked) > 1) {
                    $warnings[] = 'พบเครื่องหมายติ๊กมากกว่า 1 ช่องในบรรทัด "รอบ..." - กรุณาเลือกไตรมาสเอง';
                    $ambiguous = true;
                } else {
                    $warnings[] = 'ไม่พบเครื่องหมายติ๊กในช่อง "รอบ..." ของไฟล์นี้ - กรุณาเลือกไตรมาสเอง';
                }
                continue;
            }

            if (count($months) === 1) {
                $month = (string) array_key_first($months);
                if ($singleMention === null && isset(self::MONTH_TO_QUARTER[$month])) {
                    $singleMention = $month;
                }
            }
        }

        if (!$ambiguous && $singleMention !== null) {
            return [self::MONTH_TO_QUARTER[$singleMention], 'title'];
        }

        if (!$ambiguous && $originalName) {
            $name = $this->thaiDigitsToAscii($originalName);
            if (preg_match('/รอบ\s*(3|6|9|12)\s*เดือน/u', $name, $hit) === 1 || preg_match('/(?<!\d)(3|6|9|12)\s*(?:เดือน|ด(?![\p{L}\p{M}]))/u', $name, $hit) === 1) {
                // The "no ticked box" warning above (if any) is superseded.
                $warnings = array_values(array_filter($warnings, fn($w) => mb_strpos($w, 'ไม่พบเครื่องหมายติ๊ก') === false));
                $warnings[] = 'ไตรมาสนี้เดาจากชื่อไฟล์ (รอบ ' . $hit[1] . ' เดือน) - โปรดตรวจสอบก่อนนำเข้า';
                return [self::MONTH_TO_QUARTER[$hit[1]], 'filename'];
            }
        }

        if (!$ambiguous && !$sawRoundLine) {
            $warnings[] = 'ไม่พบบรรทัดเลือกรอบการรายงานในไฟล์นี้ - กรุณาเลือกไตรมาสเอง';
        }

        return [null, null];
    }

    /**
     * A paragraph's text with every ticked box rendered as U+E000 and every
     * empty box as U+E001, so detectQuarter() can regex "box + รอบ N เดือน".
     */
    private function paragraphWithTicks(\DOMNode $p, DOMXPath $xpath): string
    {
        $out = '';
        foreach ($xpath->query('.//w:t|.//w:sym|.//w:tab', $p) as $node) {
            if ($node->localName === 't') {
                $text = $node->textContent;
                foreach (self::TEXT_TICKS as $tick) {
                    $text = str_replace($tick, "\u{E000}", $text);
                }
                $out .= $text;
            } elseif ($node->localName === 'tab') {
                $out .= ' ';
            } else {
                $font = strtolower(trim((string) $node->getAttribute('w:font')));
                $char = strtoupper((string) $node->getAttribute('w:char'));
                $checkedSet = self::CHECKED_SYMBOLS[$font] ?? null;
                if ($checkedSet !== null) {
                    $out .= in_array($char, $checkedSet, true) ? "\u{E000}" : "\u{E001}";
                }
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // Reporter block
    // ------------------------------------------------------------------

    /**
     * Best-effort "ผู้รายงาน / ตำแหน่ง / เบอร์ติดต่อ / E-mail / หน่วยงาน /
     * อำเภอ / จังหวัด" block. Never saved blindly - the admin confirms it
     * in the preview; the agency/district/province/rank only pre-select the
     * import dialog's dropdowns.
     *
     * @return array<string, ?string>
     */
    private function extractReporter(array $paragraphs): array
    {
        $name = $position = $phone = $email = $agency = $district = $province = null;
        $rankProbeFull = null;

        foreach ($paragraphs as $rawLine) {
            $line = trim(preg_replace('/[ \t]+/u', ' ', $rawLine));
            if ($line === '') {
                continue;
            }

            if (mb_strpos($line, 'ผู้รายงาน') !== false || mb_strpos($line, 'ชื่อ-สกุล') !== false) {
                $name = $name ?? $this->cleanDots($this->extractBetween($line, 'ชื่อ-สกุล', 'ตำแหน่ง') ?? '') ?: null;
                $position = $position ?? $this->cleanDots($this->extractAfter($line, 'ตำแหน่ง') ?? '') ?: null;
            } elseif (mb_strpos($line, 'เบอร์ติดต่อ') !== false || mb_stripos($line, 'e-mail') !== false) {
                $emailLabel = null;
                foreach (['E-mail', 'e-mail', 'E-Mail', 'Email', 'email', 'อีเมล'] as $needle) {
                    if (mb_strpos($line, $needle) !== false) {
                        $emailLabel = $needle;
                        break;
                    }
                }
                $rawPhone = $emailLabel !== null
                    ? $this->extractBetween($line, 'เบอร์ติดต่อ', $emailLabel)
                    : $this->extractAfter($line, 'เบอร์ติดต่อ');
                $rawEmail = $emailLabel !== null ? $this->extractAfter($line, $emailLabel) : null;
                $phone = $phone ?? ($this->cleanDots($rawPhone ?? '') ?: null);
                if ($rawEmail !== null && $email === null) {
                    $candidate = trim($this->cleanDots(ltrim($rawEmail, ':： ')));
                    if (preg_match('/[^\s@,;]+@[^\s@,;]+/u', $candidate, $em) === 1) {
                        $email = $em[0];
                    }
                }
            } elseif ($agency === null && mb_strpos($line, 'หน่วยงาน') === 0) {
                // "หน่วยงาน <ชื่อหน่วยงาน> อำเภอ <อำเภอ> จังหวัด <จังหวัด>" - the
                // name itself can contain "อำเภอ" (สำนักงานสาธารณสุขอำเภอX), so
                // split on the LAST อำเภอ / จังหวัด instead of the first.
                $rest = mb_substr($line, mb_strlen('หน่วยงาน'));
                $rest = ltrim($rest, " \t:：.…_");
                $rankProbe = $rest;
                $pd = mb_strrpos($rest, 'อำเภอ');
                $pp = mb_strrpos($rest, 'จังหวัด');
                $dLen = mb_strlen('อำเภอ');
                $pLen = mb_strlen('จังหวัด');
                if ($pd !== false && $pp !== false && $pd < $pp) {
                    // ... อำเภอ X จังหวัด Y
                    $agencyRaw = mb_substr($rest, 0, $pd);
                    $district = $this->cleanDots(mb_substr($rest, $pd + $dLen, $pp - $pd - $dLen)) ?: null;
                    $province = $this->cleanDots(mb_substr($rest, $pp + $pLen)) ?: null;
                } elseif ($pd !== false && $pp !== false) {
                    // ... จังหวัด Y อำเภอ X
                    $agencyRaw = mb_substr($rest, 0, $pp);
                    $province = $this->cleanDots(mb_substr($rest, $pp + $pLen, $pd - $pp - $pLen)) ?: null;
                    $district = $this->cleanDots(mb_substr($rest, $pd + $dLen)) ?: null;
                } elseif ($pp !== false) {
                    $agencyRaw = mb_substr($rest, 0, $pp);
                    $province = $this->cleanDots(mb_substr($rest, $pp + $pLen)) ?: null;
                } elseif ($pd !== false) {
                    $agencyRaw = mb_substr($rest, 0, $pd);
                    $district = $this->cleanDots(mb_substr($rest, $pd + $dLen)) ?: null;
                } else {
                    $agencyRaw = $rest;
                }
                $agency = $this->cleanDots($agencyRaw) ?: null;
                if ($agency === null) {
                    // the name may sit only inside "สำนักงานสาธารณสุขอำเภอX" with the
                    // อำเภอ label swallowed - fall back to the whole line for ranking
                    $agency = $this->cleanDots($rankProbe) ?: null;
                }
                $rankProbeFull = $rankProbe;
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
        if ($province) {
            $parts[] = 'จังหวัด: ' . $province;
        }

        return [
            'summary' => empty($parts) ? null : implode(' | ', $parts),
            'name' => $name,
            'position' => $position,
            'phone' => $phone,
            'email' => $email,
            'agency' => $agency,
            'district' => $district,
            'province' => $province,
            'rank' => $this->guessRank($rankProbeFull ?? $agency),
        ];
    }

    /** ประเภทหน่วยงาน (users.User_rank_id) guessed from the agency's name. */
    private function guessRank(?string $agency): ?string
    {
        if ($agency === null || $agency === '') {
            return null;
        }
        $a = $this->squash($agency);
        if (preg_match('/สสจ|สำนักงานสาธารณสุขจังหวัด/u', $a) === 1) {
            return '2';
        }
        if (preg_match('/สสอ|สาธารณสุขอำเภอ|สสอ\./u', $a) === 1) {
            return '3';
        }
        if (preg_match('/รพ\.?สต|ส่งเสริมสุขภาพตำบล|ศูนย์สุขภาพ|ศสม|PCU|สถานีอนามัย|ชุมชน|บ้าน/iu', $a) === 1) {
            return '4';
        }
        if (preg_match('/โรงพยาบาล|^รพ\./u', $a) === 1) {
            return '5';
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Free-text sections ("ปัญหา อุปสรรค", "ข้อเสนอแนะ")
    // ------------------------------------------------------------------

    /**
     * Collects the paragraphs from a heading matching $startRe up to (not
     * including) the next heading matching $stopRe, or the document end.
     * Headings are tested against the whitespace-free line, and any text
     * written on the heading line itself ("2. ปัญหาอุปสรรค แผนงานยัง…")
     * is kept.
     */
    private function extractSection(array $paragraphs, string $startRe, ?string $stopRe): string
    {
        $collecting = false;
        $collected = [];

        foreach ($paragraphs as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            $n = $this->squash($trimmed);

            if (!$collecting) {
                if (preg_match($startRe, $n) === 1) {
                    $collecting = true;
                    // Heading text out of the original (spaced) line.
                    $inline = preg_replace('/^\s*[\d๐-๙]*\s*[.)]?\s*(ปัญหา\s*(และ\s*)?อุปสรรค(\s*ที่พบ)?|ข้อเสนอแนะ(\s*[\/และ]*\s*(โอกาส\s*)?พัฒนา|\s*และ\s*การพัฒนา)?)\s*[:：]?/u', '', $trimmed, 1);
                    $inline = trim((string) $inline);
                    if ($inline !== '') {
                        $collected[] = $inline;
                    }
                }
                continue;
            }

            if ($stopRe !== null && preg_match($stopRe, $n) === 1) {
                break;
            }
            $collected[] = $trimmed;
        }

        // Template filler ("........") is not content.
        $clean = [];
        foreach ($collected as $c) {
            $c = trim((string) preg_replace('/^[.…_\-\s]{3,}|[.…_\-\s]{3,}$/u', '', $c));
            if ($c !== '') {
                $clean[] = $c;
            }
        }

        return trim(implode("\n", $clean));
    }

    // ------------------------------------------------------------------
    // Text helpers
    // ------------------------------------------------------------------

    /**
     * Plain text of one <w:p>, honoring <w:tab/> and <w:br/> the same way
     * Word spaces them. Never throws on an empty/graphic-only paragraph.
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

    private function firstLine(string $text): string
    {
        $lines = preg_split('/\R/u', trim($text));
        return trim($lines[0] ?? '');
    }

    /** Removes every whitespace character (Thai text is matched without spaces). */
    private function squash(string $s): string
    {
        return preg_replace('/\s+/u', '', $s) ?? $s;
    }

    private function thaiDigitsToAscii(string $s): string
    {
        return strtr($s, ['๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4', '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9']);
    }

    /**
     * Matches $label against the START of $text, character by character,
     * ignoring ALL whitespace on both sides (a heading is often wrapped
     * over two lines in the Word file). Returns whatever follows the label,
     * trimmed, or null when $text does not start with it.
     */
    private function matchLabelPrefix(string $text, string $label): ?string
    {
        $textChars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
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

    /** Strips the "......" leader-dot placeholders and stray whitespace. */
    private function cleanDots(string $s): string
    {
        $s = preg_replace('/[.…]{2,}/u', ' ', $s);
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
