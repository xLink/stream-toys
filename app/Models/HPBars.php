<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class HPBars extends Model
{
    protected $table = 'hp_bars';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $fillable = ['uuid', 'name', 'current_hp', 'max_hp', 'shield', 'options'];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i:s',
        'updated_at' => 'datetime:d-m-Y H:i:s',
    ];

    public const PHASE_SLOTS = 10;

    public static function defaultOptions(): array
    {
        $phases = [
            ['enabled' => true, 'threshold' => 100, 'color' => '#22c55e'],
            ['enabled' => true, 'threshold' => 65, 'color' => '#eab308'],
            ['enabled' => true, 'threshold' => 32, 'color' => '#ef4444'],
        ];
        while (count($phases) < self::PHASE_SLOTS) {
            $phases[] = ['enabled' => false, 'threshold' => 0, 'color' => '#ffffff'];
        }

        return [
            'orientation' => 'horizontal',
            'shield' => [
                'display' => 'overlay',
            ],
            'multi' => [
                'enabled' => false,
                'layout' => 'stacked',
                'splits' => [50, 50],
                'capped' => false,
            ],
            'text' => [
                'position' => 'outside',
                'font_size' => 36,
                'shield_position' => 'bar',
            ],
            'bar' => [
                'thickness' => 80,
                'rounded' => true,
                'radius' => 999,
                'animate' => true,
            ],
            'colors' => [
                'mode' => 'phases',
                'solid' => '#22c55e',
                'background' => '#d1d5db',
                'shield_background' => '#000000',
                'shield_foreground' => '#e1e1e1',
                'phases' => $phases,
            ],
        ];
    }

    /**
     * Stored options merged over the defaults, so older bars (and partial
     * option sets) always have every key.
     */
    protected function options(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::mergeOptions(
                self::defaultOptions(),
                $value ? (json_decode($value, true) ?? []) : []
            ),
            set: fn (?array $value) => $value === null ? null : json_encode($value),
        );
    }

    /**
     * Recursive merge where lists (splits, phases) are replaced wholesale
     * rather than merged index-by-index.
     */
    private static function mergeOptions(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            if (
                is_array($value)
                && isset($defaults[$key])
                && is_array($defaults[$key])
                && !array_is_list($defaults[$key])
            ) {
                $defaults[$key] = self::mergeOptions($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
}
