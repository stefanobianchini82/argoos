<?php

namespace App\Livewire;

use App\Models\Host;
use App\Models\HttpCheck;
use Livewire\Component;

class HttpCheckList extends Component
{
    public Host $host;

    public function toggleActive(int $checkId): void
    {
        $check = $this->host->httpChecks()->findOrFail($checkId);
        $check->update(['is_active' => ! $check->is_active]);
    }

    public function deleteCheck(int $checkId): void
    {
        $this->host->httpChecks()->findOrFail($checkId)->delete();
    }

    public function render()
    {
        $checks = $this->host->httpChecks()
            ->with('openEvent')
            ->orderBy('label')
            ->get();

        return view('livewire.http-check-list', [
            'checks' => $checks,
        ])->layout('layouts.app')->title("HTTP Checks — {$this->host->label} — Argoos");
    }
}
