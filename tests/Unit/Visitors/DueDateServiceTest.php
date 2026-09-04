<?php

namespace Tests\Unit\Visitors;

use App\Models\VisitorCapaAction;
use App\Services\Visitors\DueDateService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DueDateServiceTest extends TestCase
{
    // ---- periodToHours ----

    public function test_numeric_periods_are_returned_as_hours(): void
    {
        $this->assertSame(null, DueDateService::periodToHours(null));
        $this->assertSame(null, DueDateService::periodToHours(''));
        $this->assertSame(0.0, DueDateService::periodToHours(0));
        $this->assertSame(0.5, DueDateService::periodToHours(0.5));
        $this->assertSame(24.0, DueDateService::periodToHours('24'));
        $this->assertSame(2.5, DueDateService::periodToHours('2.5'));
    }

    public function test_legacy_text_periods_are_mapped_to_hours(): void
    {
        $this->assertSame(0.0, DueDateService::periodToHours('فوري'));
        $this->assertSame(24.0, DueDateService::periodToHours('1 day'));
        $this->assertSame(48.0, DueDateService::periodToHours('2 days'));
        $this->assertSame(168.0, DueDateService::periodToHours('1 week'));
        $this->assertSame(720.0, DueDateService::periodToHours('1 month'));
    }

    public function test_unknown_text_period_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DueDateService::periodToHours('quarterly');
    }

    // ---- dueDate ----

    public function test_due_date_is_base_plus_period_hours(): void
    {
        $base = CarbonImmutable::parse('2026-09-01 10:00:00');
        $this->assertNull(DueDateService::dueDate(null, $base));
        $this->assertSame('2026-09-02 10:00:00', DueDateService::dueDate(24, $base)?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01 10:30:00', DueDateService::dueDate(0.5, $base)?->format('Y-m-d H:i:s'));
    }

    public function test_immediate_actions_have_no_due_date(): void
    {
        $base = CarbonImmutable::parse('2026-09-01 10:00:00');
        $this->assertNull(DueDateService::dueDate(0, $base));
        $this->assertNull(DueDateService::dueDate(-2, $base));
    }

    // ---- hoursLabel ----

    public function test_hours_label_maps_common_values(): void
    {
        $this->assertSame('Immediate', DueDateService::hoursLabel(0, 'en'));
        $this->assertSame('30 minutes', DueDateService::hoursLabel(0.5, 'en'));
        $this->assertSame('1 hour', DueDateService::hoursLabel(1, 'en'));
        $this->assertSame('1 day', DueDateService::hoursLabel(24, 'en'));
        $this->assertSame('2 days', DueDateService::hoursLabel(48, 'en'));
        $this->assertSame('1 week', DueDateService::hoursLabel(168, 'en'));
        $this->assertSame('1 month', DueDateService::hoursLabel(720, 'en'));
    }

    public function test_hours_label_falls_back_for_arbitrary_values(): void
    {
        $label = DueDateService::hoursLabel(2.5, 'en');
        $this->assertStringContainsString('2.5', $label);
    }

    // ---- dueStatus ----

    private function action(string $status, ?CarbonImmutable $due = null, ?CarbonImmutable $completed = null): VisitorCapaAction
    {
        $action = new VisitorCapaAction([
            'status' => $status,
            'due_at' => $due,
            'completed_at' => $completed,
        ]);
        $action->exists = true;

        return $action;
    }

    public function test_open_action_without_due_date_is_immediate(): void
    {
        $this->assertSame('immediate', DueDateService::dueStatus($this->action('open')));
    }

    public function test_open_action_past_due_is_overdue(): void
    {
        $due = CarbonImmutable::now()->subDay();
        $this->assertSame('overdue', DueDateService::dueStatus($this->action('in_progress', $due)));
    }

    public function test_open_action_due_within_window_is_due_soon(): void
    {
        $due = CarbonImmutable::now()->addHours(config('visitors.due_soon_hours', 24) - 1);
        $this->assertSame('due_soon', DueDateService::dueStatus($this->action('open', $due)));
    }

    public function test_open_action_due_beyond_window_is_upcoming(): void
    {
        $due = CarbonImmutable::now()->addHours((config('visitors.due_soon_hours', 24) * 3) + 6);
        $this->assertSame('upcoming', DueDateService::dueStatus($this->action('open', $due)));
    }

    public function test_closed_action_is_completed_unless_late(): void
    {
        $due = CarbonImmutable::now()->subDay();
        $this->assertSame('closed_late', DueDateService::dueStatus($this->action('closed', $due, CarbonImmutable::now())));
        $this->assertSame('closed_late', DueDateService::dueStatus($this->action('closed', $due, $due->copy()->addHour())));
    }

    public function test_closed_action_without_due_date_is_completed(): void
    {
        $this->assertSame('completed', DueDateService::dueStatus($this->action('closed', null, CarbonImmutable::now())));
    }

    public function test_rejected_action_keeps_stored_status(): void
    {
        $this->assertSame('rejected', DueDateService::dueStatus($this->action('rejected')));
    }
}