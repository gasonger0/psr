import dayjs, { Dayjs } from 'dayjs';

// Причины простоя
export const cancelReasons = [{
    label: 'Ремонт',
    value: 1
}, {
    label: 'Неправильное планирование',
    value: 2
}, {
    label: 'Нехватка рабочих',
    value: 3
}, {
    label: 'Недостаточная квалификация рабочих',
    value: 4
}, {
    label: 'Отсутствие сырья',
    value: 5
}, {
    label: 'Опоздание рабочих',
    value: 6
}, {
    label: 'Корректировка предыдущих операций',
    value: 7
}, {
    label: 'Иное',
    value: 8
}] as const;

// export type CancelReasonValue = typeof cancelReasons[number];

/**
 *  Должности ответственных
*/
export const positions = {
    1: 'Начальник смены',
    2: 'Мастер смены',
    3: 'Мастер варочного участка',
    4: 'Инженер',
    5: 'Наладчик'
};

// Столбцы справочника сотрудников
export const workerDictColumns = [{
    title: '',
    dataIndex: 'actions',
    width: '10%'
}, {
    title: 'ФИО',
    dataIndex: 'title',
    width: '60%'
}, {
    title: 'Компания',
    dataIndex: 'company',
}];

export const responsibleDictColumns = [{
    title: '',
    dataIndex: 'actions',
    width: '10%'
}, {
    title: 'ФИО',
    dataIndex: 'title',
    width: '40%'
}, {
    title: 'Доложность',
    dataIndex: 'position',
}];

// Слот графика / Обед / Линия / Изготовление
export type Slot = {
    started_at: Dayjs,
    ended_at: Dayjs,
    slot_id?: number
}

/**
 * Формат даты для MySQL
 * */
export const format = 'YYYY-MM-DD HH:mm:ss';

/**
 * Форматирование дат для передачи в БД
 * @param data 
 * @returns 
 */
export const prepareDate = (data: any) => {
    ['started_at', 'ended_at'].forEach((k: string) => {
        if (data[k] && data[k] instanceof dayjs.Dayjs) {
            data[k] = data[k].format(format);
        }
    });

    return data;
};

/**
 * Табы окна реестра продукции
 */
export const productsTabs = {
    1: "Варка",
    2: "Упаковка",
    3: "Справочная информация"
};

/**
 * Столбцы для таблиц в реестре продукции
 */
export const productsTableColumns = {
    1: [{
        title: 'Действия',
        dataIndex: 'actions',
        width: '5%'
    }, {
        title: 'Линия',
        dataIndex: 'line_id',
        // width: '65%'
    }, {
        title: 'Оборудование',
        dataIndex: 'hardware',
        width: '15%'
    }, {
        title: 'Кол-во сотрудников',
        dataIndex: 'people_count',
        width: '5%'
    }, {
        title: 'Кг в час',
        dataIndex: 'perfomance',
        width: '10%'
    }],
    2: [{
        title: 'Действия',
        dataIndex: 'actions',
        width: '5%'
    }, {
        title: 'Линия',
        dataIndex: 'line_id',
        // width: '80%'
    }, {
        title: 'Кол-во сотрудников',
        dataIndex: 'people_count',
        width: '5%'
    }, {
        title: 'Кг в час',
        dataIndex: 'perfomance',
        width: '10%'
    }],
    3: [
        { title: 'Штук в Ящике:', dataIndex: 'amount2parts', addon: '' },
        { title: 'Штуки в Кг:', dataIndex: 'parts2kg', addon: 'Шт ×' },
        { title: 'Кг в Варки:', dataIndex: 'kg2boil', addon: 'Кг ×' },
        { title: 'Телеги:', dataIndex: 'cars', addon: 'Варка ×' },
        { title: 'Поддоны:', dataIndex: 'cars2plates', addon: '(Варка - Варка(цел)) ×' },
        { title: 'Отображать, даже если нет в анализе', dataIndex: 'always_show', addon: false },
    ]
};

/**
 * Список оборудования
 */
export const hardwares = [
    { value: 1, label: 'ТОРНАДО' },
    { value: 2, label: 'Мондомикс' },
    { value: 3, label: 'Китайский Аэрос' }
]