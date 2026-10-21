<?php

namespace App\Services;

use App\Models\HPBars;

class HPBarService
{
    /**
     * Split max HP into per-bar ranges. Bar 1 holds the top slice and drains first.
     *
     * @return array<int, array{from: int, to: int}>
     */
    public function segments(int $max, array $splits): array
    {
        $segments = [];
        $to = $max;
        $remainingPct = 100;

        foreach (array_values($splits) as $i => $split) {
            $remainingPct -= $split;
            $from = $i === count($splits) - 1 ? 0 : (int) round($max * max($remainingPct, 0) / 100);
            $segments[] = ['from' => $from, 'to' => $to];
            $to = $from;
        }

        return $segments;
    }

    /**
     * Lowest HP value the current bar can be damaged down to.
     */
    public function segmentFloor(HPBars $hpBar): int
    {
        $options = $hpBar->options;
        if (!$options['multi']['enabled'] || !$options['multi']['capped']) {
            return 0;
        }

        $segments = $this->segments((int) $hpBar->max_hp, $options['multi']['splits']);
        foreach ($segments as $segment) {
            if ($hpBar->current_hp > $segment['from']) {
                return $segment['from'];
            }
        }

        return 0;
    }

    /**
     * Shield absorbs damage first, then HP down to the segment floor.
     */
    public function applyDamage(HPBars $hpBar, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $shield = (int) $hpBar->shield;
        if ($shield > 0) {
            $absorbed = min($shield, $amount);
            $hpBar->shield = $shield - $absorbed;
            $amount -= $absorbed;
        }

        if ($amount > 0) {
            $hpBar->current_hp = max((int) $hpBar->current_hp - $amount, $this->segmentFloor($hpBar));
        }
    }

    public function applyHeal(HPBars $hpBar, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $hpBar->current_hp = min((int) $hpBar->current_hp + $amount, (int) $hpBar->max_hp);
    }
}
