<script setup lang="ts">
import { computed } from 'vue';
import { LineInfo, useLinesStore } from '../../store/lines';
import { cancelReasons } from '../../store/dicts';
import { useResponsiblesStore } from '../../store/responsibles';
import { DefaultOptionType } from 'ant-design-vue/es/select';
import { Card, Input, Switch, Tooltip, Popconfirm, Select, SelectOption, TimePicker, RadioGroup, RadioButton, Checkbox } from 'ant-design-vue';
import { InfoCircleOutlined, ForwardOutlined, StopOutlined } from '@ant-design/icons-vue';
import { ColorPicker } from 'vue-color-kit';
const props = defineProps({
    data: {
        type: Object as () => LineInfo,
        required: true
    }
});

const linesStore = useLinesStore();
const responsiblesStore = useResponsiblesStore();
const saveLine = (c: boolean, line: LineInfo) => {
    if (c) {
        return
    }
    if (line.line_id) {
        linesStore._update(line);
    } else {
        linesStore._create(line);
    }
}
const sendStop = (line: LineInfo, reason?: DefaultOptionType) => {
    linesStore._sendStop(line, reason ? reason.label : undefined);
}
const getLineWorkerLimit = (line: LineInfo) => {
    return line.count_current! < line.workers_count ? 'color:#ff4d4f;' : '';
}
const formatLineResponsible = (line: LineInfo) => {
    let arr: string[] = [];
    if (line.master) {
        let f = responsiblesStore.getById(line.master!);
        if (f) {
            let n = f.title.split(' ');
            arr.push(n[0] + ' ' + n[1][0] + '.' + ', ' + f.position);
        }
    }

    if (line.engineer) {
        let f = responsiblesStore.getById(line.master!);
        if (f) {
            let n = f.title.split(' ');
            arr.push(n[0] + ' ' + n[1][0] + '.' + ', ' + f.position);
        }
    }
    return arr.join('\n');
}

const lineHead = computed(() => {
   return { 
    'background-color': (
        props.data.color ? 
        props.data.color : 
        '#1677ff'
    )}; 
});
const getStartStop = computed(() => {
    return props.data.down_from ? 'Возобновить работу' : 'Остановить работу';
})

// TODO стили и вычислялки
</script>
<template>
    <Card :bordered="false" class="head" :headStyle="lineHead">
        <template #title>
            <div class="line_title" :data-id="data.line_id" v-show="!data.edit">
                <b>{{ data.title }}</b>
                <br>
                <span>{{data.extra_title }}</span>
            </div>
            <Input v-show="data.edit" class="line_title line-input" v-model:value="data.title" placeholder="Наименование"/>
            <Input v-show="data.edit" class="line_title line-input" v-model:value="data.extra_title" placeholder="Производительность" />
            <div class="line-card_section">
                <Switch v-model:checked="data.edit" checked-children="Редактирование" un-checked-children="Просмотр"
                    class="title-switch" @change="(c, e) => saveLine(c as boolean, data)" />
                <Tooltip v-show="data.edit">
                    <template #title>
                        <ColorPicker theme="light" :color="data.color" @changeColor="(ev) => { data.color = ev.hex; }" />
                    </template>
                    <section :style="'background-color:' + data.color" v-show="data.edit"></section>
                </Tooltip>
                <div>
                    <Tooltip v-if="data.cancel_reason != null && !data.edit"
                        :title="'Время работы было изменено по причине: ' + cancelReasons.find((el) => el.value == data.cancel_reason)!.label">
                        <InfoCircleOutlined id="info-icon"/>
                    </Tooltip>
                    <Tooltip :title="getStartStop">
                        <ForwardOutlined @click="sendStop(data)" v-if="data.down_from" id="forward-icon"/>
                        <Popconfirm v-else :showCancel="false" id="popover" placement="right">
                            <template #title>
                                <Select placeholder="Причина остановки" class="cancel-select"
                                    @change="(value, option) => sendStop(data, option)">
                                    <SelectOption v-for="i in cancelReasons" v-model:value="i.value">
                                        <Tooltip :title="i.label">
                                            {{ i.label }}
                                        </Tooltip>
                                    </SelectOption>
                                </Select>
                            </template>
                            <StopOutlined id="stop-icon" />
                        </Popconfirm>
                    </Tooltip>
                </div>
            </div>
        </template>
        <template v-if="data.edit">
            <div style="width:100%; max-width:400px;">
                <span style="display: flex; justify-content: space-between; margin-bottom:10px;align-items: center;">
                    <span style="height:fit-content;">Необходимо:&nbsp;&nbsp;</span>
                    <Input v-model:value="data.workers_count" type="number" placeholder="10 человек" />
                </span>
                <span>Время работы:</span><br />
                <div style="display: flex; justify-content: space-between;">
                    <TimePicker v-model:value="data.work_time.started_at" format="HH:mm" :showTime="true"
                        :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                    <TimePicker v-model:value="data.work_time.ended_at" format="HH:mm" :showTime="true"
                        :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                </div>
                <Select v-model:value="data.cancel_reason" placeholder="Причина переноса старта"
                    style="margin-top: 10px; width: 100%;">
                    <SelectOption v-for="i in cancelReasons" v-model:value="i.value">
                        {{ i.label }}
                    </SelectOption>
                </Select>
                <span>Подготовительное время(мин):</span><Input v-model:value="data.prep_time" placeholder="0" />
                <span>Заключительное время(мин):</span><Input v-model:value="data.after_time" placeholder="0" />
                <br>
                <br>
                <RadioGroup v-model:value="data.type_id">
                    <RadioButton value="1">Варка</RadioButton>
                    <RadioButton value="2">Упаковка</RadioButton>
                </RadioGroup>
                <span>Ответственные:</span>
                <Select v-model:value="data.master" style="width:100%;">
                    <SelectOption v-for="i in responsiblesStore.responsibles" v-model:value="i.responsible_id">
                        {{ i.title }}
                    </SelectOption>
                </Select>
                <Select v-model:value="data.engineer" style="width:100%;margin-top:10px;">
                    <SelectOption v-for="i in responsiblesStore.responsibles" v-model:value="i.responsible_id">
                        {{ i.title }}
                    </SelectOption>
                </Select>
                <Checkbox v-model:checked="data.detector.has_detector" style="margin-top:10px;">
                    Установить металодетектор
                </Checkbox>
                <div v-if="data.detector.has_detector">
                    <div style="display: flex; justify-content: space-between;">
                        <TimePicker v-model:value="data.detector.detector_start" format="HH:mm" :showTime="true"
                            :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                        <TimePicker v-model:value="data.detector.detector_end" format="HH:mm" :showTime="true"
                            :allowClear="true" type="time" :showDate="false" style="width:47%;" />
                    </div>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="line_sub-title">
                <span :style="getLineWorkerLimit(data)">
                    Необходимо работников: {{ data.workers_count ?? 'без ограничений' }}</span>
                <br>
                <span>Всего работников на линии: {{ data.count_current ? data.count_current : '0' }}</span>
                <br>
                <span v-show="data.engineer || data.master">
                    Ответственные:
                    <br />
                    {{ formatLineResponsible(data) }}
                </span>
            </div>
        </template>
    </Card>
</template>