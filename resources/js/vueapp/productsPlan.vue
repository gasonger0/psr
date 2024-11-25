<script setup>
import { Card, Button, Divider, Modal, TimePicker, Tooltip, Popconfirm, Switch, InputNumber, Input, Upload, FloatButton, RadioGroup, RadioButton, CheckboxGroup, Checkbox } from 'ant-design-vue';
import axios from 'axios';
import { reactive, ref } from 'vue';
import dayjs from 'dayjs';
import Loading from './loading.vue';
import { CloudDownloadOutlined, CloudUploadOutlined, DeleteOutlined, LeftOutlined, PrinterOutlined, RightOutlined } from '@ant-design/icons-vue';
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
            isNewPlan: ref(false),
            lines: reactive([]),
            listenerSet: ref(false),
            showList: ref(false),
            active: ref({}),
            showLoader: ref(false),
            confirmPlanOpen: ref(false),
            plans: reactive([]),
            key: ref(1),
            categorySwitch: ref(false),
            // stageSwitch: ref(false),
            // nullOrders: ref(false),
            exportFileName: ref(''),
            file: ref([]),
            stages: {
                1: "Варка",
                2: "Упаковка"
            },
            isScrolling: false
        }
    },
    methods: {
        async getProducts() {
            return new Promise((resolve, reject) => {
                axios.post('/api/get_products', {
                    packaged: this.categorySwitch
                })
                    .then((response) => {
                        if (response.data) {
                            this.products = response.data.map(el => {
                                el.isSelected = false;
                                el.active_slots = {
                                    1: false,
                                    2: false
                                };
                                el.slots = {
                                    1: [],
                                    2: []
                                };
                                el.order_amount = 0;
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
                                    prod.slots = {
                                        1: [],
                                        2: []
                                    };
                                }
                                // let f = this.lines.find((s) => s.line_id == el.line_id);
                                // if (f) {
                                //     el.title = f.title;
                                // }
                                let isActive = this.plans.find(i => i.line_id == el.line_id && i.product_id == el.product_id);
                                el.isActive = isActive != null;
                                prod.slots[el.type_id].push(el);
                            }
                        });
                        this.products.forEach(el => {
                            if (el.slots) {
                                if (el.slots[1].find(i => i.isActive == true)) {
                                    el.active_slots[1] = true;
                                }
                                if (el.slots[2].find(i => i.isActive == true)) {
                                    el.active_slots[2] = true;
                                }
                            }
                        });
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
                        this.plans = response.data.map((el) => {
                            let prod = this.products.find((i) => i.product_id == el.product_id);

                            if (el.started_at < timeString && el.ended_at > timeString) {
                                prod.current_line_id = el.line_id;
                            }
                            return el;
                        });
                        resolve(true);
                    })
            })
        },
        async getOrders() {
            return new Promise((resolve, reject) => {
                axios.get('/api/get_product_orders')
                    .then((response) => {
                        console.log(this.products);
                        response.data.forEach(el => {
                            let prod = this.products.find((i) => i.product_id == el.product_id);
                            if (prod) {
                                prod.order_amount = el.amount;
                            }
                        });

                        this.products = this.products.filter(el => el.order_amount > 0);
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
                    let x = this.document.querySelector('.lines-container');
                    setTimeout(() => {
                        x.scrollTo(
                            { top: 0, behavior: 'smooth' }
                        );
                    }, 100);
                    ev.target.classList.add(`selected`);

                    this.active.html = ev.target;
                    if (ev.target.closest('.line').dataset.id == "-1") {
                        this.isNewPlan = true;
                        this.document.querySelectorAll('.line').forEach(el => {
                            el.classList.add('hidden-hard');
                        });

                        this.document.querySelector('.line[data-id="-1"]').classList.remove('hidden-hard');


                        let product = this.products.find(el => el.product_id == ev.target.dataset.id);
                        if (product) {
                            if (!product.active_slots[1] && product.slots[1]) {
                                for (let i in product.slots[1]) {
                                    console.log(i);
                                    this.document.querySelector('.line[data-id="' + product.slots[1][i].line_id + '"]').classList.toggle('hidden-hard');
                                }
                            }
                            if (product.active_slots[1] && product.slots[2]) {
                                for (let i in product.slots[2]) {
                                    this.document.querySelector('.line[data-id="' + product.slots[2][i].line_id + '"]').classList.toggle('hidden-hard');
                                }
                            }
                        }
                    }
                })

                line.addEventListener(`dragend`, (ev) => {
                    if (this.isNewPlan) {
                        this.document.querySelectorAll('.line').forEach(el => {
                            el.classList.remove('hidden-hard');
                        });
                        if (ev.target.classList.contains('selected') && ev.target == this.active.html) {
                            ev.target.classList.remove(`selected`);
                            let line_id = ev.target.closest('.line').dataset.id;

                            this.active.line = ref(this.lines.find(f => f.line_id == line_id));
                            let prod = this.products.find(i => i.product_id == ev.target.dataset.id);
                            this.active.slot = prod.slots[1].concat(prod.slots[2]).find(n => n.line_id == line_id);
                            this.active.perfomance = this.active.slot.perfomance
                            this.active.amount = prod.order_amount;
                            this.active.order_amount = ref(prod.order_amount);
                            this.active.title = prod.title;

                            let lastProd = this.plans.filter((el) => el.line_id == line_id);
                            console.log(lastProd)
                            if (lastProd.length > 0) {
                                lastProd = lastProd.reduce((p, c) => p.ended_at > c.ended_at ? p : c);
                                this.active.started_at = ref(dayjs(lastProd.ended_at, 'HH:mm:ss'));
                            } else if (this.active.line.started_at != null) {
                                this.active.started_at = ref(dayjs(this.active.line.started_at, 'HH:mm:ss'));
                            } else {
                                this.active.started_at = ref(dayjs());
                            }
                            this.active.time = (this.active.amount / this.active.perfomance).toFixed(2);
                            this.active.ended_at = ref(this.active.started_at.add(this.active.time, 'hour'));
                            this.active.showError = (this.active.line.ended_at < this.active.ended_at.format('HH:mm:ss'));
                            this.confirmPlanOpen = true;
                            // this.addPlan(ev.target.closest('.line').dataset.id, ev.target.dataset.id);
                            // this.changeLine(ev.target.closest('.line').dataset.id, ev.target.dataset.id);

                        }
                    } else {
                        if (ev.target.classList.contains('selected') && ev.target == this.active.html) {
                            ev.target.classList.remove(`selected`);
                            let children = Array.from(ev.target.parentNode.children);
                            let sp = [ev.target.dataset.order, children.indexOf(ev.target)];

                            let changeIds = children.filter(el => el.dataset.order >= Math.min(sp[0], sp[1]) && el.dataset.order <= Math.max(sp[0], sp[1]));

                            console.log(changeIds);

                            let oldId = changeIds.find(el => el.dataset.order == sp[1]);
                            let newId = children[sp[1]];


                            console.log(oldId);
                            console.log(newId);
                            console.log(this.plans);
                            let oldd = this.plans.find(el => el.plan_product_id == oldId.dataset.id);
                            let neww = this.plans.find(el => el.plan_product_id == newId.dataset.id);

                            // console.log(dayjs('10:00:00', 'hh:mm:ss'));

                            let a = dayjs(oldd.started_at, 'hh:mm:ss');
                            let b = dayjs(neww.started_at, 'hh:mm:ss');
                            console.log(a, b);

                            let dateDiff = a.diff(b, 'minutes');
                            console.log('data:');
                            console.log(dateDiff);
                            this.showLoader = true;
                            changeIds.forEach((el, k) => {
                                if (el.dataset.id == ev.target.dataset.id) {
                                    axios.post('/api/change_plan', {
                                        plan_product_id: el.dataset.id,
                                        diff: dateDiff
                                    }).then(async (response) => {
                                        if (k == (changeIds.length - 1)) {
                                            this.listenerSet = false;
                                            this.key += 1;
                                            await this.getProducts();
                                            await this.getProductPlan();
                                            await this.getProductSlots();
                                            await this.getOrders();
                                            this.$forceUpdate();
                                            this.initFunc();
                                            this.showLoader = false;
                                        }
                                    });
                                } else {
                                    axios.post('/api/change_plan', {
                                        plan_product_id: el.dataset.id,
                                        diff: -dateDiff
                                    }).then(async (response) => {
                                        if (k == (changeIds.length - 1)) {
                                            this.listenerSet = false;
                                            this.key += 1;
                                            await this.getProducts();
                                            await this.getProductPlan();
                                            await this.getProductSlots();
                                            await this.getOrders();
                                            this.$forceUpdate();
                                            this.initFunc();
                                            this.showLoader = false;
                                        }
                                    });
                                }
                            });
                            // Получить ID и время работый старой карточки
                            // Получит ИД и время работы новой карточки
                            // Посчитать разницу
                            // Посчитать, сколько карточек изменит время работы
                            // Отправить на бэкенд, как изменить время работы в карточках и их ИД

                            // Обработка при изменении порядка для планов на линии
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
            if (t) {
                this.active.started_at;
            }
            this.active.ended_at = this.active.started_at.add(this.active.time, 'hour');
            this.active.showError = this.active.line.ended_at < this.active.ended_at.format('HH:mm:ss');
        },
        changeAmount() {
            this.active.time = (this.active.amount / this.active.perfomance).toFixed(2);
            this.changeTime()
        },
        async addPlan(add) {
            if (add) {
                axios.post('/api/add_product_plan',
                    {
                        started_at: this.active.started_at.format('HH:mm'),
                        ended_at: this.active.ended_at.format('HH:mm'),
                        slot_id: this.active.slot.product_slot_id,
                        amount: this.active.amount,
                        colon: this.active.colon,
                        hardware: this.active.hardware
                    }
                ).then(async () => {
                    this.confirmPlanOpen = false;
                    this.listenerSet = false;
                    this.showLoader = true;
                    this.key += 1;
                    await this.getProducts();
                    await this.getProductPlan();
                    await this.getProductSlots();
                    await this.getOrders();
                    this.$forceUpdate();
                    this.initFunc();
                    this.showLoader = false;
                });
            } else {
                this.confirmPlanOpen = false;
                this.listenerSet = false;
                this.showLoader = true;
                this.key += 1;
                await this.getProducts();
                await this.getProductPlan();
                await this.getProductSlots();
                await this.getOrders();
                this.$forceUpdate();
                this.initFunc();
                this.showLoader = false;
            }
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
                await this.getOrders();
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
            let a = this.plans.filter(el => el.line_id == line_id)
                .sort((a, b) => {
                    if (a.started_at == b.started_at) {
                        return 0;
                    } else if (a.started_at < b.started_at) {
                        return -1;
                    } else {
                        return 1;
                    }
                });
            console.log(a);
            return a;
        },
        printPlan() {
            axios.get('/api/download_plan')
                .then(response => {
                    let url = response.data;
                    console.log(response);
                    let a = document.createElement('a');
                    if (typeof a.download === undefined) {
                        window.location = url;
                    } else {
                        a.href = url;
                        a.download = response.data;
                        document.body.appendChild(a);
                        a.click();
                    }
                })
        },
        exportPlan() {
            let jsonString = JSON.stringify({ plans: this.plans, lines: this.lines });
            const blob = new Blob([jsonString], { type: 'application/json' });

            // Trigger download
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${this.exportFileName}.json`;
            a.click();

            // Clean up
            URL.revokeObjectURL(url);

        },
        importPlan(file) {
            console.log(file);
            let fd = new FormData();
            fd.append('file', file);

            this.$emit('notify', 'info', "Подождите, идёт обработка файла...");

            axios.post('/api/load_plan_json', fd).then((response) => {
                if (response) {
                    console.log(response);
                    this.$emit('notify', 'success', "Файл успешно загружен. Обновите страницу, чтобы оперировать актуальными данными");
                }
            }).catch((err) => {
                console.log(err);
                this.$emit('notify', 'warning', err);
            })
            console.log(file);
            return false;
        },
        async changeCat() {
            this.showLoader = true;
            await this.getProducts();
            await this.getProductPlan();
            await this.getProductSlots();
            await this.getOrders();
            this.showLoader = false;
        },
        scroll(direction, start) {
            if (start == 1) {
                if (!this.isScrolling) {
                    let cont = this.document.querySelector('.lines-container');
                    this.isScrolling = setInterval((el) => {
                        cont.scrollTo({
                            left: cont.scrollLeft + (direction ? 100 : -100),
                            behavior: "smooth"
                        });
                        if (cont.scrollLeft == cont.scrollWidth) {
                            clearInterval(this.isScrolling);
                            this.isScrolling = false;
                            this.showRight = false;
                        }
                    }, 20);
                }
            } else if (start == 2) {
                console.log(start);
                clearInterval(this.isScrolling);
                this.isScrolling = null;
            } else {
                let cont = this.document.querySelector('.lines-container');
                cont.scrollTo({
                    left: cont.scrollLeft + (direction ? 100 : -100),
                    behavior: "smooth"
                });
                setTimeout(() => {
                    return;
                }, 20);
            }
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
    }
}
</script>
<template>
    <div style="height: fit-content; margin-left: 1vw;display: flex; gap: 10px;" :key="key">
        <Button type="dashed" @click="() => showList = !showList">{{ !showList ? 'Показать список продукции' : 'Скрыть'
            }}</Button>
        <Popconfirm okText="ОК" cancelText="Отмена" @confirm="exportPlan">
            <template #title>
                <Input v-model:value="exportFileName" placeholder="Наименование файла" />
            </template>
            <Button type="default">
                <CloudDownloadOutlined />Экспорт плана
            </Button>
        </Popconfirm>
        <Upload v-model:file-list="file" :before-upload="(ev) => importPlan(ev)" :showUploadList="false">
            <Button type="default">
                <CloudUploadOutlined />Импорт плана
            </Button>
        </Upload>
        <Button typr="default" @click="printPlan">
            <PrinterOutlined />Распечатать план в xlsx
        </Button>
        <Popconfirm title="Это действие удалит весь план продукции" okText="Да" cancelText="Отмена"
            @confirm="clearPlan">
            <Button type="primary">Очистить план</Button>
        </Popconfirm>
    </div>
    <div class="lines-container" :key="key">
        <div class="line" :data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" :headStyle="{ 'background-color': 'white' }">
                <template #title>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Продукция</span>
                        <Switch checked-children="Фасованная" un-checked-children="Весовая"
                            v-model:checked="categorySwitch" @change="changeCat" />
                    </div>
                </template>
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
                        <ol v-if="(v.slots[1].length + v.slots[2].length) > 0">
                            <li v-if="v.slots[1].length > 0">
                                <span
                                    :style="v.active_slots[1] ? 'background: #50bb50;padding: 5px; color: white;' : ''">
                                    {{ stages[1] }}
                                </span>
                            </li>
                            <li v-if="v.slots[2].length > 0">
                                <span
                                    :style="v.active_slots[2] ? 'background: #50bb50;padding: 5px; color: white;' : ''">
                                    {{ stages[2] }}
                                </span>
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
                    :data-order="k" :key="v.plan_product_id" draggable="true">
                    <template #title>
                        <div style="display:flex;align-items: center;justify-content: space-between;">
                            <span>{{ v.started_at }} - {{ v.ended_at }}</span>
                            <Tooltip title="Убрать из плана">
                                <DeleteOutlined style="height:fit-content; color:#ff4d4f;"
                                    @click="deletePlan(v.plan_product_id)" />
                            </Tooltip>
                        </div>
                    </template>
                    <b style="margin-bottom: 10px;display: block;">Объём изготовления: {{ v.amount }}</b>
                    <span style="white-space: break-spaces;">{{ v.title }}</span>
                </Card>
            </section>
        </div>
    </div>
    <FloatButton @dragover="scroll(true, 0)" @mouseover="scroll(true, 1)" @mouseleave="scroll(true, 2)"
        style="top:50%;">
        <template #icon>
            <RightOutlined />
        </template>
    </FloatButton>
    <FloatButton @dragover="scroll(false, 0)" @mouseover="scroll(false, 1)" @mouseleave="scroll(false, 2)"
        style="top:50%;left:1%">
        <template #icon>
            <LeftOutlined />
        </template>
    </FloatButton>
    <Modal v-model:open="confirmPlanOpen" @ok="addPlan(true)" @cancel="addPlan(false)" okText="Да" cancelText="Нет">
        <span>Это действие поставит работу <b>{{ active.title }}</b> на линии <b>{{ active.line.title }}</b>.</span>
        <br>
        <div style="display: flex;justify-content: space-between;margin: 14px 0px;">
            <b style="font-size: 16px">Объём изготовления:</b>
            <InputNumber v-model:value="active.amount" @change="changeAmount" />
        </div>
        <div style="display: flex;justify-content: space-between;margin: 14px 0px;">
            <b style="font-size: 16px">Время начала:</b>
            <TimePicker v-model:value="active.started_at" @change="changeTime" format="HH:mm" />
        </div>
        <h3>Колонка: </h3>
        <!-- <RadioGroup v-model:value="active.colon"> -->
            <!-- <RadioButton value="1">Варочная колонка №1</RadioButton> -->
            <!-- <RadioButton value="2">Варочная колонка №2</RadioButton> -->
        <!-- </RadioGroup> -->
        <CheckboxGroup v-model:value="active.colon">
            <Checkbox value="1">Варочная колонка №1</Checkbox>
            <Checkbox value="2">Варочная колонка №2</Checkbox>
        </CheckboxGroup>
        <br>
        <h3>Оборудование:</h3>
        <RadioGroup v-model:value="active.hardware">
            <RadioButton value="1">Мондомикс</RadioButton>
            <RadioButton value="2">Торнадо</RadioButton>
            <RadioButton value="3">Китайский АЭРОС</RadioButton>
        </RadioGroup>
        <br>
        <br>
        <span>С учётом производительности линии для данного продукта, время изготовления составит <b>{{ active.time }}</b>ч.</span>
        <br>
        <span>Работа по данной продукции закончится в <b>{{ active.ended_at.format('HH:mm') }}</b></span>
        <br>
        <span v-if="active.showError" style="color:#ff4d4f">
            Внимание! Продукция будет изготавливаться дольше, чем работает линия!
            <br />
            Скорректируте объём изготовления продукции или время работы линии
        </span>
    </Modal>
    <Loading :open="showLoader" />
</template>