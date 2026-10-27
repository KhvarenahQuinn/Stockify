<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The activity report's detail modal.
 *
 * Guards the two ways the feature can silently regress: a trigger that renders
 * as an empty control, and an edit that stores no before/after payload, which
 * would leave the modal claiming there is nothing to show.
 */
class ActivityLogDetailTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $role): User
    {
        $roleModel = Role::firstOrCreate(
            ['name' => $role],
            ['display_name' => ucwords(str_replace('_', ' ', $role))]
        );

        $user = User::factory()->create([
            'role_id' => $roleModel->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_an_update_records_the_values_that_moved(): void
    {
        $this->signIn('admin');

        $category = Category::factory()->create(['name' => 'Old name']);

        app(CategoryService::class)->updateCategory($category->id, ['name' => 'New name']);

        $log = ActivityLog::query()->where('action', 'category.updated')->latest('id')->firstOrFail();

        // Renaming regenerates the slug, so the payload also carries that; the
        // name is what this test is about.
        $this->assertSame('Old name', $log->properties['old']['name']);
        $this->assertSame('New name', $log->properties['new']['name']);
        $this->assertArrayNotHasKey('id', $log->properties['old']);
        $this->assertArrayNotHasKey('updated_at', $log->properties['new']);

        $details = collect($log->changeDetails())->firstWhere('label', 'Name');

        $this->assertSame('Old name', $details['old']);
        $this->assertSame('New name', $details['new']);
    }

    public function test_a_log_without_a_payload_renders_no_change_rows(): void
    {
        $this->signIn('admin');

        $log = ActivityLog::factory()->create([
            'user_id' => auth()->id(),
            'action' => 'user.created',
            'description' => 'Created user',
            'properties' => null,
        ]);

        $this->assertSame([], $log->changeDetails());
        $this->assertFalse($log->hasProperties());
    }

    public function test_a_password_change_is_never_written_to_the_audit_payload(): void
    {
        $this->signIn('admin');

        $staffRole = Role::firstOrCreate(
            ['name' => 'warehouse_staff'],
            ['display_name' => 'Warehouse Staff']
        );

        $target = User::factory()->create([
            'role_id' => $staffRole->id,
            'status' => 'active',
            'name' => 'Before Name',
        ]);

        app(UserService::class)->updateUser($target->id, [
            'name' => 'After Name',
            'email' => $target->email,
            'role_id' => $staffRole->id,
            'status' => 'active',
            'password' => 'a-brand-new-secret',
        ]);

        $log = ActivityLog::query()->where('action', 'user.updated')->latest('id')->firstOrFail();
        $payload = json_encode($log->properties);

        $this->assertSame('After Name', $log->properties['new']['name']);
        $this->assertStringNotContainsString('password', $payload);
        $this->assertStringNotContainsString('remember_token', $payload);
        $this->assertStringNotContainsString('a-brand-new-secret', $payload);
    }

    public function test_deleting_a_user_keeps_a_snapshot_of_the_removed_record(): void
    {
        $this->signIn('admin');

        $staffRole = Role::firstOrCreate(
            ['name' => 'warehouse_staff'],
            ['display_name' => 'Warehouse Staff']
        );

        $target = User::factory()->create([
            'role_id' => $staffRole->id,
            'status' => 'active',
            'name' => 'Siti Rahayu',
            'email' => 'siti@example.com',
        ]);

        app(UserService::class)->deleteUser($target->id);

        $log = ActivityLog::query()->where('action', 'user.deleted')->latest('id')->firstOrFail();

        // The row is gone, so the payload is the only remaining answer to
        // "who was this?" and "what role did they hold?".
        $this->assertSame('Siti Rahayu', $log->subject());
        $this->assertSame('Siti Rahayu', $log->properties['old']['name']);
        $this->assertSame('siti@example.com', $log->properties['old']['email']);
        $this->assertSame('Warehouse Staff', $log->properties['old']['role_id']);
        $this->assertSame([], $log->properties['new']);

        // A snapshot must not resurrect the credentials it captured.
        $payload = json_encode($log->properties);
        $this->assertStringNotContainsString('password', $payload);
        $this->assertStringNotContainsString('remember_token', $payload);

        $details = collect($log->changeDetails())->firstWhere('label', 'Name');
        $this->assertSame('Siti Rahayu', $details['old']);
        $this->assertSame('—', $details['new']);
    }

    public function test_resetting_a_password_names_the_user_and_stores_a_note(): void
    {
        $this->signIn('admin');

        $staffRole = Role::firstOrCreate(
            ['name' => 'warehouse_staff'],
            ['display_name' => 'Warehouse Staff']
        );

        $target = User::factory()->create([
            'role_id' => $staffRole->id,
            'status' => 'active',
            'name' => 'Budi Santoso',
        ]);

        app(UserService::class)->resetPassword($target->id, 'a-brand-new-secret');

        $log = ActivityLog::query()->where('action', 'user.password_reset')->latest('id')->firstOrFail();

        // Without the note the redacted hash leaves the entry looking empty.
        $this->assertSame('Password was reset.', $log->noteKey());
        $this->assertSame('Budi Santoso', $log->subject());
        $this->assertStringContainsString('Budi Santoso', $log->description);
        $this->assertStringNotContainsString((string) $target->id, $log->description);

        $payload = json_encode($log->properties);
        $this->assertStringNotContainsString('a-brand-new-secret', $payload);
        $this->assertStringNotContainsString('password', $payload);
    }

    public function test_the_report_renders_a_visible_details_trigger_and_the_shared_modal(): void
    {
        $manager = $this->signIn('warehouse_manager');

        ActivityLog::factory()->create([
            'user_id' => $manager->id,
            'action' => 'category.updated',
            'description' => 'Updated category Hardware',
            'properties' => [
                'subject' => 'Hardware',
                'note' => 'Password was changed.',
                'old' => ['name' => 'Hardware'],
                'new' => ['name' => 'Hardware & Tools'],
            ],
        ]);

        $this->get(route('reports.activity'))
            ->assertOk()
            ->assertSee('Updated category Hardware')
            // A self-closing <button /> would render as nothing at all; the
            // label is the proof it is a real, clickable control.
            ->assertSee('>Details</span>', false)
            ->assertSee('data-activity-detail', false)
            ->assertSee('activity-log-detail-modal', false)
            // The modal reads these three from the row it was opened for.
            ->assertSee('data-subject="Hardware"', false)
            ->assertSee('data-note="Password was changed."', false)
            ->assertSee('What changed')
            ->assertSee('Hardware &amp; Tools', false);
    }

    public function test_a_description_cannot_inject_markup_into_the_modal_payload(): void
    {
        $manager = $this->signIn('warehouse_manager');

        ActivityLog::factory()->create([
            'user_id' => $manager->id,
            'description' => '<script>alert(1)</script>',
        ]);

        $this->get(route('reports.activity'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
