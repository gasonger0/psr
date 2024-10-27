<script setup>
import { Card, Button, Divider, Modal, TimePicker, Tooltip, Popconfirm } from 'ant-design-vue';
import axios from 'axios';
import { reactive, ref } from 'vue';
import dayjs from 'dayjs';
import Loading from './loading.vue';
import { DeleteOutlined } from '@ant-design/icons-vue';
</script>
<script>
export default {
    props: {
        data: {
            type: Object
        }
    },
    data() {
        return {
            products: reactive([]),
            productsSlots: reactive([]),
            document: document,
            lines: reactive([]),
            listenerSet: ref(false),
            showList: ref(false),
            active: ref({}),
            showLoader: ref(false),
            confirmPlanOpen: ref(false),
            plans: reactive([]),
            key: ref(1)
        }
    },
    methods: {
        async getProducts() {
            return new Promise((resolve, reject) => {
                axios.post('/api/get_products')
                    .then((response) => {
                        if (response.data) {
                            this.products = response.data.map(el => {
                                el.isSelected = false;
                                return el;
                            });
                            resolve(true);
                        }
                    });
            });
        },
        async getProductSlots() {
            return new Promise((resolve, reject) => {
                axios.post('/api/get_product_slots')
                    .then((response) => {
                        this.productsSlots = response.data.forEach(el => {
                            let prod = this.products.find((i) => i.product_id == el.product_id);
                            if (prod) {
                                if (!prod.slots) {
                                    prod.slots = [];
                                }
                                let f = this.lines.find((s) => s.line_id == el.line_id);
                                if (f) {
                                    el.title = f.title;
                                }
                                let isActive = this.plans.find(i => i.slot_id == el.product_slot_id);
                                console.log(el);
                                el.isActive = isActive != null;
                                prod.slots.push(el);
                            }
                        })
                        resolve(true);
                    });
            });
        },
        async getProductPlan() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_product_plans')
                    .then((response) => {
                        let curTime = new Date();
                        let timeString =
                            (String(curTime.getHours()).length == 1 ? '0' + String(curTime.getHours()) : String(curTime.getHours()))
                            + ':' +
                            (String(curTime.getMinutes()).length == 1 ? '0' + String(curTime.getMinutes()) : String(curTime.getMinutes()))
                            + ':' +
                            (String(curTime.getSeconds()).length == 1 ? '0' + String(curTime.getSeconds()) : String(curTime.getSeconds()));
                        this.plans = response.data;
                        this.plans.forEach((el) => {
                            let prod = this.products.find((i) => i.product_id == el.product_id);
                            el.title = prod.title;
                            // if (el.started_at < timeString && el.ended_at > timeString) {
                            //     prod.current_line_id = el.line_id;
                            // }
                        });
                        resolve(true);
                    })
            })
        },
        async getOrders() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_product_orders')
                    .then((response) => {
                        response.data.forEach(el => {
                            let prod = this.products.find((i) => i.product_id == el.product_id);
                            if (prod) {
                                prod.order_amount = el.amount;
                            }
                        })
                        resolve(true);
                    })
            })
        },
        getNextElement(cursorPosition, currentElement) {
            // Получаем объект с размерами и координатами
            const currentElementCoord = currentElement.getBoundingClientRect();
            // Находим вертикальную координату центра текущего элемента
            const currentElementCenter = currentElementCoord.y + currentElementCoord.height / 2;

            // Если курсор выше центра элемента, возвращаем текущий элемент
            // В ином случае — следующий DOM-элемент
            const nextElement = (cursorPosition < currentElementCenter) ?
                currentElement :
                currentElement.nextElementSibling;
            return nextElement;
        },
        initFunc() {
            // if (!this.listenerSet) {
            let draggable = this.document.querySelectorAll('.line_items.products');
            draggable.forEach(line => {
                line.addEventListener(`dragstart`, (ev) => {
                    ev.target.classList.add(`selected`);

                    this.document.querySelectorAll('.line').forEach(el => {
                        el.classList.add('hidden');
                    });

                    this.active.html = ev.target;
                    this.active.started_at = dayjs();
                    let product = this.products.find(el => el.product_id == ev.target.dataset.id);
                    if (product) {
                        for (let i in product.slots) {
                            this.document.querySelector('.line[data-id="' + product.slots[i].line_id + '"]').classList.toggle('hidden');
                        }
                    }
                })

                line.addEventListener(`dragend`, (ev) => {
                    this.document.querySelectorAll('.line').forEach(el => {
                        el.classList.remove('hidden');
                    });
                    if (ev.target.classList.contains('selected') && ev.target == this.active.html) {
                        ev.target.classList.remove(`selected`);
                        let line_id = ev.target.closest('.line').dataset.id;
                        this.active.line = this.lines.find(f => f.line_id == line_id);
                        let prod = this.products.find(i => i.product_id == ev.target.dataset.id);
                        console.log(prod);
                        console.log(line_id);
                        console.log(prod.slots);
                        this.active.slot = prod.slots.find(n => n.line_id == line_id);
                        this.active.perfomance = this.active.slot.perfomance
                        this.active.amount = prod.order_amount;
                        this.active.title = prod.title;
                        this.active.time = (this.active.perfomance / this.active.amount).toFixed(2);
                        this.active.ended_at = this.active.started_at.add(this.active.time, 'hour');
                        console.log(this.active.slot);
                        this.confirmPlanOpen = true;
                        // this.addPlan(ev.target.closest('.line').dataset.id, ev.target.dataset.id);
                        // this.changeLine(ev.target.closest('.line').dataset.id, ev.target.dataset.id);

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

                    const nextElement = this.getNextElement(ev.clientY, currentElement);
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
            // }
            this.listenerSet = true;
            this.document.querySelector('.lines-container').scrollTo({ left: 0 });
        },
        changeTime(t, s) {
            this.active.started_at = t;
            this.active.ended_at = this.active.started_at.add(this.active.time, 'hour');
        },
        addPlan() {
            axios.post('/api/add_product_plan',
                {
                    started_at: this.active.started_at.format('HH:mm'),
                    ended_at: this.active.ended_at.format('HH:mm'),
                    slot_id: this.active.slot.product_slot_id
                }
            ).then(async () => {
                this.confirmPlanOpen = false;
                this.listenerSet = false;
                this.showLoader = true;
                this.key += 1;
                await this.getProducts();
                await this.getProductPlan();
                await this.getProductSlots();
                this.$forceUpdate();
                this.initFunc();
                this.showLoader = false;
            });

        },
        deletePlan(id) {
            console.log('ID: ' + id);
            axios.post('/api/delete_product_plan',
                {
                    product_plan_id: id
                }
            ).then(async (response) => {
                this.showLoader = true;
                this.key += 1;
                await this.getProducts();
                await this.getProductPlan();
                await this.getProductSlots();
                this.listenerSet = false;
                this.initFunc();
                this.showLoader = false;
            })
        },
        clearPlan() {
            axios.delete('/api/clear_plan')
                .then(() => {
                    window.location.reload();
                })
        },
        filterPlans(line_id) {
            return this.plans.filter(el => el.line_id == line_id)
                .sort((a, b) => {
                    let i = a.started_at.split(':');
                    let j = b.started_at.split(':');
                    let k = new Date();
                    let l = new Date();
                    k.setHours(a[0], a[1], a[2]);
                    l.setHours(b[0], b[1], b[2]);
                    if (k == l) {
                        return 0;
                    }
                    if (k < l) {
                        return -1;
                    } else {
                        return 1;
                    }
                });
        }
    },
    async created() {
        this.showLoader = true;
        this.lines = this.$props.data.lines;
        await this.getProducts();
        await this.getProductPlan();
        await this.getProductSlots();
        await this.getOrders();
        this.showLoader = false;
    },
    updated() {
        this.initFunc();
    },
}
</script>
<template>
    <div style="height: fit-content; margin-left: 1vw;display: flex; gap: 10px;" :key="key">
        <Button type="dashed" @click="() => showList = !showList">{{ !showList ? 'Показать список продукции' : 'Скрыть'
            }}</Button>
        <Popconfirm title="Это действие удалит весь план продукции" okText="Да" cancelText="Отмена" @confirm="clearPlan">
            <Button type="primary">Очистить план</Button>
        </Popconfirm>
    </div>
    <div class="lines-container" :key="key">
        <div class="line" :data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" title="Продукция" :headStyle="{ 'background-color': 'white' }">
            </Card>
            <section class="line_items products">
                <Card draggable="true" class="draggable-card" v-for="(v, k) in products" :data-id="v.product_id"
                    :key="v.product_id">
                    <template #title>
                        <span style="white-space: break-spaces;">{{ v.title }}</span>
                    </template>
                    <div class="hiding-data">
                        <span>Нужно обеспечить: <b>{{ v.order_amount }}</b></span>
                        <br>
                        <span>Этапы изготовления по линиям:</span>
                        <ol>
                            <li v-for="(i, j) in v.slots">
                                <span :style="i.isActive ? 'background: #50bb50;padding: 5px; color: white;' : ''">{{
                                    i.title }}</span>
                            </li>
                        </ol>
                    </div>
                </Card>
            </section>
        </div>
        <Divider type="vertical" v-show="showList" style="height: unset; width: 5px;" />
        <div class="line" v-for="line in lines" :data-id="line.line_id" :class="line.done ? 'done-line' : ''"
            :key="line.line_id">
            <Card :bordered="false" class="head"
                :headStyle="{ 'background-color': (line.color ? line.color : '#1677ff') }">
                <template #title>
                    <div class="line_title" :data-id="line.line_id">
                        <b>{{ line.title }}</b>
                    </div>
                </template>
                <span>{{ line.started_at }} - {{ line.ended_at }}</span>
            </Card>

            <section class="line_items products">
                <Card class="draggable-card" v-for="(v, k) in filterPlans(line.line_id)" :data-id="v.plan_product_id"
                    :key="v.plan_product_id" @focus="() => { v.showDelete = true }"
                    @mouseleave="() => { v.showDelete = false }">
                    <template #title>
                        <div style="display:flex;align-items: center;justify-content: space-between;">
                            <span>{{ v.started_at }} - {{ v.ended_at }}</span>
                            <Tooltip title="УБрать из плана">
                                <DeleteOutlined style="height:fit-content; color:#ff4d4f;"
                                    @click="deletePlan(v.plan_product_id)" />
                            </Tooltip>
                        </div>
                    </template>
                    <span style="white-space: break-spaces;">{{ v.title }}</span>
                </Card>
            </section>
        </div>
    </div>
    <Modal v-model:open="confirmPlanOpen" @ok="addPlan" okText="Да" cancelText="Нет">
        <span>Это действие поставит работу <b>{{ active.title }}</b> на линии <b>{{ active.line.title }}</b>.</span>
        <br>
        <div style="display: flex;justify-content: space-between;margin: 14px 0px;">
            <b style="font-size: 16px">Время начала:</b>
            <TimePicker v-model:value="active.started_at" @change="changeTime" format="HH:mm" />
        </div>
        <br>
        <span>С учётом производительности линии для данного продукта, время изготовления составит {{ active.time }}
            ч.</span>
        <br>
        <span>Работа по данной продукции закончится в {{ active.ended_at.format('HH:mm') }}</span>
    </Modal>
    <Loading :open="showLoader" />
</template>