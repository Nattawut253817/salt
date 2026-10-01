<?php

namespace App\Services;

use ZipArchive;

/**
 * Hand-built OOXML (.docx) writer using ZipArchive + raw WordprocessingML -
 * the export-side mirror of KidneyDhbWordParser / SaltAssessmentWordParser's
 * import-side approach (ZipArchive + DOMDocument). No PHPWord or any other
 * package is needed, same as Word *import* already doesn't need one.
 *
 * v3: rebuilt to match the two real official reference documents the user
 * supplied (แบบรายงานพชอ.ไต and แบบรายงานลดการบริโภคเกลือและโซเดียม) as
 * closely as possible, rather than an independently "cleaned up" redesign:
 *  - Wingdings symbol checkboxes (w:sym), not Unicode ☐/☒.
 *  - Header identification fields as plain paragraphs with dot-leaders
 *    ("ผู้รายงาน ชื่อ-สกุล ......ชื่อ......... ตำแหน่ง....."), not bordered
 *    table cells.
 *  - No visible border box around the ปัญหา/ข้อเสนอแนะ note sections.
 *  - Each report keeps the reference's own font, divider character, and
 *    exact heading wording (they differ between the two report types).
 */
class AssessmentWordExportService
{
    private const FONT_KIDNEY = 'TH SarabunPSK';
    private const FONT_SALT = 'TH SarabunIT๙';
    private const EASTASIA_FONT = 'TH Sarabun New';

    // Matches both reference documents' own section properties exactly.
    private const PAGE_W = 11906;
    private const PAGE_H = 16838;
    private const MARGIN_TOP = 709;
    private const MARGIN_RIGHT = 851;
    private const MARGIN_BOTTOM = 426;
    private const MARGIN_LEFT = 851;
    private const USABLE_WIDTH = 10204; // PAGE_W - MARGIN_LEFT - MARGIN_RIGHT

    /** Font used for the document currently being built (set by each build*Docx() entry point). */
    private static string $font = self::FONT_KIDNEY;

    // ---------------------------------------------------------------
    // Low-level WordprocessingML fragment builders
    // ---------------------------------------------------------------

    private static function esc($text): string
    {
        $text = (string) ($text ?? '');
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }

    /** One <w:r> run. "\n" in $text becomes a <w:br/> line break within the run. */
    private static function run($text, bool $bold = false, bool $underline = false, int $size = 32): string
    {
        $rpr = '<w:rPr>';
        $rpr .= '<w:rFonts w:ascii="' . self::$font . '" w:hAnsi="' . self::$font . '" w:eastAsia="' . self::EASTASIA_FONT . '" w:cs="' . self::$font . '"/>';
        if ($bold) {
            $rpr .= '<w:b/><w:bCs/>';
        }
        if ($underline) {
            $rpr .= '<w:u w:val="single"/>';
        }
        $rpr .= '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/>';
        $rpr .= '</w:rPr>';

        $parts = explode("\n", (string) $text);
        $segs = [];
        foreach ($parts as $i => $p) {
            if ($i > 0) {
                $segs[] = '<w:br/>';
            }
            $segs[] = '<w:t xml:space="preserve">' . self::esc($p) . '</w:t>';
        }

        return '<w:r>' . $rpr . implode('', $segs) . '</w:r>';
    }

    /** One Wingdings checkbox glyph run: $char is 'F0FE' (checked) or 'F06F' (unchecked). */
    private static function symRun(string $char, int $size = 28): string
    {
        return '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Wingdings" w:hAnsi="Wingdings" w:cs="Wingdings"/>'
            . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/>'
            . '</w:rPr><w:sym w:font="Wingdings" w:char="' . $char . '"/></w:r>';
    }

    /** One <w:p> paragraph wrapping already-built run XML. */
    private static function paragraph(string $runsXml, ?string $align = null, int $spacingAfter = 120): string
    {
        $ppr = '<w:pPr>';
        if ($align) {
            $ppr .= '<w:jc w:val="' . $align . '"/>';
        }
        $ppr .= '<w:spacing w:after="' . $spacingAfter . '" w:line="276" w:lineRule="auto"/>';
        $ppr .= '</w:pPr>';

        return '<w:p>' . $ppr . $runsXml . '</w:p>';
    }

    /** All four sides 'single' (visible grid line) or 'nil' (no line). */
    private static function borders(string $top, string $left, string $bottom, string $right): array
    {
        return ['top' => $top, 'left' => $left, 'bottom' => $bottom, 'right' => $right];
    }

    /** One table cell. $contentXml must be one-or-more <w:p> paragraphs. */
    private static function tableCell(string $contentXml, int $widthTwips, ?string $vMerge = null, string $valign = 'center', ?array $borders = null, int $gridSpan = 1): string
    {
        $borders = $borders ?? self::borders('single', 'single', 'single', 'single');

        $tcpr = '<w:tcPr><w:tcW w:w="' . $widthTwips . '" w:type="dxa"/>';
        if ($gridSpan > 1) {
            $tcpr .= '<w:gridSpan w:val="' . $gridSpan . '"/>';
        }
        $tcpr .= '<w:tcBorders>';
        foreach (['top', 'left', 'bottom', 'right'] as $side) {
            if (($borders[$side] ?? 'nil') === 'nil') {
                $tcpr .= '<w:' . $side . ' w:val="nil"/>';
            } else {
                $tcpr .= '<w:' . $side . ' w:val="single" w:sz="4" w:color="auto"/>';
            }
        }
        $tcpr .= '</w:tcBorders>';
        if ($vMerge === 'restart') {
            $tcpr .= '<w:vMerge w:val="restart"/>';
        } elseif ($vMerge === 'continue') {
            $tcpr .= '<w:vMerge w:val="continue"/>';
        }
        $tcpr .= '<w:vAlign w:val="' . $valign . '"/>';
        $tcpr .= '</w:tcPr>';

        if ($contentXml === '') {
            $contentXml = self::paragraph('');
        }

        return '<w:tc>' . $tcpr . $contentXml . '</w:tc>';
    }

    private static function tableRow(array $cellsXml, bool $header = false): string
    {
        $trpr = '<w:trPr>' . ($header ? '<w:tblHeader/>' : '') . '</w:trPr>';
        return '<w:tr>' . $trpr . implode('', $cellsXml) . '</w:tr>';
    }

    /**
     * @param int[] $colWidthsTwips
     * Plain full grid (single 4-weight border all sides + inside lines),
     * matching both reference documents' own table style exactly.
     */
    private static function table(array $rowsXml, array $colWidthsTwips, bool $gridded = true): string
    {
        $totalWidth = array_sum($colWidthsTwips);
        $borderTag = $gridded ? 'single' : 'nil';
        $tblpr = '<w:tblPr><w:tblW w:w="' . $totalWidth . '" w:type="dxa"/>'
            . '<w:tblBorders>'
            . '<w:top w:val="' . $borderTag . '" w:sz="4" w:color="auto"/>'
            . '<w:left w:val="' . $borderTag . '" w:sz="4" w:color="auto"/>'
            . '<w:bottom w:val="' . $borderTag . '" w:sz="4" w:color="auto"/>'
            . '<w:right w:val="' . $borderTag . '" w:sz="4" w:color="auto"/>'
            . '<w:insideH w:val="' . $borderTag . '" w:sz="4" w:color="auto"/>'
            . '<w:insideV w:val="' . $borderTag . '" w:sz="4" w:color="auto"/>'
            . '</w:tblBorders>'
            . '<w:tblCellMar>'
            . '<w:top w:w="40" w:type="dxa"/><w:bottom w:w="40" w:type="dxa"/>'
            . '<w:left w:w="108" w:type="dxa"/><w:right w:w="108" w:type="dxa"/>'
            . '</w:tblCellMar>'
            . '<w:tblLayout w:type="fixed"/>'
            . '</w:tblPr>';
        $grid = '<w:tblGrid>';
        foreach ($colWidthsTwips as $w) {
            $grid .= '<w:gridCol w:w="' . $w . '"/>';
        }
        $grid .= '</w:tblGrid>';

        return '<w:tbl>' . $tblpr . $grid . implode('', $rowsXml) . '</w:tbl>';
    }

    /** Plain bold heading paragraph - no shading, no border, matching both references. */
    private static function sectionHeading(string $text, ?string $align = null): string
    {
        return self::paragraph(self::run($text, true), $align, 80);
    }

    /**
     * One centered row of "[checkbox] label   [checkbox] label   ..." cells
     * using real Wingdings glyphs, matching the reference documents' own
     * checkbox convention (☐ = Wingdings F06F, ☒ = Wingdings F0FE).
     *
     * @param string[] $labels e.g. ['3 เดือน', '9 เดือน', '12 เดือน']
     * @param int $selectedIndex 1-based index into $labels, or 0 for "none checked"
     */
    private static function checkboxRow(array $labels, int $selectedIndex): string
    {
        $n = count($labels);
        $base = intdiv(self::USABLE_WIDTH, $n);
        $widths = array_fill(0, $n, $base);
        $widths[$n - 1] += self::USABLE_WIDTH - $base * $n;

        $noBorder = self::borders('nil', 'nil', 'nil', 'nil');
        $cells = [];
        foreach ($labels as $i => $label) {
            $checked = ($i + 1 === $selectedIndex);
            $runs = self::symRun($checked ? 'F0FE' : 'F06F') . self::run(' รอบ ' . $label, false, false, 28);
            $cells[] = self::tableCell(self::paragraph($runs, 'center'), $widths[$i], null, 'center', $noBorder);
        }

        return self::table([self::tableRow($cells)], $widths, false);
    }

    /** Maps the stored quarter (1..4 = 3/6/9/12 months) to a 1-based index into $questionMonths, or 0 if that period isn't offered on this form. */
    private static function checkboxSelectedIndex(?int $quarter, array $questionMonths): int
    {
        $monthsByQuarter = [1 => 3, 2 => 6, 3 => 9, 4 => 12];
        $months = $monthsByQuarter[$quarter] ?? null;
        if ($months === null) {
            return 0;
        }
        $idx = array_search($months, $questionMonths, true);
        return $idx === false ? 0 : $idx + 1;
    }

    /** Renders freeform multi-line text (problems/suggestions/etc.) as one plain paragraph per line, exactly as the user typed it - no added bullets, no border. */
    private static function textLines(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return self::paragraph(self::run('-'));
        }
        $out = '';
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $out .= self::paragraph(self::run($line), null, 40);
        }
        return $out !== '' ? $out : self::paragraph(self::run('-'));
    }

    // ---------------------------------------------------------------
    // Fixed package parts
    // ---------------------------------------------------------------

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>';
    }

    private static function packageRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>';
    }

    private static function documentRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="' . self::$font . '" w:hAnsi="' . self::$font . '" w:eastAsia="' . self::EASTASIA_FONT . '" w:cs="' . self::$font . '"/>'
            . '<w:sz w:val="32"/><w:szCs w:val="32"/>'
            . '</w:rPr></w:rPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            . '</w:styles>';
    }

    private static function corePropsXml(string $title): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/">'
            . '<dc:title>' . self::esc($title) . '</dc:title>'
            . '<dc:creator>DDDC Smart Monitor</dc:creator>'
            . '</cp:coreProperties>';
    }

    private static function documentXml(string $bodyXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:body>' . $bodyXml
            . '<w:sectPr>'
            . '<w:pgSz w:w="' . self::PAGE_W . '" w:h="' . self::PAGE_H . '"/>'
            . '<w:pgMar w:top="' . self::MARGIN_TOP . '" w:right="' . self::MARGIN_RIGHT . '" w:bottom="' . self::MARGIN_BOTTOM . '" w:left="' . self::MARGIN_LEFT . '" w:header="709" w:footer="709" w:gutter="0"/>'
            . '</w:sectPr>'
            . '</w:body></w:document>';
    }

    /** Zips up the parts into a .docx and returns its raw binary contents. */
    private static function packageDocx(string $title, string $bodyXml): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'docx_export_');

        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::packageRelsXml());
        $zip->addFromString('docProps/core.xml', self::corePropsXml($title));
        $zip->addFromString('word/_rels/document.xml.rels', self::documentRelsXml());
        $zip->addFromString('word/styles.xml', self::stylesXml());
        $zip->addFromString('word/document.xml', self::documentXml($bodyXml));
        $zip->close();

        $binary = file_get_contents($tempPath);
        @unlink($tempPath);

        return $binary;
    }

    // ---------------------------------------------------------------
    // Report builders
    // ---------------------------------------------------------------

    /**
     * @param \App\Models\KidneyAssessment $assessment
     * @param array{name:?string,position:?string,phone:?string,email:?string} $reporter
     */
    public static function buildKidneyDocx($assessment, array $reporter): string
    {
        self::$font = self::FONT_KIDNEY;

        $unitName = self::resolveUnitName($assessment->user);
        $districtName = $assessment->user->district->district_name ?? '-';

        $body = '';
        $body .= self::paragraph(
            self::run('แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชนผ่านกลไก', true),
            'center', 0
        );
        $body .= self::paragraph(
            self::run('คณะกรรมการพัฒนาคุณภาพชีวิตระดับอำเภอ(พชอ.) ', true),
            'center', 120
        );
        $body .= self::checkboxRow(['3 เดือน', '9 เดือน', '12 เดือน'], self::checkboxSelectedIndex($assessment->quarter, [3, 9, 12]));
        $body .= self::paragraph(self::run(str_repeat('*', 74), true), 'center', 160);

        $body .= self::paragraph(
            self::run('ผู้รายงาน ชื่อ-สกุล ', true) . self::run('......') . self::run($reporter['name'] ?: '-') . self::run('.........')
            . self::run(' ตำแหน่ง', true) . self::run('.....') . self::run($reporter['position'] ?: '-') . self::run(str_repeat('.', 30))
        );
        $body .= self::paragraph(
            self::run('เบอร์ติดต่อ ', true) . self::run('……') . self::run($reporter['phone'] ?: '-') . self::run(str_repeat('.', 29))
            . self::run(' E-mail ', true) . self::run('……') . self::run($reporter['email'] ?: '-') . self::run(str_repeat('.', 30))
        );
        $body .= self::paragraph(
            self::run('หน่วยงาน ', true) . self::run('......') . self::run($unitName)
            . self::run(' อำเภอ ', true) . self::run('...') . self::run($districtName) . self::run(str_repeat('.', 40))
        );
        $body .= self::paragraph('', null, 120);

        $body .= self::sectionHeading('1. ความก้าวหน้าและผลการดำเนินงาน:  ');

        $wLabel = 3114;
        $wResult = 7080;

        $rows = [];
        $rows[] = self::tableRow([
            self::tableCell(self::paragraph(self::run('กิจกรรม', true), 'center'), $wLabel),
            self::tableCell(self::paragraph(self::run('ผลการดำเนินงาน', true), 'center'), $wResult),
        ], true);

        // Category headings are the template's own fixed wording, with no
        // "1." / "2." numbering prefix - matching the reference exactly.
        $catLabels = [
            1 => 'การขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง(ระดับอำเภอ)',
            2 => 'การจัดการข้อมูลเฝ้าระวัง',
            3 => 'การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม',
            4 => 'สนับสนุนการสร้างนโยบายสาธารณะที่เกี่ยวข้องกับการป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน',
            5 => 'การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ',
            6 => 'การสร้างความเข้มแข็งของชุมชนในการลดปัจจัยเสี่ยงของการเกิดโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน',
            7 => 'การจัดบริการเชิงรุกในชุมชน',
        ];

        $isFirstOfGroup1 = true;
        foreach ($catLabels as $num => $label) {
            $field = 'category_' . $num;
            $leftCell = $isFirstOfGroup1
                ? self::tableCell(self::paragraph(self::run('รายงานผลการดำเนินงาน')), $wLabel, 'restart')
                : self::tableCell('', $wLabel, 'continue');
            $rows[] = self::tableRow([
                $leftCell,
                self::tableCell(
                    self::paragraph(self::run($label, true), null, 40)
                    . self::paragraph(self::run(trim((string) ($assessment->$field ?? '')) ?: '-')),
                    $wResult
                ),
            ]);
            $isFirstOfGroup1 = false;
        }

        // Category 8 is ONE combined row in the reference (not split into
        // 8.1/8.2/8.3 sub-rows); the left column's second vMerge group has
        // no visible label at all, matching the reference exactly.
        $cat8Text = trim(implode("\n", array_filter([
            trim((string) ($assessment->category_8_1 ?? '')),
            trim((string) ($assessment->category_8_2 ?? '')),
            trim((string) ($assessment->category_8_3 ?? '')),
        ], fn ($v) => $v !== '')));

        $rows[] = self::tableRow([
            self::tableCell('', $wLabel, 'restart'),
            self::tableCell(
                self::paragraph(self::run('การประเมินผลลัพธ์การดำเนินงาน', true), null, 40)
                . self::paragraph(self::run($cat8Text !== '' ? $cat8Text : 'รอดำเนินการ')),
                $wResult
            ),
        ]);

        $body .= self::table($rows, [$wLabel, $wResult]);
        $body .= self::paragraph('', null, 120);

        $body .= self::sectionHeading('2. ปัญหา อุปสรรค');
        $body .= self::textLines($assessment->problems_obstacles ?? '');

        $body .= self::paragraph('', null, 40);
        $body .= self::sectionHeading('3. ข้อเสนอแนะ/โอกาสพัฒนา');
        $body .= self::textLines($assessment->recommendations_opportunities ?? '');

        return self::packageDocx('รายงานความก้าวหน้า พชอ.ไต - ' . $unitName, $body);
    }

    /**
     * @param \App\Models\SaltAssessment $assessment
     * @param array{name:?string,position:?string,phone:?string,email:?string} $reporter
     */
    public static function buildSaltDocx($assessment, array $reporter): string
    {
        self::$font = self::FONT_SALT;

        $unitName = self::resolveUnitName($assessment->user);

        $body = '';
        $body .= self::paragraph(self::run('แบบรายงานการดำเนินงาน', true), 'center', 0);
        $body .= self::paragraph(
            self::run('ตัวชี้วัด SDA0902 : ร้อยละเครือข่ายเป้าหมายที่ดำเนินการลดการบริโภคเกลือโซเดียมตามแนวทางที่กำหนด', true),
            'center', 120
        );
        $body .= self::checkboxRow(['6 เดือน', '9 เดือน', '12 เดือน'], self::checkboxSelectedIndex($assessment->quarter, [6, 9, 12]));
        $body .= self::paragraph(self::run($unitName), 'center', 120);
        $body .= self::paragraph(self::run(str_repeat('-', 76), true), 'center', 160);

        $body .= self::paragraph(
            self::run('ผู้รายงาน ชื่อ-สกุล ', true) . self::run('......') . self::run($reporter['name'] ?: '-') . self::run('.........')
            . self::run(' ตำแหน่ง', true) . self::run('.....') . self::run($reporter['position'] ?: '-') . self::run(str_repeat('.', 24))
        );
        $body .= self::paragraph(
            self::run('เบอร์ติดต่อ ', true) . self::run('……') . self::run($reporter['phone'] ?: '-') . self::run(str_repeat('.', 20))
            . self::run(' E-mail : ', true) . self::run($reporter['email'] ?: '-') . self::run(str_repeat('.', 20))
        );
        $body .= self::paragraph(
            self::run('หน่วยงาน ', true) . self::run('......') . self::run($unitName) . self::run(str_repeat('.', 80))
        );
        $body .= self::paragraph('', null, 120);

        $body .= self::sectionHeading('ความก้าวหน้าของการดำเนินงาน');

        $w1 = 4248;
        $w2 = 4111;
        $w3 = 1275;

        $headerRow = fn () => self::tableRow([
            self::tableCell(self::paragraph(self::run('ขั้นตอนการดำเนินงาน', true), 'center'), $w1),
            self::tableCell(self::paragraph(self::run('ผลการดำเนินงาน', true), 'center'), $w2),
            self::tableCell(self::paragraph(self::run('เอกสาร/หลักฐาน', true), 'center'), $w3),
        ], true);

        $fileMark = fn ($flag) => self::tableCell(self::paragraph(self::run($flag ? '✓' : '-'), 'center'), $w3);

        // Steps 1-4 - their own table, exactly as the reference splits it.
        $stepsA = [
            ['1. จัดทำบันทึกความเข้าใจ (MOU) ', 'หรือข้อตกลงความร่วมมือ หรือคำสั่งคณะกรรมการ/คณะทำงานขับเคลื่อนการดำเนินงานเฝ้าระวังและการดำเนินงานลดการบริโภคเกลือและโซเดียมร่วมกับหน่วยงานเครือข่ายระดับจังหวัด', 'ans_1_detail', 'ans_1_file'],
            ['2. การสำรวจปริมาณโซเดียมในอาหาร ', 'ด้วยเครื่องวัดความเค็ม (Salt meter) (สำหรับจังหวัดที่ยังไม่ได้ดำเนินการ)', 'ans_2_detail', 'ans_2_file'],
            ['3. จัดทำแผนปฏิบัติการลดการบริโภคเกลือและโซเดียม ', 'ระดับจังหวัด ภายใต้กลยุทธ์ 5 ด้าน', 'ans_3_detail', 'ans_3_file'],
            ['4. การประเมินความตระหนักรู้ความเสี่ยง ', 'การบริโภคเกลือและโซเดียมระดับจังหวัด (เป้าหมายจังหวัดละ 500 คน)', 'ans_4_detail', 'ans_4_file'],
        ];
        $rowsA = [$headerRow()];
        foreach ($stepsA as [$bold, $rest, $detailField, $fileField]) {
            $rowsA[] = self::tableRow([
                self::tableCell(self::paragraph(self::run($bold, true) . self::run($rest)), $w1),
                self::tableCell(self::paragraph(self::run(trim((string) ($assessment->$detailField ?? '')) ?: '-')), $w2),
                $fileMark($assessment->$fileField ?? false),
            ]);
        }
        $body .= self::table($rowsA, [$w1, $w2, $w3]);

        // Small gap between the two tables, matching the reference's own spacing.
        $body .= self::paragraph('', null, 120);
        $body .= self::paragraph('', null, 120);
        $body .= self::paragraph('', null, 120);

        // Steps 5.1-5.5 - the shared "5. ..." heading sits above 5.1 inside
        // that first row's own cell, exactly as in the reference.
        $stepsB = [
            ['5.1 การส่งเสริมให้ผู้บริโภค/ประชาชนมีความรู้ ', 'และความตระหนักถึงความเสี่ยงต่อสุขภาพผ่านสื่อสารมวลชน/social media', 'ans_5_1_detail', 'ans_5_1_file'],
            ['5.2 การปรับลดปริมาณเกลือและโซเดียม ', 'ในผลิตภัณฑ์อาหาร', 'ans_5_2_detail', 'ans_5_2_file'],
            ['5.3 การปรับลดปริมาณเกลือและโซเดียม ', 'ในอาหารปรุงสุกที่จำหน่าย', 'ans_5_3_detail', 'ans_5_3_file'],
            ['5.4 การปรับสิ่งแวดล้อมที่เอื้อต่อการมีสุขภาพดี ', 'ภายในและบริเวณโดยรอบโรงเรียน/โรงพยาบาล/สถานที่ทำงาน', 'ans_5_4_detail', 'ans_5_4_file'],
            ['5.5 การดำเนินงานป้องกันควบคุมโรคไต ', 'ในชุมชน ผ่านกลไก พชอ. ตามแนวทางที่กำหนด', 'ans_5_5_detail', 'ans_5_5_file'],
        ];
        $rowsB = [$headerRow()];
        foreach ($stepsB as $i => [$bold, $rest, $detailField, $fileField]) {
            $stepCellRuns = $i === 0
                ? self::paragraph(self::run('5. การดำเนินงานตามแผนการดำเนินงานลดการบริโภคเกลือและโซเดียมระดับจังหวัด ภายใต้กลยุทธ์ 5 ด้าน', true), null, 40)
                    . self::paragraph(self::run($bold) . self::run($rest))
                : self::paragraph(self::run($bold) . self::run($rest));

            $rowsB[] = self::tableRow([
                self::tableCell($stepCellRuns, $w1),
                self::tableCell(self::paragraph(self::run(trim((string) ($assessment->$detailField ?? '')) ?: '-')), $w2),
                $fileMark($assessment->$fileField ?? false),
            ]);
        }
        $body .= self::table($rowsB, [$w1, $w2, $w3]);
        $body .= self::paragraph('', null, 120);

        $body .= self::sectionHeading('ปัญหา/อุปสรรคและข้อเสนอแนะการพัฒนา');
        $body .= self::sectionHeading('ปัญหา/อุปสรรค ');
        $body .= self::textLines($assessment->problems ?? '');
        $body .= self::paragraph('', null, 40);
        $body .= self::sectionHeading('ข้อเสนอแนะการพัฒนา ');
        $body .= self::textLines($assessment->suggestions ?? '');

        $body .= self::paragraph('', null, 120);
        $body .= self::paragraph(
            self::run('หมายเหตุ', true) . self::run(' ') . self::run(':- ')
            . self::run('สำนักงานสาธารณสุขจังหวัด ส่งแบบรายงานการดำเนินงาน พร้อมแนบเอกสารที่เกี่ยวข้องไปยังสำนักงานป้องกันควบคุมโรคเขตที่รับผิดชอบ'),
            null, 0
        );

        return self::packageDocx('แบบรายงาน SDA0902 - ' . $unitName, $body);
    }

    // ---------------------------------------------------------------
    // Shared helpers
    // ---------------------------------------------------------------

    /**
     * Same rank-based agency-name resolution used throughout the admin
     * views (kidney-dhb-detail / salt-assessment-detail / their -print
     * blades / the *-table partials) - kept in one place here.
     */
    public static function resolveUnitName($user): string
    {
        if (!$user) {
            return '-';
        }

        switch ($user->User_rank_id) {
            case 2:
                return 'สำนักงานสาธารณสุขจังหวัด' . ($user->province->province_name ?? '');
            case 3:
                return 'สำนักงานสาธารณสุขอำเภอ' . ($user->district->district_name ?? '');
            case 4:
                return $user->subdistrictHospital ? $user->subdistrictHospital->hospital_name : ($user->Con_name ?? '');
            case 5:
                return 'โรงพยาบาล' . ($user->hospital ? $user->hospital->hos_name : ($user->Con_name ?? ''));
            default:
                return $user->name ?? '-';
        }
    }

    /**
     * Same reporter-metadata resolution (with Word-import fallback) used at
     * the top of kidney-dhb-print.blade.php / salt-assessment-print.blade.php.
     *
     * @return array{name:?string,position:?string,phone:?string,email:?string}
     */
    public static function resolveReporter($assessment): array
    {
        $importReporter = $assessment->reporter_metadata['_import_reporter'] ?? null;
        $name = $importReporter['name'] ?? ($assessment->user->name ?? null);
        $position = $importReporter['position'] ?? ($assessment->user->User_position ?? null);
        $phoneRaw = $importReporter['phone'] ?? ($assessment->user->phone ?? null);
        $email = $importReporter['email'] ?? ($assessment->user->email ?? null);

        $phoneDigits = preg_replace('/\D/', '', (string) ($phoneRaw ?? ''));
        $phone = strlen($phoneDigits) === 10
            ? substr($phoneDigits, 0, 3) . '-' . substr($phoneDigits, 3)
            : ($phoneRaw ?? '-');

        return ['name' => $name, 'position' => $position, 'phone' => $phone, 'email' => $email];
    }
}
