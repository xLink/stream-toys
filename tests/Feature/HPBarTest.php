<?php

use App\Models\HPBars;
use App\Services\HPBarService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
});

function hpBarOptions(array $overrides = []): array
{
    return array_replace_recursive(HPBars::defaultOptions(), $overrides);
}

function makeHPBar(array $attributes = []): HPBars
{
    $hpBar = new HPBars();
    $hpBar->uuid = \Str::uuid();
    $hpBar->name = $attributes['name'] ?? 'boss';
    $hpBar->max_hp = $attributes['max_hp'] ?? 100;
    $hpBar->current_hp = $attributes['current_hp'] ?? 100;
    $hpBar->shield = $attributes['shield'] ?? 0;
    $hpBar->options = $attributes['options'] ?? HPBars::defaultOptions();
    $hpBar->save();

    return $hpBar;
}

test('setup creates a bar with options and redirects to the options editor', function () {
    $options = hpBarOptions(['orientation' => 'vertical']);

    $this->post('/hp-bar/setup', [
        'name' => 'boss',
        'max' => 200,
        'current' => 150,
        'options' => $options,
    ])->assertRedirect('/hp-bar/boss?options=1');

    $hpBar = HPBars::where('name', 'boss')->firstOrFail();
    expect($hpBar->max_hp)->toBe(200)
        ->and($hpBar->current_hp)->toBe(150)
        ->and($hpBar->options['orientation'])->toBe('vertical');
});

test('setup rejects duplicate and reserved names', function () {
    makeHPBar(['name' => 'boss']);

    foreach (['boss', 'setup', 'bad name!'] as $name) {
        $this->post('/hp-bar/setup', [
            'name' => $name,
            'max' => 100,
            'current' => 100,
            'options' => hpBarOptions(),
        ])->assertSessionHasErrors('name');
    }
});

test('options are validated', function (array $overrides, string $errorKey) {
    makeHPBar();

    $options = hpBarOptions($overrides);
    if ($errorKey === 'options.colors.phases') {
        array_pop($options['colors']['phases']);
    }

    $this->postJson('/hp-bar/boss/options', ['options' => $options])
        ->assertStatus(422)
        ->assertJsonValidationErrors($errorKey);
})->with([
    'splits not 100' => [['multi' => ['splits' => [50, 40]]], 'options.multi.splits'],
    'bad hex' => [['colors' => ['solid' => 'red']], 'options.colors.solid'],
    'bad orientation' => [['orientation' => 'diagonal'], 'options.orientation'],
    'nine phase slots' => [[], 'options.colors.phases'],
]);

test('options are saved', function () {
    makeHPBar();

    $options = hpBarOptions([
        'multi' => ['enabled' => true, 'splits' => [50, 30, 20], 'capped' => true],
        'text' => ['position' => 'inline'],
        'bar' => ['rounded' => true, 'radius' => 12, 'animate' => false],
    ]);

    $this->postJson('/hp-bar/boss/options', [
        'max' => 300,
        'current' => 250,
        'shield' => 40,
        'options' => $options,
    ])->assertOk();

    $hpBar = HPBars::where('name', 'boss')->firstOrFail();
    expect($hpBar->max_hp)->toBe(300)
        ->and($hpBar->current_hp)->toBe(250)
        ->and($hpBar->shield)->toBe(40);

    $saved = $hpBar->options;
    expect($saved['multi']['splits'])->toBe([50, 30, 20])
        ->and($saved['multi']['capped'])->toBeTrue()
        ->and($saved['text']['position'])->toBe('inline')
        ->and($saved['bar']['radius'])->toBe(12)
        ->and($saved['bar']['animate'])->toBeFalse();
});

test('bars without stored options fall back to defaults', function () {
    $hpBar = makeHPBar();
    $hpBar->options = null;
    $hpBar->save();

    expect($hpBar->fresh()->options)->toBe(HPBars::defaultOptions());
});

test('damage via update respects capped bars', function () {
    makeHPBar([
        'current_hp' => 80,
        'options' => hpBarOptions(['multi' => ['enabled' => true, 'splits' => [50, 30, 20], 'capped' => true]]),
    ]);

    $this->get('/hp-bar/boss/update?update=1&damage=500')->assertOk();
    expect(HPBars::where('name', 'boss')->first()->current_hp)->toBe(50);

    $this->get('/hp-bar/boss/update?update=1&damage=500')->assertOk();
    expect(HPBars::where('name', 'boss')->first()->current_hp)->toBe(20);
});

test('damage via update spills over when uncapped, shield first', function () {
    makeHPBar([
        'current_hp' => 80,
        'shield' => 10,
        'options' => hpBarOptions(['multi' => ['enabled' => true, 'splits' => [50, 50], 'capped' => false]]),
    ]);

    $this->get('/hp-bar/boss/update?update=1&damage=60')->assertOk();

    $hpBar = HPBars::where('name', 'boss')->first();
    expect($hpBar->shield)->toBe(0)
        ->and($hpBar->current_hp)->toBe(30);
});

test('heal via update is capped at max hp', function () {
    makeHPBar(['current_hp' => 90]);

    $this->get('/hp-bar/boss/update?update=1&heal=50')->assertOk();
    expect(HPBars::where('name', 'boss')->first()->current_hp)->toBe(100);
});

test('segments split max hp from the top down', function () {
    expect((new HPBarService())->segments(100, [50, 30, 20]))->toBe([
        ['from' => 50, 'to' => 100],
        ['from' => 20, 'to' => 50],
        ['from' => 0, 'to' => 20],
    ]);
});
