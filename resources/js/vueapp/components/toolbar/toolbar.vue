<script setup lang="ts">
import { Switch, Dropdown, Button, MenuItem, Menu, Upload, UploadFile, DatePicker } from 'ant-design-vue';
import {
    UploadOutlined,
    FileExcelOutlined,
    BarChartOutlined,
    EditOutlined,
    DatabaseOutlined,
    CalendarOutlined,
    AppstoreOutlined,
    TeamOutlined,
    CheckCircleOutlined,
    TableOutlined
} from '@ant-design/icons-vue';
import dayjs, { Dayjs } from 'dayjs';
import { Ref, ref } from 'vue';
import { FileType } from 'ant-design-vue/es/upload/interface';
import { notify, postRequest } from '../../functions';
import { AxiosResponse } from 'axios';
import locale from 'ant-design-vue/es/date-picker/locale/ru_RU';

const boardMode: Ref<boolean> = ref(false);
const uploadedFile: Ref<UploadFile[] | undefined> = ref();
const date: Dayjs = dayjs(new Date(sessionStorage.getItem('date')!));
const isDay: Ref<boolean> = ref(Boolean(Number(sessionStorage.getItem('isDay')!)));
const showAccept: Ref<boolean> = ref(false);

const changeBoard = () => emit('change-board');
const processOrder = (file: FileType) => {
    let fd = new FormData();
    fd.append('file', file);
    notify('info', "Подождите, идёт обработка файла...");

    postRequest('/api/table/load_order', fd,
        (r: AxiosResponse) => {
            let data = JSON.parse(r.data);
            if (data.uncategorized.length > 0) {
                notify('warning', 'Следующие строки не были обработаны: ' + data.uncategorized.join(', '));
            }
            if (data.amounts.length > 0) {
                // TODO добавить объёмы в продукцию
                notify('success', 'Данные обновлены');
            }
        }
    )
};
const updateSession = () => {
    postRequest('/api/update_session', {
        date: date.format('YYYY-MM-DD'),
        isDay: Number(isDay)
    }, (r: AxiosResponse) => {
        sessionStorage.setItem('date', date.format('YYYY-MM-DD'));
        sessionStorage.setItem('isDay', String(Number(isDay.value)));
        // TODO возможно, из-за этого полетит сессия
        window.location.reload();
    });
}
const props = defineProps({
    boils: {
        type: Number,
        required: false
    }
});
const emit = defineEmits([
    'change-board',
    'result-window',
    'graph-window',
    'plans-window',
    'products-window',
    'workers-window',
    'logs-window'
]);
</script>
<template>
    <div class="top-container">
        <section>
            <Switch checked-children="Продукция" un-checked-children="Работники" v-model:checked="boardMode"
                @change="changeBoard" />
            <Dropdown>
                <Button class="excel-button">
                    <FileExcelOutlined />
                    Загрузить
                </Button>
                <template #overlay>
                    <Menu>
                        <MenuItem>
                        <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processOrder(ev)"
                            :showUploadList="false">
                            <Button type="primary" class="excel-button">
                                <UploadOutlined />
                                Анализ заказов
                            </Button>
                        </Upload>
                        </MenuItem>
                    </Menu>
                </template>
            </Dropdown>
            <Button type="default" @click="$emit('result-window')">
                <BarChartOutlined />
                Отчёт
            </Button>
            <Button type="primary" @click="$emit('graph-window')">
                <EditOutlined />
                Редактировать график
            </Button>
            <Dropdown>
                <Button type="primary">
                    <DatabaseOutlined />
                    Реестры
                </Button>
                <template #overlay>
                    <Menu>
                        <MenuItem>
                        <Button type="primary" @click="$emit('plans-window')">
                            <CalendarOutlined />
                            Реестр планов
                        </Button>
                        </MenuItem>
                        <MenuItem>
                        <Button type="primary" @click="$emit('products-window')">
                            <AppstoreOutlined />
                            Реестр продукции
                        </Button>
                        </MenuItem>
                        <MenuItem>
                        <Button type="primary" @click="$emit('workers-window')">
                            <TeamOutlined />
                            Реестр работников
                        </Button>
                        </MenuItem>
                    </Menu>
                </template>
            </Dropdown>
            <Button type="dashed" @click="$emit('logs-window')">
                <TableOutlined />
                Журнал
            </Button>
            <div v-show="boils">
                <b>Всего варок: {{ boils }}</b>
            </div>
        </section>
        <section>
            <DatePicker v-model:value="date" format="DD.MM.YYYY" mode="date" @change="showAccept = true"
                :locale="locale" />
            <Switch v-model:checked="isDay" @change="showAccept = true" checkedChildren="День"
                unCheckedChildren="Ночь" />
            <img src="./../../logo.png" class="logo" v-if="!showAccept">
            <CheckCircleOutlined class="accept-button" @click="updateSession" v-else />
        </section>
    </div>
</template>