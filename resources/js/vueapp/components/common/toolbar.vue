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
import * as dayjs from "dayjs";
import { Ref, ref } from 'vue';
import { FileType } from 'ant-design-vue/es/upload/interface';
import { notify, postRequest } from '../../functions';
import { AxiosResponse } from 'axios';
import locale from 'ant-design-vue/es/date-picker/locale/ru_RU';
import { useModalsStore } from '@stores/modal';
import { useProductsStore } from '@/store/products';

const boardMode: Ref<boolean> = ref(false);
const uploadedFile: Ref<UploadFile[] | undefined> = ref();
const date: Ref<dayjs.Dayjs> = ref(dayjs.default(sessionStorage.getItem('date'), 'YYYY-MM-DD'));
const isDay: Ref<boolean> = ref(Boolean(Number(sessionStorage.getItem('isDay')!)));
const showAccept: Ref<boolean> = ref(false);

const modalStore = useModalsStore();

const changeBoard = () => emit('change-board');
const processOrder = async (file: FileType) => {
    let fd = new FormData();
    fd.append('file', file);
    notify('info', "Подождите, идёт обработка файла...");

    await postRequest('/api/tables/load_order', fd,
        (r: AxiosResponse) => {
            console.log(r);
            let data = r.data;
            console.log(data, r);
            if (data.uncategorized.length > 0) {
                notify('warning', 'Следующие строки не были обработаны: ' + data.uncategorized.join(', '));
            }
            if (data.amounts.length > 0) {
                useProductsStore().fillOrders(data.amounts);
                notify('success', 'Данные обновлены');
            }
        }
    );

    return false;
};
const updateSession = () => {
    console.log(date);
    postRequest('/api/update_session', {
        date: date.value.format('YYYY-MM-DD'),
        isDay: Number(isDay.value)
    }, (r: AxiosResponse) => {
        console.log(r, date, isDay);
        sessionStorage.setItem('date', date.value.format('YYYY-MM-DD'));
        sessionStorage.setItem('isDay', String(Number(isDay.value)));
        window.location.reload();
    });
}
const openModal = (name: string) => {
    modalStore.open(name);
}

const emit = defineEmits([
    'change-board'
]);
</script>
<template>
    <div class="top-container">
        <section>
            <Switch checked-children="Продукция" un-checked-children="Работники" v-model:checked="boardMode"
                @change="changeBoard" />
            <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processOrder(ev)"
                :showUploadList="false">
                <Button type="primary" class="excel-button">
                    <FileExcelOutlined />
                    Загрузить заказы
                </Button>
            </Upload>
            <Button type="default" @click="openModal('result')">
                <BarChartOutlined />
                Отчёт
            </Button>
            <Button type="primary" @click="openModal('graph')">
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
                        <Button type="primary" @click="openModal('plans')">
                            <CalendarOutlined />
                            Реестр планов
                        </Button>
                        </MenuItem>
                        <MenuItem>
                        <Button type="primary" @click="openModal('products')">
                            <AppstoreOutlined />
                            Реестр продукции
                        </Button>
                        </MenuItem>
                        <MenuItem>
                        <Button type="primary" @click="openModal('workers')">
                            <TeamOutlined />
                            Реестр работников
                        </Button>
                        </MenuItem>
                    </Menu>
                </template>
            </Dropdown>
            <Button type="dashed" @click="openModal('logs')">
                <TableOutlined />
                Журнал
            </Button>
            <div>
                <b>Всего варок: {{ useModalsStore().getBoils() }}</b>
            </div>
        </section>
        <section>
            <DatePicker v-model:value="date" format="DD.MM.YYYY" mode="date" @open-change="showAccept = true;"
                :locale="locale" />
            <Switch v-model:checked="isDay" @change="showAccept = true" checkedChildren="День"
                unCheckedChildren="Ночь" />
            <img src="./../../logo.png" class="logo" v-if="!showAccept">
            <CheckCircleOutlined class="accept-button" @click="updateSession" v-else />
        </section>
    </div>
</template>