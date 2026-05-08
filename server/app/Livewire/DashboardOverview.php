<?php

namespace App\Livewire;

use App\Models\Host;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class DashboardOverview extends Component
{
    public function render()
    {
        return view('livewire.dashboard-overview', [
            'hosts' => Host::with('latestMetric')->orderBy('label')->get(),
        ])->layout('layouts.app');
    }
}
