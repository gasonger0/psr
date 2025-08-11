import { defineStore } from "pinia";
import { Ref, ref } from "vue";

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
    const boils: Ref<number> = ref(0);
    
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

    return {
        open, 
        close, 
        visibility,
        boils
    }
})