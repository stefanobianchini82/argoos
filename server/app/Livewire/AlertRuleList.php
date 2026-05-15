<?php

namespace App\Livewire;

use App\Models\AlertRule;
use App\Models\Host;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AlertRuleList extends Component
{
    public Host $host;

    public bool $showCopyFrom = false;
    public ?int $selectedSourceHostId = null;
    public int $totalSourceRules = 0;
    public int $newRulesToCopy = 0;

    #[Computed]
    public function otherHosts(): Collection
    {
        return Host::where('id', '!=', $this->host->id)->orderBy('label')->get();
    }

    public function openCopyFrom(): void
    {
        $this->showCopyFrom = true;
        $this->selectedSourceHostId = null;
        $this->totalSourceRules = 0;
        $this->newRulesToCopy = 0;
    }

    public function cancelCopy(): void
    {
        $this->showCopyFrom = false;
        $this->selectedSourceHostId = null;
        $this->totalSourceRules = 0;
        $this->newRulesToCopy = 0;
    }

    public function updatedSelectedSourceHostId(): void
    {
        if (! $this->selectedSourceHostId) {
            $this->totalSourceRules = 0;
            $this->newRulesToCopy = 0;

            return;
        }

        $sourceRules = AlertRule::where('host_id', $this->selectedSourceHostId)->get();
        $existingRules = $this->host->alertRules()->get();

        $this->totalSourceRules = $sourceRules->count();
        $this->newRulesToCopy = $sourceRules->filter(
            fn ($r) => ! $this->isDuplicate($r, $existingRules)
        )->count();
    }

    public function executeCopy(): void
    {
        if (! $this->selectedSourceHostId) {
            return;
        }

        $sourceRules = AlertRule::where('host_id', $this->selectedSourceHostId)->get();
        $existingRules = $this->host->alertRules()->get();

        foreach ($sourceRules as $rule) {
            if ($this->isDuplicate($rule, $existingRules)) {
                continue;
            }

            AlertRule::create([
                'host_id'             => $this->host->id,
                'metric'              => $rule->metric,
                'operator'            => $rule->operator,
                'threshold'           => $rule->threshold,
                'duration_minutes'    => $rule->duration_minutes,
                'channel'             => $rule->channel,
                'channel_target'      => $rule->channel_target,
                'is_active'           => $rule->is_active,
                'excluded_partitions' => $rule->excluded_partitions,
            ]);
        }

        $this->cancelCopy();
    }

    private function isDuplicate(AlertRule $source, Collection $existing): bool
    {
        return $existing->contains(fn ($r) =>
            $r->metric           === $source->metric &&
            $r->operator         === $source->operator &&
            $r->threshold        == $source->threshold &&
            $r->duration_minutes === $source->duration_minutes &&
            $r->channel          === $source->channel &&
            $r->channel_target   === $source->channel_target
        );
    }

    public function toggleActive(int $ruleId): void
    {
        $rule = $this->host->alertRules()->findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function deleteRule(int $ruleId): void
    {
        $this->host->alertRules()->findOrFail($ruleId)->delete();
    }

    public function render()
    {
        return view('livewire.alert-rule-list', [
            'rules' => $this->host->alertRules()->orderBy('metric')->get(),
        ])->layout('layouts.app')->title("Alert Rules — {$this->host->label} — Argoos");
    }
}
