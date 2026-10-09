<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** D-33 / BR-M3-02 — yearly reference numbers from the M1 `sequences` table. Call inside a transaction. */
class SequenceService
{
    private const PREFIX = ['request' => 'REQ', 'task' => 'TSK'];

    public function next(string $scope): string
    {
        $year = (int) now('UTC')->format('Y');
        DB::table('sequences')->insertOrIgnore(['scope' => $scope, 'year' => $year, 'last_value' => 0]);
        DB::table('sequences')->where('scope', $scope)->where('year', $year)->lockForUpdate()->first();
        DB::table('sequences')->where('scope', $scope)->where('year', $year)->increment('last_value');
        $value = (int) DB::table('sequences')->where('scope', $scope)->where('year', $year)->value('last_value');

        return sprintf('%s-%d-%06d', self::PREFIX[$scope], $year, $value);
    }
}
