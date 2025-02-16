<script setup>
import { ref } from 'vue';
import { Upload, Button, Switch, Dropdown, Menu, MenuItem, DatePicker } from 'ant-design-vue';
import { BarChartOutlined, UploadOutlined, EditOutlined, TeamOutlined, AppstoreOutlined, DatabaseOutlined, FileExcelOutlined, TableOutlined, CalendarOutlined } from '@ant-design/icons-vue';
import dayjs from 'dayjs';
import locale from 'ant-design-vue/es/date-picker/locale/ru_RU';
import axios from 'axios';
</script>
<script>
export default {
    props: {
        boils: {
            type: Number
        },
        boardMode: {
            type: Boolean
        }
    },
    data() {
        return {
            uploadedFile: ref(null),
            date: dayjs(new Date(sessionStorage.getItem('date'))),
            boardMode: this.$props.boardMode
        }
    },
    methods: {
        processXlsx(file) {
            // console.log(file);
            let fd = new FormData();
            fd.append('file', file);

            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");

            axios.post('/api/load_xlsx', fd).then((response) => {
                if (response) {
                    // console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            }).catch((err) => {
                // console.log(err);
                this.$emit('notify', 'warning', err);
            })
            // console.log(file);
            return false;
        },
        processOrder(file) {
            let fd = new FormData();
            fd.append('file', file);
            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");

            axios.post('/api/load_order', fd).then((response) => {
                if (response) {
                    // console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            }).catch((err) => {
                // console.log(err);
                this.$emit('notify', 'warning', err);
            });
            return false;
        },
        processDefaults(file) {
            let fd = new FormData();
            fd.append('file', file);
            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");

            axios.post('/api/load_defaults', fd).then((response) => {
                if (response) {
                    // console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            }).catch((err) => {
                // console.log(err);
                this.$emit('notify', 'warning', err);
            });
            return false;
        },
        processFormulas(file) {
            let fd = new FormData();
            fd.append('file', file);
            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");

            axios.post('/api/load_formulas', fd).then((response) => {
                if (response) {
                    // console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            }).catch((err) => {
                // console.log(err);
                this.$emit('notify', 'warning', err);
            });
            return false;
        },
        changeBoard(prod) {
            // console.log(prod);
            this.$emit('change-board');
        },
        updatesession(obj, datestring) {
            let date = dayjs(obj).format('YYYY-MM-DD');
            axios.post('/api/update_session', {
                date: date
            }).then(response => {
                if (response) {
                    sessionStorage.setItem('date', date);
                    window.location.reload();      
                }
            })
        }
    },
    mounted() {
    }
}
</script>
<template>
    <div class="top-container">
        <div>
            <Switch checked-children="Продукция" un-checked-children="Работники" v-model:checked="boardMode"
                @change="changeBoard" />

            <Dropdown>
                <Button style="background-color: green; color:white;">
                    <FileExcelOutlined />
                    Загрузить
                </Button>
                <template #overlay>
                    <Menu>
                        <MenuItem>
                        <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processXlsx(ev)"
                            :showUploadList=false>
                            <Button type="primary" style="background-color: green;">
                                <UploadOutlined />
                                Новый график
                            </Button>
                        </Upload>
                        </MenuItem>
                        <MenuItem>
                            <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processOrder(ev)"
                                :showUploadList="false">
                                <Button type="primary" style="background-color: green;">
                                    <UploadOutlined />
                                    Анализ заказов
                                </Button>
                            </Upload>
                        </MenuItem>
                        <MenuItem>
                            <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processDefaults(ev)"
                                :showUploadList="false"> 
                                <Button type="primary" style="background-color: green;">
                                    <UploadOutlined />
                                    Нормы планирования
                                </Button>
                            </Upload>
                        </MenuItem>
                        <MenuItem>
                            <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processFormulas(ev)"
                                :showUploadList="false">
                                <Button type="primary" style="background-color: green;">
                                    <UploadOutlined/>
                                    Бланк на варку
                                </Button>
                            </Upload>
                        </MenuItem>
                    </Menu>
                </template>
            </Dropdown>
            <Button type="default" @click="$emit('showResult')">
                <BarChartOutlined />
                Отчёт
            </Button>
            <Button type="primary" @click="$emit('showGraph')">
                <EditOutlined />
                Редактировать график
            </Button>
            <Dropdown>
                <Button type="primary">
                    <DatabaseOutlined/>
                    Реестры
                </Button>
                <template #overlay>
                    <Menu>
                        <MenuItem>
                            <Button type="primary" @click="$emit('showPlansDict')">
                                <CalendarOutlined/>
                                Реестр планов
                            </Button>
                        </MenuItem>
                        <MenuItem>
                            <Button type="primary" @click="$emit('showProductsDict')">
                                <AppstoreOutlined />
                                Реестр продукции
                            </Button>
                        </MenuItem>
                        <MenuItem>
                            <Button type="primary" @click="$emit('showWorkersDict')">
                                <TeamOutlined/>
                                Реестр работников
                            </Button>
                        </MenuItem>
                    </Menu>
                </template>
            </Dropdown>
            <Button type="dashed" @click="$emit('showLogs')">
                <TableOutlined />
                Журнал
            </Button>
            <div v-show="$props.boils">
                <b>Всего варок: {{ $props.boils }}</b>
            </div>
        </div>
        <div>
            <DatePicker v-model:value="date" format="DD.MM.YYYY" mode="date" @change="updatesession" :locale="locale" />
            <!-- <span style="height:fit-content;font-size: 18px;font-weight: 600;">{{ date }}</span> -->
            <img src="./logo.png" alt="" style="height:32px;">
        </div>
    </div>
</template>