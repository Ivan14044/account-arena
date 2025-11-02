<template>
    <div class="flex-1 max-w-lg lg:text-left">
        <div class="about-image-container relative">
            <img
                src="/img/who.webp"
                alt="About Us"
                class="w-full max-w-md rounded-2xl mx-auto"
                loading="lazy"
            />
        </div>
    </div>

    <div class="flex-1 max-w-lg lg:text-left">
        <h2 class="text-4xl md:text-6xl font-normal mb-6 text-gray-900 dark:text-gray-100">
            {{ $t('about.title') }}
        </h2>
        <p class="text-lg leading-relaxed text-gray-600 dark:text-gray-300">
            {{ $t('about.description') }}
        </p>
    </div>
</template>
