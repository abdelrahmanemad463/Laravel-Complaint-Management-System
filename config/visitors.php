<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Corrective action due-date tracking
    |--------------------------------------------------------------------------
    |
    | period_hours is the number of hours allowed for a corrective action to be
    | closed, stored on the checklist item, snapshotted on the visit item and
    | copied to each CAPA action when it is created. 0 means the action is
    | Immediate (no due date). The due date is always computed by the backend
    | as the action's created_at + period_hours and never entered by hand.
    |
    */

    'due_soon_hours' => (float) env('VISITORS_DUE_SOON_HOURS', 24),

];