<script setup>
import { ref } from 'vue';
import { Upload, Button } from 'ant-design-vue';
import { BarChartOutlined, UploadOutlined, EditOutlined, TeamOutlined } from '@ant-design/icons-vue';
import axios from 'axios';
</script>
<script>
export default {
    data() {
        return {
            uploadedFile: ref(null),
            date: (new Date()).toLocaleDateString() 
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
                this.$emit('notify', 'warning', res);
            })
            console.log(file);
            return false;
        },
    },
    mounted() {
    }
}
</script>
<template>
    <div class="top-container">
        <div>
            <Upload :v-model:file-list="uploadedFile" name="file" :before-upload="(ev) => processXlsx(ev)">
                <Button type="primary" style="background-color: green;">
                    <UploadOutlined />
                    Новый график
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