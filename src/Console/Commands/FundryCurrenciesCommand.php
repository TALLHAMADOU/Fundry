<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;

class FundryCurrenciesCommand extends Command
{
    protected $signature = 'fundry:currencies {action?}';
    protected $description = 'Gérer les devises Fundry (sync, list, seed...)';
 public function handle()
    {
        $this->info('Commande currencies — implémente l\'action souhaitée.');
        return self::SUCCESS;
    }
}