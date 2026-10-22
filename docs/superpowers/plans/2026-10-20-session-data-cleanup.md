# Session Data Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Добавить безопасную автоматическую очистку сессионных данных старше 14 дней.

**Architecture:** Laravel scheduled command + batch hard delete + retention по сессионному полю `date`. Для `products_plan` используется отдельная безопасная leaf-first логика удаления, чтобы не ломать parent/child цепочки планов.

**Tech Stack:** PHP 8.2, Laravel 11, MySQL 8, Laravel Scheduler, PHPUnit / `php artisan test`.

**Spec:** Согласованный chat-design от 2026-10-20. Формальный spec-файл не выносился отдельно; источник правды — согласованное в conversation проектное решение:
- чистим все сессионные данные;
- retention по `date`;
- запуск через scheduler + cron;
- hard delete;
- master data не трогаем.

## Global Constraints

- Не добавляем новые зависимости.
- Не вводим queue / jobs.
- Не вводим soft delete.
- Не трогаем `products_slots`, `lines`, `workers`, `products_dictionary`, `companies`, `responsible`, `lines_defaults`.
- Retention считается только по сессионному полю `date`, а не по `started_at` / `ended_at`.
- Timezone по умолчанию — `Europe/Moscow`.
- Записи с `date = NULL` не удалять автоматически, а показывать в отчёте как anomaly.
- Коммиты не выполнять без явного разрешения человека.

## Review Focus

- `date = NULL` — команда должна пропустить такие строки и показать их в отчёте.
- Ночная смена с `ended_at` на `D+1` — retention всё равно должен смотреть только на `date`.
- Старый parent, на который ссылается новый child — parent удалять нельзя.
- Старая parent/child цепочка без новых children — должна удаляться полностью, но сначала “листья”, потом parent.
- Повторный запуск после partial failure — должен быть идемпотентным и не ломать данные.
- Large-table deletion — не должна делать один большой long-running delete, а идти батчами.

---

# File Structure

## Create

- `config/retention.php`
  - retention-настройки: `days`, `timezone`, `batch_size`.
- `database/migrations/2026_10_20_000000_add_session_retention_indexes.php`
  - индексы для cleanup и текущих сессионных запросов.
- `app/Console/Commands/CleanupSessionData.php`
  - artisan-команда очистки.
- `tests/Feature/CleanupSessionDataTest.php`
  - feature-тесты команды.

## Modify

- `routes/console.php`
  - регистрация scheduled command.
- `.env.example`
  - exposure retention-переменных.
- `README.md`
  - краткая инструкция по команде и cron.

---

# Task 1: Retention indexes

**Files:**
- Create: `database/migrations/2026_10_20_000000_add_session_retention_indexes.php`
- Test: `tests/Feature/CleanupSessionDataTest.php`

**Interfaces:**
- Consumes:
  - существующие таблицы:
    - `products_plan`
    - `slots`
    - `lines_extra`
    - `products_order`
    - `workers_breaks`
    - `logs`
- Produces:
  - индексы:
    - `products_plan_date_isday_index`
    - `products_plan_parent_index`
    - `slots_date_isday_index`
    - `lines_extra_date_isday_index`
    - `products_order_date_isday_index`
    - `workers_breaks_date_isday_index`
    - `logs_date_isday_index`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CleanupSessionDataTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanupSessionDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retention_indexes_exist(): void
    {
        $this->assertIndexExists('products_plan', 'products_plan_date_isday_index');
        $this->assertIndexExists('products_plan', 'products_plan_parent_index');
        $this->assertIndexExists('slots', 'slots_date_isday_index');
        $this->assertIndexExists('lines_extra', 'lines_extra_date_isday_index');
        $this->assertIndexExists('products_order', 'products_order_date_isday_index');
        $this->assertIndexExists('workers_breaks', 'workers_breaks_date_isday_index');
        $this->assertIndexExists('logs', 'logs_date_isday_index');
    }

    private function assertIndexExists(string $table, string $index): void
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'`');
        $names = array_column($rows, 'Key_name');
        $this->assertContains($index, $names);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=CleanupSessionDataTest
```

Expected: FAIL, потому что индексы ещё не созданы.

- [ ] **Step 3: Create migration**

Create `database/migrations/2026_10_20_000000_add_session_retention_indexes.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dateIndexes = [
            ['products_plan', 'products_plan_date_isday_index'],
            ['slots', 'slots_date_isday_index'],
            ['lines_extra', 'lines_extra_date_isday_index'],
            ['products_order', 'products_order_date_isday_index'],
            ['workers_breaks', 'workers_breaks_date_isday_index'],
            ['logs', 'logs_date_isday_index'],
        ];

        foreach ($dateIndexes as [$table, $index]) {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->index(['date', 'isDay'], $index);
            });
        }

        Schema::table('products_plan', function (Blueprint $table) {
            $table->index('parent', 'products_plan_parent_index');
        });
    }

    public function down(): void
    {
        $dateIndexes = [
            ['products_plan', 'products_plan_date_isday_index'],
            ['slots', 'slots_date_isday_index'],
            ['lines_extra', 'lines_extra_date_isday_index'],
            ['products_order', 'products_order_date_isday_index'],
            ['workers_breaks', 'workers_breaks_date_isday_index'],
            ['logs', 'logs_date_isday_index'],
        ];

        foreach ($dateIndexes as [$table, $index]) {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        }

        Schema::table('products_plan', function (Blueprint $table) {
            $table->dropIndex('products_plan_parent_index');
        });
    }
};
```

- [ ] **Step 4: Apply migration in testing environment**

Run:
```bash
php artisan migrate --force
```

Expected: SUCCESS.

- [ ] **Step 5: Run test to verify it passes**

Run:
```bash
php artisan test --filter=CleanupSessionDataTest
```

Expected: PASS.

- [ ] **Step 6: Run linter**

Run:
```bash
vendor/bin/pint --test
```

Expected: PASS.

---

# Task 2: Retention config + cleanup command

**Files:**
- Create: `config/retention.php`
- Create: `app/Console/Commands/CleanupSessionData.php`
- Test: `tests/Feature/CleanupSessionDataTest.php`

**Interfaces:**
- Consumes:
  - индексы из Task 1
- Produces:
  - artisan-команда `cleanup:session-data`
  - options:
    - `--dry-run`
    - `--days=`
    - `--cutoff=`
    - `--batch=`
  - config keys:
    - `retention.days`
    - `retention.timezone`
    - `retention.batch_size`

- [ ] **Step 1: Extend failing tests**

Replace `tests/Feature/CleanupSessionDataTest.php` with:

```php
<?php

namespace Tests\Feature;

use App\Models\LinesExtra;
use App\Models\Logs;
use App\Models\ProductsOrder;
use App\Models\ProductsPlan;
use App\Models\Slots;
use App\Models\WorkersBreaks;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanupSessionDataTest extends TestCase
{
    use DatabaseTransactions;

    private const CUTOFF = '2026-10-06';
    private const OLD_DATE = '2026-10-05';
    private const BOUNDARY_DATE = '2026-10-06';
    private const CURRENT_DATE = '2026-10-20';
    private const FUTURE_DATE = '2026-10-21';

    public function test_retention_indexes_exist(): void
    {
        $this->assertIndexExists('products_plan', 'products_plan_date_isday_index');
        $this->assertIndexExists('products_plan', 'products_plan_parent_index');
        $this->assertIndexExists('slots', 'slots_date_isday_index');
        $this->assertIndexExists('lines_extra', 'lines_extra_date_isday_index');
        $this->assertIndexExists('products_order', 'products_order_date_isday_index');
        $this->assertIndexExists('workers_breaks', 'workers_breaks_date_isday_index');
        $this->assertIndexExists('logs', 'logs_date_isday_index');
    }

    public function test_dry_run_does_not_delete_old_rows(): void
    {
        $plan = $this->createPlan(self::OLD_DATE);
        $line = $this->createLinesExtra(self::OLD_DATE);

        $this->artisan('cleanup:session-data', [
            '--dry-run' => true,
            '--cutoff' => self::CUTOFF,
        ])->assertExitCode(0);

        $this->assertNotNull(ProductsPlan::find($plan->plan_product_id));
        $this->assertNotNull(LinesExtra::find($line->line_extra_id));
    }

    public function test_deletes_old_session_rows_and_keeps_boundary_current_future_null(): void
    {
        $oldLine = $this->createLinesExtra(self::OLD_DATE);
        $boundaryLine = $this->createLinesExtra(self::BOUNDARY_DATE);
        $currentLine = $this->createLinesExtra(self::CURRENT_DATE);
        $futureLine = $this->createLinesExtra(self::FUTURE_DATE);
        $nullLine = $this->createLinesExtra(null);

        $oldSlot = $this->createSlot(self::OLD_DATE);
        $oldOrder = $this->createProductsOrder(self::OLD_DATE);
        $oldBreak = $this->createWorkersBreaks(self::OLD_DATE);
        $oldLog = $this->createLog(self::OLD_DATE);

        $this->artisan('cleanup:session-data', [
            '--cutoff' => self::CUTOFF,
        ])->assertExitCode(0);

        $this->assertNull(LinesExtra::find($oldLine->line_extra_id));
        $this->assertNotNull(LinesExtra::find($boundaryLine->line_extra_id));
        $this->assertNotNull(LinesExtra::find($currentLine->line_extra_id));
        $this->assertNotNull(LinesExtra::find($futureLine->line_extra_id));
        $this->assertNotNull(LinesExtra::find($nullLine->line_extra_id));

        $this->assertNull(Slots::find($oldSlot->slot_id));
        $this->assertNull(ProductsOrder::find($oldOrder->order_id));
        $this->assertNull(WorkersBreaks::find($oldBreak->break_id));
        $this->assertNull(Logs::find($oldLog->log_id));
    }

    public function test_deletes_old_plan_tree_without_newer_children(): void
    {
        $root = $this->createPlan(self::OLD_DATE);
        $child = $this->createPlan(self::OLD_DATE, ['parent' => $root->plan_product_id]);

        $this->artisan('cleanup:session-data', [
            '--cutoff' => self::CUTOFF,
        ])->assertExitCode(0);

        $this->assertNull(ProductsPlan::find($root->plan_product_id));
        $this->assertNull(ProductsPlan::find($child->plan_product_id));
    }

    public function test_keeps_old_parent_referenced_by_newer_plan(): void
    {
        $parent = $this->createPlan(self::OLD_DATE);
        $child = $this->createPlan(self::CURRENT_DATE, ['parent' => $parent->plan_product_id]);

        $this->artisan('cleanup:session-data', [
            '--cutoff' => self::CUTOFF,
        ])->assertExitCode(0);

        $this->assertNotNull(ProductsPlan::find($parent->plan_product_id));
        $this->assertNotNull(ProductsPlan::find($child->plan_product_id));
    }

    private function createPlan(?string $date, array $extra = []): ProductsPlan
    {
        $safeDate = $date ?? self::CURRENT_DATE;

        return ProductsPlan::create([
            'slot_id' => $this->getSlotId(),
            'amount' => 1,
            'started_at' => $safeDate.' 08:00:00',
            'ended_at' => $safeDate.' 09:00:00',
            'date' => $date,
            'isDay' => 1,
            'delay' => 0,
            'colon' => '1',
            'hardware' => 1,
        ] + $extra);
    }

    private function createLinesExtra(?string $date): LinesExtra
    {
        $safeDate = $date ?? self::CURRENT_DATE;

        return LinesExtra::create([
            'line_id' => $this->getLineId(),
            'date' => $date,
            'isDay' => 1,
            'started_at' => $safeDate.' 08:00:00',
            'ended_at' => $safeDate.' 17:00:00',
        ]);
    }

    private function createSlot(?string $date): Slots
    {
        $safeDate = $date ?? self::CURRENT_DATE;

        return Slots::create([
            'line_id' => $this->getLineId(),
            'worker_id' => $this->getWorkerId(),
            'date' => $date,
            'isDay' => 1,
            'started_at' => $safeDate.' 08:00:00',
            'ended_at' => $safeDate.' 17:00:00',
        ]);
    }

    private function createProductsOrder(?string $date): ProductsOrder
    {
        return ProductsOrder::create([
            'product_id' => $this->getProductId(),
            'amount' => 1,
            'date' => $date,
            'isDay' => 1,
        ]);
    }

    private function createWorkersBreaks(?string $date): WorkersBreaks
    {
        $safeDate = $date ?? self::CURRENT_DATE;

        return WorkersBreaks::create([
            'worker_id' => $this->getWorkerId(),
            'date' => $date,
            'isDay' => 1,
            'started_at' => $safeDate.' 12:00:00',
            'ended_at' => $safeDate.' 13:00:00',
        ]);
    }

    private function createLog(?string $date): Logs
    {
        $safeDate = $date ?? self::CURRENT_DATE;

        return Logs::create([
            'action' => 'cleanup test',
            'line_id' => $this->getLineId(),
            'workers' => '',
            'people_count' => 0,
            'date' => $date,
            'isDay' => 1,
            'started_at' => $safeDate.' 09:00:00',
            'ended_at' => $safeDate.' 10:00:00',
        ]);
    }

    private function getSlotId(): int
    {
        $slotId = DB::table('products_slots')->value('product_slot_id');

        if ($slotId) {
            return (int) $slotId;
        }

        return (int) DB::table('products_slots')->insertGetId([
            'product_id' => $this->getProductId(),
            'line_id' => $this->getLineId(),
            'people_count' => 1,
            'perfomance' => 1,
            'type_id' => 1,
            'hardware' => 1,
        ]);
    }

    private function getLineId(): int
    {
        return (int) DB::table('lines')->value('line_id');
    }

    private function getWorkerId(): int
    {
        return (int) DB::table('workers')->value('worker_id');
    }

    private function getProductId(): int
    {
        return (int) DB::table('products_dictionary')->value('product_id');
    }

    private function assertIndexExists(string $table, string $index): void
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'`');
        $names = array_column($rows, 'Key_name');
        $this->assertContains($index, $names);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:
```bash
php artisan test --filter=CleanupSessionDataTest
```

Expected: FAIL, потому что команда `cleanup:session-data` ещё не существует.

- [ ] **Step 3: Create config**

Create `config/retention.php`:

```php
<?php

return [

    'days' => (int) env('RETENTION_DAYS', 14),

    'timezone' => env('RETENTION_TIMEZONE', 'Europe/Moscow'),

    'batch_size' => (int) env('RETENTION_BATCH_SIZE', 1000),

];
```

- [ ] **Step 4: Create command**

Create `app/Console/Commands/CleanupSessionData.php`:

```php
<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupSessionData extends Command
{
    protected $signature = 'cleanup:session-data
        {--dry-run : Показать кандидатов без удаления}
        {--days= : Переопределить retention-горизонт в днях}
        {--cutoff= : Явный cutoff в формате Y-m-d}
        {--batch= : Размер батча}';

    protected $description = 'Удаляет сессионные данные старше retention-горизонта';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = (int) ($this->option('days') ?: config('retention.days', 14));
        $batchSize = max(1, (int) ($this->option('batch') ?: config('retention.batch_size', 1000)));
        $cutoff = $this->resolveCutoff($days);

        $this->info(sprintf(
            'cleanup:session-data %s cutoff=%s batch=%d',
            $dryRun ? 'dry-run' : 'run',
            $cutoff,
            $batchSize
        ));

        Log::info('[cleanup:session-data] start', [
            'dry_run' => $dryRun,
            'cutoff' => $cutoff,
            'days' => $days,
            'batch_size' => $batchSize,
        ]);

        $stats = [
            'products_plan' => $this->cleanupProductsPlans($cutoff, $dryRun, $batchSize),
            'logs' => $this->cleanupTable('logs', 'log_id', $cutoff, $dryRun, $batchSize),
            'workers_breaks' => $this->cleanupTable('workers_breaks', 'break_id', $cutoff, $dryRun, $batchSize),
            'products_order' => $this->cleanupTable('products_order', 'order_id', $cutoff, $dryRun, $batchSize),
            'slots' => $this->cleanupTable('slots', 'slot_id', $cutoff, $dryRun, $batchSize),
            'lines_extra' => $this->cleanupTable('lines_extra', 'line_extra_id', $cutoff, $dryRun, $batchSize),
        ];

        $stats['dangling_parents'] = $this->countDanglingParents();

        $this->info(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        Log::info('[cleanup:session-data] finish', $stats);

        if ($stats['dangling_parents'] > 0) {
            $this->warn('Есть планы с отсутствующим parent. Проверьте отчёт.');
        }

        return self::SUCCESS;
    }

    private function resolveCutoff(int $days): string
    {
        if ($this->option('cutoff')) {
            return Carbon::parse((string) $this->option('cutoff'))->toDateString();
        }

        return Carbon::now(config('retention.timezone', 'Europe/Moscow'))
            ->startOfDay()
            ->subDays($days)
            ->toDateString();
    }

    private function cleanupTable(string $table, string $key, string $cutoff, bool $dryRun, int $batchSize): array
    {
        $nullDates = (int) DB::table($table)->whereNull('date')->count();
        $candidates = (int) DB::table($table)->where('date', '<', $cutoff)->count();

        if ($dryRun) {
            return [
                'candidates' => $candidates,
                'deleted' => 0,
                'null_dates' => $nullDates,
            ];
        }

        $deleted = 0;

        while (true) {
            $ids = DB::table($table)
                ->where('date', '<', $cutoff)
                ->orderBy($key)
                ->limit($batchSize)
                ->pluck($key);

            if ($ids->isEmpty()) {
                break;
            }

            $deletedChunk = DB::transaction(function () use ($table, $key, $ids) {
                return DB::table($table)->whereIn($key, $ids)->delete();
            });

            if ($deletedChunk === 0) {
                break;
            }

            $deleted += $deletedChunk;
            $this->line(sprintf('%s: deleted %d, total %d', $table, $deletedChunk, $deleted));
        }

        return [
            'candidates' => $candidates,
            'deleted' => $deleted,
            'null_dates' => $nullDates,
        ];
    }

    private function cleanupProductsPlans(string $cutoff, bool $dryRun, int $batchSize): array
    {
        $nullDates = (int) DB::table('products_plan')->whereNull('date')->count();
        $candidates = (int) DB::table('products_plan')->where('date', '<', $cutoff)->count();

        if ($dryRun) {
            return [
                'candidates' => $candidates,
                'deleted' => 0,
                'null_dates' => $nullDates,
            ];
        }

        $deleted = 0;

        while (true) {
            $ids = $this->getDeletablePlanIds($cutoff, $batchSize);

            if ($ids->isEmpty()) {
                break;
            }

            $deletedChunk = DB::transaction(function () use ($ids) {
                return DB::table('products_plan')->whereIn('plan_product_id', $ids)->delete();
            });

            if ($deletedChunk === 0) {
                break;
            }

            $deleted += $deletedChunk;
            $this->line(sprintf('products_plan: deleted %d, total %d', $deletedChunk, $deleted));
        }

        return [
            'candidates' => $candidates,
            'deleted' => $deleted,
            'null_dates' => $nullDates,
        ];
    }

    private function getDeletablePlanIds(string $cutoff, int $batchSize)
    {
        return DB::table('products_plan as plan')
            ->leftJoin('products_plan as child', function ($join) {
                $join->on('child.parent', '=', 'plan.plan_product_id')
                    ->whereNotNull('child.parent');
            })
            ->where('plan.date', '<', $cutoff)
            ->whereNull('child.plan_product_id')
            ->orderBy('plan.plan_product_id')
            ->limit($batchSize)
            ->pluck('plan.plan_product_id');
    }

    private function countDanglingParents(): int
    {
        return (int) DB::table('products_plan as plan')
            ->leftJoin('products_plan as parent', 'plan.parent', '=', 'parent.plan_product_id')
            ->whereNotNull('plan.parent')
            ->whereNull('parent.plan_product_id')
            ->count();
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run:
```bash
php artisan test --filter=CleanupSessionDataTest
```

Expected: PASS.

- [ ] **Step 6: Run full test suite**

Run:
```bash
php artisan test
```

Expected: PASS.

- [ ] **Step 7: Run linter**

Run:
```bash
vendor/bin/pint --test
```

Expected: PASS.

---

# Task 3: Scheduler + env + docs

**Files:**
- Modify: `routes/console.php`
- Modify: `.env.example`
- Modify: `README.md`

**Interfaces:**
- Consumes:
  - команда `cleanup:session-data`
  - `config/retention.php`
- Produces:
  - ежедневный scheduled run
  - задокументированные env-переменные
  - операторскую инструкцию по ручному запуску

- [ ] **Step 1: Register schedule**

Replace `routes/console.php` with:

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('cleanup:session-data')
    ->dailyAt('03:00')
    ->timezone(config('retention.timezone', 'Europe/Moscow'))
    ->withoutOverlapping();
```

- [ ] **Step 2: Add env defaults**

Append to `.env.example`:

```env
RETENTION_DAYS=14
RETENTION_TIMEZONE=Europe/Moscow
RETENTION_BATCH_SIZE=1000
```

- [ ] **Step 3: Update README**

Append to `README.md`:

````md
## Очистка старых сессионных данных

Команда удаляет сессионные данные старше retention-горизонта:

- `products_plan`
- `slots`
- `lines_extra`
- `products_order`
- `workers_breaks`
- `logs`

Проверка без удаления:

```bash
php artisan cleanup:session-data --dry-run
```

Реальная очистка:

```bash
php artisan cleanup:session-data
```

Расписание:

```bash
* * * * * cd /path/to/psr && php artisan schedule:run --no-interaction
```
````

- [ ] **Step 4: Verify schedule registration**

Run:
```bash
php artisan schedule:list
```

Expected: в выводе есть `cleanup:session-data`.

- [ ] **Step 5: Run linter**

Run:
```bash
vendor/bin/pint --test
```

Expected: PASS.

---

# Task 4: Production rollout checklist

Это operational task, без новых файлов.

- [ ] **Step 1: Dry-run в production**

```bash
php artisan cleanup:session-data --dry-run
```

Что проверить:
- cutoff корректный;
- объёмы по таблицам ожидаемые;
- `null_dates` не выглядит как массовая аномалия;
- нет неожиданно большого числа кандидатов.

- [ ] **Step 2: Backup перед initial run**

Сделать бэкап затронутых таблиц:
- `products_plan`
- `slots`
- `lines_extra`
- `products_order`
- `workers_breaks`
- `logs`

- [ ] **Step 3: Initial cleanup**

```bash
php artisan cleanup:session-data
```

- [ ] **Step 4: Verification queries**

Пример для cutoff `2026-10-06`:

```sql
SELECT 'products_plan' AS table_name, COUNT(*) AS old_rows
FROM products_plan
WHERE date < '2026-10-06'
UNION ALL
SELECT 'slots', COUNT(*)
FROM slots
WHERE date < '2026-10-06'
UNION ALL
SELECT 'lines_extra', COUNT(*)
FROM lines_extra
WHERE date < '2026-10-06'
UNION ALL
SELECT 'products_order', COUNT(*)
FROM products_order
WHERE date < '2026-10-06'
UNION ALL
SELECT 'workers_breaks', COUNT(*)
FROM workers_breaks
WHERE date < '2026-10-06'
UNION ALL
SELECT 'logs', COUNT(*)
FROM logs
WHERE date < '2026-10-06';
```

Expected:
- `0` в большинстве таблиц;
- либо только заранее понятные защищённые parent / аномалии.

Проверка dangling parents:

```sql
SELECT COUNT(*) AS dangling_parents
FROM products_plan AS plan
LEFT JOIN products_plan AS parent
  ON parent.plan_product_id = plan.parent
WHERE plan.parent IS NOT NULL
  AND parent.plan_product_id IS NULL;
```

Expected:
- `0`, либо только известные заранее аномалии.

- [ ] **Step 5: Confirm cron**

Убедиться, что на сервере есть:

```bash
* * * * * cd /path/to/psr && php artisan schedule:run --no-interaction
```

---

# Rollback / Failure Strategy

- Для кода:
  - откат изменений делается стандартным образом через revert / rollback ветки.
- Для миграции индексов:
  - `php artisan migrate:rollback --step=1` если последний миграции именно наш.
- Для данных:
  - hard delete не откатывается автоматически;
  - поэтому перед первым production-прогоном обязателен бэкап.
- Если cleanup упал на середине:
  - повторный запуск той же команды безопасен;
  - она продолжит удалять только ещё не удалённые candidates по тем же правилам.

---

# Self-Review

- Spec coverage:
  - все 6 сессионных таблиц — есть в команде и тестах;
  - retention по `date` — реализовано и покрыто тестами;
  - scheduler + cron — Task 3 + Task 4;
  - hard delete — команда делает physical delete;
  - parent safety — отдельная leaf-first логика и тесты.
- Placeholder scan:
  - нет `TBD`, `TODO`, “implement later”, “add appropriate handling”.
- Type consistency:
  - имя команды `cleanup:session-data` везде одно и то же;
  - config keys `retention.days`, `retention.timezone`, `retention.batch_size` согласованы;
  - index names в миграции и тестах совпадают.
- Review Focus:
  - `NULL` dates — команда их не удаляет и показывает в stats;
  - night shift — retention не зависит от `ended_at`;
  - protected parent — отдельный тест;
  - old chain without newer children — отдельный тест;
  - rerun safety — логика идемпотентная по кандидатам и батчам.
