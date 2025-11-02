<template>
    <div>
        <ul class="justify-end mt-3 mb-3 md:mb-7 flex flex-col gap-5 md:flex-row">
            <li
                v-for="(item, index) in footerMenu"
                :key="index"
                class="cursor-pointer font-medium text-gray-300 hover:text-white transition-colors duration-300"
                @click="handleClick(item)"
            >
                {{ item.title }}
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useOptionStore } from '@/stores/options';
import { useI18n } from 'vue-i18n';

const serviceOption = useOptionStore();
const router = useRouter();
const { locale } = useI18n();

function handleClick(item: { link: string; is_target: boolean }) {
    if (item.is_target) {
        window.open(item.link, '_blank');
    } else {
        router.push(item.link);
    }
}

const footerMenu = computed(() => {
    const raw = serviceOption.options.footer_menu;
    return raw ? JSON.parse(JSON.parse(raw)[locale.value]) : [];
});
</script>
