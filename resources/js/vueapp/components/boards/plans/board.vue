<script setup lang="ts">
import { FileExcelOutlined } from '@ant-design/icons-vue';
import { Button, Card, Popconfirm, Switch, Divider } from 'ant-design-vue';
import { computed, onBeforeMount, onMounted, ref, Ref, TemplateRef } from 'vue';
import { ProductInfo, useProductsStore } from '@stores/products';
import ProductCard from '@/components/boards/plans/productCard.vue'
import { useLinesStore } from '@/store/lines';
import LineForm from '@/components/common/lineForm.vue';
import PlanCard from './planCard.vue';
import { usePlansStore } from '@/store/productsPlans';
import ScrollButtons from '@/components/common/scrollButtons.vue';
import { getNextElement, scrollToTop } from '@/functions';
import { useProductsSlotsStore } from '@/store/productsSlots';
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
// TODO в план пишем инф-цию о продукте
const activePlan: Ref = ref();

const hideProducts = () => {
    productsStore.hide(Number(categorySwitch.value) + 1);
}
const clearPlan = () => {
    // TODO plansStore._clear();
}

const prodListTitle = computed(() => {
    return showList.value ? 'Скрыть' : 'Показать список продукции';
});
const hideEmptyLinesTitle = computed(() => {
    return hideEmpty.value ? 'Показать пустые линии' : 'Скрыть пустые линии';
});
onBeforeMount(async () => {
    let draggable = document.querySelectorAll('.line_items.products');
    draggable.forEach(line => {
        line.addEventListener(`dragstart`, (ev: Event) => {
            if (!ev) {
                return;
            }
            scrollToTop(linesContainer);
            console.log('Scrolled!');
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
                    let activeSlots = plansStore.getActiveSlots(product.product_id);
                    // Если есть слоты варки, но не выставлены в план
                    let check1 = slots[1].filter(el => activeSlots.includes(el.product_slot_id));
                    if (check1.length == 0) {
                        for (let i in slots[1]) {
                            document.querySelector('.line[data-id="' + slots[1][i].line_id + '"]').classList.remove('hidden-hard');
                        }
                    }

                    // Если есть слоты варки или их нет и в план не выставлены
                    if ((!slots[1] || check1.length)) {
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
            let target = ev.target as HTMLElement;
            if (target.classList.contains('selected') && target == active.value) {
                target.classList.remove(`selected`);
                let childs = Array.from(target.parentNode.children);
                let line = linesStore.getByID(Number(target.closest('.line').getAttribute('data-id')));


                if (isNewPlan.value) {
                    document.querySelectorAll('.line').forEach(el => {
                        el.classList.remove('hidden-hard');
                    });

                    let product = productsStore.getByID(Number(target.getAttribute('data-id'))),
                        position = childs.indexOf(target);

                    let slots = slotsStore.getByLineId(line.line_id),
                        plans = plansStore.getByLine(line.line_id),
                        lastProd = null,
                        started_at = dayjs.default();

                    if (plans.length > 0 && position > 0) {
                        lastProd = plans.find(i => i.position == (position - 1));
                        started_at = dayjs.default(lastProd.ended_at, format);
                    } else if (line.work_time.started_at != null) {
                        started_at = line.work_time.started_at;
                        if (line.prep_time) {
                            started_at = started_at.add(line.prep_time, 'minute');
                        }
                    }
                    activePlan.value = plansStore.add(
                        slots[0].product_slot_id,
                        started_at,
                        product.order.amount,
                        position
                    )
                    modal.open("plan");

                } else {
                        let ids = childs.map(el => el.getAttribute('data-id'));

                        let cards = [];
                        ids.forEach((el, k) => {
                            let pl = plansStore.plans.find(f => f.plan_product_id == Number(el));
                            pl.position = k;
                            cards.push(pl);
                        })

                        for (let i in cards) {
                            let timeDiff = cards[i].ended_at.diff(cards[i].started_at, 'minutes');
                            if (Number(i) == 0) {
                                cards[i].started_at = line.work_time.started_at.add(line.prep_time, 'minutes');
                                // if (line.prep_time) {
                            } else {
                                cards[i].started_at = cards[Number(i) - 1].ended_at;
                            }
                            cards[i].ended_at = cards[i].started_at.add(timeDiff, 'minutes');
                        }
                        console.log("Cards:", cards);
                        
                        plansStore._change(cards);                    
                }
            }
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
onMounted(() => document.querySelector('.lines-container').scrollTo({ left: 0 }))
</script>
<template>
    <section class="plans-toolbar">
        <Button type="dashed" @click="() => showList = !showList">
            {{ prodListTitle }}
        </Button>
        <Button type="dashed" @click="() => hideEmpty = !hideEmpty">
            {{ hideEmptyLinesTitle }}
        </Button>
        <Button type="primary" class="excel-button">
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
        <div class="line" data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" :headStyle="{ 'background-color': 'white' }">
                <template #title>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Продукция</span>
                        <Switch checked-children="Фасованная" un-checked-children="Весовая"
                            v-model:checked="categorySwitch" @change="hideProducts" />
                    </div>
                </template>
            </Card>
            <div class="line_items products">
                <ProductCard v-for="i in productsStore.products" :product="i">{{ 1 }}</ProductCard>
            </div>
        </div>
        <Divider type="vertical" v-show="showList" style="height: unset; width: 5px;" />
        <div class="line" v-for="line in linesStore.lines" :data-id="line.line_id"
            v-show="!(hideEmpty && !line.has_plans)">
            <LineForm :data="line" />
            <div class="line_items products">
                <PlanCard v-for="plan in plansStore.getByLine(line.line_id)" :data="plan" />
            </div>
        </div>
    </section>
    <ScrollButtons :containerRef="linesContainer" :speed="280" />
    <PlanModal :data="activePlan" />
</template>