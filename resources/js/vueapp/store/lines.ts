import { defineStore } from "pinia";
import { format, Slot } from "@stores/dicts";
import * as dayjs from 'dayjs'
import { ResponsibleInfo } from "@stores/responsibles";
import { reactive, Ref, ref } from "vue";
import { deleteRequest, getRequest, getTimeString, postRequest, putRequest } from "@/functions";
import { WorkerInfo } from "@stores/workers";
import { AxiosResponse } from "axios";
import { SelectValue } from "ant-design-vue/es/select/index";
import { now } from "moment";
import { useLogsStore } from "./logs";

// Интерфейсы

/**
 * Независимые параметры линий
 */
export type LineInfo = {
    line_id?: number,
    title: string,
    color?: string,
    type_id: number,
    count_current?: number, 
    line_extra_id?: number,
    workers_count: number,
    work_time: Slot,
    down_from?: dayjs.Dayjs,
    cancel_reason?: number, 
    master?: number,
    engineer?: number,
    prep_time: number,
    after_time: number,
    extra_title?: string,
    detector: Detector,
    date: dayjs.Dayjs,
    isDay: boolean,
    edit: boolean,
    has_plans?: boolean,
    version: number
};

type Detector = {
    line_extra_id?: number, //TODO ХЗ, надо ли оно тут вообще
    has_detector: boolean,
    detector_start?: dayjs.Dayjs,
    detector_end?: dayjs.Dayjs
};

export const useLinesStore = defineStore('lines', () => {
    // TODO написать доку
    const lines = ref<LineInfo[]>([]);

    async function _load(): Promise<void> {
        lines.value = (await getRequest('/api/lines/get')).map(el => {
            return serialize(el);
        });
    }

    async function _create(line: LineInfo) {
        await postRequest('/api/lines/create', unserialize(line), (response: AxiosResponse) => {
            let data = response.data;
            if (data.line_id) {
                line.line_id = data.line_id;
                line.line_extra_id = data.line_extra_id;
            }
        });
    }
    async function _update(line: LineInfo) {
        await putRequest('/api/lines/update', unserialize(line), (r: AxiosResponse) => {
            if (r.data.log_id) {
                useLogsStore().logs.push(r.data);
            }
        });
    }

    async function _delete(line: LineInfo) {
        await deleteRequest('/api/lines/delete', { line_id: line.line_id });
        splice(line.line_id!);
    }

    async function _sendStop(line: LineInfo, reason?: number | undefined): Promise<void> {
        await putRequest('/api/lines/down', {
            line_id: line.line_id!,
            reason: reason ? reason : ''
        }, (response: AxiosResponse) => {
            if (reason) {
                line.down_from = dayjs.default();
            } else {
                line.down_from = undefined;
            }
        });
    }

    function splice(id: number): void {
        lines.value = lines.value.filter((n) => n.line_id != id);
        return;
    };

    function getByID(line_id: number) {
        return lines.value.find((el) => el.line_id == line_id);
    }

    function getIfDone(line: LineInfo): boolean {
        let timeString = getTimeString();
        if (line.work_time.started_at < timeString
            &&
            timeString < line.work_time.ended_at
        ) {
            return false;
        }
        return true;
    }

    function add() {
        let l = {
            edit: true,
            work_time: {
                started_at: dayjs.default(),
                ended_at: dayjs.default(),
            },
            title: 'Новая линия',
            workers_count: 0,
            type_id: 1,
            prep_time: 0,
            after_time: 0,
            detector: {
                has_detector: false
            } as Detector,
            version: 1,
            date: dayjs.default(sessionStorage.getItem('date'), 'y-m-d'),
            isDay: Boolean(Number(sessionStorage.getItem('isDay')))
        } as LineInfo;
        lines.value.push(l);
    }

    function serialize(line): LineInfo {
        line.edit = false;
        line.color = ref(line.color);
        line.showDelete = ref(false);
        line.date = dayjs.default(line.date);
        line.work_time = ref({
            started_at: dayjs.default(line.started_at),
            ended_at: dayjs.default(line.ended_at)
        });
        line.detector = ref({
            has_detector: line.has_detector,
            detector_start: line.detector_start,
            detector_end: line.detector_end
        });
        line.version = 1;
        delete line.started_at, line.ended_at, line.has_detector, line.detector_start, line.detector_end;
        return line as LineInfo;
    }

    function unserialize(line: LineInfo) {
        const item = JSON.parse(JSON.stringify(line));
        item.started_at = line.work_time.started_at.format(format);
        item.ended_at = line.work_time.ended_at.format(format);
        item.detector_start = line.detector.detector_start?.format(format);
        item.detector_end = line.detector.detector_end?.format(format);
        item.date = line.date.format('YYYY-MM-DD');
        delete item.detector, item.work_time, item.version;
        return item;
    }

    const updateVersion = (lineId: number) => {
        const line = lines.value.find(l => l.line_id === lineId);
        if (line) {
            line.version = (line.version || 0) + 1; // Инкрементим версию
        }
        console.log(line.version);
    };
    return { lines, _load, _create, _update, _delete, _sendStop, getIfDone, add, splice, getByID, updateVersion }
})
