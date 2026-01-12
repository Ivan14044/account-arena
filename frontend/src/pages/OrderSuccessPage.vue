<template>
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-12 px-4"
    >
        <div class="max-w-4xl mx-auto">
            <!-- Успешное сообщение -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full mb-4"
                >
                    <svg
                        class="w-10 h-10 text-green-600 dark:text-green-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        ></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $t('order_success.title') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    {{ $t('order_success.subtitle') }}
                </p>
                <div
                    v-if="!loading && recentPurchases.length > 0"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                        ></path>
                    </svg>
                    {{ $t('order_success.items_purchased', { count: recentPurchases.length }) }}
                </div>
            </div>

            <!-- Загрузка / Подготовка товара -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="flex flex-col items-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('checkout.preparing_product') }}
                    </p>
                </div>
            </div>

            <!-- Сообщение об ожидании подтверждения оплаты (если нет заказов после таймаута) -->
            <div
                v-else-if="!loading && recentPurchases.length === 0 && pollingAttempts >= maxPollingAttempts"
                class="flex justify-center py-12"
            >
                <div class="flex flex-col items-center max-w-md text-center">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-full mb-4"
                    >
                        <svg
                            class="w-8 h-8 text-yellow-600 dark:text-yellow-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        {{ $t('order_success.waiting_payment_title') }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        {{ $t('order_success.waiting_payment_description') }}
                    </p>
                    <button
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium"
                        @click="fetchPurchases(); startPolling();"
                    >
                        {{ $t('order_success.refresh') }}
                    </button>
                </div>
            </div>

            <!-- Список купленных товаров -->
            <div v-else-if="recentPurchases.length > 0" class="space-y-4">
                <div v-for="purchase in recentPurchases" :key="purchase.id" class="purchase-card">
                    <!-- Заголовок товара -->
                    <div class="flex items-center gap-4 mb-4">
                        <img
                            v-if="purchase.product.image_url"
                            :src="purchase.product.image_url"
                            :alt="getProductTitle(purchase.product.title || {})"
                            class="w-16 h-16 rounded-lg object-contain"
                        />
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ getProductTitle(purchase.product.title || {}) }}
                            </h3>
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                {{
                                    $t('order_success.order_number', {
                                        number: purchase.order_number || purchase.id
                                    })
                                }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{
                                    $t('order_success.quantity_price', {
                                        quantity: purchase.quantity,
                                        unit: $t('profile.purchases.quantity_unit'),
                                        price: purchase.price,
                                        total: purchase.total_amount
                                    })
                                }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                {{
                                    $t('order_success.purchased_at', {
                                        date: formatDate(purchase.purchased_at)
                                    })
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Статус обработки для ручной выдачи -->
                    <div
                        v-if="purchase.status === 'processing'"
                        class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4"
                    >
                        <div class="flex items-start gap-3">
                            <svg
                                class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <div class="flex-1">
                                <h4 class="font-semibold text-yellow-900 dark:text-yellow-200 mb-1">
                                    {{ $t('profile.purchases.processing') }}
                                </h4>
                                <p class="text-sm text-yellow-800 dark:text-yellow-300 mb-2">
                                    {{ $t('profile.purchases.processing_description') }}
                                </p>
                                <p class="text-xs text-yellow-700 dark:text-yellow-400 mb-3">
                                    {{ $t('profile.purchases.working_hours') }}
                                </p>
                                
                                <!-- Прогресс-бар обработки заказа -->
                                <div class="mb-3">
                                    <div class="flex items-center gap-2 mb-2">
                                        <!-- Этап 1: Заказ принят -->
                                        <div class="flex items-center gap-1.5 flex-1">
                                            <div 
                                                :class="[
                                                    'w-6 h-6 rounded-full flex items-center justify-center shrink-0',
                                                    getProgressStage(purchase) >= 1 
                                                        ? 'bg-yellow-500 dark:bg-yellow-600' 
                                                        : 'bg-gray-300 dark:bg-gray-600'
                                                ]"
                                            >
                                                <svg 
                                                    :class="[
                                                        'w-4 h-4',
                                                        getProgressStage(purchase) >= 1 
                                                            ? 'text-white' 
                                                            : 'text-gray-500 dark:text-gray-400'
                                                    ]"
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <span 
                                                :class="[
                                                    'text-xs',
                                                    getProgressStage(purchase) >= 1 
                                                        ? 'text-yellow-700 dark:text-yellow-300' 
                                                        : 'text-gray-500 dark:text-gray-400'
                                                ]"
                                            >
                                                {{ $t('profile.purchases.progress.accepted') }}
                                            </span>
                                        </div>
                                        <!-- Линия -->
                                        <div 
                                            :class="[
                                                'flex-1 h-0.5',
                                                getProgressStage(purchase) >= 2 
                                                    ? 'bg-yellow-300 dark:bg-yellow-700' 
                                                    : 'bg-gray-300 dark:bg-gray-600'
                                            ]"
                                        ></div>
                                        <!-- Этап 2: В обработке -->
                                        <div class="flex items-center gap-1.5 flex-1">
                                            <div 
                                                :class="[
                                                    'w-6 h-6 rounded-full flex items-center justify-center shrink-0',
                                                    getProgressStage(purchase) === 2 
                                                        ? 'bg-yellow-500 dark:bg-yellow-600 animate-pulse' 
                                                        : getProgressStage(purchase) >= 2
                                                        ? 'bg-yellow-500 dark:bg-yellow-600'
                                                        : 'bg-gray-300 dark:bg-gray-600'
                                                ]"
                                            >
                                                <div 
                                                    v-if="getProgressStage(purchase) === 2"
                                                    class="w-2 h-2 rounded-full bg-white"
                                                ></div>
                                                <svg 
                                                    v-else-if="getProgressStage(purchase) >= 2"
                                                    class="w-4 h-4 text-white" 
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <span 
                                                :class="[
                                                    'text-xs',
                                                    getProgressStage(purchase) >= 2 
                                                        ? 'text-yellow-700 dark:text-yellow-300 font-medium' 
                                                        : 'text-gray-500 dark:text-gray-400'
                                                ]"
                                            >
                                                {{ $t('profile.purchases.progress.processing') }}
                                            </span>
                                        </div>
                                        <!-- Линия -->
                                        <div 
                                            :class="[
                                                'flex-1 h-0.5',
                                                getProgressStage(purchase) >= 3 
                                                    ? 'bg-yellow-300 dark:bg-yellow-700' 
                                                    : 'bg-gray-300 dark:bg-gray-600'
                                            ]"
                                        ></div>
                                        <!-- Этап 3: Готов -->
                                        <div class="flex items-center gap-1.5 flex-1">
                                            <div 
                                                :class="[
                                                    'w-6 h-6 rounded-full flex items-center justify-center shrink-0',
                                                    getProgressStage(purchase) >= 3 
                                                        ? 'bg-green-500 dark:bg-green-600' 
                                                        : 'bg-gray-300 dark:bg-gray-600'
                                                ]"
                                            >
                                                <svg 
                                                    :class="[
                                                        'w-4 h-4',
                                                        getProgressStage(purchase) >= 3 
                                                            ? 'text-white' 
                                                            : 'text-gray-500 dark:text-gray-400'
                                                    ]"
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <span 
                                                :class="[
                                                    'text-xs',
                                                    getProgressStage(purchase) >= 3 
                                                        ? 'text-green-700 dark:text-green-300' 
                                                        : 'text-gray-500 dark:text-gray-400'
                                                ]"
                                            >
                                                {{ $t('profile.purchases.progress.ready') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Индикация времени обработки -->
                                <p class="text-xs text-yellow-600 dark:text-yellow-500 mb-2">
                                    {{ $t('profile.purchases.processing_since', { date: formatDate(purchase.created_at) }) }}
                                </p>
                                
                                <!-- Таймер обработки -->
                                <p class="text-xs text-yellow-600 dark:text-yellow-500 mb-3">
                                    {{ getProcessingDuration(purchase) }}
                                </p>
                                
                                <!-- Кнопки действий -->
                                <div class="flex gap-2 flex-wrap">
                                    <!-- Кнопка связи с менеджером -->
                                    <button
                                        class="px-4 py-2 text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors font-medium flex items-center gap-2"
                                        @click="contactManagerAboutOrder(purchase)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        {{ $t('profile.purchases.contact_manager') }}
                                    </button>
                                    <!-- Кнопка отмены заказа -->
                                    <button
                                        class="px-4 py-2 text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors font-medium"
                                        @click="openCancelModal(purchase)"
                                    >
                                        {{ $t('profile.purchases.cancel_order') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Данные аккаунтов -->
                    <div
                        v-if="purchase.account_data && purchase.account_data.length > 0"
                        class="space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <svg
                                    class="w-5 h-5 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $t('order_success.access_data') }}:
                                </h4>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{
                                    $t('order_success.accounts_shown', {
                                        shown: isPurchaseExpanded(purchase.id)
                                            ? purchase.account_data.length
                                            : Math.min(5, purchase.account_data.length),
                                        total: purchase.account_data.length
                                    })
                                }}
                            </span>
                        </div>

                        <!-- Заметки администратора (если есть) -->
                        <div
                            v-if="purchase.processing_notes && purchase.status === 'completed'"
                            class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg"
                        >
                            <div class="flex items-start gap-3">
                                <svg
                                    class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                                <div class="flex-1">
                                    <h5 class="font-semibold text-blue-900 dark:text-blue-200 mb-1">
                                        {{ $t('profile.purchases.admin_notes') }}
                                    </h5>
                                    <p class="text-sm text-blue-800 dark:text-blue-300 whitespace-pre-wrap">
                                        {{ purchase.processing_notes }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-for="(accountItem, index) in getVisibleAccounts(purchase)"
                            :key="index"
                            class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div
                                        class="font-mono text-sm text-gray-900 dark:text-white whitespace-pre-wrap break-all"
                                    >
                                        {{ formatAccountData(accountItem) }}
                                    </div>
                                </div>
                                <button
                                    class="shrink-0 p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200"
                                    :title="$t('profile.purchases.copy')"
                                    @click="copyToClipboard(formatAccountData(accountItem))"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </button>
                                <button
                                    class="shrink-0 p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200"
                                    :title="$t('profile.purchases.download')"
                                    @click="downloadSingleAccount(purchase, accountItem, index)"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Кнопка "Показать все" если аккаунтов больше 5 -->
                        <button
                            v-if="purchase.account_data.length > 5"
                            class="w-full mt-3 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2"
                            @click="toggleExpandPurchase(purchase.id)"
                        >
                            <svg
                                class="w-5 h-5 transition-transform"
                                :class="{ 'rotate-180': isPurchaseExpanded(purchase.id) }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                ></path>
                            </svg>
                            <span v-if="isPurchaseExpanded(purchase.id)">
                                {{
                                    $t('order_success.hide_accounts', {
                                        count: purchase.account_data.length - 5
                                    })
                                }}
                            </span>
                            <span v-else>
                                {{
                                    $t('order_success.show_all_accounts', {
                                        total: purchase.account_data.length,
                                        more: purchase.account_data.length - 5
                                    })
                                }}
                            </span>
                        </button>

                        <!-- Кнопка скачать все -->
                        <button
                            v-if="purchase.account_data.length > 1"
                            class="w-full mt-3 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-300 flex items-center justify-center gap-2"
                            @click="downloadAllAccounts(purchase)"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                ></path>
                            </svg>
                            {{
                                $t('order_success.download_all_accounts', {
                                    count: purchase.account_data.length
                                })
                            }}
                        </button>
                    </div>

                    <!-- Если нет данных -->
                    <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
                        <svg
                            class="w-12 h-12 mx-auto mb-2 opacity-50"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                        <p>{{ $t('order_success.no_data') }}</p>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="text-center py-12">
                <div class="empty-state-card">
                    <svg
                        class="w-16 h-16 mx-auto mb-4 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        ></path>
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400 text-lg mb-4">
                        {{ $t('order_success.loading') }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
                        {{ $t('order_success.loading_hint') }}
                    </p>

                    <button
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-300 inline-flex items-center gap-2"
                        @click="fetchPurchases"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            ></path>
                        </svg>
                        {{ $t('order_success.refresh') }}
                    </button>
                </div>
            </div>

            <!-- Кнопки навигации -->
            <div class="mt-8 flex gap-4 justify-center">
                <router-link
                    to="/profile"
                    class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg font-medium hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700"
                >
                    {{ $t('order_success.go_to_profile') }}
                </router-link>
                <router-link
                    to="/"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-300"
                >
                    {{ $t('order_success.back_to_home') }}
                </router-link>
            </div>
        </div>
        
        <!-- Модальное окно отмены заказа -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showCancelModal"
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/30 backdrop-blur-sm p-4"
                    @click.self="closeCancelModal"
                >
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6 relative"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('profile.purchases.cancel_order') }}
                        </h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                            {{ $t('profile.purchases.cancel_order_confirm') }}
                        </p>
                        <div class="mb-4">
                            <label for="cancellationReason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('profile.purchases.cancel_reason_label') }}
                            </label>
                            <textarea
                                id="cancellationReason"
                                v-model="cancellationReason"
                                rows="4"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                :placeholder="$t('profile.purchases.cancel_reason_placeholder')"
                                minlength="10"
                                maxlength="500"
                            ></textarea>
                            <p v-if="cancellationReasonError" class="text-red-500 text-xs mt-1">{{ cancellationReasonError }}</p>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                @click="closeCancelModal"
                            >
                                {{ $t('profile.purchases.cancel_modal_close') }}
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!cancellationReason || cancellationReason.length < 10"
                                @click="confirmCancelOrder"
                            >
                                {{ $t('profile.purchases.cancel_order_confirm_button') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, Teleport, Transition } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import axios from '@/bootstrap'; // Используем настроенный axios из bootstrap
import { useProductTitle } from '@/composables/useProductTitle';
import { useLoadingStore } from '@/stores/loading';

const router = useRouter();
const toast = useToast();
const { t } = useI18n();
const { getProductTitle } = useProductTitle();
const loadingStore = useLoadingStore();

const purchases = ref([]);
const loading = ref(true);
const expandedPurchases = ref(new Set()); // Отслеживаем раскрытые покупки
const pollingInterval = ref(null); // Интервал для опроса
const pollingAttempts = ref(0); // Счетчик попыток
const maxPollingAttempts = 12; // Максимум 12 попыток (60 секунд при интервале 5 сек)
const statusPollingInterval = ref(null); // Интервал для обновления статуса заказов в обработке

// Проверяем, есть ли сообщение о подготовке товара
const isPreparingProduct = computed(() => {
    const msg = loadingStore.message;
    return msg && (
        msg.includes('Подготовка') || 
        msg.includes('Preparing') ||
        msg.includes('Підготовка')
    );
});

// Вычисляемое свойство для покупок (убран фильтр времени)
const recentPurchases = computed(() => {
    // Возвращаем все покупки, отсортированные по дате (новые первые)
    return purchases.value;
});

// Проверяем, раскрыта ли покупка полностью
const isPurchaseExpanded = purchaseId => {
    return expandedPurchases.value.has(purchaseId);
};

// Переключаем состояние раскрытия покупки
const toggleExpandPurchase = purchaseId => {
    if (expandedPurchases.value.has(purchaseId)) {
        expandedPurchases.value.delete(purchaseId);
    } else {
        expandedPurchases.value.add(purchaseId);
    }
    // Принудительно обновляем реактивность
    expandedPurchases.value = new Set(expandedPurchases.value);
};

// Получаем список аккаунтов для отображения (первые 5 или все)
const getVisibleAccounts = purchase => {
    const maxVisible = 5;
    if (isPurchaseExpanded(purchase.id) || purchase.account_data.length <= maxVisible) {
        return purchase.account_data;
    }
    return purchase.account_data.slice(0, maxVisible);
};

// Загрузка покупок при монтировании
onMounted(async () => {
    // Если нет сообщения о подготовке, но мы только что пришли с checkout,
    // показываем сообщение "Подготовка товара к выдаче"
    // НО не запускаем прелоадер, если он уже запущен (чтобы не увеличивать activeRequests)
    if (!isPreparingProduct.value && !loadingStore.isLoading) {
        loadingStore.start(t('checkout.preparing_product'));
    }
    
    await fetchPurchases();

    // Запускаем опрос, если покупок нет (webhook может задержаться)
    startPolling();
});

// Запуск опроса для ожидания заказов
const startPolling = () => {
    if (purchases.value.length > 0) {
        return; // Уже есть покупки, не нужно опрашивать
    }

    pollingAttempts.value = 0;
    
    pollingInterval.value = setInterval(async () => {
        pollingAttempts.value++;
        
        // Если превышен лимит попыток, останавливаем опрос
        if (pollingAttempts.value >= maxPollingAttempts) {
            stopPolling();
            return;
        }

        // Если появились покупки, останавливаем опрос
        await fetchPurchases();
        if (purchases.value.length > 0) {
            stopPolling();
            // Запускаем обновление статуса для заказов в обработке
            startStatusPolling();
        }
    }, 5000); // Опрашиваем каждые 5 секунд
};

// Запуск опроса для обновления статуса заказов в обработке
const startStatusPolling = () => {
    // Проверяем, есть ли заказы в обработке
    const hasProcessingOrders = purchases.value.some(p => p.status === 'processing');
    if (!hasProcessingOrders) {
        return; // Нет заказов в обработке, не нужно опрашивать
    }

    // Останавливаем предыдущий интервал, если он есть
    stopStatusPolling();
    
    statusPollingInterval.value = setInterval(async () => {
        // Проверяем, есть ли еще заказы в обработке
        const hasProcessing = purchases.value.some(p => p.status === 'processing');
        if (!hasProcessing) {
            stopStatusPolling();
            return;
        }

        // Обновляем статусы заказов
        await fetchPurchases();
    }, 10000); // Опрашиваем каждые 10 секунд
};

// Остановка опроса
const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

// Остановка опроса статуса
const stopStatusPolling = () => {
    if (statusPollingInterval.value) {
        clearInterval(statusPollingInterval.value);
        statusPollingInterval.value = null;
    }
};

// Очистка при размонтировании
onBeforeUnmount(() => {
    stopPolling();
    stopStatusPolling();
});

const fetchPurchases = async () => {
    try {
        loading.value = true;

        // Если нет сообщения о подготовке, показываем сообщение "Подготовка товара к выдаче"
        // Это более понятно для пользователя, чем просто "Загрузка..."
        if (!isPreparingProduct.value) {
            loadingStore.start(t('checkout.preparing_product'));
        }

        // ИСПРАВЛЕНИЕ: Используем authStore вместо прямого доступа к localStorage
        const { useAuthStore } = await import('@/stores/auth');
        const authStore = useAuthStore();
        const token = authStore.token;

        console.log('🔍 Fetching purchases...', {
            url: '/purchases',
            hasToken: !!token,
            tokenStart: token ? token.substring(0, 20) + '...' : 'нет',
            authStoreUser: authStore.user?.email
        });

        if (!token) {
            toast.error(t('order_success.not_authorized'));
            await router.push('/login');
            return;
        }

        const response = await axios.get('/purchases', {
            headers: {
                Authorization: `Bearer ${token}`
            }
        });

        console.log('✅ Response received:', {
            status: response.status,
            success: response.data.success,
            purchasesCount: response.data.purchases?.length || 0,
            data: response.data
        });

        if (response.data.success) {
            purchases.value = response.data.purchases;
            console.log('✅ Purchases set:', purchases.value.length);
            
            // Если товар выдан (есть покупки), скрываем ВСЕ прелоадеры немедленно
            if (purchases.value.length > 0) {
                loading.value = false;
                // Используем reset() для гарантированного скрытия прелоадера
                // независимо от количества activeRequests
                loadingStore.reset();
                console.log('✅ Preloaders hidden, purchases loaded');
                
                // Запускаем обновление статуса для заказов в обработке
                startStatusPolling();
                
                // Проверяем, есть ли заказы, которые только что завершились
                const completedOrders = purchases.value.filter(p => p.status === 'completed');
                if (completedOrders.length > 0) {
                    // Показываем уведомление о завершении обработки
                    completedOrders.forEach(purchase => {
                        toast.success(
                            t('profile.purchases.order_completed_notification', {
                                order_number: purchase.order_number
                            })
                        );
                    });
                }
                
                return;
            }
        } else {
            console.warn('⚠️ Response success=false');
            loading.value = false;
            loadingStore.reset();
        }
    } catch (error) {
        console.error('❌ Failed to fetch purchases:', {
            message: error.message,
            response: error.response?.data,
            status: error.response?.status
        });
        toast.error(
            t('order_success.load_error') + ': ' + (error.response?.data?.message || error.message)
        );
        // При ошибке скрываем прелоадеры
        loading.value = false;
        loadingStore.reset();
    } finally {
        // Гарантируем скрытие, если товар выдан (на случай, если мы не вышли через return)
        if (purchases.value && purchases.value.length > 0) {
            loading.value = false;
            loadingStore.reset();
        }
    }
};

const formatAccountData = accountItem => {
    if (typeof accountItem === 'string') {
        return accountItem;
    }
    if (typeof accountItem === 'object') {
        return Object.entries(accountItem)
            .map(([key, value]) => `${key}: ${value}`)
            .join('\n');
    }
    return String(accountItem);
};

const formatDate = dateString => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU');
};

const copyToClipboard = async text => {
    try {
        await navigator.clipboard.writeText(text);
        toast.success(t('profile.purchases.copy_success'));
    } catch (error) {
        console.error('Failed to copy:', error);
        toast.error(t('profile.purchases.copy_error'));
    }
};

const downloadAsText = (content, filename) => {
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    toast.success(t('profile.purchases.download_success'));
};

const downloadSingleAccount = (purchase, accountItem, index) => {
    const orderNumber = purchase.order_number || `ID${purchase.id}`;
    const productTitle =
        getProductTitle(purchase.product.title || {}) || t('profile.purchases.unknown');
    const header = `======================================
${t('profile.purchases.download_labels.order')}: ${orderNumber}
${t('profile.purchases.download_labels.product')}: ${productTitle}
${t('profile.purchases.download_labels.date')}: ${formatDate(purchase.purchased_at)}
${t('profile.purchases.download_labels.account')}: ${index + 1}
======================================\n\n`;

    const content = formatAccountData(accountItem);
    const filename = `ORDER_${orderNumber}_${index + 1}.txt`;
    downloadAsText(header + content, filename);
};

const downloadAllAccounts = purchase => {
    const orderNumber = purchase.order_number || `ID${purchase.id}`;
    const productTitle =
        getProductTitle(purchase.product.title || {}) || t('profile.purchases.unknown');

    // Заголовок с информацией о заказе
    const header = `======================================
${t('profile.purchases.download_labels.order')}: ${orderNumber}
${t('profile.purchases.download_labels.product')}: ${productTitle}
${t('profile.purchases.download_labels.date')}: ${formatDate(purchase.purchased_at)}
${t('profile.purchases.download_labels.quantity')}: ${purchase.account_data.length} ${t('profile.purchases.quantity_unit')}
======================================\n\n`;

    const allData = purchase.account_data
        .map(
            (item, index) =>
                `=== ${t('profile.purchases.account')} ${index + 1} ===\n${formatAccountData(item)}`
        )
        .join('\n\n');

    downloadAsText(header + allData, `ORDER_${orderNumber}_${productTitle}.txt`);
};

// Получить этап прогресса заказа
const getProgressStage = (purchase) => {
    if (purchase.status === 'completed') return 3;
    if (purchase.status === 'processing') return 2;
    return 1;
};

// Получить длительность обработки заказа
const getProcessingDuration = (purchase) => {
    if (purchase.status !== 'processing') return '';
    
    const now = new Date();
    const created = new Date(purchase.created_at);
    const diffMs = now - created;
    const hours = Math.floor(diffMs / (1000 * 60 * 60));
    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours >= 24) {
        const days = Math.floor(hours / 24);
        const remainingHours = hours % 24;
        return t('profile.purchases.processing_duration_days', { days, hours: remainingHours });
    }
    return t('profile.purchases.processing_duration_hours', { hours, minutes });
};

// Модальное окно отмены заказа
const showCancelModal = ref(false);
const selectedPurchaseForCancel = ref(null);
const cancellationReason = ref('');
const cancellationReasonError = ref('');

// Открытие модального окна отмены заказа
const openCancelModal = (purchase) => {
    selectedPurchaseForCancel.value = purchase;
    cancellationReason.value = '';
    cancellationReasonError.value = '';
    showCancelModal.value = true;
};

// Закрытие модального окна отмены
const closeCancelModal = () => {
    showCancelModal.value = false;
    selectedPurchaseForCancel.value = null;
    cancellationReason.value = '';
    cancellationReasonError.value = '';
};

// Подтверждение отмены заказа
const confirmCancelOrder = async () => {
    if (!selectedPurchaseForCancel.value) return;
    
    // Валидация причины отмены
    if (!cancellationReason.value || cancellationReason.value.trim().length < 10) {
        cancellationReasonError.value = t('profile.purchases.cancel_reason_min_length');
        return;
    }
    
    if (cancellationReason.value.length > 500) {
        cancellationReasonError.value = t('profile.purchases.cancel_reason_max_length');
        return;
    }
    
    cancellationReasonError.value = '';
    
    try {
        const { useAuthStore } = await import('@/stores/auth');
        const authStore = useAuthStore();
        const token = authStore.token;
        
        if (!token) {
            toast.error(t('profile.purchases.not_authorized'));
            await router.push('/login');
            return;
        }
        
        const response = await axios.post(
            `/purchases/${selectedPurchaseForCancel.value.id}/cancel`,
            {
                cancellation_reason: cancellationReason.value.trim()
            },
            {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            }
        );
        
        if (response.data.success) {
            toast.success(t('profile.purchases.order_cancelled'));
            closeCancelModal();
            // Обновляем список покупок
            await fetchPurchases();
        } else {
            toast.error(response.data.message || t('profile.purchases.cancel_order_error'));
        }
    } catch (error) {
        console.error('Error cancelling order:', error);
        toast.error(error.response?.data?.message || t('profile.purchases.cancel_order_error'));
    }
};

// Связаться с менеджером о заказе
const contactManagerAboutOrder = async (purchase) => {
    try {
        // Пытаемся открыть чат поддержки
        const event = new CustomEvent('openSupportChat', {
            detail: {
                subject: `Вопрос по заказу #${purchase.order_number}`,
                message: `Здравствуйте! У меня вопрос по заказу #${purchase.order_number} на товар "${getProductTitle(purchase.product?.title || {})}".`
            }
        });
        window.dispatchEvent(event);
        
        // Если чат не открылся, показываем сообщение
        setTimeout(() => {
            toast.info(t('profile.purchases.contact_manager_hint'));
        }, 500);
    } catch (error) {
        console.error('Error opening support chat:', error);
        toast.error(t('profile.purchases.contact_manager_error'));
    }
};
</script>

<style scoped>
/* Purchase Card Styles - Улучшенный контраст */
.purchase-card {
    background: #f9fafb;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.dark .purchase-card {
    background: #1f2937;
    border-color: #4b5563;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.purchase-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
    border-color: #9ca3af;
}

.dark .purchase-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    border-color: #6b7280;
}

/* Empty State Card Styles */
.empty-state-card {
    background: #f9fafb;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    padding: 32px;
    max-width: 28rem;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.dark .empty-state-card {
    background: #1f2937;
    border-color: #4b5563;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
</style>
