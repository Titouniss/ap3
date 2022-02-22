<?php

namespace App\Console;

use App\Models\BaseModule;
use App\Models\Document;
use App\Models\ModelHasDocuments;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            // Sync modules
            BaseModule::where('is_active', 1)->get()->each(function ($item) {
                $item->sync();
            });

            // Delete unused files if too old
            $documents = Document::whereNotNull('token')->get();
            foreach ($documents as $doc) {
                if (
                    ModelHasDocuments::where('document_id', $doc->id)->doesntExist()
                    && Carbon::parse($doc->created_at)->isBefore(Carbon::now()->subDays(2))
                ) {
                    $doc->deleteFile();
                }
            }

            // Update subscription states based on date
            Subscription::whereDate('starts_at', '<', Carbon::now())->where('state', 'pending')->update(['state' => 'active']);
            Subscription::whereDate('ends_at', '<', Carbon::now())->where('state', 'active')->update(['state' => 'inactive']);
        })->dailyAt("00:00");
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
