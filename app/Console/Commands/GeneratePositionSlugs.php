<?php

namespace App\Console\Commands;

use App\Models\Position;
use Illuminate\Console\Command;

class GeneratePositionSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'positions:generate-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for positions that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $positions = Position::whereNull('slug')->orWhere('slug', '')->get();

        if ($positions->isEmpty()) {
            $this->info('All positions already have slugs!');

            return self::SUCCESS;
        }

        $this->info("Generating slugs for {$positions->count()} positions...");

        $bar = $this->output->createProgressBar($positions->count());

        foreach ($positions as $position) {
            $position->slug = Position::generateSlug($position->title, $position->id);
            $position->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Slugs generated successfully!');

        return self::SUCCESS;
    }
}
