<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class AppFreshStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fresh-start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the Happiee-Bite';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting application setup...');

        // 1. Create environment file
        $this->info('Creating environment file...');
        if (!File::exists(base_path('.env'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->info('Environment file created successfully.');
        } else {
            $this->warn('Environment file already exists. Skipping...');
        }

        // 2. Generate application key
        $this->info('Generating application key...');
        Artisan::call('key:generate', ['--force' => true]);
        $this->info('Application key generated successfully.');

        // 3. Clear application caches
        $this->info('Clearing application caches...');
        Artisan::call('optimize:clear');
        $this->info('Application caches cleared.');

        // 4. Run database migrations
        $this->info('Running database migrations...');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->info('Database migrations completed.');

        // 5. Run database seeders
        $this->info('Running database seeders...');
        Artisan::call('db:seed', [
            '--class' => 'RolePermissionSeeder',
            '--force' => true,
        ]);
        $this->info('Database seeding completed.');

        // 6. Create storage symbolic link
        $this->info('Creating storage link...');
        Artisan::call('storage:link');
        $this->info('Storage link created.');

        // 7. Optimize application
        $this->info('Optimizing application...');
        Artisan::call('optimize');
        $this->info('Application optimization completed.');

        $this->newLine();
        $this->info('Application setup completed successfully!');

        return self::SUCCESS;
    }
}
