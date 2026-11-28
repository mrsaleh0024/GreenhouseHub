<?php

namespace Tests\Feature\Audit;

use App\Models\Activity;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ActivityImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_update_delete_and_destroy_are_blocked(): void
    {
        $activity = $this->activity();

        foreach ([
            'update' => fn () => $activity->update(['event' => 'tampered']),
            'delete' => fn () => $activity->delete(),
            'forceDelete' => fn () => $activity->forceDelete(),
            'destroy' => fn () => Activity::destroy($activity->id),
        ] as $operation => $callback) {
            try {
                $callback();
                $this->fail("Activity {$operation} was not blocked.");
            } catch (LogicException $exception) {
                $this->assertContains($exception->getMessage(), [
                    'رویدادها تغییرناپذیرند.',
                    'رویدادها حذف‌پذیر نیستند.',
                ]);
            }
        }

        $this->assertSame('created', $activity->fresh()->event);
        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
    }

    public function test_query_builder_bulk_mutations_are_blocked(): void
    {
        $activity = $this->activity();

        $operations = [
            'bulk update' => fn () => Activity::query()->whereKey($activity->id)->update(['event' => 'tampered']),
            'bulk delete' => fn () => Activity::query()->whereKey($activity->id)->delete(),
            'bulk force delete' => fn () => Activity::query()->forceDelete(),
            'upsert' => fn () => Activity::query()->upsert([['id' => $activity->id, 'event' => 'tampered']], ['id']),
            'insert' => fn () => Activity::query()->insert(['event' => 'tampered']),
            'insert or ignore' => fn () => Activity::query()->insertOrIgnore(['event' => 'tampered']),
            'truncate' => fn () => Activity::query()->truncate(),
            'update or insert' => fn () => Activity::query()->updateOrInsert(['id' => $activity->id], ['event' => 'tampered']),
            'increment' => fn () => Activity::query()->increment('id'),
            'decrement' => fn () => Activity::query()->decrement('id'),
            'increment each' => fn () => Activity::query()->incrementEach(['id' => 1]),
            'decrement each' => fn () => Activity::query()->decrementEach(['id' => 1]),
        ];

        foreach ($operations as $operation => $callback) {
            try {
                $callback();
                $this->fail("Activity {$operation} was not blocked.");
            } catch (LogicException $exception) {
                $this->assertContains($exception->getMessage(), [
                    'رویدادها تغییرناپذیرند.',
                    'رویدادها حذف‌پذیر نیستند.',
                ]);
            }
        }

        $this->assertSame('created', $activity->fresh()->event);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_database_triggers_block_direct_query_builder_writes(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['sqlite', 'mysql', 'mariadb', 'pgsql'], true)) {
            $this->markTestSkipped('Database-level activity immutability triggers are not installed for this driver.');
        }

        $activity = $this->activity();

        foreach ([
            'direct update' => fn () => DB::table('activities')->where('id', $activity->id)->update(['event' => 'tampered']),
            'direct delete' => fn () => DB::table('activities')->where('id', $activity->id)->delete(),
        ] as $operation => $callback) {
            try {
                $callback();
                $this->fail("{$operation} was not blocked by the database trigger.");
            } catch (QueryException $exception) {
                $this->assertStringContainsString('immutable', strtolower($exception->getMessage()));
            }
        }

        $this->assertSame('created', $activity->fresh()->event);
        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
    }

    public function test_sqlite_activity_triggers_are_installed(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('SQLite-specific trigger assertion.');
        }

        $triggers = DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->where('tbl_name', 'activities')
            ->pluck('name')
            ->all();

        $this->assertContains('activities_prevent_update', $triggers);
        $this->assertContains('activities_prevent_delete', $triggers);
    }

    private function activity(): Activity
    {
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);

        return Activity::query()
            ->where('subject_type', $customer->getMorphClass())
            ->where('subject_id', $customer->getKey())
            ->where('event', 'created')
            ->sole();
    }
}
