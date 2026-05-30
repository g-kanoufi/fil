<?php

declare(strict_types=1);

return [
    /** When false, royalties:calculate-due exits without processing stores. */
    'enable_royalty_calculation_job' => (bool) env('FIL_ENABLE_ROYALTY_CALC_JOB', false),

    /** When false, ach:process-due-transfers exits without creating transfers. */
    'enable_ach_royalty_collection' => (bool) env('FIL_ENABLE_ACH_COLLECTION', false),

    /** Day of month (1–28) when areas:calculate-royalties aggregates area fees. */
    'area_royalties_calc_day' => (int) env('FIL_AREA_ROYALTIES_CALC_DAY', 1),

    /** Default area royalty percentage when not set on the area record. */
    'default_area_royalty_percentage' => (float) env('FIL_DEFAULT_AREA_ROYALTY_PCT', 0.5),
];
