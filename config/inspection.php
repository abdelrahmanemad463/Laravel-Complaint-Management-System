<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inspection scoring
    |--------------------------------------------------------------------------
    |
    | The inspection score is computed dynamically from every completed visit's
    | immutable item snapshots (visitors_visit_items). Each Non-Compliant (NC)
    | item deducts its own deduction_score from the visit's available points;
    | the final score = available - deduction and percentage = final / available.
    |
    | Any item whose snapshot severity is Critical and whose status is
    | Non-Compliant always deducts this fixed amount instead of the item's
    | normal deduction_score. The deduction applies per qualifying item
    | (one visit with 2 Critical NC items deducts 2 x this value), and it is
    | applied identically by the single-visit report, the reports list, the
    | dashboard/branch analytics and the color-band filters, all of which read
    | the same VisitScoreService helpers.
    |
    | Change this single value to 30 or 50 later; no other file needs editing.
    |
    */

    'critical_deduction' => (int) env('INSPECTION_CRITICAL_DEDUCTION', 40),

];