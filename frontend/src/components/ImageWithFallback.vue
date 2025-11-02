<template>
    <div class="block w-full h-full bg-gray-100 overflow-hidden" :style="styleObject">
        <img
            v-if="didError"
            :src="ERROR_IMG_SRC"
            alt="Error loading image"
            class="w-full h-full"
            v-bind="$attrs"
            :data-original-url="src"
        />
        <img
            v-else
            :src="computedSrc"
            :alt="alt"
            v-bind="$attrs"
            @error="handleError"
            class="w-full h-full object-cover"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const ERROR_IMG_SRC =
    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODgiIGhlaWdodD0iODgiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgc3Ryb2tlPSIjMDAwIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBvcGFjaXR5PSIuMyIgZmlsbD0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIzLjciPjxyZWN0IHg9IjE2IiB5PSIxNiIgd2lkdGg9IjU2IiBoZWlnaHQ9IjU2IiByeD0iNiIvPjxwYXRoIGQ9Im0xNiA1OCAxNi0xOCAzMiAzMiIvPjxjaXJjbGUgY3g9IjUzIiBjeT0iMzUiIHI9IjciLz48L3N2Zz4KCg==';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    src?: string;
    alt?: string;
    style?: string | Record<string, string>;
}>();

const didError = ref(false);

function handleError() {
    didError.value = true;
}

const styleObject = props.style as any;

const computedSrc = computed(() => {
    if (!props.src || props.src === 'null' || props.src === 'undefined') {
        return ERROR_IMG_SRC;
    }
    return props.src;
});
</script>
