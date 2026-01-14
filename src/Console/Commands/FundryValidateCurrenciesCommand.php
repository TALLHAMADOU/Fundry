<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;

class FundryValidateCurrenciesCommand extends Command
{
    protected $signature = 'fundry:validate-currencies 
                            {--fix : Corriger automatiquement les erreurs détectées}';
    
    protected $description = 'Valider toutes les devises et détecter les problèmes';

    public function handle()
    {
        $this->info('🔍 Validation des devises...');
        $this->newLine();

        $currencies = Currency::all();
        $errors = [];
        $warnings = [];
        $fixed = 0;

        foreach ($currencies as $currency) {
            $issues = $this->validateCurrency($currency, $this->option('fix'));
            
            if (!empty($issues['errors'])) {
                $errors[$currency->iso_code ?? $currency->code] = $issues['errors'];
            }
            
            if (!empty($issues['warnings'])) {
                $warnings[$currency->iso_code ?? $currency->code] = $issues['warnings'];
            }
            
            if ($issues['fixed']) {
                $fixed++;
            }
        }

        // Afficher les résultats
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Toutes les devises sont valides!');
        } else {
            if (!empty($errors)) {
                $this->error('❌ Erreurs détectées:');
                foreach ($errors as $code => $errorList) {
                    $this->line("  • {$code}: " . implode(', ', $errorList));
                }
                $this->newLine();
            }

            if (!empty($warnings)) {
                $this->warn('⚠️  Avertissements:');
                foreach ($warnings as $code => $warningList) {
                    $this->line("  • {$code}: " . implode(', ', $warningList));
                }
                $this->newLine();
            }
        }

        if ($fixed > 0) {
            $this->info("🔧 {$fixed} devise(s) corrigée(s) automatiquement.");
        }

        $this->info("📊 Total de devises validées: {$currencies->count()}");
        
        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    private function validateCurrency(Currency $currency, bool $fix = false): array
    {
        $errors = [];
        $warnings = [];
        $fixed = false;

        // Vérifier le code ISO 4217 pour les devises fiat
        if ($currency->type->value === 'fiat') {
            if (empty($currency->iso_code)) {
                $errors[] = 'Code ISO 4217 manquant';
                if ($fix && !empty($currency->code)) {
                    $currency->iso_code = strtoupper($currency->code);
                    $currency->save();
                    $fixed = true;
                }
            } elseif (!Currency::isValidIso4217Code($currency->iso_code)) {
                $errors[] = "Code ISO 4217 invalide: {$currency->iso_code}";
            }
        }

        // Vérifier le taux de change
        if ($currency->exchange_rate <= 0) {
            $errors[] = "Taux de change invalide: {$currency->exchange_rate}";
        }

        // Vérifier la relation avec le pays pour les devises fiat
        if ($currency->type->value === 'fiat' && empty($currency->country_id)) {
            $warnings[] = 'Aucun pays associé';
            
            if ($fix && !empty($currency->iso_code)) {
                $country = Country::where('currency_code', $currency->iso_code)->first();
                if ($country) {
                    $currency->country_id = $country->id;
                    $currency->save();
                    $fixed = true;
                }
            }
        }

        // Vérifier que le pays existe si country_id est défini
        if ($currency->country_id) {
            $country = Country::find($currency->country_id);
            if (!$country) {
                $errors[] = "Pays associé introuvable (ID: {$currency->country_id})";
                if ($fix) {
                    $currency->country_id = null;
                    $currency->save();
                    $fixed = true;
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'fixed' => $fixed,
        ];
    }
}
