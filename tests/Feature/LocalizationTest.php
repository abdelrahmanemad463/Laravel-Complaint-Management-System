<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_core_flash_and_activity_messages_exist_in_both_locales(): void
    {
        $messages = [
            'en' => [
                'complaint_created' => 'Complaint created successfully.',
                'complaint_updated' => 'Complaint updated successfully.',
                'customer_created' => 'Customer created successfully.',
                'login_failed' => 'These credentials do not match our records.',
                'activity_created' => 'Complaint created.',
            ],
            'ar' => [
                'complaint_created' => 'تم إنشاء الشكوى بنجاح.',
                'complaint_updated' => 'تم تحديث الشكوى بنجاح.',
                'customer_created' => 'تم إنشاء العميل بنجاح.',
                'login_failed' => 'بيانات الاعتماد غير مطابقة لسجلاتنا.',
                'activity_created' => 'تم إنشاء الشكوى.',
            ],
        ];

        foreach ($messages as $locale => $expected) {
            app()->setLocale($locale);
            $this->assertSame($expected['complaint_created'], __('complaints.created'));
            $this->assertSame($expected['complaint_updated'], __('complaints.updated'));
            $this->assertSame($expected['customer_created'], __('customers.created'));
            $this->assertSame($expected['login_failed'], __('auth.failed'));
            $this->assertSame($expected['activity_created'], __('activity.complaint.created'));
        }
    }

    public function test_theme_switch_labels_are_localized(): void
    {
        $this->assertSame('Dark mode', __('common.dark_mode', [], 'en'));
        $this->assertSame('Light mode', __('common.light_mode', [], 'en'));
        $this->assertSame('تبديل مظهر الألوان.', __('common.theme_switcher', [], 'ar'));
        $this->assertSame('الوضع الداكن', __('common.dark_mode', [], 'ar'));
        $this->assertSame('الوضع الفاتح', __('common.light_mode', [], 'ar'));
    }

    public function test_dashboard_labels_are_localized(): void
    {
        $this->assertSame('Dashboard filters', __('common.dashboard_filters', [], 'en'));
        $this->assertSame('Hold Ctrl/Cmd to select multiple branches', __('common.multi_select_hint', [], 'en'));
        $this->assertSame('فلاتر لوحة التحكم', __('common.dashboard_filters', [], 'ar'));
        $this->assertSame('اضغط Ctrl/Cmd لاختيار عدة فروع', __('common.multi_select_hint', [], 'ar'));
        $this->assertSame('Solved and Closed are counted as resolved.', __('common.resolution_definition', [], 'en'));
        $this->assertSame('تُحتسب حالتا تم الحل ومغلقة ضمن الحالات المحلولة.', __('common.resolution_definition', [], 'ar'));
    }

    public function test_login_and_customer_summary_use_selected_locale(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $customer = Customer::factory()->create();

        $this->withSession(['locale' => 'ar'])->get(route('login'))->assertOk()->assertSee('نظام الشكاوى')->assertSee('تجريبي: admin@example.com / password');
        $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('customers.show', $customer))->assertOk()->assertSee('قيد الانتظار')->assertSee('قيد المعالجة')->assertSee('تم الحل')->assertSee('مغلقة')->assertDontSee('In Progress');
    }

    public function test_audit_log_renders_action_key_in_selected_locale(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'complaint.updated',
            'description' => 'complaints.updated',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->withSession(['locale' => 'ar'])
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('تم تحديث الشكوى.')
            ->assertDontSee('complaints.updated');

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Complaint updated.')
            ->assertDontSee('complaints.updated');
    }
}
