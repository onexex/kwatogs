<?php

/**
 * generate-changelog.php
 *
 * Standalone (no Laravel) generator for the client-facing "What's New" page.
 * Parses recent git history into public/changelog.json, which ships inside the
 * deploy zip (the live server has no .git, so this MUST run during CI where
 * full history exists — see .github/workflows/staging.yml).
 *
 * Usage (from repo root): php scripts/generate-changelog.php
 *
 * Output: public/changelog.json
 *   { "generated_at": ISO8601, "current": "<HEAD short sha>", "entries": [ ... ] }
 *   entry: { hash, short, type, scope, breaking, subject, date }
 */

$root   = dirname(__DIR__);
$outDir = $root . DIRECTORY_SEPARATOR . 'public';
$outFile = $outDir . DIRECTORY_SEPARATOR . 'changelog.json';

// Changelog "launch" date — only commits on/after this date (Manila time, +08:00)
// are shown, so the page starts fresh from go-live instead of dumping all history.
// Set to null to include the full history again.
$sinceDate = '2026-06-29';
$tzOffset  = '+08:00';

/**
 * Run a git command from the repo root, return trimmed stdout or null on failure.
 * Uses proc_open with an argument array (no shell) so quoting is identical on
 * Windows (cmd.exe) and Linux (/bin/sh) — the %x1f/%x1e format string in
 * particular must not be mangled by shell quote handling.
 */
function git(array $args, string $root): ?string
{
    $cmd = array_merge(['git', '-C', $root], $args);
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

    $proc = @proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($proc)) {
        return null;
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);   // read stdout fully (stderr stays small/empty)
    fclose($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    if ($code !== 0 || $out === false) {
        return null;
    }
    $out = trim($out);
    return $out === '' ? null : $out;
}

// Guard: bail cleanly if this isn't a git checkout (e.g. local non-git copy).
// exit 0 so a missing .git never fails the build — the page just shows an empty state.
if (git(['rev-parse', '--is-inside-work-tree'], $root) !== 'true') {
    fwrite(STDERR, "[changelog] Not a git checkout — skipping changelog generation.\n");
    exit(0);
}

// Field separator \x1f, record separator \x1e — safe against commas/quotes/newlines in messages.
$format = '%H%x1f%h%x1f%s%x1f%cI%x1f%an%x1e';
$logArgs = ['log', '-n', '500', '--no-merges', '--pretty=format:' . $format];

// Cutoff: include only commits on/after $sinceDate at 00:00 Manila time.
$sinceTs = null;
if ($sinceDate !== null) {
    $sinceTs = strtotime($sinceDate . 'T00:00:00' . $tzOffset);
    $logArgs[] = '--since=' . $sinceDate . 'T00:00:00' . $tzOffset; // prune most history at the git level
}

$raw = git($logArgs, $root);

$entries = [];
if ($raw !== null) {
    foreach (explode("\x1e", $raw) as $record) {
        $record = trim($record);
        if ($record === '') {
            continue;
        }
        $fields = explode("\x1f", $record);
        if (count($fields) < 5) {
            continue;
        }
        [$hash, $short, $subject, $date, $author] = $fields;
        $subject = trim($subject);

        // Enforce the launch-date cutoff precisely (tz-aware) — git --since can be
        // fuzzy across timezones, so re-check each commit's committer date here.
        if ($sinceTs !== null) {
            $commitTs = strtotime($date);
            if ($commitTs === false || $commitTs < $sinceTs) {
                continue;
            }
        }

        // Conventional commit: type(scope)!: description
        $type     = 'other';
        $scope    = null;
        $breaking = false;
        $clean    = $subject;

        if (preg_match('/^(\w+)(\(([^)]*)\))?(!)?:\s*(.+)$/', $subject, $m)) {
            $type     = strtolower($m[1]);
            $scope    = ($m[3] ?? '') !== '' ? $m[3] : null;
            $breaking = ($m[4] ?? '') === '!';
            $clean    = trim($m[5]);
        }

        // Capitalize first letter for display niceness, leave the rest as-authored.
        if ($clean !== '') {
            $clean = mb_strtoupper(mb_substr($clean, 0, 1)) . mb_substr($clean, 1);
        }

        $entries[] = [
            'hash'     => $hash,
            'short'    => $short,
            'type'     => $type,
            'scope'    => $scope,
            'breaking' => $breaking,
            'subject'  => $clean,
            'date'     => $date,
        ];
    }
}

$payload = [
    'generated_at' => date('c'),
    'current'      => git(['rev-parse', '--short', 'HEAD'], $root) ?? '',
    'entries'      => $entries,
];

if (!is_dir($outDir)) {
    @mkdir($outDir, 0755, true);
}

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    fwrite(STDERR, "[changelog] json_encode failed: " . json_last_error_msg() . "\n");
    exit(1);
}

if (@file_put_contents($outFile, $json) === false) {
    fwrite(STDERR, "[changelog] Failed to write {$outFile}\n");
    exit(1);
}

fwrite(STDOUT, "[changelog] Wrote " . count($entries) . " entries to public/changelog.json\n");
exit(0);
