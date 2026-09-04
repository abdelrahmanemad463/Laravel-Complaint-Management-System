<?php

namespace App\Services\Visitors;

use App\Models\VisitorCapaAction;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Single source of truth for corrective-action due-date logic.
 *
 * period_hours is stored on the checklist item (master data), snapshotted onto
 * every visit item at visit start (so historical records keep the period that
 * was in force at the time), and copied to the CAPA action when it is created.
 *
 * The due date is always computed by the backend:
 *   due_date = action.created_at + period_hours           (period_hours > 0)
 *   due_date = NULL                                       (period_hours = 0 -> Immediate)
 *
 * The stored period snapshot is authoritative; it is NEVER recomputed against
 * current master data, so old actions keep their original deadline even when
 * master data changes later.
 */
class DueDateService
{
    /**
     * Normalize an imported/stored period value to hours.
     *
     * Accepts numeric values already in hours (0, 0.5, 1, 24, 48, 168 ...).
     * Legacy free-text values found in the old `deadline` column are mapped to
     * their hour equivalent so historical data can be migrated. Text values
     * from NEW imports are rejected by the master-data validator instead.
     *
     * @throws \InvalidArgumentException when the value cannot be interpreted.
     */
    public static function periodToHours(mixed $period): ?float
    {
        if ($period === null || $period === '') {
            return null;
        }

        if (is_int($period) || is_float($period)) {
            return round((float) $period, 2);
        }

        $value = trim((string) $period);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $lookup = [
            'فوري' => 0.0,
            'immediate' => 0.0,
            '30 دقيقة' => 0.5,
            '30 minutes' => 0.5,
            '30 دقائق' => 0.5,
            'ساعة' => 1.0,
            '1 ساعة' => 1.0,
            'hour' => 1.0,
            '1 hour' => 1.0,
            'ساعتان' => 2.0,
            '2 hours' => 2.0,
            '3 ساعات' => 3.0,
            '3 hours' => 3.0,
            '24 ساعة' => 24.0,
            'اليوم' => 24.0,
            '1 day' => 24.0,
            'يوم' => 24.0,
            '48 ساعة' => 48.0,
            '2 days' => 48.0,
            'يومين' => 48.0,
            '3 days' => 72.0,
            '4 days' => 96.0,
            '5 days' => 120.0,
            'أسبوع' => 168.0,
            '1 week' => 168.0,
            'أسبوعين' => 336.0,
            '2 weeks' => 336.0,
            '3 weeks' => 504.0,
            'شهر' => 720.0,
            'شهري' => 720.0,
            '1 month' => 720.0,
            '2 months' => 1440.0,
        ];

        $key = mb_strtolower($value);
        if (array_key_exists($key, $lookup)) {
            return $lookup[$key];
        }

        throw new \InvalidArgumentException("Unrecognized period value: {$value}");
    }

    /**
     * Compute the due date from a base timestamp and period hours.
     * Returns null for Immediate (period_hours 0) actions.
     */
    public static function dueDate(?float $hours, CarbonInterface $base): ?CarbonInterface
    {
        $hours = self::normalizeHours($hours);
        if ($hours === null || $hours <= 0.0) {
            return null;
        }

        return $base instanceof CarbonImmutable ? $base->addHours($hours) : $base->copy()->addHours($hours);
    }

    /**
     * Human-readable label for a period_hours value (locale-aware).
     *
     * 0 -> Immediate, 0.5 -> 30 minutes, 1 -> 1 hour, 24 -> 1 day,
     * 48 -> 2 days, 168 -> 1 week, 720 -> 1 month.
     */
    public static function hoursLabel(?float $hours, ?string $locale = null): string
    {
        $hours = self::normalizeHours($hours);
        if ($hours === null) {
            return (string) __('visitors.period_none', [], $locale);
        }
        if ($hours <= 0.0) {
            return (string) __('visitors.period_immediate', [], $locale);
        }

        if ($hours == 0.5) {
            return (string) __('visitors.period_30min', [], $locale);
        }
        if ($hours == 1.0) {
            return (string) __('visitors.period_1hour', [], $locale);
        }

        $wholeDays = $hours / 24.0;
        if ($wholeDays == 1.0) {
            return (string) __('visitors.period_1day', [], $locale);
        }
        if ($wholeDays == 2.0) {
            return (string) __('visitors.period_2days', [], $locale);
        }
        if ($wholeDays == 3.0) {
            return (string) __('visitors.period_3days', [], $locale);
        }
        if ($wholeDays == 7.0) {
            return (string) __('visitors.period_1week', [], $locale);
        }
        if ($wholeDays == 14.0) {
            return (string) __('visitors.period_2weeks', [], $locale);
        }
        if ($wholeDays == 30.0) {
            return (string) __('visitors.period_1month', [], $locale);
        }

        // Fallback: "48 h" / "2.5 h" style
        $formatted = rtrim(rtrim(number_format($hours, 1), '0'), '.');
        return (string) __('visitors.period_hours_fallback', ['hours' => $formatted], $locale);
    }

    /**
     * Due-status taxonomy for a CAPA action.
     *
     * Open/in-progress actions:
     *   immediate  -> period_hours = 0 (needs action now, no due date)
     *   upcoming   -> due date is in the future, beyond the due-soon threshold
     *   due_soon   -> due date within the configured threshold (default 24h)
     *   overdue    -> due date has passed, action still open
     *
     * Closed actions:
     *   completed   -> closed on or before the due date
     *   closed_late -> closed after the due date (only possible when a due date exists)
     *
     * Rejected actions keep their stored status.
     */
    public static function dueStatus(VisitorCapaAction $action): string
    {
        if ($action->status === 'rejected') {
            return 'rejected';
        }

        if ($action->status === 'closed') {
            if ($action->due_at !== null && $action->completed_at !== null && $action->completed_at->gt($action->due_at)) {
                return 'closed_late';
            }
            return 'completed';
        }

        // open / in_progress
        if ($action->due_at === null) {
            return 'immediate';
        }

        $dueSoonWindow = (float) config('visitors.due_soon_hours', 24);
        $now = now();

        if ($now->gte($action->due_at)) {
            return 'overdue';
        }
        if ($action->due_at->gte($now->copy()->addHours($dueSoonWindow))) {
            return 'upcoming';
        }

        return 'due_soon';
    }

    private static function normalizeHours(?float $hours): ?float
    {
        if ($hours === null) {
            return null;
        }

        return round((float) $hours, 2);
    }
}