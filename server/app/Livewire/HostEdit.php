<?php

namespace App\Livewire;

use App\Models\Host;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Host — Argoos')]
class HostEdit extends Component
{
    public Host $host;

    public string $label       = '';
    public string $description = '';
    public string $ip          = '';
    public string $tags        = '';

    public bool    $confirmingRegenerate = false;
    public ?string $regeneratedKey       = null;
    public bool    $regenerated          = false;

    protected array $rules = [
        'label'       => ['required', 'string', 'max:100'],
        'description' => ['nullable', 'string', 'max:5000'],
        'ip'          => ['nullable', 'string', 'max:45'],
        'tags'        => ['nullable', 'string', 'max:500'],
    ];

    public function mount(): void
    {
        $this->label       = $this->host->label;
        $this->description = $this->host->description ?? '';
        $this->ip          = $this->host->ip ?? '';
        $this->tags        = $this->host->tags->pluck('name')->implode(', ');
    }

    public function save(): void
    {
        $this->validate();

        $this->host->update([
            'label'       => trim($this->label),
            'description' => trim($this->description) ?: null,
            'ip'          => trim($this->ip) ?: null,
        ]);

        $this->host->syncTags(
            collect(explode(',', $this->tags))
                ->map(fn ($t) => trim($t))
                ->filter()
                ->values()
                ->all()
        );

        Cache::forget('dashboard.available_tags');

        $this->redirectRoute('hosts.show', $this->host);
    }

    public function confirmRegenerate(): void
    {
        $this->confirmingRegenerate = true;
    }

    public function cancelRegenerate(): void
    {
        $this->confirmingRegenerate = false;
    }

    public function regenerateApiKey(): void
    {
        $plaintext = Str::random(48);
        $prefix    = substr($plaintext, 0, 12);

        $this->host->update([
            'api_key'        => bcrypt($plaintext),
            'api_key_prefix' => $prefix,
        ]);

        $this->confirmingRegenerate = false;
        $this->regeneratedKey       = $plaintext;
        $this->regenerated          = true;
    }

    public function render()
    {
        return view('livewire.host-edit')
            ->layout('layouts.app', ['title' => 'Edit ' . $this->host->label . ' — Argoos']);
    }
}
