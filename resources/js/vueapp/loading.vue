<script setup>
import { LoadingOutlined } from '@ant-design/icons-vue';
</script>
<script>
export default {
    props: {
        open: {
            type: Boolean
        }
    }
}
</script>
<template>
    <div class="load-container" v-show="$props.open">
        <div class="load-modal">
            <div class="load-content">
                <LoadingOutlined style="font-size: 50px;margin: auto;" />
            </div>
        </div>
    </div>
</template>