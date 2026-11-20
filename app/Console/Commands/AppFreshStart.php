<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppFreshStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-fresh-start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the Happiee-Bite';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting application setup...');

        // clear all caches
        $this->info('Clearing application caches... ');
        Artisan::call('optimize:clear');
        $this->info('Application caches cleared.');

        // 2. Run database migrations
        $this->info('Running database migrations...');
        Artisan::call('migrate:fresh');
        $this->info('Database migrations completed.');

        // 3. Run database seeders
        $this->info('Running database seeders...');
        Artisan::call('db:seed', [
            '--class' => 'RolePermissionSeeder',
            '--force' => true,
        ]);
        $this->info('Database seeding completed.');

        // 4. Create storage symbolic link
        $this->info('Creating storage link...');
        Artisan::call('storage:link');
        $this->info('Storage link created.');

        // 5. Optimize application
        $this->info('Optimizing application...');
        Artisan::call('optimize');
        $this->info('Application optimization completed.');
        $this->newLine();
        $this->info('Application started successfully!');
    }
}
