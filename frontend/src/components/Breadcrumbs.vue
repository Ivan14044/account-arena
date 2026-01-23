<template>
    <nav v-if="crumbs.length > 0" class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 list-none p-0">
            <li class="inline-flex items-center">
                <router-link
                    to="/"
                    class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                >
                    <Home class="w-4 h-4 mr-2" />
                    {{ $t('common.home') || 'Главная' }}
                </router-link>
            </li>
            <li v-for="(crumb, index) in crumbs" :key="index">
                <div class="flex items-center">
                    <ChevronRight class="w-4 h-4 text-gray-400 mx-1" />
                    <router-link
                        v-if="index < crumbs.length - 1"
                        :to="crumb.path"
                        class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white"
                    >
                        {{ crumb.name }}
                    </router-link>
                    <span
                        v-else
                        class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400"
                        aria-current="page"
                    >
                        {{ crumb.name }}
                    </span>
                </div>
            </li>
        </ol>

        <!-- JSON-LD Microdata -->
        <component :is="'script'" type="application/ld+json">
            {{ jsonLd }}
        </component>
    </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Home, ChevronRight } from 'lucide-vue-next';

interface Crumb {
    name: string;
    path: string;
}

const props = defineProps<{
    crumbs: Crumb[];
}>();

const jsonLd = computed(() => {
    const itemListElement = [
        {
            '@type': 'ListItem',
            position: 1,
            name: 'Home',
            item: window.location.origin
        }
    ];

    props.crumbs.forEach((crumb, index) => {
        itemListElement.push({
            '@type': 'ListItem',
            position: index + 2,
            name: crumb.name,
            item: window.location.origin + crumb.path
        });
    });

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement
    });
});
</script>
