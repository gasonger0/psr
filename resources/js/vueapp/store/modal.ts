import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { ProductPlan, usePlansStore } from "./productsPlans";
import { useProductsSlotsStore } from "./productsSlots";
import { useProductsStore } from "./products";

/**
 * Менеджер окон
 */

export const useModalsStore = defineStore('modals', () => {
    const visibility: Object = {
        workers: ref(false),
        products: ref(false),
        result: ref(false),
        logs: ref(false),
        graph: ref(false),
        plan: ref(false)
    };
    const boils: Ref<any> = ref({});
    const linesRef: Ref<Array<number>> = ref([]);
    
    /**
     * Открывает модальное окно
     * @param modal Имя окна
     */
    const open = (modal: string) => {
        visibility[modal].value = true;
    }

    /**
     * Закрывает окно
     * @param modal Имя окна
     */
    const close = (modal: string) => {
        visibility[modal].value = false;
    } 

    const setLineRef = (lineId: number) => {
        linesRef.value[lineId] = 1;
    };

    const getBoils = () => {
        let ammount = 0;
        for (let i in boils.value) {
            ammount += boils.value[i];
        }
        console.log(boils);
        return ammount.toFixed(2);
    }

    return {
        open, 
        close, 
        visibility,
        boils,
        linesRef, 
        setLineRef,
        getBoils
    }
})