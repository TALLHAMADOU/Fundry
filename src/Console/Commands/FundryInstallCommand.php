<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;

class FundryInstallCommand extends Command
{
    protected $signature = 'fundry:install';
    protected $description = 'Installer Fundry (publier config, migrations, etc.)';

    public function handle()
    {
        $this->info('Installation Fundry — à compléter selon besoins.');
        // ex: $this->call('vendor:publish', ['--tag' => 'fundry-config']);
        return self::SUCCESS;
    }
}