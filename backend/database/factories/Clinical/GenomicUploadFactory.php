<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinical\GenomicUpload>
 */
class GenomicUploadFactory extends Factory
{
    protected $model = GenomicUpload::class;

    public function definition(): array
    {
        $formats = ['vcf', 'csv', 'tsv', 'maf'];
        $builds = ['GRCh37', 'GRCh38'];
        $statuses = ['pending', 'parsing', 'mapped', 'review', 'imported', 'failed'];

        return [
            'original_filename' => fake()->word().'.'.fake()->randomElement($formats),
            'stored_path' => 'genomic-uploads/'.fake()->uuid().'.'.fake()->randomElement($formats),
            'file_format' => fake()->randomElement($formats),
            'genome_build' => fake()->randomElement($builds),
            'sample_id' => fake()->optional()->bothify('SAMPLE-####'),
            'status' => fake()->randomElement($statuses),
            'total_variants' => fake()->numberBetween(0, 50000),
            'mapped_variants' => fake()->numberBetween(0, 25000),
            'unmapped_variants' => fake()->numberBetween(0, 5000),
            'file_size' => fake()->numberBetween(1024, 104857600),
            'uploaded_by' => null,
            'parsed_at' => null,
            'matched_at' => null,
            'imported_at' => null,
            'clinvar_annotated_at' => null,
            'error_message' => null,
            'last_result' => null,
        ];
    }
}
