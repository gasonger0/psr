import { defineStore } from "pinia";
import { computed, ref } from "vue";
import { deleteRequest, getRequest, postRequest, putRequest } from "@/functions";

export type HardwareInfo = {
    hardware_id: number,
    title: string,
    full_title?: string | null,
    type: number
};

export type ReturnTypeInfo = {
    return_type_id: number,
    title: string,
    formula_type: 'fixed' | 'coef',
    fixed_value?: number | null,
    coef_z?: number | null,
    coef_s?: number | null,
    coef_k?: number | null
};

export type RegistryLine = {
    line_id: number,
    title: string,
    color?: string | null,
    type_id: number,
    return_type?: number | null,
    use_dating?: boolean,
    perfomance?: string | null,
    prep_time?: number | null,
    after_time?: number | null,
    workers_count?: number | null
};

export type SettingsInfo = {
    interval_boil: number,
    interval_pack: number,
    zm_perfomance: number,
    zm_perfomance2: number
};

export const useProductionStore = defineStore('production', () => {
    const hardwares = ref<HardwareInfo[]>([]);
    const returnTypes = ref<ReturnTypeInfo[]>([]);
    const registryLines = ref<RegistryLine[]>([]);
    const settings = ref<SettingsInfo>({
        interval_boil: 10,
        interval_pack: 15,
        zm_perfomance: 143.5,
        zm_perfomance2: 287
    });

    async function _load(): Promise<void> {
        const [hw, rt, st] = await Promise.all([
            getRequest('/api/production/hardwares/get'),
            getRequest('/api/production/return_types/get'),
            getRequest('/api/production/settings/get')
        ]);
        hardwares.value = hw;
        returnTypes.value = rt;
        settings.value = {
            interval_boil: Number(st.interval_boil ?? 10),
            interval_pack: Number(st.interval_pack ?? 15),
            zm_perfomance: Number(st.zm_perfomance ?? 143.5),
            zm_perfomance2: Number(st.zm_perfomance2 ?? 287)
        };
    }

    async function _loadRegistry(): Promise<void> {
        registryLines.value = await getRequest('/api/lines/registry');
    }

    const boilHardwares = computed(() => [
        { value: null, label: 'Нет' } as { value: number | null, label: string },
        ...hardwares.value.filter((h) => h.type === 1).map((h) => ({ value: h.hardware_id, label: h.title }))
    ]);

    const packHardwares = computed(() =>
        hardwares.value.filter((h) => h.type === 2).map((h) => ({
            value: h.hardware_id,
            label: h.title,
            title: h.full_title
        }))
    );

    const slotHardwareOptions = computed(() => [
        { value: null, label: 'Нет' } as { value: number | null, label: string },
        ...hardwares.value.map((h) => ({ value: h.hardware_id, label: h.title }))
    ]);

    const returnTypeOptions = computed(() =>
        returnTypes.value.map((r) => ({ value: r.return_type_id, label: r.title }))
    );

    function getByID(id: number | null): HardwareInfo | undefined {
        return hardwares.value.find((h) => h.hardware_id === id);
    }

    async function _createHardware(h: HardwareInfo): Promise<void> {
        const res = await postRequest('/api/production/hardwares/create', h);
        h.hardware_id = res.hardware_id;
    }

    async function _updateHardware(h: HardwareInfo): Promise<void> {
        await putRequest('/api/production/hardwares/update', h);
    }

    async function _deleteHardware(id: number): Promise<void> {
        await deleteRequest('/api/production/hardwares/delete', { hardware_id: id });
    }

    async function _createReturnType(r: ReturnTypeInfo): Promise<void> {
        const res = await postRequest('/api/production/return_types/create', r);
        r.return_type_id = res.return_type_id;
    }

    async function _updateReturnType(r: ReturnTypeInfo): Promise<void> {
        await putRequest('/api/production/return_types/update', r);
    }

    async function _deleteReturnType(id: number): Promise<void> {
        await deleteRequest('/api/production/return_types/delete', { return_type_id: id });
    }

    async function _saveSettings(): Promise<void> {
        await putRequest('/api/production/settings/update', { ...settings.value });
    }

    async function _bulkUpdateLines(lineIds: number[], fields: object): Promise<void> {
        await postRequest('/api/lines/bulk-update', { line_ids: lineIds, fields });
    }

    async function _deleteLine(lineId: number): Promise<void> {
        await deleteRequest('/api/lines/delete', { line_id: lineId });
    }

    return {
        hardwares, returnTypes, registryLines, settings,
        boilHardwares, packHardwares, slotHardwareOptions, returnTypeOptions,
        getByID,
        _load, _loadRegistry,
        _createHardware, _updateHardware, _deleteHardware,
        _createReturnType, _updateReturnType, _deleteReturnType,
        _saveSettings, _bulkUpdateLines, _deleteLine
    };
});
