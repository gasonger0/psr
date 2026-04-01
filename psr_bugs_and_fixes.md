# PSR Project - Все исправленные баги (Полная документация)

## 📋 Общая информация
- **Проект:** PSR (Production Schedule & Resource management)
- **Структура:** Laravel + Vue 3 + TypeScript
- **Дата обновления:** 2026-04-01

---

## 🐛 БАГ #1: Упаковка встает неправильно при переносе позиции на варке

### Файлы:
- Backend: `app/Http/Controllers/ProductsPlanController.php`
- Методы: `adjustPacksForGlazing()`, `change()`, `updateBoxPlans()`

### Проблемы:
1. **Неправильный синтаксис `max()`**
   - Было: `->max(fn($p) => $p->ended_at)->ended_at` ❌
   - Возвращает значение вместо объекта, приводит к ошибке

2. **Упаковка сдвигалась только по `ended_at`**
   - Сохранялся старый `started_at`
   - Результат: странный интервал (например, 19:00-22:17 когда глазировка 20:51-22:17)

3. **При смене порядка глазировка не проверялась**
   - В методе `change()` упаковка пересчитывалась, но без проверки относительно глазировки

### Исправления:
```php
// ✅ Правильно получить объект с максимальным концом
$latestGlazPlan = $glazPlans->max(fn($p) => Carbon::parse($p->ended_at));
$glaz_end = Carbon::parse($latestGlazPlan->ended_at);

// ✅ Сдвигать ВЕСЬ блок упаковки, сохраняя её длительность
$duration = $packEnd->diffInMinutes($packStart);
$p->update([
    'started_at' => $glaz_end->copy(),
    'ended_at' => $glaz_end->copy()->addMinutes($duration)
]);

// ✅ В методе change() добавить проверку глазировки
$this->adjustPacksForGlazing($request, $prod, $packsCheck, $order);
```

---

## 🐛 БАГ #2: Объём изготовления не обновлялся после удаления позиции

### Файл:
- Frontend: `resources/js/vueapp/components/boards/plans/productCard.vue`

### Проблема:
- `activeSlots` вычислялось один раз при монтировании
- При удалении плана массив **не пересчитывался**
- Зелёная подсветка и объём оставались старыми

### Решение:
```typescript
// ❌ БЫЛО - вычисляется один раз
const activeSlots: Array<number> = usePlansStore().getActiveSlots(props.product.product_id);

// ✅ СТАЛО - вычисляемое свойство, реактивное
const activeSlots = computed(() => {
    return plansStore.getActiveSlots(props.product.product_id);
});

// Обновить использование
const amountFact = (stage_id: number) => {
    return plansStore.getAmountFact(activeSlots.value, stage_id);  // .value для доступа
}
```

---

## 🐛 БАГ #3: Новая позиция встала на неправильное время

### Файл:
- Frontend: `resources/js/vueapp/components/boards/plans/board.vue`
- Метод: `dragend` при `isNewPlan.value === true`

### Проблема:
- Код использовал **visual позицию в DOM** вместо **фактического порядка по времени**
- Планы отсортированы по `started_at`, но новая позиция считала индекс в DOM

### Пример:
- DOM: `[новая карточка] → [План 20:16-5:56]`
- Код: `plans[position - 1]` → `plans[-1]` → undefined
- Результат: новая позиция встала на 19:24 ❌

### Решение:
```typescript
// ✅ Следовать ФИЗИЧЕСКОМУ порядку в DOM, не временному

// 1. Ищем предыдущий элемент в DOM
let prevSibling = target.previousElementSibling as HTMLElement | null;

if (prevSibling && prevSibling.getAttribute('data-id')) {
    // Если есть - его конец = наше начало
    let prevPlanId = Number(prevSibling.getAttribute('data-id'));
    let prevPlan = plansStore.getById(prevPlanId);
    if (prevPlan) {
        started_at = prevPlan.ended_at;
    }
} else if (plans.length > 0) {
    // Если первая позиция - начинаем ПОСЛЕ всех существующих
    let lastProd = plans.reduce((latest, current) => {
        return current.ended_at.isAfter(latest.ended_at) ? current : latest;
    }, plans[0]);
    started_at = lastProd.ended_at;
}
```

---

## 🐛 БАГ #4: Перетаскивание на одну линию результирует в продукцию на другой

### Файл:
- Frontend: `resources/js/vueapp/components/boards/plans/board.vue`

### Проблемы:

#### Проблема 4.1: dragend использовал исходный контейнер
```javascript
// ❌ БЫЛО
let draggable = document.querySelectorAll('.line_items.products');
draggable.forEach(line => {
    line.addEventListener(`dragend`, (ev: Event) => {
        let curLine = linesStore.getByID(Number(
            line.parentElement!.dataset.id  // ← Всегда исходный контейнер!
        ));
```

#### Проблема 4.2: dragover вставлял в исходный контейнер
```javascript
// ❌ БЫЛО
line.addEventListener('dragover', (ev) => {
    const targetLineContainer = (ev.target as Element).closest('.line_items.products');
    ...
    line.append(activeElement);  // ← В ИСХОДНЫЙ контейнер, не в целевой!
    line.insertBefore(activeElement, nextElement);
```

### Решения:

#### Решение 4.1:
```typescript
// ✅ СТАЛО - получить линию из текущей позиции элемента
let lineElement = target.closest('.line');
if (!lineElement) return;
let curLine = linesStore.getByID(Number(lineElement.getAttribute('data-id')));
```

#### Решение 4.2:
```typescript
// ✅ СТАЛО - использовать целевой контейнер из события
const targetLineContainer = (ev.target as Element).closest('.line_items.products');
if (!targetLineContainer) return;

// ... затем использовать targetLineContainer
targetLineContainer.append(activeElement as HTMLElement);
targetLineContainer.insertBefore(activeElement as HTMLElement, nextElement);
```

---

## 🔧 Дополнительные улучшения на фронте

### Вертикальная прокрутка при перетаскивании (dragScrollZones.vue)
- Создан новый компонент `resources/js/vueapp/components/common/dragScrollZones.vue`
- Невидимые зоны сверху/снизу (высота 100px) для автоматической прокрутки
- Активируются только во время drag событий
- Параметры: `speed="15"` (пиксели/итерация), `zoneHeight="100"` (высота зон)

**Использование в board.vue:**
```vue
<DragScrollZones :containerRef="linesContainer" :speed="15" :zoneHeight="100" />
```

---

## 📍 Ключевые коммиты/изменения

1. **ProductsPlanController.php** - 3 функции оптимизированы
2. **productCard.vue** - activeSlots сделан computed
3. **board.vue** - 3 критичных фикса в перетаскивании
4. **dragScrollZones.vue** - новый компонент для удобства

---

## ✅ Чек-лист для тестирования

- [ ] Новая позиция встает на правильное время при перетаскивании
- [ ] Упаковка встает после глазировки при переносе варки
- [ ] Объём изготовления обновляется при удалении позиции
- [ ] Перетаскивание между разными линиями работает корректно
- [ ] Вертикальная прокрутка работает при перетаскивании вверх/вниз

---

## 🚀 Как использовать эту информацию

1. При появлении похожего бага - ищи по ключевому слову в этом документе
2. Проверь указанные файлы и методы
3. Примени аналогичное исправление
4. Обнови этот документ, если нашел новое решение

**Последнее обновление:** 2026-04-01
