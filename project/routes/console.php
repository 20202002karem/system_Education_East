<?php

use App\Console\Commands\VerifyAuditChain;
use Illuminate\Support\Facades\Schedule;

Schedule::command(VerifyAuditChain::class)->dailyAt('02:00');
