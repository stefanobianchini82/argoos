<?php

namespace App\Livewire;

use App\Models\Host;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New Host — Argoos')]
class HostCreate extends Component
{
    public string $label       = '';
    public string $description = '';
    public string $ip          = '';

    public bool   $created      = false;
    public string $generatedKey = '';

    protected array $rules = [
        'label'       => ['required', 'string', 'max:100'],
        'description' => ['nullable', 'string', 'max:5000'],
        'ip'          => ['nullable', 'string', 'max:45'],
    ];

    public function save(): void
    {
        $this->validate();

        $plaintext = Str::random(48);
        $prefix    = substr($plaintext, 0, 12);

        Host::create([
            'label'          => trim($this->label),
            'description'    => trim($this->description) ?: null,
            'ip'             => trim($this->ip) ?: null,
            'api_key'        => bcrypt($plaintext),
            'api_key_prefix' => $prefix,
        ]);

        $this->generatedKey = $plaintext;
        $this->created      = true;
    }

    public function render()
    {
        return view('livewire.host-create')->layout('layouts.app');
    }
}
