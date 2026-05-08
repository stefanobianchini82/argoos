<?php

namespace App\Livewire;

use App\Models\Host;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Host — Argoos')]
class HostEdit extends Component
{
    public Host $host;

    public string $label       = '';
    public string $description = '';
    public string $ip          = '';

    protected array $rules = [
        'label'       => ['required', 'string', 'max:100'],
        'description' => ['nullable', 'string', 'max:5000'],
        'ip'          => ['nullable', 'string', 'max:45'],
    ];

    public function mount(): void
    {
        $this->label       = $this->host->label;
        $this->description = $this->host->description ?? '';
        $this->ip          = $this->host->ip ?? '';
    }

    public function save(): void
    {
        $this->validate();

        $this->host->update([
            'label'       => trim($this->label),
            'description' => trim($this->description) ?: null,
            'ip'          => trim($this->ip) ?: null,
        ]);

        $this->redirectRoute('hosts.show', $this->host);
    }

    public function render()
    {
        return view('livewire.host-edit')
            ->layout('layouts.app', ['title' => 'Edit ' . $this->host->label . ' — Argoos']);
    }
}
