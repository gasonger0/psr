import { deleteRequest, getRequest, notify, postRequest, putRequest } from "@/functions";
import { AxiosError, AxiosResponse } from "axios";
import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { compileScript } from "vue/compiler-sfc";

export type CompanyInfo = {
    company_id?: number,
    title: string
};

export const useCompaniesStore = defineStore("companies", () => {
    const companies: Ref<CompanyInfo[]> = ref([]);

    async function _load(): Promise<void> {
        companies.value = await getRequest('/api/companies/get');
    }

    async function _create(rec: CompanyInfo): Promise<void> {
        return await postRequest('/api/companies/create',
            rec,
            (r: AxiosResponse) => {
                rec.company_id = r.data.company_id;
                return true;
            },
            (err: AxiosError) => {
                notify('warning', err.message);
                return false;
            }
        )
    }
    async function _update(rec: CompanyInfo): Promise<void> {
        return await putRequest('/api/companies/update', rec);
    }
    async function _delete(rec: CompanyInfo): Promise<void> {
        let res = await deleteRequest('/api/companies/delete', rec)
        if (res) {
            splice(rec.company_id);
        }
        return;
    }

    /*** LOCAL ***/
    function getByID(id: number) {
        return companies.value.find((el: CompanyInfo) => el.company_id == id);
    }
    function add() {
        companies.value.push({
            title: ''
        });
    }
    function splice(id: number) {
        companies.value = companies.value.filter((n: CompanyInfo) => n.company_id != id);
        return;
    }
    return {
        companies,
        _load,
        _create,
        _update,
        _delete,
        getByID,
        add
    }
})