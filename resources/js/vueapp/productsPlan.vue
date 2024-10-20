<script setup>
import { Card, Button, Divider, Modal, TimePicker } from 'ant-design-vue';
import axios from 'axios';
import { reactive, ref } from 'vue';
import dayjs from 'dayjs';
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
            confirmPlanOpen: ref(false)
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
                        response.data.forEach(el => {
                            let prod = this.products.find((i) => i.product_id == el.product_id);
                            if (prod) {
                                console.log('prod');
                                if (!prod.slots) {
                                    prod.slots = [];
                                }
                                let f = this.lines.find((s) => s.line_id == el.line_id);
                                if (f) {
                                    el.title = f.title;
                                }
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
                        console.log(response.data);
                        response.data.forEach((el) => {
                            let prod = this.products.find((i) => i.product_id == el.product_id);
                            if (prod) {
                                prod.started_at = el.started_at;
                                prod.ended_at = el.ended_at;
                            }
                            if (el.started_at < timeString && el.ended_at > timeString) {
                                prod.current_line_id = el.line_id;
                            }
                        })
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
        // addPlan(line_id, )
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
            console.log('draggable:');
            console.log(draggable);
            draggable.forEach(line => {
                line.addEventListener(`dragstart`, (ev) => {
                    ev.target.classList.add(`selected`);


                    this.document.querySelectorAll('.done-line').forEach(el => {
                        el.classList.toggle('hidden');
                    })
                    this.active.html = ev.target;
                    this.active.started_at = dayjs();
                    
                    console.log(ev.target)
                })

                line.addEventListener(`dragend`, (ev) => {
                    this.document.querySelectorAll('.done-line').forEach(el => {
                        el.classList.toggle('hidden');
                    });
                    if (ev.target.classList.contains('selected') && ev.target == this.active.html) {
                        ev.target.classList.remove(`selected`);
                        let line_id = ev.target.closest('.line').dataset.id;
                        this.active.line = this.lines.find(f => f.line_id == line_id);
                        let prod = this.products.find(i => i.product_id = ev.target.dataset.id);
                        console.log(this.active);
                        this.active.perfomance = prod.slots.find(n => n.line_id == line_id).perfomance;
                        this.active.amount = prod.order_amount;

                        this.active.time = (this.active.perfomance / this.active.amount).toFixed(2);
                        this.active.ended_at = this.active.started_at.add(this.active.time, 'hour');

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
            console.log(t);
            console.log(s);
            this.active.started_at = t;
            this.active.ended_at = this.active.started_at.add(this.active.time, 'hour');
            console.log(this.active);
        },
        addPlan() {

            this.listenerSet = false;
            this.initFunc();
        }
    },
    async created() {
        this.lines = this.$props.data.lines;
        await this.getProducts();
        await this.getProductPlan();
        await this.getProductSlots();
        await this.getOrders();
        console.log(this.lines);
        console.log(this.products);
    },
    updated() {
        this.initFunc();
    },
    // async mounted() {
    //     await this.getProducts();
    //     await this.getProductPlan();
    //     this.lines = this.$props.data.lines;
    //     console.log(this.lines);
    //     this.initFunc();
    // }
}
</script>
<template>
    <div style="height: fit-content; margin-left: 1vw;">
        <Button type="dashed" @click="() => showList = !showList">{{ !showList ? 'Показать список продукции' : 'Скрыть'
            }}</Button>
    </div>
    <div class="lines-container">
        <div class="line" :data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" title="Продукция" :headStyle="{ 'background-color': 'white' }">
            </Card>
            <section class="line_items products">
                <Card draggable="true" class="draggable-card" v-for="(v, k) in products" :data-id="v.product_id">
                    <template #title>
                        <span style="white-space: break-spaces;">{{ v.title }}</span>
                    </template>
                    <div class="hiding-data">
                        <span>Нужно обеспечить: {{ v.order_amount }}</span>
                        <br>
                        <span>Этапы изготовления по линиям:</span>
                        <ol>
                            <li v-for="(i, j) in v.slots">
                                {{ i.title }}
                            </li>
                        </ol>
                    </div>
                </Card>
            </section>
        </div>
        <Divider type="vertical" v-show="showList" style="height: unset; width: 5px;" />
        <div class="line" v-for="line in lines" :data-id="line.line_id" :class="line.done ? 'done-line' : ''">
            <Card :bordered="false" class="head"
                :headStyle="{ 'background-color': (line.color ? line.color : '#1677ff') }">
                <template #title>
                    <div class="line_title" :data-id="line.line_id">
                        <b>{{ line.title }}</b>
                    </div>
                </template>
                <!-- <div class="line_sub-title">
                </div> -->
            </Card>

            <section class="line_items products">
                <Card class="draggable-card" v-for="(v, k) in products.filter(el => el.current_line_id == line.line_id)"
                    :data-id="v.product_plan_id" @focus="() => { v.showDelete = true }"
                    @mouseleave="() => { v.showDelete = false }">
                    <template #title>
                        <span style="white-space: break-spaces;">{{ v.title }}</span>
                    </template>
                    <span>Время изготовления: {{ v.started_at }} - {{ v.ended_at }}</span>
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
</template>