<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitorVisit;

class VisitorVisitPolicy
{
    /**
     * A user can access a visit only if they own it (are the inspector).
     * Completed visits are read-only even for the owner.
     */
    public function view(User $user, VisitorVisit $visit): bool
    {
        return (int) $visit->inspector_id === (int) $user->id;
    }

    public function update(User $user, VisitorVisit $visit): bool
    {
        return $this->view($user, $visit) && !$visit->isCompleted();
    }

    public function submit(User $user, VisitorVisit $visit): bool
    {
        return $this->view($user, $visit) && !$visit->isCompleted();
    }

    /**
     * Reports follow the application's roles/permissions, not the owner rule.
     * Management roles (whose permissions include visit.manage) may view every
     * completed report; roles with only report.view may additionally view
     * completed reports they inspected. In-progress visits are never reports.
     */
    public function viewReport(User $user, VisitorVisit $visit): bool
    {
        if (!$visit->isCompleted()) {
            return false;
        }
        if ($user->can('visit.manage')) {
            return true;
        }
        if ($user->can('report.view')) {
            return (int) $visit->inspector_id === (int) $user->id;
        }
        return false;
    }
}
