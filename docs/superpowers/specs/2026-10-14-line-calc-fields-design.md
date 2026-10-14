# Дизайн: Настраиваемые правила расчёта отчёта (итерация 2)

Дата: 2026-10-14
Статус: черновик, ожидает согласования
Основание: `docs/line-system-tz.md` (итерация 1, e87b169), раздел 9 «кандидаты на следующие итерации»
Целевая ветка: `main` (окно «Производство» и `TableController` в актуальном виде живут на `main`)

## 1. Контекст и цель

Итерация 1 (e87b169) перенесла на настраиваемые поля: роль линии (`type_id`), возвратные массы (`return_type`), датирование (`use_dating`), телевизор (`televisor`).

В `TableController` (отчёт xlsx) остались захардкоженные правила:

| Правило | Хардкод (`main`) | Места |
|---|---|---|
| Варочные линии зефира | `[8, 9, 10, 11, 12]` | строки 508, 544, 557, 595 |
| Итоги упаковки по категориям | з `[14, 17, 18, 20, 24, 25, 41]` (без 51), к `== 31`, с `== 20` | switch ~579–592 |
| Подтипы зефира | `$map` + default `p` + заголовки | 471–490 и 644–652; ключи a/z/n/f/p/c в `$globalBy` (268–275), `$sumBy` (610–617), цикле 557–564 |

Датирование уже настраивается через `use_dating` (строка 498) — **не трогаем**.

**Цель:** вынести три правила в настраиваемые поля (реестр линий + новый справочник), редактируемые в окне «Производство», без правки кода. Поведение отчёта сохранить идентичным.

## 2. Принятые решения

| Вопрос | Решение |
|---|---|
| «Расчёт (варка)» | Чекбокс `lines.calc_boil` (bool). Заменяет все `[8,9,10,11,12]` |
| «Расчёт (упаковка)» | Мульти-выбор `lines.pack_categories` (JSON-массив кодов `z`/`k`/`s`). Заменяет switch итогов упаковки |
| Почему мульти-выбор | Линия 20 (ШЛ1) в итогах зефира И суфле — одно значение не выразит. Подтверждено пользователем |
| Датирование | Не трогаем — уже `use_dating` (по линии). Подтверждено |
| Типы зефира | Новая таблица `zephyr_types` (id, title, is_default). Подтип у продукции выбирается вручную дропдауном (поле `products_dictionary.zephyr_type_id`); ключи-буквы a/z/n/f/p/c → `zephyr_type_id` |
| Совместимость | Только добавляем столбцы + таблицу; старая версия их игнорирует. Сиды = текущее поведение |

## 3. Схема БД

### 3.1 Новые колонки `lines`

| Поле | Тип | Назначение |
|---|---|---|
| `calc_boil` | boolean, default false | «Расчёт (варка)»: линия варочная — участвует в расчёте варки зефира и его подтипов |
| `pack_categories` | json, nullable | «Расчёт (упаковка)»: массив категорий, итоги которых линия даёт: `["z"]`, `["k"]`, `["s"]`, `["z","s"]`; `null`/`[]` — не участвует |

### 3.2 Новая таблица `zephyr_types`

| Поле | Тип | Назначение |
|---|---|---|
| `zephyr_type_id` | PK autoincrement | Порядок id = порядок строк «ИТОГО» |
| `title` | string | Текст в строке «ИТОГО {title}» |
| `is_default` | boolean, default false | Тип по умолчанию: подтип, к которому относится продукция, если у неё явно не выбран подтип |

Подтип хранится у продукции в `products_dictionary.zephyr_type_id` (nullable, логическая ссылка на `zephyr_types`, без DB-констрейнта — как hardwares/return_types): выбор в карточке продукции (дропдаун). Целостность держит delete-guard в `ProductionController`.

### 3.3 Данные (паритет с текущим), в миграции

`zephyr_types` (порядок id = текущий порядок строк «ИТОГО»):

| id | title | is_default |
|---|---|---|
| 1 | агара | false |
| 2 | желатина | false |
| 3 | без сахара | false |
| 4 | фруктозы | false |
| 5 | на пектине | true |
| 6 | крем-десерты | false |

`products_dictionary.zephyr_type_id` — одноразовый backfill по старым правилам названий (first-match-wins): `агар→1`, `желатин→2`, `/без\s+(добавления\s+)?сахар[а]?/u→3`, `фруктоз→4`, `крем-десерт→6`, иначе `5` (default «на пектине»). После переноса подтип меняется вручную дропдауном.

Колонки `lines`:
- `calc_boil = true` для `[8, 9, 10, 11, 12]`, иначе false.
- `pack_categories`: `14,17,18,24,25,41 → ["z"]`; **`20 → ["z","s"]`**; `31 → ["k"]`; остальные → `null`.

Примечание: на `main` z-итоги = `[14,17,18,20,24,25,41]` **без 51** (в исходной задаче было с 51 — берём актуальный список `main`).

## 4. Бэкенд

### 4.1 Модели
- `app/Models/ZephyrType.php` (новый): `$fillable = ['zephyr_type_id','title','is_default']`, `$primaryKey = 'zephyr_type_id'`, `$timestamps = false`.
- `app/Models/Lines.php`: `calc_boil`, `pack_categories` в `$fillable`; `$casts = ['calc_boil' => 'boolean', 'pack_categories' => 'array']`.
- `app/Models/ProductsDictionary.php`: `zephyr_type_id` в `$fillable`.

### 4.2 `TableController` (отчёт)
Одна выборка в начале: `$zefTypes = ZephyrType::orderBy('zephyr_type_id')->get();` (+ `$zefDefaultId`).

| Сейчас (`main`) | Станет |
|---|---|
| 508/544/557/595: `array_search($line['line_id'], [8,9,10,11,12])` | `$line['calc_boil']` |
| switch итогов упаковки ~579–592: `case 'z'` → `[14,…,41]`, `case 'k'` → `==31`, `case 's'` → `==20` | `if (in_array($i, $line['pack_categories'] ?? [], true)) $add($i, $sum);` |
| 471–490: `$map` + `$key='p'` | ключ = `$product['zephyr_type_id'] ?? $zefDefaultId` (подтип из поля продукции, без сопоставления по названию) |
| 268–275 / 610–617: `$globalBy`/`$sumBy` с ключами a/z/n/f/p/c | инициализация по `$zefTypes` (ключ = `zephyr_type_id`) |
| 557–564: `foreach (['a','z','n','f','p','c'])` | `foreach ($zefTypes as $t)` |
| 644–652: `['a'=>'агара',…] as $key=>$title` | `foreach ($zefTypes as $t)` с `$t->title` |

Паритет: порядок id = порядок строк «ИТОГО»; линия 20 даёт и з, и с (`pack_categories = ["z","s"]`); backfill продукции по старым правилам названий даёт те же подтипы, что и старый код.

### 4.3 `LinesController`
- `registry()`: добавить `calc_boil`, `pack_categories` в вывод (приходят из `lines` через `toArray()`; cast → массив).
- `bulkUpdate()`: `calc_boil`, `pack_categories` в список полей → `lines`.

### 4.4 `ProductionController` + `routes/api.php`
CRUD `zephyr_types` (get/create/update/delete) по образцу `hardwares`/`return_types`. Удаление типа, на который ссылаются продукты (`products_dictionary.zephyr_type_id`), блокируется (delete-guard).

### 4.5 Миграция (обратно совместима — только добавляет)
1. `lines.calc_boil` (boolean default false), `lines.pack_categories` (json nullable).
2. Таблица `zephyr_types`.
3. Данные: заполнить `calc_boil`/`pack_categories` по текущим спискам; засидить 6 типов зефира; backfill `products_dictionary.zephyr_type_id` по старым правилам названий.

Откат: drop `zephyr_types`, drop колонок.

## 5. Фронтенд
Окно «Производство» (`production.vue`), стор `production.ts`:
- Вкладка «Линии»: колонки «Варка» (checkbox `calc_boil`) и «Расчёт (упаковка)» (Select `mode="multiple"`, опции Зефир/Конфеты/Суфле, пусто = «Нет»). В bulk-edit — то же (мульти-селект + чекбокс).
- Новая вкладка «Типы зефира»: таблица Наименование (input), По умолчанию (checkbox); «Добавить» / «Удалить».
- Карточка продукции (`products.vue`): дропдаун «Тип зефира» (`zephyr_type_id`, опции из `zephyr_types`, можно очистить → в отчёте default).
- `production.ts`: `zephyrTypes`, `zephyrTypeOptions`, флаг `loaded`, типы `RegistryLine` (+`calc_boil`, `pack_categories`), `packCategoryOptions`; CRUD `zephyr_types`.
- `bulk-update` шлёт `calc_boil` (bool) и `pack_categories` (массив).

## 6. Тестирование
- Feature: CRUD `/zephyr_types`; `/lines/bulk-update` пишет `calc_boil`/`pack_categories` в `lines`; пустые не перетирают.
- Feature отчёт: сессия с планами на линиях 8/10/14/20/31; сверка итоговых строк (варка, подтипы зефира, итоги з/с/к) с текущей версией (паритет). Убрать «s» у линии 20 → «ИТОГО суфле» не включает линию 20.
- Ручная: xlsx до/после (паритет); окно «Производство» — новые колонки + вкладка «Типы зефира»; карточка продукции — дропдаун «Тип зефира».

## 7. Вне скоупа (оставшиеся кандидаты из раздела 9 ТЗ ит. 1)
- `plan.vue`: «one shot» + `line_id == 31` (опции упаковки конфет).
- `fixFisMachineConflicts`: «фис машина».
- Слоты `type_id` 1–5 (магические числа).
- `Util::getLinesPersonalTime`: классификация z/s/k по подстрокам названия.
