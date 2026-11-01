<?php

namespace App\Console\Commands;

use App\Models\Tetris\Room;
use Illuminate\Console\Command;

class CleanupTetrisRooms extends Command
{
    protected $signature = 'tetris:cleanup {--days=10 : Days of inactivity before removal}';
    protected $description = 'Remove Tetris MP rooms inactive for the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $rooms = Room::where('updated_at', '<', $cutoff)->get();

        if ($rooms->isEmpty()) {
            $this->info("No rooms inactive for {$days}+ days.");
            return 0;
        }

        foreach ($rooms as $room) {
            $pieceCount = $room->pieces()->count();
            $playerCount = $room->players()->count();
            $room->pieces()->delete();
            $room->players()->delete();
            $room->delete();
            $this->line("Deleted room {$room->id} ({$room->name}) - {$pieceCount} pieces, {$playerCount} players");
        }

        $this->info("Cleaned up {$rooms->count()} inactive room(s).");
        return 0;
    }
}
