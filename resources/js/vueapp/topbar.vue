<script setup>
import { ref } from 'vue';
import { Upload, Button, Switch } from 'ant-design-vue';
import { BarChartOutlined, UploadOutlined, EditOutlined, TeamOutlined, AppstoreOutlined } from '@ant-design/icons-vue';
import axios from 'axios';
</script>
<script>
export default {
    data() {
        return {
            uploadedFile: ref(null),
            date: (new Date()).toLocaleDateString(),
            boardMode: ref(false)
        }
    },
    methods: {
        processXlsx(file) {
            console.log(file);
            let fd = new FormData();
            fd.append('file', file);
            
            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");

            axios.post('/api/load_xlsx', fd).then((response) => {
                if (response) {
                    console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            }).catch((err) => {
                console.log(err);
                this.$emit('notify', 'warning', err);
            })
            console.log(file);
            return false;
        },
        processOrder(file) {
            let fd = new FormData();
            fd.append('file', file);
            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");

            axios.post('/api/load_order', fd).then((response) => {
                if (response) {
                    console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            }).catch((err) => {
                console.log(err);
                this.$emit('notify', 'warning', err);
            });
            return false;
        },
        changeBoard(prod) {
            console.log(prod);
            this.$emit('change-board');
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
            <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processXlsx(ev)" :showUploadList=false>
                <Button type="primary" style="background-color: green;">
                    <UploadOutlined />
                    Новый график
                </Button>
            </Upload>
            <Upload v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processOrder(ev)" :showUploadList="false">
                <Button type="primary" style="background-color: green;">
                    <UploadOutlined />
                    Выгрузить остатки
                </Button>
            </Upload>
            <Button type="default" @click="$emit('showResult')">
                <BarChartOutlined />
                Отчёт
            </Button>
            <Button type="primary" @click="$emit('showGraph')">
                <EditOutlined />
                Редактировать график
            </Button>
            <Button type="primary" @click="$emit('showProductsDict')">
                <AppstoreOutlined />
                Реестр продукции
            </Button>
            <Button type="dashed" @click="$emit('showLogs')">
                <TeamOutlined />
                Журнал
            </Button>
        </div>
        <div>
            <span style="height:fit-content;font-size: 18px;font-weight: 600;">{{ date }}</span>
            <img src="./logo.png" alt="" style="height:32px;">
        </div>
    </div>
</template>