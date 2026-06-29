<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Short, unique login handle so employees no longer have to type a
            // long email at sign-in. Nullable: existing rows are backfilled
            // below, and any account with no usable name keeps logging in by email.
            $table->string('username', 50)->nullable()->unique()->after('email');
        });

        // Backfill a username for every existing account. Candidate formats, in
        // order (all ASCII, lowercase, punctuation-stripped so multi-word names
        // collapse to one token):
        //   1. first initial + surname        ("Juan Dela Cruz" -> "jdelacruz")
        //   2. surname initial + first name    (collision swap: "Realyn Cuenca"
        //      -> "crealyn", "Roselyn Cuenca" -> "croselyn")
        // Only if both formats are already taken does it fall back to a numeric
        // suffix on the primary format.
        $used = [];

        DB::table('users')->orderBy('id')
            ->get(['id', 'fname', 'lname', 'empID', 'username'])
            ->each(function ($u) use (&$used) {
                // Never overwrite a handle that somehow already exists.
                if (! empty($u->username)) {
                    $used[strtolower($u->username)] = true;
                    return;
                }

                $username = $this->pickUsername($u->fname, $u->lname, $u->empID, $used);
                if ($username === null) {
                    return; // no usable name or empID -> leave NULL, logs in by email
                }

                $used[strtolower($username)] = true;

                DB::table('users')->where('id', $u->id)->update(['username' => $username]);
            });
    }

    /**
     * Candidate login handles in priority order (see up() for the rules).
     */
    private function usernameCandidates($fname, $lname, $empID): array
    {
        $first = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', Str::ascii((string) $fname)));
        $last  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', Str::ascii((string) $lname)));

        $candidates = [];
        if ($first !== '' && $last !== '') {
            $candidates[] = substr($first, 0, 1) . $last;  // jdelacruz
            $candidates[] = substr($last, 0, 1) . $first;  // crealyn (interchanged)
        } elseif ($last !== '') {
            $candidates[] = $last;
        } elseif ($first !== '') {
            $candidates[] = $first;
        }

        $emp = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $empID));
        if ($emp !== '') {
            $candidates[] = $emp;
        }

        // Cap to the column width, drop empties/dupes, keep order.
        $out = [];
        foreach ($candidates as $c) {
            $c = substr($c, 0, 50);
            if ($c !== '' && ! in_array($c, $out, true)) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /**
     * Pick the first candidate not already in $used; if every format is taken,
     * number the primary one. Returns null when there's nothing usable.
     */
    private function pickUsername($fname, $lname, $empID, array $used): ?string
    {
        $candidates = $this->usernameCandidates($fname, $lname, $empID);
        if (! $candidates) {
            return null;
        }

        foreach ($candidates as $c) {
            if (! isset($used[strtolower($c)])) {
                return $c;
            }
        }

        $base = $candidates[0];
        $i = 1;
        do {
            $suffix   = (string) $i++;
            $username = substr($base, 0, 50 - strlen($suffix)) . $suffix;
        } while (isset($used[strtolower($username)]));

        return $username;
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
