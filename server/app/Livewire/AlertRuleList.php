<?php

namespace App\Livewire;

use App\Models\AlertRule;
use App\Models\Host;
use Livewire\Attributes\Title;
use Livewire\Component;

class AlertRuleList extends Component
{
    public Host $host;

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
