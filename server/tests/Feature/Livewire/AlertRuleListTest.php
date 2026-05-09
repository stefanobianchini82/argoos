<?php

use App\Livewire\AlertRuleList;
use App\Models\AlertRule;
use App\Models\Host;
use Livewire\Livewire;

beforeEach(function () {
    $this->host = Host::factory()->create();
});

describe('AlertRuleList — toggleActive', function () {
    it('deactivates an active rule', function () {
        $rule = AlertRule::factory()->create(['host_id' => $this->host->id, 'is_active' => true]);

        Livewire::test(AlertRuleList::class, ['host' => $this->host])
            ->call('toggleActive', $rule->id);

        expect($rule->fresh()->is_active)->toBeFalse();
    });

    it('activates an inactive rule', function () {
        $rule = AlertRule::factory()->inactive()->create(['host_id' => $this->host->id]);

        Livewire::test(AlertRuleList::class, ['host' => $this->host])
            ->call('toggleActive', $rule->id);

        expect($rule->fresh()->is_active)->toBeTrue();
    });

    it('throws when toggling a rule belonging to another host', function () {
        $otherHost = Host::factory()->create();
        $rule = AlertRule::factory()->create(['host_id' => $otherHost->id]);

        expect(fn () => Livewire::test(AlertRuleList::class, ['host' => $this->host])
            ->call('toggleActive', $rule->id)
        )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});

describe('AlertRuleList — deleteRule', function () {
    it('deletes the rule from the database', function () {
        $rule = AlertRule::factory()->create(['host_id' => $this->host->id]);

        Livewire::test(AlertRuleList::class, ['host' => $this->host])
            ->call('deleteRule', $rule->id);

        expect(AlertRule::find($rule->id))->toBeNull();
    });

    it('throws when deleting a rule belonging to another host', function () {
        $otherHost = Host::factory()->create();
        $rule = AlertRule::factory()->create(['host_id' => $otherHost->id]);

        expect(fn () => Livewire::test(AlertRuleList::class, ['host' => $this->host])
            ->call('deleteRule', $rule->id)
        )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});
