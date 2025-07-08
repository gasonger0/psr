<script setup lang="ts">
import { FloatButton } from 'ant-design-vue';
import { RightOutlined, LeftOutlined } from '@ant-design/icons-vue';
import { ref, ShallowRef, useTemplateRef } from 'vue';

const props = defineProps({
    containerRef: {
        type: HTMLElement,
        required: true
    },
    speed: {
        type: Number,
        required: true
    }
});

let isScrolling: number | null = null;
const cont = props.containerRef;
const speed = props.speed;
const scroll = (direction: string, isStart: boolean) => {
    if (isStart) {
        if (!isScrolling) {
            cont.scrollTo({
                left: cont.scrollLeft + (direction ? speed : -speed),
                behavior: "smooth"
            });
            isScrolling = setInterval(() => {
                cont.scrollTo({
                    left: cont.scrollLeft + (direction ? speed : -speed),
                    behavior: "smooth"
                });
            }, 300);
        }
    } else {
        isScrolling != null ? clearInterval(isScrolling) : '';
        isScrolling = null;
    }
}

// Выводим наружу
defineExpose(props);

</script>
<template>
    <FloatButton @dragover="scroll('L', true)" @dragleave="scroll('L', false)" @mouseover="scroll('L', true)"
        @mouseleave="scroll('L', false)" style="top:50%;">
        <template #icon>
            <RightOutlined />
        </template>
    </FloatButton>
    <FloatButton @dragover="scroll('R', true)" @dragleave="scroll('R', false)" @mouseover="scroll('R', true)"
        @mouseleave="scroll('R', false)" style="top:50%;left:1%">
        <template #icon>
            <LeftOutlined />
        </template>
    </FloatButton>
</template>