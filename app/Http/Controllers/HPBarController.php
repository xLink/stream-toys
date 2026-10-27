<?php

namespace App\Http\Controllers;

use Closure;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\HPBars;
use App\Services\HPBarService;
use App\Events\HPBar\Update as UpdateEvent;

class HPBarController extends Controller
{

    public function getIndex(?String $bar): Response
    {
        $bar = $bar ?? 'default';

        $hpBar = HPBars::where('name', $bar)->first();

        return Inertia::render('Pages/HPBar', [
            'bar' => $bar,
            'current' => $hpBar->current_hp ?? 0,
            'max' => $hpBar->max_hp ?? 0,
            'shield' => $hpBar->shield ?? 0,
            'options' => $hpBar?->options ?? HPBars::defaultOptions(),
            'showOptions' => request()->boolean('options', false),
        ]);
    }

    public function getSetup(): Response
    {
        return Inertia::render('Pages/HPBar/Setup', [
            'defaults' => HPBars::defaultOptions(),
        ]);
    }

    public function postSetup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/', 'not_in:setup', 'unique:hp_bars,name'],
            'max' => ['required', 'integer', 'min:1'],
            'current' => ['required', 'integer', 'min:0', 'lte:max'],
            'shield' => ['sometimes', 'integer', 'min:0'],
            ...$this->optionsRules(),
        ]);

        $hpBar = new HPBars();
        $hpBar->uuid = \Str::uuid();
        $hpBar->name = $data['name'];
        $hpBar->max_hp = (int) $data['max'];
        $hpBar->current_hp = (int) $data['current'];
        $hpBar->shield = (int) ($data['shield'] ?? 0);
        $hpBar->options = $data['options'];
        $hpBar->save();

        return redirect()->route('hp-bar.index', ['bar' => $hpBar->name, 'options' => 1]);
    }

    public function postOptions(Request $request, ?String $bar)
    {
        $bar = $bar ?? 'default';
        $data = $request->validate([
            'max' => ['required_with:current', 'integer', 'min:1'],
            'current' => ['required_with:max', 'integer', 'min:0', 'lte:max'],
            'shield' => ['sometimes', 'integer', 'min:0'],
            ...$this->optionsRules(),
        ]);

        $hpBar = HPBars::where('name', $bar)->first();

        if (!$hpBar) {
            $hpBar = new HPBars();
            $hpBar->uuid = \Str::uuid();
            $hpBar->name = $bar;
        }

        if (isset($data['max'])) {
            $hpBar->max_hp = (int) $data['max'];
        }
        if (isset($data['current'])) {
            $hpBar->current_hp = (int) $data['current'];
        }
        if (isset($data['shield'])) {
            $hpBar->shield = (int) $data['shield'];
        }
        $hpBar->options = $data['options'];
        $hpBar->save();

        broadcast(
            new UpdateEvent($bar, $hpBar)
        )->toOthers();

        return response()->json([
            'status' => 'OK',
            'message' => 'Options saved!',
            'options' => $hpBar->options,
        ], 200);
    }

    public function update(?String $bar, HPBarService $service)
    {
        $bar = $bar ?? 'default';
        $update = request()->validate([
            'current' => ['sometimes', 'integer', 'min:0'],
            'max' => ['sometimes', 'integer', 'min:1'],
            'shield' => ['sometimes', 'integer', 'min:0'],
            'damage' => ['sometimes', 'integer', 'min:0'],
            'heal' => ['sometimes', 'integer', 'min:0'],
            'update' => ['sometimes', 'boolean'],
        ]);

        if (!isset($update['update'])) {
            return response()->json([
                'status' => 'OK', 
                'message' => 'Update applied.'
            ], 200);
        }

        $hpBar = HPBars::where('name', $bar)->first();
        
        if (!$hpBar) {
            $hpBar = new HPBars();
            $hpBar->uuid = \Str::uuid();
            $hpBar->name = $bar;
        }

        if (isset($update['current'])) {
            $hpBar->current_hp = (int) $update['current'];
        }
        if (isset($update['max'])) {
            $hpBar->max_hp = (int) $update['max'];
        }
        if (isset($update['shield'])) {
            $hpBar->shield = (int) $update['shield'];
        }
        if (isset($update['damage'])) {
            $service->applyDamage($hpBar, (int) $update['damage']);
        }
        if (isset($update['heal'])) {
            $service->applyHeal($hpBar, (int) $update['heal']);
        }
        $hpBar->save();

        broadcast(
            new UpdateEvent($bar, $hpBar)
        )->toOthers();

        return response()->json([
            'status' => 'OK', 
            'message' => 'Update applied!'
        ], 200);
    }

    private function optionsRules(): array
    {
        $hex = 'regex:/^#[0-9a-fA-F]{6}$/';

        return [
            'options' => ['required', 'array:orientation,shield,multi,text,bar,colors'],
            'options.orientation' => ['required', 'in:horizontal,vertical'],

            'options.shield' => ['required', 'array'],
            'options.shield.display' => ['required', 'in:overlay,separate,none'],

            'options.multi' => ['required', 'array'],
            'options.multi.enabled' => ['required', 'boolean'],
            'options.multi.layout' => ['required', 'in:stacked,segmented'],
            'options.multi.capped' => ['required', 'boolean'],
            'options.multi.splits' => [
                'required', 'array', 'min:2', 'max:10',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (is_array($value) && array_sum(array_map('intval', $value)) !== 100) {
                        $fail('The bar splits must add up to 100%.');
                    }
                },
            ],
            'options.multi.splits.*' => ['required', 'integer', 'min:1', 'max:99'],

            'options.text' => ['required', 'array'],
            'options.text.position' => ['required', 'in:inline,outside'],
            'options.text.font_size' => ['required', 'integer', 'min:8', 'max:200'],
            'options.text.shield_position' => ['required', 'in:bar,text,none'],

            'options.bar' => ['required', 'array'],
            'options.bar.thickness' => ['required', 'integer', 'min:10', 'max:200'],
            'options.bar.rounded' => ['required', 'boolean'],
            'options.bar.radius' => ['required', 'integer', 'min:0', 'max:999'],
            'options.bar.animate' => ['required', 'boolean'],

            'options.colors' => ['required', 'array'],
            'options.colors.mode' => ['required', 'in:solid,phases'],
            'options.colors.solid' => ['required', $hex],
            'options.colors.background' => ['required', $hex],
            'options.colors.shield_background' => ['required', $hex],
            'options.colors.shield_foreground' => ['required', $hex],
            'options.colors.phases' => ['required', 'array', 'size:' . HPBars::PHASE_SLOTS],
            'options.colors.phases.*.enabled' => ['required', 'boolean'],
            'options.colors.phases.*.threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'options.colors.phases.*.color' => ['required', $hex],
        ];
    }
}
