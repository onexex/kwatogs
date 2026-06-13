<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data migration: set emp_details.empPayrollType (CARD / CASH) and empCardNo
 * from the "MODE OF PAYROLL RELEASE (ATM / CASH)" list.
 *
 * Employees are matched by name (the source file uses "LASTNAME, FIRSTNAME").
 * Matching is accent/format tolerant. A summary of matched / unmatched names
 * is printed to the console when the migration runs.
 */
return new class extends Migration
{
    /** CARD (ATM) employees: [ "LASTNAME, FIRSTNAME", "account number" ] */
    private array $cardList = [
            ['FLORES, JENICA MIRANDA', '0000066789691'],
            ['LONTOK, JOEL', '0000066789602'],
            ['SARDAÑAS, ENRIQUE', '0000066789668'],
            ['PALVINO, JAYSON', '0000071933783'],
            ['DAET, HANNAH', '0000074803773'],
            ['PANGANIBAN, CRISOLOGO', '0000066789613'],
            ['DAET, JHEDEE', '0000066789599'],
            ['ALVARO, JERRY ANN', '0000066789624'],
            ['INOC, JOJO', '0000066789891'],
            ['LUBI, RAYMART', '0000066789806'],
            ['NOSCE, JAYSON', '0000066789759'],
            ['QUIAMO, LORELA', '0000066789880'],
            ['MONDELO, AILEEN', '0000066789919'],
            ['HERNANDEZ, REYMARK', '0000070947221'],
            ['DELA CRUZ, RICA', '0000070947523'],
            ['RECIO, LIZA', '0000067244762'],
            ['VILLANUEVA, JOHN MARK', '0000066790163'],
            ['RAMOS, JENNY', '0000070947341'],
            ['BRIONES, SHAIRYN FAUSTO', '0000074803813'],
            ['DELA PENA, ADRIENNE', '0000076640432'],
            ['DELOS REYES, JHON DAVE', '0000067244751'],
            ['ESCUZAR, REAN SIMON TOLENTINO', '0000076640403'],
            ['SARDANAS, JONATHAN', '0000074803831'],
            ['SARDANAS, JOVELYN', '0000066789960'],
            ['TORREFRANCA, CHRISTIAN PAUL', '0000077155090'],
            ['BELEN, ORLAND', '0000077155163'],
            ['CUENCA, RENATO', '0000066789646'],
            ['CUENCA, RICHELLE', '0000070947374'],
            ['CUENCA, ROLLY', '0000070947512'],
            ['MARTIN, MYLA', '0000066789908'],
            ['MENDOZA, DONALD', '0000066789817'],
            ['MIRANDA, PATRICK', '0000066789657'],
            ['MOLINYAWE, ANABEL', '0000066789726'],
            ['RIVERA, RHAMIL', '0000066789931'],
            ['CRISTOBAL, CECIL', '0000066789771'],
            ['WATAN, CELIA OLAN', '0000066790203'],
            ['PANGANIBAN, APRIL ROSE', '0000067447858'],
            ['LAZARO, HADASA', '0000076640352'],
            ['BELLEN, JUDE', '0000066789737'],
            ['ALCOBERA, MARICRIS', '0000070947192'],
            ['CABALLERO, RONALYN', '0000068960870'],
            ['CATINGGAN, RENEBOY', '0000070947396'],
            ['DE HITTA, JULIE MAY', '0000068960807'],
            ['MONTOYA, AILEEN', '0000070947272'],
            ['GARCIA, EDRIAN', '0000067901808'],
            ['CANAS, MHARLO B.', '0000074803795'],
            ['MENDAÑA, MARIBEL', '0000074803820'],
            ['ALEGRE, ARNEL', '0000077155072'],
            ['STO. DOMINGO, JHONFERDS', '0000077155083'],
            ['DELA TORRE, AIAN', '0000077155152'],
            ['VALDERAMA, JAMES', '0000077155170'],
            ['ESCAMA, RUSSEL JEROME', '0000077155130'],
            ['HIRAW, PONKANA', '0000077155061'],
            ['ALVAREZ, EVELYN', '0000066789760'],
            ['TAMAROSA, CHARLES', '0000071932882']
    ];

    /** CASH employees: "LASTNAME, FIRSTNAME" */
    private array $cashList = [
            'DIMAYUGA, JOSEPHINE',
            'FETALCO, JERWIL',
            'NERVAR, RANDY',
            'DELA CRUZ, MARFEL',
            'OLARTE, NIKKI',
            'TILBE, MARLENE',
            'MANGAYA, RICHARD',
            'TOLENTINO, EVANGELINE',
            'LABITAD, ANNE JONIKAH',
            'MENDOZA, ANGEL MARIE',
            'CAPAGUE, RONNEL',
            'LATINA, DAVE',
            'GARCIA, PAMELA',
            'LOYZAGA, ANGELA',
            'CANAS, LILIOSA B.',
            'CAÑETE, JUSTIN',
            'CACAL, CLENT',
            'VIAL, IRENE',
            'LIBREJO, EMILY',
            'PALMA, ERIKA JANE'
    ];

    public function up(): void
    {
        $index = $this->buildUserIndex();

        $cardOk = 0; $cardMiss = [];
        foreach ($this->cardList as [$name, $acct]) {
            $empID = $this->matchEmpID($name, $index);
            if ($empID === null) { $cardMiss[] = $name; continue; }
            DB::table('emp_details')->where('empID', $empID)->update([
                'empPayrollType' => 'CARD',
                'empCardNo'      => $acct !== '' ? $acct : null,
                'updated_at'     => now(),
            ]);
            $cardOk++;
        }

        $cashOk = 0; $cashMiss = [];
        foreach ($this->cashList as $name) {
            $empID = $this->matchEmpID($name, $index);
            if ($empID === null) { $cashMiss[] = $name; continue; }
            DB::table('emp_details')->where('empID', $empID)->update([
                'empPayrollType' => 'CASH',
                'empCardNo'      => null,
                'updated_at'     => now(),
            ]);
            $cashOk++;
        }

        echo PHP_EOL;
        echo "── Payroll mode update ─────────────────────────────────\n";
        echo "  CARD matched: {$cardOk} / " . count($this->cardList) . "\n";
        echo "  CASH matched: {$cashOk} / " . count($this->cashList) . "\n";
        if ($cardMiss) {
            echo "  UNMATCHED (CARD) — set these manually:\n";
            foreach ($cardMiss as $n) { echo "     - {$n}\n"; }
        }
        if ($cashMiss) {
            echo "  UNMATCHED (CASH) — set these manually:\n";
            foreach ($cashMiss as $n) { echo "     - {$n}\n"; }
        }
        echo "────────────────────────────────────────────────────────\n";
    }

    public function down(): void
    {
        // Revert: any account number that came from this list goes back to CASH.
        $accts = array_values(array_filter(array_map(fn($r) => $r[1], $this->cardList)));
        if ($accts) {
            DB::table('emp_details')
                ->whereIn('empCardNo', $accts)
                ->update(['empPayrollType' => 'CASH', 'empCardNo' => null, 'updated_at' => now()]);
        }
    }

    /** Build [ "NORMLAST|NORMFIRSTTOKEN" => [empID, ...] ] from users. */
    private function buildUserIndex(): array
    {
        $index = [];
        $users = DB::table('users')->select('empID', 'fname', 'lname')->get();
        foreach ($users as $u) {
            if (!$u->empID) { continue; }
            $last  = $this->norm($u->lname);
            $firstTok = $this->firstToken($this->norm($u->fname));
            $key = $last . '|' . $firstTok;
            $index[$key][] = $u->empID;
            // also index full first name for disambiguation
            $fullKey = $last . '||' . $this->norm($u->fname);
            $index[$fullKey][] = $u->empID;
        }
        return $index;
    }

    /** Resolve a "LASTNAME, FIRSTNAME" string to a single empID, or null. */
    private function matchEmpID(string $name, array $index): ?string
    {
        $parts = explode(',', $name, 2);
        $last  = $this->norm($parts[0] ?? '');
        $first = $this->norm($parts[1] ?? '');
        $firstTok = $this->firstToken($first);

        // 1) exact last + full first
        $fullKey = $last . '||' . $first;
        if (!empty($index[$fullKey]) && count($index[$fullKey]) === 1) {
            return $index[$fullKey][0];
        }
        // 2) last + first token
        $key = $last . '|' . $firstTok;
        if (!empty($index[$key])) {
            $ids = array_values(array_unique($index[$key]));
            if (count($ids) === 1) { return $ids[0]; }
        }
        return null; // ambiguous or not found
    }

    private function firstToken(string $s): string
    {
        $s = trim($s);
        if ($s === '') { return ''; }
        $bits = explode(' ', $s);
        return $bits[0];
    }

    /** Uppercase, strip accents (Ñ→N), drop punctuation, collapse spaces. */
    private function norm($s): string
    {
        $s = (string) $s;
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== '') { $s = $t; }
        $s = strtoupper($s);
        $s = preg_replace('/[^A-Z0-9 ]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
};
