<?php

namespace App\Console\Commands;

use App\Models\TypingTest;
use Illuminate\Console\Command;

class RecalculateTypingTestScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typing-tests:recalculate
                            {--dry-run : Show what would be changed without actually updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate WPM and accuracy for existing typing tests using the corrected formula';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Recalculating typing test scores with corrected formula...');
        $this->newLine();

        $tests = TypingTest::whereNotNull('typed_text')
            ->whereNotNull('original_text')
            ->orderBy('completed_at')
            ->get();

        if ($tests->isEmpty()) {
            $this->warn('No tests found with typed_text data.');

            return Command::SUCCESS;
        }

        $this->info("Found {$tests->count()} tests to recalculate.");
        $this->newLine();

        $updated = 0;
        $unchanged = 0;

        $this->withProgressBar($tests, function ($test) use (&$updated, &$unchanged) {
            // Recalculate using the correct method
            $analysis = TypingTest::analyzeTyping(
                $test->original_text,
                $test->typed_text
            );

            $newWpm = TypingTest::calculateWpm(
                $analysis['total_characters'], // Now uses typed length, not original
                $test->duration_seconds
            );

            $newAccuracy = TypingTest::calculateAccuracy(
                $analysis['correct_characters'],
                $analysis['total_characters']
            );

            $wpmChanged = $test->wpm !== $newWpm;
            $accuracyChanged = abs($test->accuracy - $newAccuracy) > 0.01;

            if ($wpmChanged || $accuracyChanged) {
                if (! $this->option('dry-run')) {
                    $test->update([
                        'wpm' => $newWpm,
                        'accuracy' => $newAccuracy,
                        'total_characters' => $analysis['total_characters'],
                        'correct_characters' => $analysis['correct_characters'],
                        'incorrect_characters' => $analysis['incorrect_characters'],
                    ]);
                }
                $updated++;
            } else {
                $unchanged++;
            }
        });

        $this->newLine(2);

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN - No changes were made');
            $this->info("Would update: {$updated} tests");
        } else {
            $this->info("✅ Successfully updated {$updated} tests");
        }

        $this->info("Unchanged: {$unchanged} tests");

        // Show before/after examples
        if ($updated > 0 && ! $this->option('dry-run')) {
            $this->newLine();
            $this->info('📊 Sample of updated tests:');

            $sampleTests = TypingTest::whereNotNull('completed_at')
                ->orderBy('completed_at', 'desc')
                ->limit(3)
                ->get();

            $this->table(
                ['ID', 'Candidate', 'WPM', 'Accuracy', 'Duration', 'Completed'],
                $sampleTests->map(fn ($test) => [
                    $test->id,
                    $test->candidate?->name ?? 'N/A',
                    $test->wpm,
                    number_format($test->accuracy, 2).'%',
                    $test->duration_seconds.'s',
                    $test->completed_at?->format('M d, Y'),
                ])
            );
        }

        return Command::SUCCESS;
    }
}
