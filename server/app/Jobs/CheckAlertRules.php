<?php

namespace App\Jobs;

use App\Models\AlertRule;
use App\Services\AlertEvaluator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckAlertRules implements ShouldQueue
{
    use Queueable;

    public function handle(AlertEvaluator $evaluator): void
    {
        AlertRule::active()->with('host')->each(function (AlertRule $rule) use ($evaluator) {
            $evaluator->evaluate($rule);
        });
    }
}
