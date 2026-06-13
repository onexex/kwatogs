<?php

namespace App\Support;

use ZipArchive;

/**
 * Minimal, dependency-free .xlsx writer (OOXML via ZipArchive).
 *
 * Supports: inline strings, numbers, booleans, column widths, merged cells,
 * and a small fixed set of cell styles (bold, money #,##0.00, text @, centered
 * title, subtotal with top border). Built specifically for the payroll exports
 * so the app needs no PhpSpreadsheet / maatwebsite dependency.
 */
class SimpleXlsx
{
    // Style indexes — must match the cellXfs order in stylesXml()
    public const S_NORMAL    = 0;
    public const S_BOLD      = 1;
    public const S_MONEY     = 2;
    public const S_BOLDMONEY = 3;
    public const S_TEXT      = 4;
    public const S_TITLE     = 5; // bold + centered
    public const S_SUBTOTAL  = 6; // bold money + top border

    private string $title;
    private array $cells = [];       // [row][colIndex] => ['v'=>, 't'=>, 's'=>]
    private array $merges = [];
    private array $colWidths = [];    // colIndex => width
    private int $maxRow = 1;
    private int $maxCol = 1;

    public function __construct(string $title = 'Sheet1')
    {
        $this->title = $this->safeTitle($title);
    }

    private function safeTitle(string $t): string
    {
        $t = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $t);
        return mb_substr(trim($t) ?: 'Sheet1', 0, 31);
    }

    public function setColumnWidths(array $widths): void
    {
        foreach ($widths as $col => $w) {
            $this->colWidths[$this->colIndex($col)] = (float) $w;
        }
    }

    public function mergeCells(string $range): void
    {
        $this->merges[] = $range;
    }

    public function setString(string $ref, ?string $val, int $style = self::S_NORMAL): void
    {
        $this->put($ref, (string) $val, 'inlineStr', $style);
    }

    public function setNumber(string $ref, float $val, int $style = self::S_NORMAL): void
    {
        $this->put($ref, $val, 'n', $style);
    }

    public function setBool(string $ref, bool $val, int $style = self::S_NORMAL): void
    {
        $this->put($ref, $val ? 1 : 0, 'b', $style);
    }

    private function put(string $ref, $val, string $type, int $style): void
    {
        [$col, $row] = $this->split($ref);
        $ci = $this->colIndex($col);
        $this->cells[$row][$ci] = ['v' => $val, 't' => $type, 's' => $style];
        if ($row > $this->maxRow) { $this->maxRow = $row; }
        if ($ci > $this->maxCol)  { $this->maxCol = $ci; }
    }

    // ── Column helpers ───────────────────────────────────────────────────
    private function split(string $ref): array
    {
        if (!preg_match('/^([A-Z]+)(\d+)$/', strtoupper($ref), $m)) {
            throw new \InvalidArgumentException("Bad cell ref: {$ref}");
        }
        return [$m[1], (int) $m[2]];
    }

    private function colIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $n = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n; // 1-based
    }

    private function colLetter(int $index): string
    {
        $s = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $s = chr(65 + $mod) . $s;
            $index = intdiv($index - 1, 26);
        }
        return $s;
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // ── Build + save ─────────────────────────────────────────────────────
    public function saveToTempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create xlsx archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());
        $zip->close();

        return $path;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->esc($this->title) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        // numFmtId 4 = #,##0.00 ; 49 = @ (text) — both built-in
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left/><right/><top style="thin"><color rgb="FF000000"/></top><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="7">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="4" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="4" fontId="1" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function sheetXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Columns
        if (!empty($this->colWidths)) {
            $xml .= '<cols>';
            foreach ($this->colWidths as $ci => $w) {
                $xml .= '<col min="' . $ci . '" max="' . $ci . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        // Rows
        $xml .= '<sheetData>';
        $rows = array_keys($this->cells);
        sort($rows, SORT_NUMERIC);
        foreach ($rows as $row) {
            $xml .= '<row r="' . $row . '">';
            $cols = $this->cells[$row];
            ksort($cols, SORT_NUMERIC);
            foreach ($cols as $ci => $cell) {
                $ref = $this->colLetter($ci) . $row;
                $s = $cell['s'];
                if ($cell['t'] === 'inlineStr') {
                    $xml .= '<c r="' . $ref . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">'
                        . $this->esc((string) $cell['v']) . '</t></is></c>';
                } elseif ($cell['t'] === 'b') {
                    $xml .= '<c r="' . $ref . '" s="' . $s . '" t="b"><v>' . ((int) $cell['v']) . '</v></c>';
                } else { // number
                    $xml .= '<c r="' . $ref . '" s="' . $s . '"><v>' . $this->num($cell['v']) . '</v></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        // Merges
        if (!empty($this->merges)) {
            $xml .= '<mergeCells count="' . count($this->merges) . '">';
            foreach ($this->merges as $range) {
                $xml .= '<mergeCell ref="' . $this->esc($range) . '"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    private function num($v): string
    {
        return rtrim(rtrim(sprintf('%.10F', (float) $v), '0'), '.') ?: '0';
    }
}
