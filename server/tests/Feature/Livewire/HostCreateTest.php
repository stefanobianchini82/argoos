<?php

use App\Livewire\HostCreate;
use App\Models\Host;
use Livewire\Livewire;

describe('HostCreate component', function () {
    it('renders without errors', function () {
        Livewire::test(HostCreate::class)->assertOk();
    });

    it('creates a host and shows the generated key on save', function () {
        Livewire::test(HostCreate::class)
            ->set('label', 'My Server')
            ->set('ip', '192.168.1.10')
            ->call('save')
            ->assertSet('created', true)
            ->assertSet('generatedKey', fn ($key) => strlen($key) === 48);

        expect(Host::where('label', 'My Server')->exists())->toBeTrue();
    });

    it('stores description and ip correctly', function () {
        Livewire::test(HostCreate::class)
            ->set('label', 'DB Server')
            ->set('description', 'Database host')
            ->set('ip', '10.0.0.5')
            ->call('save');

        $host = Host::where('label', 'DB Server')->first();
        expect($host->description)->toBe('Database host');
        expect($host->ip)->toBe('10.0.0.5');
    });

    it('shows a validation error when label is missing', function () {
        Livewire::test(HostCreate::class)
            ->call('save')
            ->assertHasErrors(['label' => 'required']);
    });

    it('generates a bcrypt-verifiable api key', function () {
        $component = Livewire::test(HostCreate::class)
            ->set('label', 'Test Host')
            ->call('save');

        $generatedKey = $component->get('generatedKey');
        $host = Host::where('label', 'Test Host')->first();

        expect(password_verify($generatedKey, $host->api_key))->toBeTrue();
    });

    it('stores the first 12 characters as api_key_prefix', function () {
        $component = Livewire::test(HostCreate::class)
            ->set('label', 'Prefix Test')
            ->call('save');

        $generatedKey = $component->get('generatedKey');
        $host = Host::where('label', 'Prefix Test')->first();

        expect($host->api_key_prefix)->toBe(substr($generatedKey, 0, 12));
    });

    it('trims whitespace from label', function () {
        Livewire::test(HostCreate::class)
            ->set('label', '  Trimmed  ')
            ->call('save');

        expect(Host::where('label', 'Trimmed')->exists())->toBeTrue();
    });
});
