<script setup>
    import { Card, Button } from 'ant-design-vue';
</script>
<script>
export default {
    data() {
        return {
            lines: {
                1 : {
                    title: 'Линия №1',
                    color: 'red',
                },
                2 : {
                    title: 'Линия №2',
                    color: 'blue'
                },
                3 : {
                    title: 'Линия №3',
                    color: 'grey'
                },
            }, 
            workers: [{
                id: 1,
                line_id: 1,         //test
                name: 'Комаров И.Н.',
                company: 'ООО',
                schedule: {
                    break: {
                        from : new Date(), 
                        to: new Date()
                    },
                    plan: [
                        {
                            line_id: 1,
                            from: new Date(),
                            to: new Date()
                        }
                    ]
                }
            },{
                id: 2,
                name: 'Пупупу',
                company: 'Треш',
                line_id: 1          //test
            }]
        }
    },
    methods: {

    },
    created() {

    }
}
</script>
<template>
    <div class="top-container">
        <Button type="primary" style="background-color: green;">Новый график</Button>
        <Button type="default">Отчёт</Button>
        <Button type="primary">Редактировать график</Button>
    </div>
    <div class="lines-container">
        <div class="line" v-for="(v,k) in lines">
            <div class="line_title">{{ v.title }}</div>
            <section class="line_items" v-for="i in workers.filter(el => el.line_id == k)">
                <Card>{{ i }}</Card>
            </section>
        </div>
    </div>
</template>