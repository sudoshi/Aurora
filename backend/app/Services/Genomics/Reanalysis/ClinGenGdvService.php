<?php

namespace App\Services\Genomics\Reanalysis;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches and parses the ClinGen Gene-Disease Validity (GDV) curations CSV.
 *
 * The CSV is publicly available without authentication at:
 *   https://search.clinicalgenome.org/kb/gene-validity/download
 *
 * It contains 6 preamble/header lines before data rows.  We identify real data rows
 * by requiring col[1] to match HGNC:\d+ — this naturally skips all preamble,
 * separator (++++), and header lines.
 *
 * Results are cached for 24 hours to avoid hammering the external endpoint.
 * All failures degrade to an empty array — never throws into the caller.
 */
class ClinGenGdvService
{
    /**
     * Return all GDV curations as an array of assoc arrays.
     *
     * @return array<int, array{gene_symbol:string, gene_hgnc:string, disease_label:string, disease_id:string, moi:string, classification:string, report_url:string, classification_date:string}>
     */
    public function fetchAll(): array
    {
        return Cache::remember('clingen_gdv_csv', now()->addHours(24), fn () => $this->download());
    }

    /**
     * Return all GDV curations for a given gene symbol (case-insensitive).
     *
     * @return array<int, array<string, string>>
     */
    public function byGene(string $gene): array
    {
        return array_values(
            array_filter(
                $this->fetchAll(),
                fn (array $row) => strcasecmp($row['gene_symbol'], $gene) === 0
            )
        );
    }

    /**
     * Download and parse the CSV.  Returns [] on any failure.
     *
     * @return array<int, array<string, string>>
     */
    private function download(): array
    {
        try {
            $url = (string) config('services.clingen_gdv.csv_url');
            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                return [];
            }

            $lines = explode("\n", $response->body());
            $rows = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $cols = str_getcsv($line);

                // Real data rows have a valid HGNC identifier in col[1].
                // This naturally skips: title line, FILE CREATED, WEBPAGE,
                // ++++ separator lines, and the column-header line.
                if (! isset($cols[1]) || ! preg_match('/^HGNC:\d+/', $cols[1])) {
                    continue;
                }

                $rows[] = [
                    'gene_symbol' => (string) ($cols[0] ?? ''),
                    'gene_hgnc' => (string) ($cols[1] ?? ''),
                    'disease_label' => (string) ($cols[2] ?? ''),
                    'disease_id' => (string) ($cols[3] ?? ''),
                    'moi' => (string) ($cols[4] ?? ''),
                    'classification' => (string) ($cols[6] ?? ''),
                    'report_url' => (string) ($cols[7] ?? ''),
                    'classification_date' => (string) ($cols[8] ?? ''),
                ];
            }

            return $rows;
        } catch (\Throwable $e) {
            Log::warning('ClinGen GDV CSV download failed: '.$e->getMessage());

            return [];
        }
    }
}
