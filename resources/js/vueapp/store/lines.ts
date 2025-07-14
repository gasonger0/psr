import { defineStore } from "pinia";
import { Slot } from "./dicts.ts";
import dayjs, { Dayjs } from 'dayjs'
import { ResponsibleInfo } from "./responsibles.ts";
import { reactive, Ref, ref } from "vue";
import { deleteRequest, getRequest, getTimeString, postRequest, putRequest } from "../functions.ts";
import { WorkerInfo } from "./workers.ts";
import { AxiosResponse } from "axios";
import { SelectValue } from "ant-design-vue/es/select/index";
import { now } from "moment";

// Интерфейсы

/**
 * Независимые параметры линий
 */
export type LineInfo = {
    line_id?: number,
    title: string,
    color?: string,
    type_id: number,
    count_current?: number, // TODO локальное свойство, нет аналога в базе
    line_extra_id?: number,
    workers_count: number,
    work_time: Slot,
    down_from?: Dayjs,
    cancel_reason?: number,          // TODO придумать, как привести к значениям только из справочника
    master?: number,
    engineer?: number,
    prep_time: number,
    after_time: number,
    extra_title?: string,
    detector: Detector,
    date: Dayjs,
    isDay: boolean,
    edit: boolean
};

type Detector = {
    line_extra_id?: number, //TODO ХЗ, надо ли оно тут вообще
    has_detector: boolean,
    detector_start?: Dayjs,
    detector_end?: Dayjs
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
        await putRequest('/api/lines/update', unserialize(line));
    }

    async function _delete(line: LineInfo) {
        await deleteRequest('/api/lines/delete', { line_id: line.line_id });
        splice(line.line_id!);
    }

    async function _sendStop(line: LineInfo, reason?: number | undefined): Promise<void> {
        // TODO проверить, нужно отправлять текст причины
        await putRequest('/api/lines/down', {
            line_id: line.line_id!,
            reason: reason ? reason : ''
        }, (response: AxiosResponse) => {
            if (reason) {
                line.down_from = dayjs();
            } else {
                line.down_from = undefined;
            }
        });
    }

    function splice(id: number): void {
        lines.value = lines.value.filter((n: LineInfo) => n.line_id != id);
        return;
    };

    function getById(line_id: number): LineInfo|undefined {
        return lines.value.find((el: LineInfo) => el.line_id == line_id);
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
        lines.value.push({
            edit: true,
            work_time: {
                started_at: dayjs(),
                ended_at: dayjs(),
            },
            title: 'Новая линия',
            workers_count: 0,
            type_id: 1,
            prep_time: 0,
            after_time: 0,
            detector: {
                has_detector: false
            } as Detector,
            date: dayjs(sessionStorage.getItem('date'), 'y-m-d'),
            isDay: Boolean(Number(sessionStorage.getItem('isDay')))
        } as LineInfo);

    }

    function serialize(line) {
        line.edit = false;
        line.color = ref(line.color);
        line.showDelete = ref(false);
        line.date = dayjs(line.date);
        line.work_time = ref({
            started_at: dayjs(line.started_at, 'HH:mm:ss'),
            ended_at: dayjs(line.ended_at, 'HH:mm:ss')
        });
        line.detector = ref({
            has_detector: line.has_detector,
            detector_start: line.detector_start,
            detector_end: line.detector_end
        });
        delete line.started_at, line.ended_at, line.has_detector, line.detector_start, line.detector_end;
        return line;
    }

    function unserialize(line: LineInfo) {
        const item = JSON.parse(JSON.stringify(line));
        item.started_at = line.work_time.started_at.format('HH:mm:ss');
        item.ended_at = line.work_time.ended_at.format('HH:mm:ss');
        item.detector_start = line.detector.detector_start?.format('HH:mm:ss');
        item.detector_end = line.detector.detector_end?.format('HH:mm:ss');
        item.date = line.date.format('YYYY-MM-DD');
        delete item.detector, item.work_time;
        return item;
    }
    return { lines, _load, _create, _update, _delete, _sendStop, getIfDone, add, splice, getById }
})
