<?php

namespace Hamadou\Fundry\Console\Commands;

use Hamadou\Fundry\Exports\FundryExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Hamadou\Fundry\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;
use Illuminate\Support\Facades\File;

class FundryReportCommand extends Command
{
    protected $signature = 'fundry:report 
                            {type : transactions|wallets|currencies|limits}
                            {--format=pdf : Format de sortie (pdf|excel)}
                            {--start-date= : Date de début (YYYY-MM-DD)}
                            {--end-date= : Date de fin (YYYY-MM-DD)}
                            {--user-id= : Filtrer par utilisateur}
                            {--wallet-type= : Type de portefeuille}
                            {--currency= : Devise spécifique}
                            {--output= : Fichier de sortie}';

    protected $description = 'Générer des rapports PDF ou Excel pour les données Fundry';

    public function handle(): int
    {
        $type = strtolower((string) $this->argument('type'));
        $format = strtolower((string) ($this->option('format') ?? 'pdf'));
        $outputFile = $this->option('output');

        $validTypes = ['transactions', 'wallets', 'currencies', 'limits'];
        if (!in_array($type, $validTypes, true)) {
            $this->error('Type de rapport invalide. Types valides : ' . implode(', ', $validTypes));
            return self::INVALID;
        }

        $validFormats = ['pdf', 'excel'];
        if (!in_array($format, $validFormats, true)) {
            $this->error('Format de sortie invalide. Formats valides : ' . implode(', ', $validFormats));
            return self::INVALID;
        }

        $this->info("📊 Génération du rapport {$type} en format {$format}...");

        try {
            $data = $this->getDataByType($type);

            if ($data->isEmpty()) {
                $this->warn("Aucune donnée trouvée pour le type: {$type}");
                return self::SUCCESS;
            }

            $fileName = $outputFile ?: $type . '_report_' . now()->format('Y_m_d_His');
            $storagePath = config('fundry.exports.storage_path') ?: storage_path('app/reports');
            File::ensureDirectoryExists($storagePath);

            $extension = $format === 'pdf' ? 'pdf' : config('fundry.exports.excel.format', 'xlsx');

            if ($format === 'pdf') {
                $this->generatePdf($type, $data, "{$storagePath}/{$fileName}.{$extension}");
            } else {
                $this->generateExcel($type, $data, "reports/{$fileName}.{$extension}");
            }

            $this->info("✅ Rapport généré avec succès: {$storagePath}/{$fileName}.{$extension}");
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la génération: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getDataByType(string $type)
    {
        return match ($type) {
            'transactions' => $this->getTransactionsData(),
            'wallets' => $this->getWalletsData(),
            'currencies' => $this->getCurrenciesData(),
            'limits' => $this->getLimitsData(),
            default => collect(),
        };
    }

    private function getTransactionsData()
    {
        $query = Transaction::with(['user', 'fromWallet', 'toWallet', 'currency']);

        $this->applyFilter($query, 'start-date', fn ($q, $v) => $q->whereDate('created_at', '>=', $v));
        $this->applyFilter($query, 'end-date', fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
        $this->applyFilter($query, 'user-id', fn ($q, $v) => $q->where('user_id', $v));
        $this->applyFilter($query, 'currency', function ($q, $v) {
            $code = strtoupper((string) $v);
            $q->whereHas('currency', fn ($sub) => $sub->where('iso_code', $code)->orWhere('code', $code));
        });

        return $query->orderBy('created_at', 'desc')->get();
    }

    private function getWalletsData()
    {
        $query = Wallet::with(['user', 'currency']);

        $this->applyFilter($query, 'wallet-type', fn ($q, $v) => $q->where('type', $v));
        $this->applyFilter($query, 'user-id', fn ($q, $v) => $q->where('user_id', $v));
        $this->applyFilter($query, 'currency', function ($q, $v) {
            $code = strtoupper((string) $v);
            $q->whereHas('currency', fn ($sub) => $sub->where('iso_code', $code)->orWhere('code', $code));
        });

        return $query->orderBy('balance', 'desc')->get();
    }

    private function getCurrenciesData()
    {
        return Currency::orderBy('type')->orderBy('name')->get();
    }

    private function getLimitsData()
    {
        $limits = config('fundry.limits', []);
        return collect($limits)->map(function ($limit, $type) {
            return [
                'type' => $type,
                'limit' => is_array($limit) ? ($limit['daily_limit'] ?? 0) : $limit,
            ];
        })->values();
    }

    private function generatePdf(string $type, $data, string $filePath): void
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException('PDF generation requires barryvdh/laravel-dompdf. Install with: composer require barryvdh/laravel-dompdf:^3.0 (Laravel 9–13), or ^2.0 on legacy apps.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView("fundry::reports.{$type}", [
            'data' => $data,
            'filters' => $this->options(),
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])->setPaper(
            config('fundry.exports.pdf.paper', 'a4'),
            config('fundry.exports.pdf.orientation', 'portrait')
        )->setWarnings(false);

        $pdf->save($filePath);
    }

    private function generateExcel(string $type, $data, string $filePath): void
    {
        Excel::store(new FundryExport($data, $type), $filePath);
    }

    private function applyFilter(Builder $query, string $option, callable $callback): void
    {
        if ($value = $this->option($option)) {
            $callback($query, $value);
        }
    }
}
