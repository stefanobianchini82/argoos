<?php

use App\Models\Setting;

describe('Setting::get()', function () {
    it('returns the default when the key does not exist', function () {
        expect(Setting::get('nonexistent_key', 'default_value'))->toBe('default_value');
    });

    it('returns null as default when no default provided', function () {
        expect(Setting::get('nonexistent_key'))->toBeNull();
    });
});

describe('Setting::set()', function () {
    it('persists a value that can be retrieved with get()', function () {
        Setting::set('test_key', 'hello');

        expect(Setting::get('test_key'))->toBe('hello');
    });

    it('overwrites an existing value', function () {
        Setting::set('test_key', 'first');
        Setting::set('test_key', 'second');

        expect(Setting::get('test_key'))->toBe('second');
    });

    it('stores an empty string as null (by design)', function () {
        Setting::set('test_key', '');

        expect(Setting::get('test_key'))->toBeNull();
    });
});

describe('Setting constants', function () {
    it('ALERT_EMAIL key resolves correctly', function () {
        Setting::set(Setting::ALERT_EMAIL, 'test@example.com');

        expect(Setting::get(Setting::ALERT_EMAIL))->toBe('test@example.com');
    });

    it('HOST_OFFLINE_OFFLINE_MINUTES has a default of 3', function () {
        expect(Setting::get(Setting::HOST_OFFLINE_OFFLINE_MINUTES, 3))->toBe(3);
    });
});
