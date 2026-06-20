<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Clinical Data Adapter
    |--------------------------------------------------------------------------
    |
    | Supported values: manual, fhir, omop.
    |
    | The FHIR and OMOP adapters are local projections over Aurora's clinical
    | schema. They preserve existing app-facing arrays while adding standards
    | metadata for interoperability workflows.
    |
    */
    'adapter' => env('CLINICAL_DATA_ADAPTER', 'manual'),
];
