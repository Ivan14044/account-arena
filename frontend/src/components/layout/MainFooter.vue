<template>
    <footer
        class="bg-[#161719] min-h-[100px] text-white rounded-t-[40px] xl:rounded-t-[100px] px-6 py-16 position-relative mt-auto"
    >
        <div
            class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 grid md:grid-cols-[1fr_2fr] gap-10"
        >
            <div class="flex flex-col items-start justify-start">
                <h3 class="text-3xl font-bold mb-7">SubCloudy</h3>
                <p class="address text-gray-500 text-[16px] font-normal mb-3">
                    {{ $t('footer.address') }}
                </p>

                <p class="email text-gray-500 text-[16px] font-normal mb-3">
                    <a
                        href="mailto:support@subcloudy.com"
                        class="hover:text-white transition-colors duration-300 hover:underline"
                    >
                        support@subcloudy.com
                    </a>
                </p>
            </div>
            <FooterMenu />
        </div>
        <div
            class="max-w-7xl mx-auto w-full px-4 py-8 sm:px-6 lg:px-8 grid md:grid-cols-3 gap-5 md:gap-10"
        >
            <p class="copyright text-gray-500 text-[16px] font-normal md:mb-3">
                {{ $t('footer.copyright') }}
            </p>
            <div class="card-collection flex items-center justify-center md:justify-end">
                <img class="h-8" src="/img/visa-mastercard.png" alt="visa-mastercard" />
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import FooterMenu from '@/components/layout/FooterMenu.vue';
</script>

<style scoped>
.copyright {
    text-align: center;
}
@media (min-width: 768px) {
    .copyright {
        text-align: left;
    }
}

.card-collection {
    order: 2;
}

@media (min-width: 768px) {
    .card-collection {
        order: 3;
    }
}
</style>
