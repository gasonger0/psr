<script setup lang="ts">
import { FileExcelOutlined } from '@ant-design/icons-vue';
import { Button, Card, Popconfirm, Switch, Divider } from 'ant-design-vue';
import { computed, onBeforeMount, onMounted, onUpdated, ref, Ref, TemplateRef, VNodeRef } from 'vue';
import { ProductInfo, useProductsStore } from '@stores/products';
import ProductCard from '@/components/boards/plans/productCard.vue'
import { LineInfo, useLinesStore } from '@/store/lines';
import LineForm from '@/components/common/lineForm.vue';
import PlanCard from './planCard.vue';
import { ProductPlan, usePlansStore } from '@/store/productsPlans';
import ScrollButtons from '@/components/common/scrollButtons.vue';
import { getNextElement, scrollToTop } from '@/functions';
import { ProductSlot, useProductsSlotsStore } from '@/store/productsSlots';
import { alwaysShowLines, format } from '@/store/dicts';
import * as dayjs from 'dayjs';
import PlanModal from '@modals/plan.vue';
import { useModalsStore } from '@/store/modal';

const productsStore = useProductsStore();
const linesStore = useLinesStore();
const plansStore = usePlansStore();
const slotsStore = useProductsSlotsStore();
const modal = useModalsStore();

const showList: Ref<boolean> = ref(false);
const hideEmpty: Ref<boolean> = ref(false);
const categorySwitch: Ref<boolean> = ref(false);
let linesContainer: Ref<HTMLElement | null> = ref();
let active: Ref<HTMLElement | null> = ref(null);
let isNewPlan: Ref<boolean> = ref(false);
const activePlan: Ref<ProductPlan | null> = ref();
const prodLine: Ref<PropertyKey> = ref(1);

const handleCardChange = (success: boolean) => {
    if (!success) {
        activePlan.value = null;
        if (isNewPlan) {
            plansStore.removeLast();
        }
    }
    prodLine.value = prodLine.value as number + 1;
}

const hideProducts = () => {
    productsStore.hide(Number(categorySwitch.value) + 1);
}
const clearPlan = () => {
    plansStore._clear();
}
const editPlan = (plan: ProductPlan) => {
    activePlan.value = plan;
    modal.open('plan');
}
const downloadPlan = () => {
    window.open('/api/tables/get_plans', '_blank');

}
// const setPlanRef = (el: any, product_id: number) => {
//     if (el) plansRef.value[product_id] = el;
// };
const prodListTitle = computed(() => {
    return showList.value ? 'Скрыть' : 'Показать список продукции';
});
const hideEmptyLinesTitle = computed(() => {
    return hideEmpty.value ? 'Показать пустые линии' : 'Скрыть пустые линии';
});
onUpdated(async () => {
    hideProducts();
    document.querySelector('.lines-container').scrollTo({ left: 0 })
    let draggable = document.querySelectorAll('.line_items.products');
    draggable.forEach(line => {
        line.addEventListener(`dragstart`, (ev: Event) => {
            console.log(ev, line);
            if (!ev) {
                return;
            }
            scrollToTop(linesContainer);

            let target = ev.target as HTMLElement;
            target.classList.add(`selected`);

            active.value = target;
            if (target.closest('.line').getAttribute('data-id') == "-1") {
                isNewPlan.value = true;
                document.querySelectorAll('.line').forEach(el => {
                    el.classList.add('hidden-hard');
                });

                document.querySelector('.line[data-id="-1"]').classList.remove('hidden-hard');


                let product = productsStore.getByID(Number(target.getAttribute('data-id')));
                if (product) {
                    // Прячем и показываем линии 
                    let slots = {
                        1: slotsStore.getByTypeAndProductID(product.product_id, 1),
                        2: slotsStore.getByTypeAndProductID(product.product_id, 2)
                    };
                    console.log(slots);
                    let activeSlots = plansStore.getActiveSlots(product.product_id);
                    // Если есть слоты варки, но не выставлены в план
                    let check1 = slots[1].filter(el => activeSlots.includes(el.product_slot_id));
                    if (check1.length == 0) {
                        for (let i in slots[1]) {
                            document.querySelector('.line[data-id="' + slots[1][i].line_id + '"]').classList.remove('hidden-hard');
                        }
                    }

                    // Если есть слоты варки или их нет и в план не выставлены
                    if (slots[1].length == 0 || check1.length) {
                        for (let i in slots[2]) {
                            document.querySelector('.line[data-id="' + slots[2][i].line_id + '"]').classList.remove('hidden-hard');
                        }
                        let match = slots[1].filter(el =>
                            alwaysShowLines.find(f => f == el.line_id)
                        );
                        for (let i in match) {
                            document.querySelector('.line[data-id="' + match[i].line_id + '"]').classList.remove('hidden-hard');
                        }
                    }
                }
            }
        })

        line.addEventListener(`dragend`, (ev: Event) => {
            // if (line.parentElement!.dataset.id == '-1') {
            //     return;
            // }
            let target = ev.target as HTMLElement;
            if (target.classList.contains('selected') && target == active.value) {
                target.classList.remove(`selected`);
                let childs = Array.from(target.parentNode.children);
                let curLine = linesStore.getByID(Number(
                    line.parentElement!.dataset.id
                ));

                document.querySelectorAll('.line').forEach(el => {
                    el.classList.remove('hidden-hard');
                });

                if (!curLine) {
                    return;
                }
                if (isNewPlan.value) {
                    let product = productsStore.getByID(Number(target.dataset.id)),
                        position = childs.indexOf(target),
                        slots = slotsStore.getByLineId(curLine.line_id),
                        plans = plansStore.getByLine(curLine.line_id),
                        lastProd = null,
                        started_at = dayjs.default();

                    if (plans.length > 0 && position > 0) {
                        lastProd = plans.reduce((latest, current) => {
                            return current.ended_at.isAfter(latest.ended_at) ? current : latest;
                        }, plans[0]);
                        started_at = dayjs.default(lastProd.ended_at, format);
                    } else if (curLine.work_time.started_at != null) {
                        started_at = curLine.work_time.started_at;
                        if (curLine.prep_time) {
                            started_at = started_at.add(curLine.prep_time, 'minute');
                        }
                    }

                    // Фильтруем слоты по продукции
                    slots = slots.filter((el: ProductSlot) => el.product_id == product.product_id);
                    activePlan.value = plansStore.add(
                        slots[0].product_slot_id,
                        started_at,
                        product.order ? product.order.amount : 0
                    )
                    modal.open("plan");

                } else {
                    let ids = childs.map(el => (el as HTMLElement).dataset.id);

                    let cards = [];
                    ids.forEach((el, k) => {
                        let pl = plansStore.plans.find(f => f.plan_product_id == Number(el));
                        cards.push(pl);
                    })

                    for (let i in cards) {
                        let timeDiff = cards[i].ended_at.diff(cards[i].started_at, 'minutes');
                        if (Number(i) == 0) {
                            cards[i].started_at = curLine.work_time.started_at.add(curLine.prep_time, 'minutes');
                        } else {
                            cards[i].started_at = cards[Number(i) - 1].ended_at;
                        }
                        cards[i].ended_at = cards[i].started_at.add(timeDiff, 'minutes');
                    }
                    console.log("Cards:", cards);
                    plansStore._change(cards);
                }
            }

            if (line.contains(target)) {
                line.removeChild(target);
            }
            isNewPlan.value = false;
            active.value = null;
            document.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
        });

        line.addEventListener('dragover', (ev) => {
            ev.preventDefault();
            const activeElement = document.querySelector('.selected');
            const currentElement = ev.target;
            const isMoveable = activeElement !== currentElement;

            if (!isMoveable) {
                return;
            }

            const nextElement = getNextElement(
                Number(
                    (ev.target as Element).getAttribute('clientY')
                ),
                currentElement as Element
            );
            // Проверяем, нужно ли менять элементы местами
            if (
                nextElement &&
                activeElement === nextElement.previousElementSibling ||
                activeElement === nextElement
            ) {
                // Если нет, выходим из функции, чтобы избежать лишних изменений в DOM
                return;
            }

            const lastElement = line.lastElementChild;
            if (nextElement == null) {
                line.append(activeElement);
            } else {
                if (nextElement.parentElement != line) {
                    line.append(activeElement);
                } else {
                    line.insertBefore(activeElement, nextElement);
                }
            }
        })
    });
    
});
</script>
<template>
    <section class="plans-toolbar">
        <Button type="dashed" @click="() => showList = !showList">
            {{ prodListTitle }}
        </Button>
        <Button type="dashed" @click="() => hideEmpty = !hideEmpty">
            {{ hideEmptyLinesTitle }}
        </Button>
        <Button type="primary" class="excel-button" @click="downloadPlan">
            <FileExcelOutlined />
            Скачать XLSX
        </Button>
        <Popconfirm title="Это действие удалит весь план продукции" okText="Очистить" cancelText="Отмена"
            @confirm="clearPlan">
            <Button type="primary">
                Очистить план
            </Button>
        </Popconfirm>
    </section>
    <section class="lines-container" ref="linesContainer">
        <div class="line" data-id="-1" v-show="showList" :key="prodLine">
            <Card :bordered="false" class="head" :headStyle="{ 'background-color': 'white' }">
                <template #title>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Продукция</span>
                        <Switch checked-children="Весовая" un-checked-children="Фасованная"
                            v-model:checked="categorySwitch" @change="hideProducts" />
                    </div>
                </template>
            </Card>
            <div class="line_items products">
                <ProductCard v-for="i in productsStore.products" :product="i" />
            </div>
        </div>
        <Divider type="vertical" v-show="showList" style="height: unset; width: 5px;" />
        <div class="line" v-for="line in linesStore.lines" :data-id="line.line_id"
            v-show="!hideEmpty || line.has_plans"
            ::key="`line-${line.line_id}-${line.version || 0}`" >
            <LineForm :data="line" />
            <div class="line_items products">
                <PlanCard v-for="plan in plansStore.getByLine(line.line_id)" :data="plan" @edit="editPlan" />
            </div>
        </div>
    </section>
    <ScrollButtons :containerRef="linesContainer" :speed="280" />
    <PlanModal :data="activePlan" @save="handleCardChange(true)" @cancel="handleCardChange(false)" />
</template>