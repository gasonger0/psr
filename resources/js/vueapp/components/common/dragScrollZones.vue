<script setup lang="ts">
/**
 * Компонент для автоматической прокрутки контейнера при перетаскивании элемента
 * Создает невидимые зоны сверху и снизу, при наведении на которые с зажатым элементом
 * происходит прокрутка контейнера
 */
import { ref, Ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    containerRef: {
        type: HTMLElement,
        required: true
    },
    speed: {
        type: Number,
        default: 10
    },
    zoneHeight: {
        type: Number,
        default: 80
    }
});

let isScrolling: number | null = null;
let isDragging: Ref<boolean> = ref(false);

const topZoneStyle = computed(() => ({
    position: 'fixed',
    top: '0',
    left: '0',
    right: '0',
    height: `${props.zoneHeight}px`,
    zIndex: 999,
    cursor: 'grab',
    pointerEvents: 'auto'
}));

const bottomZoneStyle = computed(() => ({
    position: 'fixed',
    bottom: '0',
    left: '0',
    right: '0',
    height: `${props.zoneHeight}px`,
    zIndex: 999,
    cursor: 'grab',
    pointerEvents: 'auto'
}));

const scroll = (direction: 'UP' | 'DOWN', isStart: boolean) => {
    if (!isDragging.value) return;
    
    if (isStart) {
        if (!isScrolling) {
            const scrollAmount = direction === 'UP' ? -props.speed : props.speed;
            
            isScrolling = setInterval(() => {
                props.containerRef.scrollBy({
                    top: scrollAmount,
                    behavior: 'auto'
                });
            }, 50);
        }
    } else {
        if (isScrolling) {
            clearInterval(isScrolling);
            isScrolling = null;
        }
    }
};

const handleGlobalDragStart = () => {
    isDragging.value = true;
};

const handleGlobalDragEnd = () => {
    isDragging.value = false;
    if (isScrolling) {
        clearInterval(isScrolling);
        isScrolling = null;
    }
};

onMounted(() => {
    document.addEventListener('dragstart', handleGlobalDragStart);
    document.addEventListener('dragend', handleGlobalDragEnd);
});

onUnmounted(() => {
    document.removeEventListener('dragstart', handleGlobalDragStart);
    document.removeEventListener('dragend', handleGlobalDragEnd);
    if (isScrolling) clearInterval(isScrolling);
});

defineExpose({ scroll });
</script>

<template>
    <!-- Верхняя зона прокрутки -->
    <div 
        v-if="isDragging"
        class="drag-scroll-zone drag-scroll-zone-top"
        :style="topZoneStyle"
        @dragover="scroll('UP', true)"
        @dragleave="scroll('UP', false)"
        @dragend="scroll('UP', false)"
        @drop="scroll('UP', false)"
    />

    <!-- Нижняя зона прокрутки -->
    <div 
        v-if="isDragging"
        class="drag-scroll-zone drag-scroll-zone-bottom"
        :style="bottomZoneStyle"
        @dragover="scroll('DOWN', true)"
        @dragleave="scroll('DOWN', false)"
        @dragend="scroll('DOWN', false)"
        @drop="scroll('DOWN', false)"
    />
</template>

<style scoped>
.drag-scroll-zone {
    /* Можно раскомментировать для отладки: */
    /* background: rgba(59, 130, 246, 0.1); */
    transition: background-color 0.2s ease;
}

.drag-scroll-zone:hover {
    background: rgba(59, 130, 246, 0.08);
}
</style>
