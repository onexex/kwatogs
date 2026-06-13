<?php

namespace App\Support;

use ZipArchive;

/**
 * Minimal, dependency-free .xlsx reader (OOXML via ZipArchive + SimpleXML).
 * Reads the FIRST worksheet into a 0-indexed array of rows, each row a
 * 0-indexed array of cell string values. Also supports .csv.
 */
class SimpleXlsxReader
{
    /** Read a file (.xlsx or .csv) into array of rows (array of cell strings). */
    public static function rows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            return self::readCsv($path);
        }
        return self::readXlsx($path);
    }

    private static function readCsv(string $path): array
    {
        $rows = [];
        if (($h = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($h, 0, ',')) !== false) {
                $rows[] = array_map(fn ($v) => trim((string) $v), $data);
            }
            fclose($h);
        }
        return $rows;
    }

    private static function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open the Excel file.');
        }

        // 1) shared strings
        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = @simplexml_load_string($ss);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    $shared[] = self::siText($si);
                }
            }
        }

        // 2) resolve first worksheet path
        $sheetPath = self::firstSheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        }
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('No worksheet found in the Excel file.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false) {
            throw new \RuntimeException('Could not parse the worksheet.');
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            $maxCol = -1;
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = self::colIndex(preg_replace('/\d+/', '', $ref));
                $type = (string) $c['t'];
                $val = '';
                if ($type === 's') {
                    $idx = (int) $c->v;
                    $val = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = self::siText($c->is);
                } elseif ($type === 'b') {
                    $val = ((string) $c->v === '1') ? 'TRUE' : 'FALSE';
                } else {
                    $val = isset($c->v) ? (string) $c->v : '';
                }
                $cells[$col] = trim($val);
                if ($col > $maxCol) { $maxCol = $col; }
            }
            // normalise to a dense 0-indexed array
            $dense = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $dense[$i] = $cells[$i] ?? '';
            }
            $rows[] = $dense;
        }
        return $rows;
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb === false || $rels === false) {
            return 'xl/worksheets/sheet1.xml';
        }
        $wbXml = @simplexml_load_string($wb);
        $relXml = @simplexml_load_string($rels);
        if ($wbXml === false || $relXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }
        // first <sheet> r:id
        $rid = null;
        foreach ($wbXml->sheets->sheet as $sheet) {
            foreach ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships') as $k => $v) {
                if ($k === 'id') { $rid = (string) $v; break; }
            }
            if ($rid) { break; }
        }
        if (!$rid) { return 'xl/worksheets/sheet1.xml'; }
        foreach ($relXml->Relationship as $r) {
            if ((string) $r['Id'] === $rid) {
                $target = (string) $r['Target'];
                $target = ltrim($target, '/');
                return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    private static function siText($si): string
    {
        if ($si === null) { return ''; }
        $text = '';
        if (isset($si->t)) {
            $text .= (string) $si->t;
        }
        foreach ($si->r as $r) {
            $text .= (string) $r->t;
        }
        return $text;
    }

    private static function colIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $n = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1; // 0-based
    }
}
