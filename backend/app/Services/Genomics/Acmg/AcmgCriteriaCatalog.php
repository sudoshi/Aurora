<?php

namespace App\Services\Genomics\Acmg;

/**
 * The active ACMG/AMP 2015 evidence codes (Richards et al., PMID 25741868),
 * excluding SVI-deprecated PP5/BP6. PM2 defaults to Supporting (SVI Sept-2020).
 * Each entry: [category, default_strength, automatable, standalone, description].
 */
class AcmgCriteriaCatalog
{
    private const CRITERIA = [
        // Pathogenic
        'PVS1' => ['pathogenic', AcmgStrength::VeryStrong, false, false, 'Null variant in a gene where loss-of-function is a known disease mechanism'],
        'PS1' => ['pathogenic', AcmgStrength::Strong, true, false, 'Same amino-acid change as an established pathogenic variant'],
        'PS2' => ['pathogenic', AcmgStrength::Strong, false, false, 'De novo (maternity and paternity confirmed) in a patient with the disease'],
        'PS3' => ['pathogenic', AcmgStrength::Strong, false, false, 'Well-established functional studies show a damaging effect'],
        'PS4' => ['pathogenic', AcmgStrength::Strong, false, false, 'Prevalence in affected individuals significantly increased vs controls'],
        'PM1' => ['pathogenic', AcmgStrength::Moderate, false, false, 'Located in a mutational hotspot or critical functional domain'],
        'PM2' => ['pathogenic', AcmgStrength::Supporting, true, false, 'Absent or extremely low frequency in population databases'],
        'PM3' => ['pathogenic', AcmgStrength::Moderate, false, false, 'For recessive disorders, detected in trans with a pathogenic variant'],
        'PM4' => ['pathogenic', AcmgStrength::Moderate, false, false, 'Protein length change (in-frame indel / stop-loss) in a non-repeat region'],
        'PM5' => ['pathogenic', AcmgStrength::Moderate, true, false, 'Novel missense at a residue where a different pathogenic missense was seen'],
        'PM6' => ['pathogenic', AcmgStrength::Moderate, false, false, 'Assumed de novo without confirmation of maternity and paternity'],
        'PP1' => ['pathogenic', AcmgStrength::Supporting, false, false, 'Co-segregation with disease in multiple affected family members'],
        'PP2' => ['pathogenic', AcmgStrength::Supporting, false, false, 'Missense in a gene with low benign-missense rate where missense is a mechanism'],
        'PP3' => ['pathogenic', AcmgStrength::Supporting, true, false, 'Calibrated in-silico evidence supports a deleterious effect'],
        'PP4' => ['pathogenic', AcmgStrength::Supporting, false, false, 'Patient phenotype/family history highly specific for a single-gene disease'],
        // Benign
        'BA1' => ['benign', AcmgStrength::Strong, true, true, 'Allele frequency >5% in population databases (stand-alone benign)'],
        'BS1' => ['benign', AcmgStrength::Strong, true, false, 'Allele frequency greater than expected for the disorder'],
        'BS2' => ['benign', AcmgStrength::Strong, false, false, 'Observed in healthy adults where full penetrance is expected'],
        'BS3' => ['benign', AcmgStrength::Strong, false, false, 'Well-established functional studies show no damaging effect'],
        'BS4' => ['benign', AcmgStrength::Strong, false, false, 'Lack of segregation in affected family members'],
        'BP1' => ['benign', AcmgStrength::Supporting, false, false, 'Missense in a gene where only truncating variants cause disease'],
        'BP2' => ['benign', AcmgStrength::Supporting, false, false, 'Observed in trans/cis with a pathogenic variant inconsistent with disease'],
        'BP3' => ['benign', AcmgStrength::Supporting, false, false, 'In-frame indel in a repetitive region without known function'],
        'BP4' => ['benign', AcmgStrength::Supporting, true, false, 'Calibrated in-silico evidence supports no impact'],
        'BP5' => ['benign', AcmgStrength::Supporting, false, false, 'Variant found in a case with an alternate molecular cause of disease'],
        'BP7' => ['benign', AcmgStrength::Supporting, false, false, 'Synonymous with no predicted splice impact and no conservation'],
    ];

    /** @return array<string, array{category:string,default_strength:AcmgStrength,automatable:bool,standalone:bool,description:string}> */
    public static function all(): array
    {
        $out = [];
        foreach (self::CRITERIA as $code => [$category, $strength, $auto, $standalone, $desc]) {
            $out[$code] = [
                'category' => $category,
                'default_strength' => $strength,
                'automatable' => $auto,
                'standalone' => $standalone,
                'description' => $desc,
            ];
        }

        return $out;
    }

    public static function exists(string $code): bool
    {
        return isset(self::CRITERIA[$code]);
    }

    public static function category(string $code): string
    {
        return self::CRITERIA[$code][0] ?? throw new \InvalidArgumentException("Unknown ACMG code: {$code}");
    }

    public static function defaultStrength(string $code): AcmgStrength
    {
        return self::CRITERIA[$code][1] ?? throw new \InvalidArgumentException("Unknown ACMG code: {$code}");
    }

    public static function isStandalone(string $code): bool
    {
        return self::CRITERIA[$code][3] ?? false;
    }
}
