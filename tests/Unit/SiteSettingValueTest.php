<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use PHPUnit\Framework\TestCase;

/**
 * Settings are stored as text and decoded by type, so the panel can keep
 * booleans, JSON and plain strings in a single table.
 */
class SiteSettingValueTest extends TestCase
{
    private function setting(string $type, ?string $value): SiteSetting
    {
        $setting = new SiteSetting;
        $setting->forceFill(['type' => $type, 'value' => $value]);

        return $setting;
    }

    public function test_strings_are_returned_untouched(): void
    {
        $this->assertSame('HIVEDFOOD', $this->setting('string', 'HIVEDFOOD')->decoded());
        $this->assertSame("Linea 1\nLinea 2", $this->setting('text', "Linea 1\nLinea 2")->decoded());
    }

    public function test_json_settings_decode_to_arrays(): void
    {
        $value = '[{"label":"Plataforma","url":"#plataforma"}]';

        $this->assertSame(
            [['label' => 'Plataforma', 'url' => '#plataforma']],
            $this->setting('json', $value)->decoded()
        );
    }

    public function test_broken_json_degrades_to_an_empty_array(): void
    {
        $this->assertSame([], $this->setting('json', '{no es json')->decoded());
        $this->assertSame([], $this->setting('json', null)->decoded());
    }

    public function test_booleans_accept_the_values_the_panel_submits(): void
    {
        foreach (['1', 'true', 'on', 'yes'] as $truthy) {
            $this->assertTrue($this->setting('boolean', $truthy)->decoded(), $truthy.' debe ser verdadero');
        }

        foreach (['0', 'false', 'off', '', null] as $falsy) {
            $this->assertFalse($this->setting('boolean', $falsy)->decoded(), var_export($falsy, true).' debe ser falso');
        }
    }
}
