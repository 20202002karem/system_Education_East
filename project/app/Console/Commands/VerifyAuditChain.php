<?php

namespace App\Console\Commands;

use App\Models\AuditChainCheck;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

/**
 * M1 criterion 5 — daily scheduled hash-chain verification (Batch 2 §B9).
 * Alerting mechanism on result=broken is left [مفتوح] (Batch 2 §B9); this
 * command only records the check row and logs a critical-level log line.
 */
class VerifyAuditChain extends Command
{
    protected $signature = 'audit:verify-chain';
    protected $description = 'Verify the audit_log hash chain (M1 criterion 5) and record the result in audit_chain_checks.';

    public function handle(AuditLogger $auditLogger): int
    {
        $result = $auditLogger->verifyChain();

        AuditChainCheck::create([
            'run_at' => now('UTC'),
            'from_seq' => $result['from_seq'],
            'to_seq' => $result['to_seq'],
            'result' => $result['result'],
        ]);

        if ($result['result'] === 'broken') {
            $this->error("Audit chain BROKEN at seq {$result['broken_at_seq']}");
            logger()->critical('audit_chain_broken', $result);

            return self::FAILURE;
        }

        $this->info('Audit chain OK.');

        return self::SUCCESS;
    }
}
