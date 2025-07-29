import { getRequest } from "@/functions";
import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { compileScript } from "vue/compiler-sfc";

export type CompanyInfo = {
    company_id?: number,
    title: string
};

export const useCompaniesStore = defineStore("companies", () => {
    const companies: Ref<CompanyInfo[]> = ref([]);
    // TODO crud
    async function _load(): Promise<void>{
        companies.value = await getRequest('/api/companies/get');
    }

    async function _create(){}
    async function _update(){}
    async function _delete(){}

    /*** LOCAL ***/
    function getByID(id: number) {
        return companies.value.find((el: CompanyInfo) => el.company_id == id);
    }
    return {
        companies, 
        _load,
        getByID
    }
})