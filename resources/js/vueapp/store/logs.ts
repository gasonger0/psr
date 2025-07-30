import * as dayjs from "dayjs";
import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { LineInfo } from "./lines";
import { getRequest } from "@/functions";

export type LogInfo = {
    started_at: string,
    ended_at: string,
    line: LineInfo,
    extra: string
}

export const useLogsStore = defineStore("logs", () => {
    const logs: Ref<LogInfo[]> = ref([]);

    async function _load(): Promise<void> {
        logs.value = (await getRequest('/api/logs/get')).map(el => {
            el.started_at = dayjs.default(el.started_at).format("H:m:s");
            el.ended_at = dayjs.default(el.ended_at).format("H:m:s");
            return el as LogInfo;
        });
    }

    return {
        logs,
        _load
    }
})