<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;

class FundryCryptoCommand extends Command
{
    protected $signature = 'fundry:crypto {action?}';
    protected $description = 'Opérations crypto (sync rates, import data...)';

    public function handle()
    {
        $this->info('Commande crypto — à compléter.');
        return self::SUCCESS;
    }
}