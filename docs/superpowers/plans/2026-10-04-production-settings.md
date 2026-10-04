# Окно «Производство» — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Окно «Производство» в реестрах (вкладки Линии/Оборудование/Возвратные массы/Постоянные величины) с редактированием правил линий, справочника оборудования и глобальных параметров — без магических чисел в коде.

**Architecture:** Три новые таблицы (`hardwares`, `return_types`, `settings`) с CRUD-эндпоинтами; один эндпоинт `/lines/bulk-update` раскладывает поля по `lines` (правила) и `lines_defaults` (шаблон смены); интервалы и коэффициенты читаются из БД через `Setting::get`/`Util::getReturnTypes`; фронтенд — новая модалка `production.vue` (Tabs с `tab-position="left"`) + стор `production.ts`.

**Tech Stack:** Laravel 11 / PHP 8.2, Vue 3 + TypeScript + Pinia, ant-design-vue 4, Vite. Линт PHP — `vendor/bin/pint` (laravel/pint в require-dev). Фронт без линтера — проверка через `npm run build`.

**Spec:** [docs/superpowers/specs/2026-10-04-production-settings-design.md](../specs/2026-10-04-production-settings-design.md)

## Global Constraints

- **Без написания новых тестов** (требование заказчика). Существующий `tests/Feature/ProductsPlanControllerTest.php` должен оставаться зелёным — правка ожиданий допустима (задача 4).
- PHP: `./vendor/bin/pint` после правок PHP-файлов (pint автоматически форматирует и иногда ломает `eval`-строки — после прогона убедиться, что тесты зелёные).
- Фронтенд: после правок `.vue`/`.ts` обязателен `npm run build` (vite-сборка падает на ошибках синтаксиса/импортов).
- Коммит после каждой задачи. Сообщения — на русском, как в истории репозитория.
- ID оборудования 1–6 и режимов возвратных масс 1–4 фиксированы сидами (на них ссылаются существующие данные).
- Порядок задач строго последовательный: миграции → бэкенд → фронтенд.
- Миграции применяются на dev-базе: `php artisan migrate`. Прод — вне скоупа плана.

---

## File Structure

**Создаётся:**
- `database/migrations/2026_10_04_000000_create_production_tables.php` — таблицы hardwares/return_types/settings
- `database/migrations/2026_10_04_000001_cleanup_lines_columns.php` — удаление мёртвых колонок
- `database/seeders/ProductionSeeder.php` — сиды оборудования, режимов, настроек
- `app/Models/Hardware.php`, `app/Models/ReturnType.php`, `app/Models/Setting.php`
- `app/Http/Controllers/ProductionController.php` — CRUD оборудования/режимов/настроек
- `resources/js/vueapp/store/production.ts` — стор справочников и настроек
- `resources/js/vueapp/components/modals/production.vue` — окно «Производство»

**Модифицируется:**
- `app/Models/Lines.php` — relation `linesDefault`
- `app/Models/LinesDefault.php` — fillable без мёртвых колонок
- `app/Util.php` — getDefaults/setDefault/calcReturnMass, удаление calcDurationForZM
- `app/Http/Controllers/LinesController.php` — registry(), bulkUpdate(), проверка планов в delete()
- `app/Http/Controllers/ProductsPlanController.php` — интервалы из Setting
- `app/Http/Controllers/TableController.php` — названия оборудования из БД
- `routes/api.php` — роуты /production/*, /lines/bulk-update, /lines/registry
- `database/seeders/DatabaseSeeder.php`, `database/seeders/LinesDefaultsSeeder.php`
- `resources/js/vueapp/store/modal.ts`, `resources/js/vueapp/store/lines.ts`, `resources/js/vueapp/store/dicts.ts`
- `resources/js/App.vue` — загрузка стора + монтирование модалки
- `resources/js/vueapp/components/common/toolbar.vue` — пункт меню «Производство»
- `resources/js/vueapp/components/common/lineForm.vue` — убрать регистровые поля
- `resources/js/vueapp/components/modals/plan.vue`, `resources/js/vueapp/components/modals/products.vue` — оборудование из стора
- `tests/Feature/ProductsPlanControllerTest.php` — ожидания +5 мин у упаковки (задача 4)

**Удаляется:**
- `config/lines_defaults.php`

---

### Task 1: Таблицы, модели и сиды производства

**Files:**
- Create: `database/migrations/2026_10_04_000000_create_production_tables.php`
- Create: `app/Models/Hardware.php`, `app/Models/ReturnType.php`, `app/Models/Setting.php`
- Create: `database/seeders/ProductionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Produces: таблицы `hardwares` (hardware_id PK), `return_types` (return_type_id PK), `settings` (key PK); модели `Hardware`, `ReturnType`, `Setting::get(string $key, $default = null)` (кэш на запрос). Задача 3 строит на них контроллер, задача 4 — замену магических чисел.

- [ ] **Step 1: Создать миграцию с тремя таблицами**

Файл `database/migrations/2026_10_04_000000_create_production_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Справочники и настройки окна «Производство».
     * hardware_id 1–6 и return_type_id 1–4 задаются сидом явно,
     * чтобы ссылки из products_slots.hardware и lines.return_type не ломались.
     */
    public function up(): void
    {
        Schema::create('hardwares', function (Blueprint $table) {
            $table->id('hardware_id');
            $table->string('title');
            $table->string('full_title')->nullable();
            $table->unsignedTinyInteger('type'); // 1 — варка, 2 — упаковка
        });

        Schema::create('return_types', function (Blueprint $table) {
            $table->id('return_type_id');
            $table->string('title');
            $table->enum('formula_type', ['fixed', 'coef']);
            $table->decimal('fixed_value', 10, 4)->nullable();
            $table->decimal('coef_z', 10, 4)->nullable();
            $table->decimal('coef_s', 10, 4)->nullable();
            $table->decimal('coef_k', 10, 4)->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('return_types');
        Schema::dropIfExists('hardwares');
    }
};
```

- [ ] **Step 2: Создать модели**

`app/Models/Hardware.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hardware extends Model
{
    protected $table = 'hardwares';
    protected $primaryKey = 'hardware_id';
    public $timestamps = false;
    protected $fillable = ['hardware_id', 'title', 'full_title', 'type'];
}
```

`app/Models/ReturnType.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnType extends Model
{
    protected $table = 'return_types';
    protected $primaryKey = 'return_type_id';
    public $timestamps = false;
    protected $fillable = ['return_type_id', 'title', 'formula_type', 'fixed_value', 'coef_z', 'coef_s', 'coef_k'];
}
```

`app/Models/Setting.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['key', 'value'];

    /**
     * Читает настройку. Статический кэш — на время запроса
     * (после сохранения в UI настройка применится со следующего запроса).
     */
    public static function get(string $key, $default = null)
    {
        static $cache = null;
        if ($cache === null) {
            $cache = self::pluck('value', 'key')->toArray();
        }
        return $cache[$key] ?? $default;
    }
}
```

- [ ] **Step 3: Создать сидер**

`database/seeders/ProductionSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Hardware;
use App\Models\ReturnType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Справочники окна «Производство».
     * ID оборудования и режимов фиксированы: на них ссылаются
     * products_slots.hardware (1–6) и lines.return_type (1–4).
     */
    public function run(): void
    {
        $hardwares = [
            ['hardware_id' => 1, 'title' => 'ТОРНАДО', 'full_title' => 'Торнадо', 'type' => 1],
            ['hardware_id' => 2, 'title' => 'Мондомикс', 'full_title' => 'Мондомикс', 'type' => 1],
            ['hardware_id' => 3, 'title' => 'Китайский Аэрос', 'full_title' => 'Китайский Аэрос', 'type' => 1],
            ['hardware_id' => 4, 'title' => 'ЗМ №1', 'full_title' => 'Завёрточная машина №1', 'type' => 2],
            ['hardware_id' => 5, 'title' => 'ЗМ №2', 'full_title' => 'Завёрточная машина №2', 'type' => 2],
            ['hardware_id' => 6, 'title' => 'ЗМ №1 и №2', 'full_title' => 'Завёрточные машины №1 и №2', 'type' => 2],
        ];
        foreach ($hardwares as $row) {
            Hardware::create($row);
        }

        // coef_k = null: категория «конфеты» не участвует (как сейчас)
        $returnTypes = [
            ['return_type_id' => 1, 'title' => 'Непрерывная линия', 'formula_type' => 'fixed', 'fixed_value' => 25],
            ['return_type_id' => 2, 'title' => 'Шоколадная линия', 'formula_type' => 'coef', 'coef_z' => 0.015, 'coef_s' => 0.00405],
            ['return_type_id' => 3, 'title' => 'Линия-полуавтомат', 'formula_type' => 'coef', 'coef_z' => 0.025],
            ['return_type_id' => 4, 'title' => 'One-Shot', 'formula_type' => 'coef', 'coef_z' => 0.005],
        ];
        foreach ($returnTypes as $row) {
            ReturnType::create($row);
        }

        $settings = [
            ['key' => 'interval_boil', 'value' => '10'],
            ['key' => 'interval_pack', 'value' => '15'],
            ['key' => 'zm_perfomance', 'value' => '143.5'],
            ['key' => 'zm_perfomance2', 'value' => '287'],
        ];
        foreach ($settings as $row) {
            Setting::create($row);
        }
    }
}
```

- [ ] **Step 4: Зарегистрировать сидер в DatabaseSeeder**

В `database/seeders/DatabaseSeeder.php` в массиве `$this->call([...])` добавить `ProductionSeeder::class` (после `LinesDefaultsSeeder::class`):

```php
        $this->call([
            LinesSeeder::class,
            ProductsCategoriesSeeder::class,
            ResponsibleSeeder::class,
            WorkerSeeder::class,
            LinesDefaultsSeeder::class,
            ProductionSeeder::class
        ]);
```

- [ ] **Step 5: Применить миграцию и сидер**

```bash
php artisan migrate --path=database/migrations/2026_10_04_000000_create_production_tables.php
php artisan db:seed --class=ProductionSeeder
```

Проверка: `php artisan tinker --execute="App\Models\Setting::get('interval_pack', 0)"` → `15`; `App\Models\Hardware::count()` → `6`; `App\Models\ReturnType::count()` → `4`.

- [ ] **Step 6: Линт и коммит**

```bash
./vendor/bin/pint database/migrations/2026_10_04_000000_create_production_tables.php app/Models/Hardware.php app/Models/ReturnType.php app/Models/Setting.php database/seeders/ProductionSeeder.php database/seeders/DatabaseSeeder.php
git add -A && git commit -m "feat: таблицы и модели производства (hardwares, return_types, settings)"
```

---

### Task 2: Чистка мёртвых колонок и дефолтов линий

**Files:**
- Create: `database/migrations/2026_10_04_000001_cleanup_lines_columns.php`
- Modify: `app/Models/LinesDefault.php` (fillable)
- Modify: `app/Util.php` (getDefaults — без fallback на конфиг; setDefault — allowed)
- Modify: `database/seeders/LinesDefaultsSeeder.php` (убрать ключи title/started_at/ended_at)
- Delete: `config/lines_defaults.php`

**Interfaces:**
- Consumes: таблица `lines_defaults` из миграции `2026_08_21_000000_create_lines_defaults_table`.
- Produces: `lines` без `workers_count/started_at/ended_at/prep_time/after_time`; `lines_defaults` без `title/started_at/ended_at`. `Util::getDefaults` читает только БД. Задача 3 опирается на то, что дефолты пишутся через `LinesDefault::updateOrCreate`.

- [ ] **Step 1: Создать миграцию-чистку**

`database/migrations/2026_10_04_000001_cleanup_lines_columns.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Удаляет колонки, затенённые lines_extra / lines_defaults:
     * - lines.workers_count/started_at/ended_at перекрываются lines_extra
     *   при array_merge в LinesController::get и TableController (extra создаётся getOrInsert всегда);
     * - lines.prep_time/after_time — не в $fillable модели, никогда не пишутся, читаются из extra;
     * - lines_defaults.title не попадает в lines_extra (нет в $fillable),
     *   lines_defaults.started_at/ended_at всегда перезаписываются Util::createDate по графику роли.
     */
    public function up(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn(['workers_count', 'started_at', 'ended_at', 'prep_time', 'after_time']);
        });

        Schema::table('lines_defaults', function (Blueprint $table) {
            $table->dropColumn(['title', 'started_at', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->integer('workers_count')->nullable();
            $table->time('started_at')->nullable();
            $table->time('ended_at')->nullable();
            $table->integer('prep_time')->nullable();
            $table->integer('after_time')->nullable();
        });

        Schema::table('lines_defaults', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->string('started_at')->nullable();
            $table->string('ended_at')->nullable();
        });
    }
};
```

- [ ] **Step 2: Обновить fillable LinesDefault**

В `app/Models/LinesDefault.php` заменить `$fillable` на:

```php
    protected $fillable = [
        'line_id',
        'perfomance',
        'workers_count',
        'prep_time',
        'after_time',
    ];
```

- [ ] **Step 3: Убрать fallback на конфиг в Util::getDefaults**

В `app/Util.php` заменить тело `getDefaults` (метод `public static function getDefaults($line_id = false): array|bool` целиком, включая ветки `config('lines_defaults')`) на:

```php
    public static function getDefaults($line_id = false): array|bool
    {
        if ($line_id !== false) {
            $default = LinesDefault::where('line_id', $line_id)->first();
            if (!$default) {
                return false;
            }
            $data = $default->toArray();
            unset($data['lines_default_id'], $data['line_id']);
            return $data;
        }

        return LinesDefault::get()->map(function ($item) {
            unset($item['lines_default_id']);
            return $item;
        })->toArray();
    }
```

- [ ] **Step 4: Обновить allowed-список Util::setDefault**

В `app/Util.php` в `setDefault` заменить строку:

```php
        $allowed = ['title', 'perfomance', 'started_at', 'ended_at', 'workers_count', 'prep_time', 'after_time'];
```

на:

```php
        $allowed = ['perfomance', 'workers_count', 'prep_time', 'after_time'];
```

- [ ] **Step 5: Убрать мёртвые ключи из LinesDefaultsSeeder**

Ключи `title`, `started_at`, `ended_at` стоят в каждом элементе `DEFAULTS` отдельными строками — удалить их одним sed:

```bash
sed -i '' -E "/^[[:space:]]*'(title|started_at|ended_at)' => /d" database/seeders/LinesDefaultsSeeder.php
```

Проверка: `grep -cE "'(title|started_at|ended_at)'" database/seeders/LinesDefaultsSeeder.php` → `0`; количество `'line_id'` не изменилось (40).

- [ ] **Step 6: Удалить старый конфиг**

```bash
git rm config/lines_defaults.php
```

Убедиться, что `grep -rn "lines_defaults'" app routes` не находит других обращений к конфигу (единственным потребителем был `Util::getDefaults`).

- [ ] **Step 7: Миграция, сид и проверка**

```bash
php artisan migrate --path=database/migrations/2026_10_04_000001_cleanup_lines_columns.php
php artisan db:seed --class=LinesDefaultsSeeder
php artisan tinker --execute="var_export(App\Util::getDefaults(8));"
```

Ожидание: массив с ключами только `perfomance`, `workers_count`, `prep_time`, `after_time` (без `title/started_at/ended_at/line_id/lines_default_id`).

- [ ] **Step 8: Линт и коммит**

```bash
./vendor/bin/pint app/Util.php app/Models/LinesDefault.php database/migrations/2026_10_04_000001_cleanup_lines_columns.php
git add -A && git commit -m "refactor: чистка мёртвых колонок линий и дефолтов, удаление config/lines_defaults.php"
```

---

### Task 3: API производства (ProductionController, роуты, registry и bulk-update линий)

**Files:**
- Create: `app/Http/Controllers/ProductionController.php`
- Modify: `routes/api.php`
- Modify: `app/Http/Controllers/LinesController.php` (registry, bulkUpdate, проверка планов в delete)
- Modify: `app/Models/Lines.php` (relation linesDefault)

**Interfaces:**
- Consumes: модели из задачи 1; `Util::successMsg(array|string, int)`, `Util::errorMsg(array|string, int)`; таблица `products_slots.hardware` (колонка `hardware`), `lines.return_type`.
- Produces (для задач 5–7):
  - `GET /api/production/hardwares/get` → `[{hardware_id, title, full_title, type}, ...]`
  - `POST /api/production/hardwares/create`, `PUT .../update`, `DELETE .../delete` (`{hardware_id}`)
  - `GET /api/production/return_types/get` → `[{return_type_id, title, formula_type, fixed_value, coef_z, coef_s, coef_k}, ...]`
  - `POST /api/production/return_types/create`, `PUT .../update`, `DELETE .../delete` (`{return_type_id}`)
  - `GET /api/production/settings/get` → `{interval_boil, interval_pack, zm_perfomance, zm_perfomance2}`
  - `PUT /api/production/settings/update` — body: любое подмножество тех же ключей
  - `GET /api/lines/registry` → строки для таблицы «Линии»: поля lines + `perfomance/prep_time/after_time/workers_count` из дефолтов
  - `POST /api/lines/bulk-update` — body `{line_ids: number[], fields: {...}}`
  - `DELETE /api/lines/delete` — теперь возвращает ошибку «Линия используется в планах», если есть планы

- [ ] **Step 1: Создать ProductionController**

`app/Http/Controllers/ProductionController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Hardware;
use App\Models\Lines;
use App\Models\ProductsSlots;
use App\Models\ReturnType;
use App\Models\Setting;
use App\Util;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    /* ===== Оборудование ===== */

    public function getHardwares()
    {
        return Util::successMsg(Hardware::orderBy('hardware_id')->get()->toArray());
    }

    public function createHardware(Request $request)
    {
        $hardware = Hardware::create($request->only(['title', 'full_title', 'type']));
        return Util::successMsg($hardware->toArray(), 201);
    }

    public function updateHardware(Request $request)
    {
        $hardware = Hardware::find($request->post('hardware_id'));
        if (!$hardware) {
            return Util::errorMsg('Оборудование не найдено', 404);
        }
        $hardware->update($request->only(['title', 'full_title', 'type']));
        return Util::successMsg($hardware->toArray());
    }

    public function deleteHardware(Request $request)
    {
        $hardware = Hardware::find($request->post('hardware_id'));
        if (!$hardware) {
            return Util::errorMsg('Оборудование не найдено', 404);
        }
        if (ProductsSlots::where('hardware', $hardware->hardware_id)->exists()) {
            return Util::errorMsg('Оборудование используется в слотах продукции');
        }
        $hardware->delete();
        return Util::successMsg('Оборудование удалено');
    }

    /* ===== Возвратные массы ===== */

    public function getReturnTypes()
    {
        return Util::successMsg(ReturnType::orderBy('return_type_id')->get()->toArray());
    }

    public function createReturnType(Request $request)
    {
        $returnType = ReturnType::create($request->only(['title', 'formula_type', 'fixed_value', 'coef_z', 'coef_s', 'coef_k']));
        return Util::successMsg($returnType->toArray(), 201);
    }

    public function updateReturnType(Request $request)
    {
        $returnType = ReturnType::find($request->post('return_type_id'));
        if (!$returnType) {
            return Util::errorMsg('Режим не найден', 404);
        }
        $returnType->update($request->only(['title', 'formula_type', 'fixed_value', 'coef_z', 'coef_s', 'coef_k']));
        return Util::successMsg($returnType->toArray());
    }

    public function deleteReturnType(Request $request)
    {
        $returnType = ReturnType::find($request->post('return_type_id'));
        if (!$returnType) {
            return Util::errorMsg('Режим не найден', 404);
        }
        if (Lines::where('return_type', $returnType->return_type_id)->exists()) {
            return Util::errorMsg('Режим используется в линиях');
        }
        $returnType->delete();
        return Util::successMsg('Режим удалён');
    }

    /* ===== Постоянные величины ===== */

    private const SETTING_KEYS = ['interval_boil', 'interval_pack', 'zm_perfomance', 'zm_perfomance2'];

    public function getSettings()
    {
        $values = Setting::pluck('value', 'key')->toArray();
        foreach (self::SETTING_KEYS as $key) {
            $values[$key] = $values[$key] ?? null;
        }
        return Util::successMsg($values);
    }

    public function updateSettings(Request $request)
    {
        foreach (self::SETTING_KEYS as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $request->post($key)]);
            }
        }
        return Util::successMsg('Настройки сохранены');
    }
}
```

- [ ] **Step 2: Добавить роуты в routes/api.php**

Внутри существующей группы с `ParseSession` (файл `routes/api.php`, рядом с блоком LINES) добавить перед блоком WORKERS:

```php
    Route::controller(ProductionController::class)
        ->prefix('/production')
        ->middleware(ForceJsonResponse::class)
        ->group(function () {
            Route::get('/hardwares/get', 'getHardwares');
            Route::post('/hardwares/create', 'createHardware');
            Route::put('/hardwares/update', 'updateHardware');
            Route::delete('/hardwares/delete', 'deleteHardware');

            Route::get('/return_types/get', 'getReturnTypes');
            Route::post('/return_types/create', 'createReturnType');
            Route::put('/return_types/update', 'updateReturnType');
            Route::delete('/return_types/delete', 'deleteReturnType');

            Route::get('/settings/get', 'getSettings');
            Route::put('/settings/update', 'updateSettings');
        });
```

В блок линий (`Route::controller(LinesController::class)->prefix('/lines')...`) добавить два роута:

```php
            Route::get('/registry', 'registry');
            Route::post('/bulk-update', 'bulkUpdate');
```

Убедиться, что `use App\Http\Controllers\ProductionController;` добавлен в шапку `routes/api.php` (рядом с остальными use).

- [ ] **Step 3: Добавить relation linesDefault в модель Lines**

В `app/Models/Lines.php` добавить метод (и `use App\Models\LinesDefault;` в шапке):

```php
    public function linesDefault() {
        return $this->hasOne(LinesDefault::class, 'line_id', 'line_id');
    }
```

- [ ] **Step 4: registry() и bulkUpdate() в LinesController**

В `app/Http/Controllers/LinesController.php` добавить `use App\Models\LinesDefault;` и два метода после `update()`:

```php
    /**
     * Данные реестра линий: правила (lines) + шаблон смены (lines_defaults)
     */
    public function registry(Request $request)
    {
        $lines = Lines::with('linesDefault')->orderBy('line_id')->get()->map(function (Lines $line) {
            $default = $line->linesDefault;
            return array_merge($line->toArray(), [
                'perfomance' => $default->perfomance ?? null,
                'prep_time' => $default->prep_time ?? null,
                'after_time' => $default->after_time ?? null,
                'workers_count' => $default->workers_count ?? null,
            ]);
        });

        return Util::successMsg($lines->toArray());
    }

    /**
     * Массовое обновление линий: правила → lines, шаблон смены → lines_defaults.
     * Поля, отсутствующие в fields, не трогаются.
     */
    public function bulkUpdate(Request $request)
    {
        $lineIds = $request->post('line_ids', []);
        $fields = $request->post('fields', []);
        if (!is_array($lineIds) || empty($lineIds) || !is_array($fields) || empty($fields)) {
            return Util::errorMsg('Нет данных для обновления');
        }

        $lineFields = array_intersect_key($fields, array_flip(['title', 'color', 'type_id', 'return_type', 'use_dating']));
        $defaultFields = array_intersect_key($fields, array_flip(['perfomance', 'workers_count', 'prep_time', 'after_time']));

        foreach ($lineIds as $lineId) {
            if ($lineFields) {
                Lines::where('line_id', $lineId)->update($lineFields);
            }
            if ($defaultFields) {
                LinesDefault::updateOrCreate(['line_id' => $lineId], $defaultFields);
            }
        }

        return Util::successMsg('Линии обновлены');
    }
```

- [ ] **Step 5: Проверка планов в LinesController::delete**

В методе `delete(Request $request)` перед удалением добавить проверку:

```php
    public function delete(Request $request)
    {
        if (ProductsPlan::whereHas('slot', fn ($q) => $q->where('line_id', $request->post('line_id')))->exists()) {
            return Util::errorMsg('Линия используется в планах');
        }
        $delete = Lines::find($request->post('line_id'))->delete();
        ...
```

(`ProductsPlan` уже импортирован в этом файле.)

- [ ] **Step 6: Линт и коммит**

```bash
./vendor/bin/pint app/Http/Controllers/ProductionController.php app/Http/Controllers/LinesController.php app/Models/Lines.php routes/api.php
php artisan route:list --path=production
git add -A && git commit -m "feat: API производства, registry и bulk-update линий"
```

Проверка `route:list`: маршруты `/api/production/hardwares/*`, `/api/production/return_types/*`, `/api/production/settings/*`, `/api/lines/registry`, `/api/lines/bulk-update` присутствуют.

---

### Task 4: Замена магических чисел в бэкенде

**Files:**
- Modify: `app/Http/Controllers/ProductsPlanController.php` (6 интервалов)
- Modify: `app/Util.php` (calcReturnMass из БД; удалить calcDurationForZM)
- Modify: `app/Http/Controllers/TableController.php` (названия оборудования из БД)
- Modify: `tests/Feature/ProductsPlanControllerTest.php` (ожидания +5 мин у упаковки — только если тесты это покажут)

**Interfaces:**
- Consumes: `Setting::get` (задача 1), таблица `return_types` (задача 1).
- Produces: интервалы планов читаются из настроек; `Util::getReturnTypes(): Collection` (кэш на запрос, ключ — return_type_id); в отчёте названия оборудования — из таблицы `hardwares`.

- [ ] **Step 1: Импорт Setting в ProductsPlanController**

В шапку `app/Http/Controllers/ProductsPlanController.php` (блок `use App\Models\...`) добавить:

```php
use App\Models\Setting;
```

- [ ] **Step 2: Заменить интервалы упаковки (5 мест, одинаковый суффикс)**

В `app/Http/Controllers/ProductsPlanController.php` подстрока

```php
->addHours($duration)->addMinutes(10)
```

встречается 5 раз (строки ~226, ~301, ~742, ~782, ~950 — все относятся к упаковке/ящикам). Заменить все вхождения (Edit с `replace_all`) на:

```php
->addHours($duration)->addMinutes((int) Setting::get('interval_pack', 15))
```

- [ ] **Step 3: Заменить интервал варки (1 место)**

Строку

```php
                $boil_end = Carbon::parse($plan->ended_at)->addMinutes(10)->addMinutes($delay);
```

заменить на:

```php
                $boil_end = Carbon::parse($plan->ended_at)->addMinutes((int) Setting::get('interval_boil', 10))->addMinutes($delay);
```

- [ ] **Step 4: calcReturnMass из справочника режимов**

В `app/Util.php`:
1) В шапку добавить `use App\Models\ReturnType;` и `use Illuminate\Support\Collection;`.
2) Заменить метод `calcReturnMass` целиком на:

```php
    /**
     * Правила возвратных масс задаются в справочнике return_types,
     * линия ссылается на режим через return_type.
     */
    public static function calcReturnMass(array $line, array $sum, string $type): string|bool
    {
        $mode = self::getReturnTypes()->get($line['return_type'] ?? 0);
        if (!$mode) {
            return false;
        }
        $m = "<f>=(" . implode("+", $sum) . ") * ";
        if ($mode->formula_type === 'fixed') {
            return (float) $mode->fixed_value;
        }
        $coef = match ($type) {
            'z' => $mode->coef_z,
            's' => $mode->coef_s,
            'k' => $mode->coef_k,
            default => null,
        };
        return $coef !== null && $coef !== '' ? $m . $coef . "</f>" : false;
    }

    /**
     * Справочник режимов возвратных масс, кэш на время запроса.
     * @return \Illuminate\Support\Collection
     */
    public static function getReturnTypes(): Collection
    {
        static $returnTypes = null;
        if ($returnTypes === null) {
            $returnTypes = ReturnType::all()->keyBy('return_type_id');
        }
        return $returnTypes;
    }
```

- [ ] **Step 5: Удалить мёртвый calcDurationForZM**

В `app/Util.php` удалить метод `calcDurationForZM` целиком (блок с docblock «Расчёт длительности для завёрточных машин» и телом с `eval`). Импорт `ProductsDictionary` в Util остаётся — он используется в `calcDuration`.

- [ ] **Step 6: Названия оборудования в TableController из БД**

В `app/Http/Controllers/TableController.php`:
1) В шапку добавить `use App\Models\Hardware;` и `use Illuminate\Support\Collection;`.
2) Удалить static-массив `private static $hardware = [ ... ];` (блок с ключами `0`, `""`, `2`, `1`, `3`, `4`, `5`, `6`).
3) Добавить на его место:

```php
    private static ?Collection $hardwaresMap = null;

    private static function hardwareTitle($hardwareId): string
    {
        if (!$hardwareId) {
            return 'Без оборудования';
        }
        if (self::$hardwaresMap === null) {
            self::$hardwaresMap = Hardware::pluck('title', 'hardware_id');
        }
        return self::$hardwaresMap[$hardwareId] ?? 'Без оборудования';
    }
```

4) Единственное использование — в `getPlans`:

```php
                        'hwTitle' => self::$hardware[$hw],
```

заменить на:

```php
                        'hwTitle' => self::hardwareTitle($hw),
```

- [ ] **Step 7: Прогнать тесты и поправить ожидания**

```bash
php artisan test
```

Ожидание: `ProductsPlanControllerTest` падает только на временах упаковочных/дочерних планов — интервал сменился 10 → 15, т.е. соответствующие `ended_at` сдвинулись на +5 минут (варочные планы не меняются). В `tests/Feature/ProductsPlanControllerTest.php` обновить ожидаемые значения, подставив фактические из вывода теста. Ориентировочно:
- тест 1: линия 37 `21:44:00` → `21:49:00`; линия 14 `23:44:00` → `23:49:00`; линия 17 `21:59:00` → `22:04:00`;
- тест 2: линия 13 `01:12:22` → `01:17:22`; линия 14 `23:44:07` → `23:49:07`, второй план `01:12:22` → `01:17:22`.

Если фактические значения из вывода теста отличаются от этого прогноза — использовать фактические (правка ожиданий допустима, главное — зелёный суит). Повторить `php artisan test` до зелёного.

- [ ] **Step 8: Линт и коммит**

```bash
./vendor/bin/pint app/Http/Controllers/ProductsPlanController.php app/Util.php app/Http/Controllers/TableController.php tests/Feature/ProductsPlanControllerTest.php
php artisan test
git add -A && git commit -m "feat: интервалы, возвратные массы и оборудование отчёта из настроек БД"
```

---

### Task 5: Фронтенд-стор production.ts и загрузка в App.vue

**Files:**
- Create: `resources/js/vueapp/store/production.ts`
- Modify: `resources/js/App.vue` (загрузка стора)
- Modify: `resources/js/vueapp/store/lines.ts` (`add()` возвращает новую линию)

**Interfaces:**
- Consumes: API из задачи 3; хелперы `getRequest/postRequest/putRequest/deleteRequest` из `@/functions` (возвращают `response.data`, ошибка — `{error: string}` с нотификацией).
- Produces (для задач 6–7): стор `useProductionStore` с полями `hardwares`, `returnTypes`, `settings`, `registryLines`; computed `boilHardwares` (с элементом `{value: null, label: 'Нет'}`), `packHardwares` (`{value, label, title}`), `slotHardwareOptions`, `returnTypeOptions`; методы `_load`, `_loadRegistry`, `_createHardware`, `_updateHardware`, `_deleteHardware`, `_createReturnType`, `_updateReturnType`, `_deleteReturnType`, `_saveSettings`, `_bulkUpdateLines`, `_deleteLine`, `getByID`.

- [ ] **Step 1: Создать стор**

`resources/js/vueapp/store/production.ts`:

```ts
import { defineStore } from "pinia";
import { computed, ref } from "vue";
import { deleteRequest, getRequest, postRequest, putRequest } from "@/functions";

export type HardwareInfo = {
    hardware_id: number,
    title: string,
    full_title?: string | null,
    type: number
};

export type ReturnTypeInfo = {
    return_type_id: number,
    title: string,
    formula_type: 'fixed' | 'coef',
    fixed_value?: number | null,
    coef_z?: number | null,
    coef_s?: number | null,
    coef_k?: number | null
};

export type RegistryLine = {
    line_id: number,
    title: string,
    color?: string | null,
    type_id: number,
    return_type?: number | null,
    use_dating?: boolean,
    perfomance?: string | null,
    prep_time?: number | null,
    after_time?: number | null,
    workers_count?: number | null
};

export type SettingsInfo = {
    interval_boil: number,
    interval_pack: number,
    zm_perfomance: number,
    zm_perfomance2: number
};

export const useProductionStore = defineStore('production', () => {
    const hardwares = ref<HardwareInfo[]>([]);
    const returnTypes = ref<ReturnTypeInfo[]>([]);
    const registryLines = ref<RegistryLine[]>([]);
    const settings = ref<SettingsInfo>({
        interval_boil: 10,
        interval_pack: 15,
        zm_perfomance: 143.5,
        zm_perfomance2: 287
    });

    async function _load(): Promise<void> {
        const [hw, rt, st] = await Promise.all([
            getRequest('/api/production/hardwares/get'),
            getRequest('/api/production/return_types/get'),
            getRequest('/api/production/settings/get')
        ]);
        hardwares.value = hw;
        returnTypes.value = rt;
        settings.value = {
            interval_boil: Number(st.interval_boil ?? 10),
            interval_pack: Number(st.interval_pack ?? 15),
            zm_perfomance: Number(st.zm_perfomance ?? 143.5),
            zm_perfomance2: Number(st.zm_perfomance2 ?? 287)
        };
    }

    async function _loadRegistry(): Promise<void> {
        registryLines.value = await getRequest('/api/lines/registry');
    }

    const boilHardwares = computed(() => [
        { value: null, label: 'Нет' } as { value: number | null, label: string },
        ...hardwares.value.filter((h) => h.type === 1).map((h) => ({ value: h.hardware_id, label: h.title }))
    ]);

    const packHardwares = computed(() =>
        hardwares.value.filter((h) => h.type === 2).map((h) => ({
            value: h.hardware_id,
            label: h.title,
            title: h.full_title
        }))
    );

    const slotHardwareOptions = computed(() => [
        { value: null, label: 'Нет' } as { value: number | null, label: string },
        ...hardwares.value.map((h) => ({ value: h.hardware_id, label: h.title }))
    ]);

    const returnTypeOptions = computed(() =>
        returnTypes.value.map((r) => ({ value: r.return_type_id, label: r.title }))
    );

    function getByID(id: number | null): HardwareInfo | undefined {
        return hardwares.value.find((h) => h.hardware_id === id);
    }

    async function _createHardware(h: HardwareInfo): Promise<void> {
        const res = await postRequest('/api/production/hardwares/create', h);
        h.hardware_id = res.hardware_id;
    }

    async function _updateHardware(h: HardwareInfo): Promise<void> {
        await putRequest('/api/production/hardwares/update', h);
    }

    async function _deleteHardware(id: number): Promise<void> {
        await deleteRequest('/api/production/hardwares/delete', { hardware_id: id });
    }

    async function _createReturnType(r: ReturnTypeInfo): Promise<void> {
        const res = await postRequest('/api/production/return_types/create', r);
        r.return_type_id = res.return_type_id;
    }

    async function _updateReturnType(r: ReturnTypeInfo): Promise<void> {
        await putRequest('/api/production/return_types/update', r);
    }

    async function _deleteReturnType(id: number): Promise<void> {
        await deleteRequest('/api/production/return_types/delete', { return_type_id: id });
    }

    async function _saveSettings(): Promise<void> {
        await putRequest('/api/production/settings/update', { ...settings.value });
    }

    async function _bulkUpdateLines(lineIds: number[], fields: object): Promise<void> {
        await postRequest('/api/lines/bulk-update', { line_ids: lineIds, fields });
    }

    async function _deleteLine(lineId: number): Promise<void> {
        await deleteRequest('/api/lines/delete', { line_id: lineId });
    }

    return {
        hardwares, returnTypes, registryLines, settings,
        boilHardwares, packHardwares, slotHardwareOptions, returnTypeOptions,
        getByID,
        _load, _loadRegistry,
        _createHardware, _updateHardware, _deleteHardware,
        _createReturnType, _updateReturnType, _deleteReturnType,
        _saveSettings, _bulkUpdateLines, _deleteLine
    };
});
```

- [ ] **Step 2: lines.ts add() возвращает созданную линию**

В `resources/js/vueapp/store/lines.ts` в конце функции `add()` после `lines.value.push(newLine);` добавить:

```ts
        return newLine;
```

и поменять сигнатуру на `function add(): LineInfo {`.

- [ ] **Step 3: Загрузка стора в App.vue**

В `resources/js/App.vue`:
1) добавить импорт `import { useProductionStore } from '@/store/production';`;
2) в `onBeforeMount` после `await useLinesStore()._load();` добавить `await useProductionStore()._load();`.

- [ ] **Step 4: Сборка и коммит**

```bash
npm run build
git add -A && git commit -m "feat: стор production.ts (оборудование, режимы, настройки, реестр линий)"
```

Сборка должна пройти (vite не типизирует, но ловит синтаксические ошибки и неразрешённые импорты).

---

### Task 6: Модалка «Производство» и её регистрация

**Files:**
- Create: `resources/js/vueapp/components/modals/production.vue`
- Modify: `resources/js/vueapp/store/modal.ts` (ключ `production`)
- Modify: `resources/js/App.vue` (монтирование `<ProductionWindow />`)
- Modify: `resources/js/vueapp/components/common/toolbar.vue` (пункт «Производство»)

**Interfaces:**
- Consumes: стор `useProductionStore` (задача 5), `useModalsStore.visibility` (ключ `production` — добавляется здесь), `useLinesStore.add/_create/_load` для кнопки «Добавить линию».

- [ ] **Step 1: Зарегистрировать модалку в modal.ts**

В `resources/js/vueapp/store/modal.ts` в объект `visibility` добавить строку:

```ts
        production: ref(false),
```

- [ ] **Step 2: Создать компонент production.vue**

`resources/js/vueapp/components/modals/production.vue` (весь файл):

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useModalsStore } from '@/store/modal';
import { HardwareInfo, RegistryLine, ReturnTypeInfo, useProductionStore } from '@/store/production';
import { useLinesStore } from '@/store/lines';
import {
    Modal, Tabs, TabPane, Table, Button, Input, InputNumber, Select, Checkbox, Popconfirm
} from 'ant-design-vue';
import { DeleteOutlined, PlusOutlined, SaveOutlined } from '@ant-design/icons-vue';

const modal = useModalsStore();
const production = useProductionStore();
const linesStore = useLinesStore();

production._loadRegistry();

/* ===== Линии ===== */
const selectedLineIds = ref<number[]>([]);
const bulkModalOpen = ref(false);
const bulk = ref<{
    type_id?: number | null;
    return_type?: string | number | null;
    use_dating?: number | null;
    perfomance?: string;
    prep_time?: number | null;
    after_time?: number | null;
    workers_count?: number | null;
}>({});

const onSelectLines = (keys: (string | number)[]) => {
    selectedLineIds.value = keys as number[];
};

const lineColumns = [
    { title: 'Наименование', dataIndex: 'title', key: 'title', width: 200 },
    { title: 'Производительность', dataIndex: 'perfomance', key: 'perfomance', width: 180 },
    { title: 'Тип', dataIndex: 'type_id', key: 'type_id', width: 140 },
    { title: 'Подг. время', dataIndex: 'prep_time', key: 'prep_time', width: 95 },
    { title: 'Закл. время', dataIndex: 'after_time', key: 'after_time', width: 95 },
    { title: 'Рабочие', dataIndex: 'workers_count', key: 'workers_count', width: 90 },
    { title: 'Возвратные массы', dataIndex: 'return_type', key: 'return_type', width: 190 },
    { title: 'Датирование', dataIndex: 'use_dating', key: 'use_dating', width: 110 },
    { title: 'Цвет', dataIndex: 'color', key: 'color', width: 60 },
    { title: '', key: 'actions', width: 90 }
];

const saveLineRow = async (row: RegistryLine) => {
    await production._bulkUpdateLines([row.line_id], {
        title: row.title,
        perfomance: row.perfomance ?? null,
        type_id: row.type_id,
        prep_time: row.prep_time ?? null,
        after_time: row.after_time ?? null,
        workers_count: row.workers_count ?? null,
        return_type: row.return_type ?? null,
        use_dating: row.use_dating ?? false,
        color: row.color ?? null
    });
    await production._loadRegistry();
};

const addLine = async () => {
    const line = linesStore.add();
    await linesStore._create(line);
    await linesStore._load();
    await production._loadRegistry();
};

const deleteLine = async (row: RegistryLine) => {
    await production._deleteLine(row.line_id);
    await linesStore._load();
    await production._loadRegistry();
};

const applyBulk = async () => {
    const fields: Record<string, any> = {};
    if (bulk.value.type_id !== undefined && bulk.value.type_id !== null) {
        fields.type_id = bulk.value.type_id;
    }
    if (bulk.value.return_type === '__clear__') {
        fields.return_type = null;
    } else if (bulk.value.return_type !== undefined && bulk.value.return_type !== null) {
        fields.return_type = bulk.value.return_type;
    }
    if (bulk.value.use_dating === 1) {
        fields.use_dating = true;
    } else if (bulk.value.use_dating === 0) {
        fields.use_dating = false;
    }
    if (bulk.value.perfomance) {
        fields.perfomance = bulk.value.perfomance;
    }
    if (bulk.value.prep_time !== undefined && bulk.value.prep_time !== null) {
        fields.prep_time = bulk.value.prep_time;
    }
    if (bulk.value.after_time !== undefined && bulk.value.after_time !== null) {
        fields.after_time = bulk.value.after_time;
    }
    if (bulk.value.workers_count !== undefined && bulk.value.workers_count !== null) {
        fields.workers_count = bulk.value.workers_count;
    }
    await production._bulkUpdateLines(selectedLineIds.value, fields);
    bulkModalOpen.value = false;
    bulk.value = {};
    await production._loadRegistry();
};

/* ===== Оборудование ===== */
const hardwareColumns = [
    { title: 'Название', dataIndex: 'title', key: 'title' },
    { title: 'Полное название', dataIndex: 'full_title', key: 'full_title' },
    { title: 'Тип', dataIndex: 'type', key: 'type', width: 160 },
    { title: '', key: 'actions', width: 90 }
];

const saveHardware = async (h: HardwareInfo) => {
    if (h.hardware_id) {
        await production._updateHardware(h);
    } else {
        await production._createHardware(h);
    }
    await production._load();
};

const deleteHardware = async (h: HardwareInfo) => {
    await production._deleteHardware(h.hardware_id);
    await production._load();
};

const addHardware = () => {
    production.hardwares.push({ hardware_id: 0, title: '', full_title: '', type: 1 });
};

/* ===== Возвратные массы ===== */
const returnTypeColumns = [
    { title: 'Название', dataIndex: 'title', key: 'title' },
    { title: 'Формула', dataIndex: 'formula_type', key: 'formula_type', width: 160 },
    { title: 'Фикс. значение', dataIndex: 'fixed_value', key: 'fixed_value', width: 120 },
    { title: 'Зефир', dataIndex: 'coef_z', key: 'coef_z', width: 100 },
    { title: 'Суфле', dataIndex: 'coef_s', key: 'coef_s', width: 100 },
    { title: 'Конфеты', dataIndex: 'coef_k', key: 'coef_k', width: 100 },
    { title: '', key: 'actions', width: 90 }
];

const saveReturnType = async (r: ReturnTypeInfo) => {
    if (r.return_type_id) {
        await production._updateReturnType(r);
    } else {
        await production._createReturnType(r);
    }
    await production._load();
};

const deleteReturnType = async (r: ReturnTypeInfo) => {
    await production._deleteReturnType(r.return_type_id);
    await production._load();
};

const addReturnType = () => {
    production.returnTypes.push({
        return_type_id: 0, title: '', formula_type: 'coef',
        fixed_value: null, coef_z: null, coef_s: null, coef_k: null
    });
};

const saveSettings = async () => {
    await production._saveSettings();
};
</script>

<template>
    <Modal v-model:open="modal.visibility['production']" title="Производство" :closable="true" width="1200"
        :footer="null">
        <Tabs tab-position="left">
            <TabPane key="lines" tab="Линии">
                <div style="display:flex; gap:8px; margin-bottom:8px;">
                    <Button type="primary" @click="addLine">
                        <PlusOutlined /> Добавить линию
                    </Button>
                    <Button :disabled="selectedLineIds.length == 0" @click="bulkModalOpen = true">
                        Изменить выделенные
                    </Button>
                </div>
                <Table :columns="lineColumns" :data-source="production.registryLines"
                    :row-key="(r: RegistryLine) => r.line_id"
                    :row-selection="{ selectedRowKeys: selectedLineIds, onChange: onSelectLines }"
                    :pagination="false" size="small" :scroll="{ x: 1200 }">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.dataIndex == 'title'">
                            <Input v-model:value="record.title" />
                        </template>
                        <template v-else-if="column.dataIndex == 'perfomance'">
                            <Input v-model:value="record.perfomance" />
                        </template>
                        <template v-else-if="column.dataIndex == 'type_id'">
                            <Select v-model:value="record.type_id" style="width:100%"
                                :options="[{ value: 1, label: 'Варка' }, { value: 2, label: 'Упаковка' }, { value: 3, label: 'Сборка ящиков' }]" />
                        </template>
                        <template v-else-if="['prep_time', 'after_time', 'workers_count'].includes(column.dataIndex)">
                            <InputNumber v-model:value="record[column.dataIndex]" :min="0" style="width:100%" />
                        </template>
                        <template v-else-if="column.dataIndex == 'return_type'">
                            <Select v-model:value="record.return_type" style="width:100%" :allowClear="true"
                                placeholder="Не участвует" :options="production.returnTypeOptions" />
                        </template>
                        <template v-else-if="column.dataIndex == 'use_dating'">
                            <Checkbox v-model:checked="record.use_dating" />
                        </template>
                        <template v-else-if="column.dataIndex == 'color'">
                            <input type="color" v-model:value="record.color" />
                        </template>
                        <template v-else-if="column.dataIndex == 'actions'">
                            <Button type="primary" size="small" @click="saveLineRow(record)">
                                <SaveOutlined />
                            </Button>
                            <Popconfirm title="Удалить линию?" @confirm="deleteLine(record)">
                                <Button type="dashed" danger size="small">
                                    <DeleteOutlined />
                                </Button>
                            </Popconfirm>
                        </template>
                    </template>
                </Table>
            </TabPane>

            <TabPane key="hardwares" tab="Оборудование">
                <Button type="primary" style="margin-bottom:8px;" @click="addHardware">
                    <PlusOutlined /> Добавить оборудование
                </Button>
                <Table :columns="hardwareColumns" :data-source="production.hardwares"
                    :row-key="(r: HardwareInfo) => r.hardware_id" :pagination="false" size="small">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.dataIndex == 'title'">
                            <Input v-model:value="record.title" />
                        </template>
                        <template v-else-if="column.dataIndex == 'full_title'">
                            <Input v-model:value="record.full_title" />
                        </template>
                        <template v-else-if="column.dataIndex == 'type'">
                            <Select v-model:value="record.type" style="width:100%"
                                :options="[{ value: 1, label: 'Варка' }, { value: 2, label: 'Упаковка' }]" />
                        </template>
                        <template v-else-if="column.dataIndex == 'actions'">
                            <Button type="primary" size="small" @click="saveHardware(record)">
                                <SaveOutlined />
                            </Button>
                            <Popconfirm title="Удалить оборудование?" @confirm="deleteHardware(record)">
                                <Button type="dashed" danger size="small">
                                    <DeleteOutlined />
                                </Button>
                            </Popconfirm>
                        </template>
                    </template>
                </Table>
            </TabPane>

            <TabPane key="return_types" tab="Возвратные массы">
                <Button type="primary" style="margin-bottom:8px;" @click="addReturnType">
                    <PlusOutlined /> Добавить режим
                </Button>
                <Table :columns="returnTypeColumns" :data-source="production.returnTypes"
                    :row-key="(r: ReturnTypeInfo) => r.return_type_id" :pagination="false" size="small">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.dataIndex == 'title'">
                            <Input v-model:value="record.title" />
                        </template>
                        <template v-else-if="column.dataIndex == 'formula_type'">
                            <Select v-model:value="record.formula_type" style="width:100%"
                                :options="[{ value: 'fixed', label: 'Фикс. значение' }, { value: 'coef', label: 'Коэффициент' }]" />
                        </template>
                        <template v-else-if="column.dataIndex == 'fixed_value'">
                            <InputNumber v-if="record.formula_type == 'fixed'" v-model:value="record.fixed_value"
                                :min="0" style="width:100%" />
                        </template>
                        <template v-else-if="['coef_z', 'coef_s', 'coef_k'].includes(column.dataIndex)">
                            <InputNumber v-if="record.formula_type == 'coef'" v-model:value="record[column.dataIndex]"
                                :min="0" style="width:100%" />
                        </template>
                        <template v-else-if="column.dataIndex == 'actions'">
                            <Button type="primary" size="small" @click="saveReturnType(record)">
                                <SaveOutlined />
                            </Button>
                            <Popconfirm title="Удалить режим?" @confirm="deleteReturnType(record)">
                                <Button type="dashed" danger size="small">
                                    <DeleteOutlined />
                                </Button>
                            </Popconfirm>
                        </template>
                    </template>
                </Table>
            </TabPane>

            <TabPane key="settings" tab="Постоянные величины">
                <div style="display:flex; flex-direction:column; gap:12px; max-width:420px;">
                    <label>Интервал варки (мин):
                        <InputNumber v-model:value="production.settings.interval_boil" :min="0" />
                    </label>
                    <label>Интервал упаковки (мин):
                        <InputNumber v-model:value="production.settings.interval_pack" :min="0" />
                    </label>
                    <label>Производительность ЗМ:
                        <InputNumber v-model:value="production.settings.zm_perfomance" :min="0" />
                    </label>
                    <label>Производительность 2×ЗМ:
                        <InputNumber v-model:value="production.settings.zm_perfomance2" :min="0" />
                    </label>
                    <Button type="primary" style="width:160px;" @click="saveSettings">Сохранить</Button>
                </div>
            </TabPane>
        </Tabs>

        <Modal v-model:open="bulkModalOpen" title="Изменить выделенные линии" @ok="applyBulk">
            <div style="display:flex; flex-direction:column; gap:8px;">
                <Select v-model:value="bulk.type_id" placeholder="Тип — не менять" :allowClear="true"
                    :options="[{ value: 1, label: 'Варка' }, { value: 2, label: 'Упаковка' }, { value: 3, label: 'Сборка ящиков' }]" />
                <Select v-model:value="bulk.return_type" placeholder="Возвратные массы — не менять"
                    :options="[...production.returnTypeOptions, { value: '__clear__', label: 'Очистить (не участвует)' }]" />
                <Select v-model:value="bulk.use_dating" placeholder="Датирование — не менять"
                    :options="[{ value: 1, label: 'Да' }, { value: 0, label: 'Нет' }]" />
                <Input v-model:value="bulk.perfomance" placeholder="Производительность" />
                <InputNumber v-model:value="bulk.prep_time" placeholder="Подготовительное время" :min="0" />
                <InputNumber v-model:value="bulk.after_time" placeholder="Заключительное время" :min="0" />
                <InputNumber v-model:value="bulk.workers_count" placeholder="Рабочие" :min="0" />
            </div>
        </Modal>
    </Modal>
</template>
```

- [ ] **Step 3: Монтировать модалку в App.vue**

В `resources/js/App.vue`:
1) добавить импорт `import ProductionWindow from '@modals/production.vue';` (рядом с `import ProductsDict ...`);
2) в шаблон добавить `<ProductionWindow />` после `<ProductsDict />`.

- [ ] **Step 4: Пункт «Производство» в toolbar.vue**

В `resources/js/vueapp/components/common/toolbar.vue`:
1) в импорт иконок добавить `SettingOutlined` (строка `import { FileExcelOutlined, BarChartOutlined, ... } from '@ant-design/icons-vue';`);
2) в выпадашку «Реестры» после MenuItem с `openModal('products')` добавить:

```html
                        <MenuItem>
                        <Button type="primary" @click="openModal('production')">
                            <SettingOutlined />
                            Производство
                        </Button>
                        </MenuItem>
```

- [ ] **Step 5: Сборка и коммит**

```bash
npm run build
git add -A && git commit -m "feat: окно «Производство» (линии, оборудование, возвратные массы, постоянные величины)"
```

---

### Task 7: Переезд оборудования на API (dicts.ts, plan.vue, products.vue)

**Files:**
- Modify: `resources/js/vueapp/store/dicts.ts` (удалить `hardwares`, `packHardwares`)
- Modify: `resources/js/vueapp/components/modals/plan.vue`
- Modify: `resources/js/vueapp/components/modals/products.vue`

**Interfaces:**
- Consumes: computed `boilHardwares` / `packHardwares` / `slotHardwareOptions` / `getByID` из стора production (задача 5).

- [ ] **Step 1: Удалить константы из dicts.ts**

В `resources/js/vueapp/store/dicts.ts` удалить целиком блоки:

```ts
/**
 * Список оборудования
 */
export const hardwares = [ ... ];

/**
 * Список оборудования для упаковки
 */
export const packHardwares = [ ... ];
```

- [ ] **Step 2: plan.vue — списки из стора**

В `resources/js/vueapp/components/modals/plan.vue`:
1) импорт: заменить `import { colons, hardwares, packHardwares, productsTabs } from '@/store/dicts';` на `import { colons, productsTabs } from '@/store/dicts';` и добавить `import { useProductionStore } from '@/store/production';`;
2) в `<script setup>` добавить `const production = useProductionStore();`;
3) в шаблоне (блок «Оборудование», radio для варки):

```html
                        <RadioGroup v-model:value="state.hardware" @change="handleHardware" :disabled="state.isLoading">
                            <RadioButton v-for="i in hardwares" :value="i.value" :key="i.value">
                                {{ i.label }}
                            </RadioButton>
                        </RadioGroup>
```

заменить `v-for="i in hardwares"` на `v-for="i in production.boilHardwares"`;

4) в блоке «Оборудование упаковки» (one shot) заменить `v-for="i in packHardwares"` на `v-for="i in production.packHardwares"` (там же `<Tooltip :title="i.title">` — форма элементов стора сохраняет `title`);
5) в `handleHardware` fallback-блок заменить:

```ts
        if ([4,5,6].includes(state.hardware)) {
            state.perfomance = (state.hardware === 4 || state.hardware === 5) ? 143.5 : 287;
        }
```

на:

```ts
        // ЗМ (ID 4–6 закреплены сидом оборудования): 143.5/287 берём из настроек
        if ([4,5,6].includes(state.hardware)) {
            state.perfomance = (state.hardware === 4 || state.hardware === 5)
                ? production.settings.zm_perfomance
                : production.settings.zm_perfomance2;
        }
```

- [ ] **Step 3: products.vue — селект и отображение оборудования**

В `resources/js/vueapp/components/modals/products.vue`:
1) импорт: заменить `import { categoriesTableColumns, hardwares, productsTableColumns, productsTabs } from '@/store/dicts';` на `import { categoriesTableColumns, productsTableColumns, productsTabs } from '@/store/dicts';` и добавить `import { useProductionStore } from '@/store/production';`;
2) в `<script setup>` добавить `const production = useProductionStore();`;
3) в шаблоне селект оборудования слотов:

```html
                                                    <Select v-model:value="record[column.dataIndex]"
                                                        style="width: 100%;" :options="hardwares">
                                                    </Select>
```

заменить `:options="hardwares"` на `:options="production.slotHardwareOptions"`;

4) отображение названия оборудования:

```html
                                                <template v-if="column.dataIndex == 'hardware' && text">
                                                    {{ hardwares[text]!.label }}
                                                </template>
```

заменить на:

```html
                                                <template v-if="column.dataIndex == 'hardware' && text">
                                                    {{ production.getByID(text)?.title ?? '' }}
                                                </template>
```

- [ ] **Step 4: Сборка и коммит**

```bash
npm run build
git add -A && git commit -m "refactor: списки оборудования из API вместо констант dicts"
```

---

### Task 8: Сокращение lineForm на доске «Компания»

**Files:**
- Modify: `resources/js/vueapp/components/common/lineForm.vue`

**Interfaces:**
- Consumes: `LineInfo` из `store/lines` (поля type_id/return_type/use_dating остаются в типе — их правит только реестр).
- Produces: форма на доске правит только сменные поля; регистровые поля (Тип, Возвратные массы, Датирование, Наименование) — только в окне «Производство».

- [ ] **Step 1: Убрать импорт lineReturnTypes**

В `resources/js/vueapp/components/common/lineForm.vue` заменить строку импорта:

```ts
import { cancelReasons, lineReturnTypes } from '../../store/dicts';
```

на:

```ts
import { cancelReasons } from '../../store/dicts';
```

- [ ] **Step 2: Убрать регистровые поля из шаблона**

В `resources/js/vueapp/components/common/lineForm.vue` удалить из шаблона:
1) Input названия в режиме редактирования (в блоке `#title`):

```html
            <Input v-show="data.edit" class="line_title line-input" v-model:value="data.title"
                placeholder="Наименование" />
```

(оставить `<b>{{ data.title }}</b>` в режиме просмотра и Input для `extra_title`);

2) блок «Тип линии»:

```html
                <RadioGroup v-model:value="data.type_id" class="select resp">
                    <RadioButton value="1">Варка</RadioButton>
                    <RadioButton value="2">Упаковка</RadioButton>
                    <RadioButton value="3">Сборка ящиков</RadioButton>
                </RadioGroup>
                <span>Возвратные массы:</span>
                <Select v-model:value="data.return_type" placeholder="Не участвует" :allowClear="true"
                    :options="lineReturnTypes" class="select" />
                <Checkbox v-model:checked="data.use_dating">
                    Учитывать в датировании
                </Checkbox>
```

Иконки-индикаторы `<ExperimentOutlined v-if="data.type_id == 1 ..." />` и `<InboxOutlined v-else-if="!data.edit" />`, а также чекбокс металлодетектора `v-if="data.type_id == 2"` НЕ трогать — они только читают `data.type_id`.

3) Если после удаления `RadioButton`/`RadioGroup` больше нигде в файле не используются — убрать их из импорта ant-design-vue (строка `import { Card, Input, Switch, Tooltip, Popconfirm, Select, SelectOption, TimePicker, RadioGroup, RadioButton, Checkbox } from 'ant-design-vue';`): оставить только реально используемые компоненты (`Select` остаётся — он используется для ответственных и причины остановки; `Checkbox` остаётся — флаги «Сохранить как значение по умолчанию»).

- [ ] **Step 3: Сборка и коммит**

```bash
npm run build
git add -A && git commit -m "refactor: lineForm правит только сменные поля, регистровые — в окне «Производство»"
```

---

### Task 9: Финальная проверка

**Files:** — (проверка без изменений; при необходимости мелкие правки)

- [ ] **Step 1: Полная проверка бэкенда**

```bash
./vendor/bin/pint --test app database
php artisan test
```

`ProductsPlanControllerTest` зелёный. Если pint что-то переформатировал в Task 4 после правки тестов — прогнать тесты ещё раз.

- [ ] **Step 2: Сборка фронтенда**

```bash
npm run build
```

- [ ] **Step 3: Ручной smoke-чек по чек-листу**

1. Открыть приложение (dev-сервер) → «Реестры» → «Производство» — окно открывается, слева 4 вкладки.
2. «Линии»: строки загружены; изменить название/тип/время одной строки → «Сохранить»; выделить 2 строки → «Изменить выделенные» → поменять «Датирование = Да» → применить → у обеих строк флаг обновился.
3. «Оборудование»: добавить запись, переименовать существующую, удалить добавленную. Удаление ЗМ №1 (используется в слотах) возвращает ошибку.
4. «Возвратные массы»: поменять коэффициент режима 4 на 0.01 → сохранить; удаление режима, привязанного к линии, возвращает ошибку.
5. «Постоянные величины»: изменить «Интервал упаковки» на 20 → сохранить → поставить новый план упаковки — к его концу прибавилось 20 мин (вернуть 15 после проверки).
6. Доска «Компания»: lineForm без полей Тип/Возвратные массы/Датирование; расстановка сотрудников работает.
7. Отчёт: названия оборудования подставляются из справочника (изменить название ЗМ №1 → в отчёте новое название).

- [ ] **Step 4: Итоговый коммит (если были правки) или фиксация**

```bash
git status
git add -A && git commit -m "chore: финальные правки окна «Производство»" || echo "clean"
```

---

## Self-Review

**1. Spec coverage:**
- Раздел 3.2 (таблицы) → Task 1; 3.3 (чистка) → Task 2; 4.1 (модели/API/защита удаления) → Task 1 + 3; 4.2 (bulk-update) → Task 3; 4.3 (магические числа) → Task 4; 4.4 (изменение поведения) → Task 4 step 7; 5.1 (разделение доски/реестра, lineForm) → Task 8; 5.2 (окно + toolbar) → Task 6; 5.3 (вкладки) → Task 6; 5.4 (стор) → Task 5; 6 (совместимость) → Tasks 1–2 (ID фиксированы сидами, откат через down()); 7 (тестирование, без новых тестов — по требованию) → Task 4 step 7 + Task 9; 8 (вне скоупа) — не реализуется. Покрытие полное.
- Удаление `config/lines_defaults.php` (спека 3.3) → Task 2 step 6. Удаление fallback из `Util::getDefaults` → Task 2 step 3.

**2. Placeholder scan:** все шаги содержат код или конкретные команды; «фактические значения из вывода теста» — осознанная инструкция для сохранения зелёного суита, не плейсхолдер.

**3. Type consistency:** `Setting::get` (Task 1) ↔ использование (Task 4); `production` стор: имена методов и computed совпадают между Task 5 (определения) и Tasks 6–7 (использование): `boilHardwares`, `packHardwares`, `slotHardwareOptions`, `returnTypeOptions`, `getByID`, `_bulkUpdateLines`, `_deleteLine`, `_loadRegistry`, `_createHardware`, `_updateHardware`, `_deleteHardware`, `_createReturnType`, `_updateReturnType`, `_deleteReturnType`, `_saveSettings`, `settings`. Модели: `Hardware`/`ReturnType`/`Setting` совпадают с контроллером. Эндпоинты `/api/production/*` совпадают в роутах и сторе.

**Известные допущения (осознанные):**
- `interval_pack` в тестах: правка ожиданий — единственное изменение суита, разрешённое заказчиком.
- Fallback-проверка `[4,5,6]` в plan.vue остаётся на ID — они закреплены сидом; задокументировано в спеке (раздел 8).
- Static-кэш `Setting::get`/`Util::getReturnTypes` живёт один запрос — после сохранения настроек в UI новые значения применятся со следующего запроса.
