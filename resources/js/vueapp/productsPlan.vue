<script setup>
import { Card, Button } from 'ant-design-vue';
import { reactive } from 'vue';

</script>
<script>
export default {
    data(){
        return {
            products: reactive([])
        }
    }
}
</script>
<template>
    <div style="height: fit-content; margin-left: 1vw;">
        <Button type="dashed" @click="() => showList = !showList">{{ !showList ? 'Показать список продукции' : 'Скрыть'
            }}</Button>
    </div>
    <div class="lines-container" :key="contKey" ref="linesContainer">
        <div class="line" :data-id="-1" v-show="showList">
            <Card :bordered="false" class="head" title="Не производятся" :headStyle="{ 'background-color': 'white' }">
                <br>
            </Card>
            <section class="line_items">
                <Card :title="v.title" draggable="true" class="draggable-card product"
                    v-for="(v, k) in products.filter(el => el.current_line_id == null)" :data-id="v.product_plan_id"
                    @focus="() => { v.showDelete = true }" @mouseleave="() => { v.showDelete = false }">
                    <!-- <template #extra>
                        <span style="color: #1677ff;text-decoration: underline;">
                            {{ v.company }}
                        </span>
                    </template> -->
                    <!-- <span v-show="v.break_started_at && v.break_ended_at" style="height: fit-content;">
                        Обед: {{ v.break_started_at.substr(0, 5) + ' - ' + v.break_ended_at.substr(0, 5) }}
                    </span> -->
                </Card>
            </section>
        </div>
        <div class="line" v-for="line in lines" :data-id="line.line_id" :class="line.done ? 'done-line' : ''">
            <Card :bordered="false" class="head"
                :headStyle="{ 'background-color': (line.color ? line.color : '#1677ff') }">
                <section class="line_items">
                    <Card :title="v.title" draggable="true" class="draggable-card"
                        v-for="(v, k) in products.filter(el => el.current_line_id == line.line_id)"
                        :data-id="v.product_plan_id"
                        @focus="() => { v.showDelete = true }" @mouseleave="() => { v.showDelete = false }">
                    </Card>
                </section>
            </Card>
        </div>
    </div>
</template>