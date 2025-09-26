<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Models\HPBars;
use App\Events\HPBar\Update as UpdateEvent;

class HPBarController extends Controller
{

    public function getIndex(?String $bar): Response
    {
        $bar = $bar ?? 'default';

        $hpBar = HPBars::where('name', $bar)->first();

        return Inertia::render('Pages/HPBar', [
            'bar' => $bar,
            'current' => $hpBar->current_hp,
            'max' => $hpBar->max_hp,
        ]);
    }

    public function update(?String $bar) 
    {
        $bar = $bar ?? 'default';
        $update = request()->validate([
            'current' => ['sometimes', 'integer', 'min:0'],
            'max' => ['sometimes', 'integer', 'min:1'],
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
        $hpBar->save();

        broadcast(
            new UpdateEvent($bar, $hpBar)
        )->toOthers();

        return response()->json([
            'status' => 'OK', 
            'message' => 'Update applied!'
        ], 200);
    }
}
