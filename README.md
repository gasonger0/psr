# PSR — Production Schedule & Resource management

A production and employee-shift planning system for a confectionery factory.

### What is it?

**PSR** is an internal web application for planning the manufacturing of confectionery
products (marshmallow / zefir, soufflé, candies) and assigning employees to production
lines across day and night shifts.

It helps production planners build daily plans, distribute workers, calculate KTU
(labor participation) and downtime, and generate printable Excel production documents.

### Key features

- **Production planning** — per-shift plans on two sheets: *Boiling* and *Packing*, with production stages such as boiling, glazing, dusting,
  coating and packing.
- **Drag & drop scheduling** — rearrange items on lines with automatic recalculation of
  start/end times and dependent stages.
- **Employee shift planning** — assign workers to lines, manage time slots and downtime.
- **Reference data (dictionaries)** — production lines, workers, companies (with downtime
  cost), products, product categories and responsible persons.
- **Order analysis import** — upload an Excel order file and calculate remaining
  quantities to be produced.
- **Excel reports** — production plan (Boiling / Packing), KTU wage report, downtime
  report; includes formulas, return-mass totals and breakdown by product type
  (zefir / soufflé / candies) and sub-type (agar, fructose, sugar-free, etc.).
- **Action logging** — track changes made in the system.
- **Session management** — switch between a date and shift (day / night).

### Goals

Automate production scheduling, worker allocation, KTU and downtime calculations, and
generation of shop-floor documents for confectionery manufacturing.

### Tech stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.2, Laravel 11 |
| Frontend | Vue 3 (Composition API), TypeScript, Pinia, Ant Design Vue |
| Build | Vite |
| Database | MySQL 8.0 |
| Excel | shuchkin/simplexlsx, shuchkin/simplexlsxgen |
| Infrastructure | Docker / Docker Compose (Laravel Sail) |

### Requirements & environment variables

Add the following to `.env` for local run:

```dotenv
FILE_UID=1000
FILE_GID=1000

WWW_PORT_VITE=5173
WWW_PORT=8001
MYSQL_PORT=3306
PHPMYADMIN_PORT=8000
```

### Local setup & run

The project is containerized with Docker Compose (`docker-compose.yml`):

- `node-dev` — Vite dev server;
- `www` — Laravel application (`php artisan serve`);
- `mysql` — MySQL 8 database.

```bash
cp .env.example .env   # then fill in the variables above
docker compose up -d
```

Frontend development server:

```bash
npm install
npm run dev
```

Production frontend build:

```bash
npm run build
```

### Project structure

```text
app/                     # Laravel backend (controllers, models)
config/                  # app configuration (incl. lines defaults)
resources/js/vueapp/     # Vue 3 frontend (components, stores)
resources/views/         # Blade views
routes/                  # web & api routes
docker/                  # Docker-related configs
docker-compose.yml       # local services
```

---


Система планирования производства и смен сотрудников кондитерской фабрики (ООО КФ «Сокол»).

### Что это?

**PSR** — внутреннее web-приложение для планирования изготовления кондитерской
продукции (зефир, суфле, конфеты) и расстановки сотрудников по производственным
линиям в дневные и ночные смены.

Оно помогает планировщикам составлять суточные планы, распределять рабочих,
рассчитывать КТУ (коэффициент трудового участия) и простои, а также формировать
печатные Excel-документы.

### Основные возможности

- **Планирование производства** — планы на смену по двум листам: *Варка* и *Упаковка*,
  с этапами изготовления: варка, глазировка, опудривание, обсыпка, упаковка.
- **Drag & drop планирование** — перестановка позиций на линиях с автоматическим
  пересчётом времени начала/окончания и зависимых этапов.
- **Планирование смен сотрудников** — расстановка рабочих по линиям, управление
  слотами времени и простоями.
- **Справочники** — производственные линии, сотрудники, компании (со стоимостью
  простоя), продукция, категории продукции и ответственные лица.
- **Импорт анализа заказов** — загрузка Excel-файла заказов и расчёт остатков,
  которые нужно изготовить.
- **Excel-отчёты** — план производства (Варка / Упаковка), отчёт по КТУ, отчёт по
  простоям; включают формулы, итоги по возвратной массе и разбивку по типам продукции
  (зефир / суфле / конфеты) и подтипам (агар, фруктоза, без сахара и т.д.).
- **Логирование действий** — отслеживание изменений в системе.
- **Управление сессией** — переключение между датой и сменой (день / ночь).

### Цели

Автоматизация планирования производства, распределения персонала, расчёта КТУ и
простоев, а также формирования производственных документов для кондитерского
производства.

### Технологический стек

| Область | Технология |
| --- | --- |
| Backend | PHP 8.2, Laravel 11 |
| Frontend | Vue 3 (Composition API), TypeScript, Pinia, Ant Design Vue |
| Сборка | Vite |
| БД | MySQL 8.0 |
| Excel | shuchkin/simplexlsx, shuchkin/simplexlsxgen |
| Инфраструктура | Docker / Docker Compose (Laravel Sail) |

### Требования и переменные окружения

Для локального запуска добавьте в `.env`:

```dotenv
FILE_UID=1000
FILE_GID=1000

WWW_PORT_VITE=5173
WWW_PORT=8001
MYSQL_PORT=3306
PHPMYADMIN_PORT=8000
```

### Локальная установка и запуск

Проект разворачивается через Docker Compose (`docker-compose.yml`):

- `node-dev` — Vite dev-сервер;
- `www` — Laravel-приложение (`php artisan serve`);
- `mysql` — база данных MySQL 8.

```bash
cp .env.example .env   # затем заполните переменные выше
docker compose up -d
```

Dev-сервер фронтенда:

```bash
npm install
npm run dev
```

Продакшн-сборка фронтенда:

```bash
npm run build
```

### Структура проекта

```text
app/                     # Laravel backend (контроллеры, модели)
config/                  # конфигурация приложения (в т.ч. дефолты линий)
resources/js/vueapp/     # Vue 3 фронтенд (компоненты, стора)
resources/views/         # Blade-представления
routes/                  # web- и api-маршруты
docker/                  # конфиги для Docker
docker-compose.yml       # локальные сервисы
```

---

## Дополнительно / See also

- [`AGENTS.md`](AGENTS.md) — операционное руководство для AI-агентов / operating guide for AI agents.
- [`TODO.md`](TODO.md) — список доработок и правок / list of improvements and fixes.
- [`psr_bugs_and_fixes.md`](psr_bugs_and_fixes.md) — история исправленных багов / history of fixed bugs.
