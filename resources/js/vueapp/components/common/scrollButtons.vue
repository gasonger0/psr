<script setup lang="ts">
import { FloatButton } from 'ant-design-vue';
import { RightOutlined, LeftOutlined } from '@ant-design/icons-vue';

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

let isScrolling: NodeJS.Timeout | null = null;
const speed = props.speed;
const scroll = (direction: string, isStart: boolean) => {
    if (isStart) {
        if (!isScrolling) {
            props.containerRef.scrollTo({
                left: props.containerRef.scrollLeft + (direction == 'L' ? speed : -speed),
                behavior: "smooth"
            });
            isScrolling = setInterval(() => {
                props.containerRef.scrollTo({
                    left: props.containerRef.scrollLeft + (direction == 'L' ? speed : -speed),
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