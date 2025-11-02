<template>
    <div
        v-if="showBanner"
        class="fixed inset-x-0 bottom-0 text-white text-sm px-4 py-3 shadow-md z-50 w-full cookie-banner"
    >
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <p class="cookie-text">
                {{ $t('cookie.message') }}
                <br />
                <a
                    target="_blank"
                    class="underline text-blue-300 hover:text-blue-200 cursor-pointer"
                    @click="router.push('/cookie-policy')"
                >
                    {{ $t('cookie.title') }}
                </a>
            </p>
            <button
                class="bg-white text-gray-800 px-4 py-2 rounded hover:bg-gray-100 transition"
                @click="acceptCookies"
            >
                {{ $t('cookie.button') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const showBanner = ref(false);
const router = useRouter();

onMounted(async () => {
    if (localStorage.getItem('cookies_accepted')) return;

    try {
        const res = await axios.get('/cookie/check');
        if (res.data?.show_cookie_banner) {
            showBanner.value = true;
        }
    } catch (err) {
        console.warn('Cookie check failed', err);
    }
});

function acceptCookies() {
    localStorage.setItem('cookies_accepted', 'true');
    showBanner.value = false;
}
</script>

<style scoped>
@media (max-width: 640px) {
    p {
        font-size: 0.875rem;
    }
}

.cookie-banner {
    background: rgb(17 24 39 / var(--tw-text-opacity, 1));
}

.cookie-text {
    text-align: center;
}

@media (min-width: 1024px) {
    .cookie-text {
        text-align: left;
    }
}
</style>
