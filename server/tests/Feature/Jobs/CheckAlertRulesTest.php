<?php

use App\Jobs\CheckAlertRules;
use App\Models\AlertRule;
use App\Models\Host;
use App\Services\AlertEvaluator;
use Mockery\MockInterface;

describe('CheckAlertRules job', function () {
    it('calls evaluate once for each active rule', function () {
        $host = Host::factory()->create();
        AlertRule::factory()->count(3)->create(['host_id' => $host->id, 'is_active' => true]);

        $mock = $this->mock(AlertEvaluator::class, function (MockInterface $mock) {
            $mock->expects('evaluate')->times(3);
        });

        (new CheckAlertRules)->handle($mock);
    });

    it('does not call evaluate when there are no active rules', function () {
        AlertRule::factory()->count(2)->inactive()->create();

        $mock = $this->mock(AlertEvaluator::class, function (MockInterface $mock) {
            $mock->expects('evaluate')->never();
        });

        (new CheckAlertRules)->handle($mock);
    });

    it('ignores inactive rules and only processes active ones', function () {
        $host = Host::factory()->create();
        AlertRule::factory()->count(2)->create(['host_id' => $host->id, 'is_active' => true]);
        AlertRule::factory()->count(3)->inactive()->create(['host_id' => $host->id]);

        $mock = $this->mock(AlertEvaluator::class, function (MockInterface $mock) {
            $mock->expects('evaluate')->times(2);
        });

        (new CheckAlertRules)->handle($mock);
    });

    it('eager-loads the host relation on each rule', function () {
        $host = Host::factory()->create();
        AlertRule::factory()->create(['host_id' => $host->id, 'is_active' => true]);

        $calledWithHost = false;

        $mock = $this->mock(AlertEvaluator::class, function (MockInterface $mock) use (&$calledWithHost) {
            $mock->expects('evaluate')->once()->andReturnUsing(function (AlertRule $rule) use (&$calledWithHost) {
                $calledWithHost = $rule->relationLoaded('host');
            });
        });

        (new CheckAlertRules)->handle($mock);

        expect($calledWithHost)->toBeTrue();
    });
});
