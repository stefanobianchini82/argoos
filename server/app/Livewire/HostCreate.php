<?php

namespace App\Livewire;

use App\Models\Host;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New Host — Argoos')]
class HostCreate extends Component
{
    public string $label       = '';
    public string $description = '';
    public string $ip          = '';
    public string $tags        = '';

    public bool   $created      = false;
    public string $generatedKey = '';

    protected array $rules = [
        'label'       => ['required', 'string', 'max:100'],
        'description' => ['nullable', 'string', 'max:5000'],
        'ip'          => ['nullable', 'string', 'max:45'],
        'tags'        => ['nullable', 'string', 'max:500'],
    ];

    public function save(): void
    {
        $this->validate();

        $plaintext = Str::random(48);
        $prefix    = substr($plaintext, 0, 12);

        $host = Host::create([
            'label'          => trim($this->label),
            'description'    => trim($this->description) ?: null,
            'ip'             => trim($this->ip) ?: null,
            'api_key'        => bcrypt($plaintext),
            'api_key_prefix' => $prefix,
        ]);

        $host->syncTags(
            collect(explode(',', $this->tags))
                ->map(fn ($t) => trim($t))
                ->filter()
                ->values()
                ->all()
        );

        Cache::forget('dashboard.available_tags');

        $this->generatedKey = $plaintext;
        $this->created      = true;
    }

    public function render()
    {
        return view('livewire.host-create')->layout('layouts.app');
    }
}
