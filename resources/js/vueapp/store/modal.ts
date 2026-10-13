import { defineStore } from "pinia";
import { Ref, ref } from "vue";
import { ProductPlan, usePlansStore } from "./productsPlans";
import { useProductsSlotsStore } from "./productsSlots";
import { useProductsStore } from "./products";

/**
 * Менеджер окон
 */

type windowType = 'production'|'workers'|'graph'|'logs'|'plan'|'products'|'result';

type Visibility = { [key in windowType]: Ref<boolean, boolean> }

export const useModalsStore = defineStore('modals', () => {
    const visibility: Visibility = {
        workers: ref(false),
        products: ref(false),
        production: ref(false),
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
    const open = (modal: windowType) => {
        visibility[modal].value = true;
    }

    /**
     * Закрывает окно
     * @param modal Имя окна
     */
    const close = (modal: windowType) => {
        visibility[modal].value = false;
    } 

    const setLineRef = (lineId: number) => {
        linesRef.value[lineId] = 1;
    };

    const getBoils = () => {
        let ammount: number = 0;
        for (let i in boils.value) {
            ammount += Number(boils.value[i]);
        }
        if (!ammount || Number(ammount) == 0) {
            return 0;
        } 
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