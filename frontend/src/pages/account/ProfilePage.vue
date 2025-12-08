<template>
    <div class="w-full lg:w-3/4 xl:w-2/3 mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-light text-gray-900 dark:text-white mt-3">
                {{ $t('profile.title') }}
            </h1>
        </div>

        <!-- Balance Card -->
        <div class="balance-card mb-8">
            <div class="balance-content">
                <div class="balance-icon">
                    <svg
                        class="w-8 h-8 text-purple-600 dark:text-purple-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </div>
                <div class="balance-info">
                    <p class="balance-label">{{ $t('profile.balance') }}</p>
                    <p class="balance-amount">{{ formatBalance(authStore.user?.balance || 0) }}</p>
                </div>
                <div class="balance-action">
                    <button class="topup-button" @click="router.push('/balance/topup')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('profile.topup') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Voucher Activation Card -->
        <div class="voucher-card mb-8">
            <div class="voucher-header">
                <svg
                    class="w-6 h-6 text-green-600 dark:text-green-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                    />
                </svg>
                <h3 class="voucher-title">{{ $t('profile.voucher.title') }}</h3>
            </div>
            <p class="voucher-description">{{ $t('profile.voucher.description') }}</p>

            <form class="voucher-form" @submit.prevent="activateVoucher">
                <div class="voucher-input-group">
                    <input
                        v-model="voucherCode"
                        type="text"
                        class="voucher-input"
                        :placeholder="$t('profile.voucher.placeholder')"
                        :disabled="voucherLoading"
                        maxlength="20"
                    />
                    <button
                        type="submit"
                        class="voucher-button"
                        :disabled="voucherLoading || !voucherCode.trim()"
                    >
                        <span v-if="!voucherLoading">{{ $t('profile.voucher.activate') }}</span>
                        <span v-else>{{ $t('profile.voucher.activating') }}</span>
                    </button>
                </div>

                <p v-if="voucherError" class="voucher-error">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ voucherError }}
                </p>

                <p v-if="voucherSuccess" class="voucher-success">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ voucherSuccess }}
                </p>
            </form>
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="space-y-2">
                <label class="text-sm text-gray-700 dark:text-gray-300" for="name">{{
                    $t('profile.name')
                }}</label>
                <div class="relative">
                    <User
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="name"
                        v-model="name"
                        type="text"
                        class="w-full pl-10 pr-4 py-2 border rounded-lg dark:!border-gray-500 dark:text-gray-300"
                        :placeholder="$t('profile.namePlaceholder')"
                        required
                    />
                </div>
                <p v-if="errors.name" class="text-red-500 text-sm">
                    {{ errors.name[0] }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm text-gray-700 dark:text-gray-300" for="email">{{
                    $t('profile.email')
                }}</label>
                <div class="relative">
                    <Mail
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        class="w-full pl-10 pr-4 py-2 border dark:!border-gray-500 rounded-lg dark:text-gray-300"
                        :placeholder="$t('profile.emailPlaceholder')"
                        required
                    />
                </div>
                <p v-if="errors.email" class="text-red-500 text-sm">
                    {{ errors.email[0] }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm text-gray-700 dark:text-gray-300" for="password">{{
                    $t('profile.password')
                }}</label>
                <div class="relative">
                    <Lock
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2 border dark:!border-gray-500 rounded-lg dark:text-gray-300"
                        :placeholder="$t('profile.passwordPlaceholder')"
                    />
                </div>
                <p v-if="errors.password" class="text-red-500 text-sm">
                    {{ errors.password[0] }}
                </p>
            </div>

            <div class="space-y-2">
                <label
                    class="text-sm text-gray-700 dark:text-gray-300"
                    for="password_confirmation"
                    >{{ $t('profile.confirmPassword') }}</label
                >
                <div class="relative">
                    <Lock
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="password_confirmation"
                        v-model="password_confirmation"
                        type="password"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2 border dark:!border-gray-500 rounded-lg dark:text-gray-300"
                        :placeholder="$t('profile.confirmPasswordPlaceholder')"
                    />
                </div>
            </div>

            <button
                type="submit"
                class="w-full text-white py-2 rounded-lg bg-blue-500 dark:bg-blue-900 hover:bg-blue-600 dark:hover:bg-blue-800"
            >
                {{ $t('profile.saveButton') }}
            </button>
        </form>

        <!-- Disputes Section (Мои претензии) -->
        <div class="disputes-section mt-12">
            <h2
                class="text-2xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-3"
            >
                <svg
                    class="w-7 h-7 text-purple-600 dark:text-purple-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
                {{ $t('profile.purchases.disputes.my_disputes') }}
            </h2>

            <div v-if="loadingDisputes" class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $t('profile.purchases.disputes.loading') }}
                </p>
            </div>

            <div
                v-else-if="disputes.length === 0"
                class="text-center py-8 bg-gray-50 dark:bg-gray-800/50 rounded-2xl"
            >
                <svg
                    class="w-16 h-16 mx-auto text-gray-400 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    {{ $t('profile.purchases.disputes.no_disputes') }}
                </p>
            </div>

            <div v-else class="space-y-4 mb-12">
                <div v-for="dispute in disputes" :key="dispute.id" class="dispute-card">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{
                                    $t('profile.purchases.disputes.dispute_number', {
                                        id: dispute.id
                                    })
                                }}</span>
                                <span
                                    :class="getDisputeStatusClass(dispute.status)"
                                    class="px-2 py-1 text-xs rounded-lg font-semibold"
                                >
                                    {{ dispute.status_text }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                <strong>{{ $t('profile.purchases.disputes.product') }}:</strong>
                                {{
                                    dispute.product_title
                                        ? getProductTitle(dispute.product_title)
                                        : $t('profile.purchases.disputes.deleted')
                                }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                <strong>{{ $t('profile.purchases.disputes.reason') }}:</strong>
                                {{ dispute.reason_text }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                {{ formatDate(dispute.created_at) }}
                            </p>
                        </div>
                        <button
                            class="px-4 py-2 bg-blue-500 dark:bg-blue-900 text-white text-sm rounded-lg hover:bg-blue-600 dark:hover:bg-blue-800 transition-colors"
                            @click="toggleDisputeDetails(dispute.id)"
                        >
                            {{
                                expandedDisputes.has(dispute.id)
                                    ? $t('profile.purchases.disputes.hide')
                                    : $t('profile.purchases.disputes.view_details')
                            }}
                        </button>
                    </div>

                    <!-- Детали претензии (раскрывающийся блок) -->
                    <Transition name="expand">
                        <div
                            v-if="expandedDisputes.has(dispute.id)"
                            class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3"
                        >
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('profile.purchases.disputes.your_description') }}:
                                </p>
                                <p
                                    class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg"
                                >
                                    {{ dispute.customer_description }}
                                </p>
                            </div>

                            <div v-if="dispute.screenshot_url">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('profile.purchases.disputes.screenshot') }}:
                                </p>
                                <a
                                    :href="dispute.screenshot_url"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    {{ $t('profile.purchases.disputes.open_screenshot') }}
                                </a>
                            </div>

                            <!-- Решение администратора -->
                            <div
                                v-if="
                                    dispute.status === 'resolved' || dispute.status === 'rejected'
                                "
                                class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-4 rounded-xl border border-blue-200 dark:border-blue-800"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0"
                                    >
                                        <svg
                                            class="w-6 h-6 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p
                                            class="text-sm font-semibold text-gray-900 dark:text-white mb-2"
                                        >
                                            {{
                                                $t(
                                                    'profile.purchases.disputes.admin_decision_title'
                                                )
                                            }}:
                                        </p>
                                        <div class="space-y-2">
                                            <div v-if="dispute.admin_decision">
                                                <span
                                                    class="text-xs text-gray-600 dark:text-gray-400"
                                                    >{{
                                                        $t(
                                                            'profile.purchases.disputes.decision.' +
                                                                dispute.admin_decision
                                                        ) ||
                                                        $t(
                                                            'profile.purchases.disputes.admin_decision_title'
                                                        )
                                                    }}:</span
                                                >
                                                <span
                                                    class="ml-2 text-sm font-bold"
                                                    :class="
                                                        getDecisionColor(dispute.admin_decision)
                                                    "
                                                >
                                                    {{ dispute.admin_decision_text }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="
                                                    dispute.refund_amount &&
                                                    dispute.admin_decision === 'refund'
                                                "
                                            >
                                                <span
                                                    class="text-xs text-gray-600 dark:text-gray-400"
                                                    >{{
                                                        $t(
                                                            'profile.purchases.disputes.refund_amount'
                                                        )
                                                    }}:</span
                                                >
                                                <span
                                                    class="ml-2 text-sm font-bold text-green-600 dark:text-green-400"
                                                >
                                                    {{ formatAmount(dispute.refund_amount) }}
                                                </span>
                                            </div>
                                            <div v-if="dispute.admin_comment">
                                                <p
                                                    class="text-xs text-gray-600 dark:text-gray-400 mb-1"
                                                >
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.admin_comment'
                                                        )
                                                    }}:
                                                </p>
                                                <p
                                                    class="text-sm text-gray-700 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
                                                >
                                                    {{ dispute.admin_comment }}
                                                </p>
                                            </div>
                                            <div v-if="dispute.resolved_at">
                                                <span
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    {{ $t('profile.purchases.disputes.resolved') }}:
                                                    {{ formatDate(dispute.resolved_at) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Purchases Section -->
        <div class="purchases-section mt-12">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                {{ $t('profile.purchases.title') }}
            </h2>

            <!-- Filters -->
            <div class="filters-card mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            {{ $t('profile.purchases.filters.date_from') }}
                        </label>
                        <input
                            v-model="filters.date_from"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            {{ $t('profile.purchases.filters.date_to') }}
                        </label>
                        <input
                            v-model="filters.date_to"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            {{ $t('profile.purchases.filters.status') }}
                        </label>
                        <select
                            v-model="filters.status"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">{{ $t('profile.purchases.filters.all') }}</option>
                            <option value="completed">
                                {{ $t('profile.purchases.statuses.completed') }}
                            </option>
                            <option value="pending">
                                {{ $t('profile.purchases.statuses.pending') }}
                            </option>
                            <option value="failed">
                                {{ $t('profile.purchases.statuses.failed') }}
                            </option>
                            <option value="refunded">
                                {{ $t('profile.purchases.statuses.refunded') }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button
                        class="px-6 py-2 bg-blue-500 dark:bg-blue-900 text-white rounded-lg hover:bg-blue-600 dark:hover:bg-blue-800 transition"
                        @click="applyFilters"
                    >
                        {{ $t('profile.purchases.filters.apply') }}
                    </button>
                    <button
                        class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                        @click="resetFilters"
                    >
                        {{ $t('profile.purchases.filters.reset') }}
                    </button>
                </div>
            </div>

            <div v-if="loadingPurchases" class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $t('profile.purchases.loading') }}
                </p>
            </div>

            <div v-else-if="filteredPurchases.length === 0" class="text-center py-8">
                <svg
                    class="w-16 h-16 mx-auto text-gray-400 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                    />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    {{ $t('profile.purchases.no_purchases') }}
                </p>
            </div>

            <div v-else class="space-y-3">
                <div v-for="purchase in filteredPurchases" :key="purchase.id" class="purchase-card">
                    <!-- Компактный заголовок в одну строку -->
                    <div class="purchase-compact-header">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="purchase-id"
                                >#{{ purchase.order_number || purchase.id }}</span
                            >
                            <span v-if="purchase.service_name" class="purchase-service-name">{{
                                getProductTitle(purchase.service_name)
                            }}</span>
                            <span v-if="purchase.quantity" class="purchase-quantity-badge"
                                >{{ purchase.quantity }}
                                {{ $t('profile.purchases.quantity_unit') }}</span
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="purchase-amount">
                                {{ formatAmount(purchase.amount, purchase.currency) }}
                            </div>
                            <span
                                :class="getStatusClass(purchase.status)"
                                class="purchase-status"
                                >{{ formatStatus(purchase.status) }}</span
                            >
                        </div>
                    </div>

                    <!-- Компактный футер с датой и методом -->
                    <div class="purchase-compact-footer">
                        <span class="purchase-method">{{
                            formatPaymentMethod(purchase.payment_method)
                        }}</span>
                        <span class="purchase-date">{{ formatDate(purchase.created_at) }}</span>
                    </div>

                    <!-- Данные аккаунтов (если есть) - компактная кнопка -->
                    <div
                        v-if="purchase.account_data && purchase.account_data.length > 0"
                        class="border-t border-gray-200 dark:border-gray-700 mt-2 pt-2"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <button
                                class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                                @click="togglePurchaseDetails(purchase.id)"
                            >
                                <svg
                                    class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-90': expandedPurchases.has(purchase.id) }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    ></path>
                                </svg>
                                <span v-if="expandedPurchases.has(purchase.id)">{{
                                    $t('profile.purchases.hide_data')
                                }}</span>
                                <span v-else
                                    >{{ $t('profile.purchases.show_data') }} ({{
                                        purchase.account_data.length
                                    }})</span
                                >
                            </button>

                            <!-- Кнопка создания претензии -->
                            <button
                                v-if="canCreateDispute(purchase)"
                                class="flex items-center gap-1.5 px-4 py-2 text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/30 bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800 rounded-lg transition-all font-medium shadow-sm hover:shadow"
                                :title="$t('profile.purchases.disputes.create_button')"
                                @click="openDisputeModal(purchase)"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">{{
                                    $t('profile.purchases.disputes.create_button')
                                }}</span>
                            </button>

                            <!-- Индикатор существующей претензии -->
                            <div
                                v-else-if="purchase.has_dispute"
                                class="flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg"
                                :class="getDisputeStatusClass(purchase.dispute?.status)"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">{{
                                    getDisputeStatusText(purchase.dispute?.status)
                                }}</span>
                            </div>
                        </div>

                        <!-- Раскрывающийся блок с данными - компактная версия -->
                        <div v-if="expandedPurchases.has(purchase.id)" class="space-y-2 mt-2">
                            <div
                                v-for="(accountItem, index) in purchase.account_data"
                                :key="index"
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <div
                                            class="font-mono text-xs text-gray-900 dark:text-white whitespace-pre-wrap break-all"
                                        >
                                            {{ formatAccountData(accountItem) }}
                                        </div>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button
                                            class="p-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200"
                                            :title="$t('profile.purchases.copy') || 'Копировать'"
                                            @click="copyToClipboard(formatAccountData(accountItem))"
                                        >
                                            <svg
                                                class="w-4 h-4"
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
                                            class="p-1.5 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors duration-200"
                                            :title="$t('profile.purchases.download') || 'Скачать'"
                                            @click="
                                                downloadSingleAccount(purchase, accountItem, index)
                                            "
                                        >
                                            <svg
                                                class="w-4 h-4"
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
                            </div>

                            <!-- Кнопка "Скачать все" если больше 1 аккаунта - компактная -->
                            <button
                                v-if="purchase.account_data.length > 1"
                                class="w-full mt-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg text-sm font-medium transition-all duration-300 flex items-center justify-center gap-2"
                                @click="downloadAllAccounts(purchase)"
                            >
                                <svg
                                    class="w-4 h-4"
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
                                {{ $t('profile.purchases.download_all') }} ({{
                                    purchase.account_data.length
                                }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания претензии -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showDisputeModal"
                    class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/30 backdrop-blur-sm p-4"
                    @click.self="closeDisputeModal"
                >
                    <div
                        class="glass-morphism-dispute rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden relative"
                    >
                        <!-- Декоративные градиенты -->
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-blue-500/5 dark:from-gray-800/10 dark:to-purple-500/5 rounded-3xl pointer-events-none"
                        ></div>
                        <div
                            class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-blue-400/10 to-transparent rounded-full blur-3xl animate-float-gentle pointer-events-none"
                        ></div>
                        <div
                            class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-purple-400/10 to-transparent rounded-full blur-3xl animate-float-gentle pointer-events-none"
                            style="animation-delay: 2s"
                        ></div>

                        <!-- Контент модального окна -->
                        <div class="relative z-10 overflow-y-auto max-h-[90vh]">
                            <!-- Заголовок -->
                            <div
                                class="sticky top-0 backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 p-6 border-b border-gray-200/50 dark:border-gray-700/50 z-20"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg"
                                        >
                                            <svg
                                                class="w-6 h-6 text-white"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                                />
                                            </svg>
                                        </div>
                                        <h3
                                            class="text-2xl font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ $t('profile.purchases.disputes.create_title') }}
                                        </h3>
                                    </div>
                                    <button
                                        class="w-10 h-10 rounded-xl bg-gray-100/80 dark:bg-gray-700/80 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-all duration-200 flex items-center justify-center"
                                        @click="closeDisputeModal"
                                    >
                                        <svg
                                            class="w-6 h-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="p-6">
                                <!-- Информация о покупке -->
                                <div
                                    class="mb-6 p-5 bg-gradient-to-br from-gray-50/80 to-gray-100/50 dark:from-gray-700/30 dark:to-gray-800/20 rounded-2xl border border-gray-200/50 dark:border-gray-600/30 backdrop-blur-sm"
                                >
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg
                                            class="w-5 h-5 text-purple-600 dark:text-purple-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                            />
                                        </svg>
                                        <span
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                            >{{
                                                $t('profile.purchases.disputes.purchase_info')
                                            }}</span
                                        >
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400"
                                                >{{ $t('profile.purchases.order') }}:</span
                                            >
                                            <span class="font-bold text-gray-900 dark:text-white"
                                                >#{{ selectedPurchase?.order_number }}</span
                                            >
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400"
                                                >{{ $t('profile.purchases.amount') }}:</span
                                            >
                                            <span
                                                class="font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent"
                                                >{{ formatAmount(selectedPurchase?.amount) }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="selectedPurchase?.service_name"
                                            class="col-span-2 flex items-center gap-2"
                                        >
                                            <span class="text-gray-500 dark:text-gray-400"
                                                >{{
                                                    $t('profile.purchases.disputes.product')
                                                }}:</span
                                            >
                                            <span class="font-bold text-gray-900 dark:text-white">{{
                                                selectedPurchase?.service_name
                                                    ? getProductTitle(selectedPurchase.service_name)
                                                    : ''
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Форма -->
                                <form class="space-y-6" @submit.prevent="submitDispute">
                                    <!-- Причина -->
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3"
                                        >
                                            {{ $t('profile.purchases.disputes.reason') }}
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <select
                                                v-model="disputeForm.reason"
                                                class="dispute-select w-full px-4 pr-10 py-3.5 bg-white/50 dark:bg-gray-700/50 border border-gray-300/50 dark:border-gray-600/50 rounded-xl dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300 backdrop-blur-sm cursor-pointer"
                                                required
                                            >
                                                <option value="">
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.select_reason'
                                                        )
                                                    }}
                                                </option>
                                                <option value="invalid_account">
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.reasons.invalid_account'
                                                        )
                                                    }}
                                                </option>
                                                <option value="wrong_data">
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.reasons.wrong_data'
                                                        )
                                                    }}
                                                </option>
                                                <option value="not_working">
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.reasons.not_working'
                                                        )
                                                    }}
                                                </option>
                                                <option value="already_used">
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.reasons.already_used'
                                                        )
                                                    }}
                                                </option>
                                                <option value="banned">
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.reasons.banned'
                                                        )
                                                    }}
                                                </option>
                                                <option value="other">
                                                    {{
                                                        $t(
                                                            'profile.purchases.disputes.reasons.other'
                                                        )
                                                    }}
                                                </option>
                                            </select>
                                            <div
                                                class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                                    :class="{
                                                        'rotate-180': disputeForm.reason !== ''
                                                    }"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 9l-7 7-7-7"
                                                    />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Описание -->
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3"
                                        >
                                            {{ $t('profile.purchases.disputes.description') }}
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="disputeForm.description"
                                            class="w-full px-4 py-3.5 bg-white/50 dark:bg-gray-700/50 border border-gray-300/50 dark:border-gray-600/50 rounded-xl dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 resize-none transition-all backdrop-blur-sm"
                                            rows="4"
                                            :placeholder="
                                                $t(
                                                    'profile.purchases.disputes.description_placeholder'
                                                )
                                            "
                                            required
                                        ></textarea>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            {{ $t('profile.purchases.disputes.min_chars') }}
                                        </p>
                                    </div>

                                    <!-- Способ прикрепления скриншота -->
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3"
                                        >
                                            {{
                                                $t('profile.purchases.disputes.screenshot_problem')
                                            }}
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex gap-3 mb-4">
                                            <label
                                                class="flex-1 relative cursor-pointer"
                                                :class="
                                                    screenshotMethod === 'file'
                                                        ? 'screenshot-method-active'
                                                        : 'screenshot-method-inactive'
                                                "
                                            >
                                                <input
                                                    v-model="screenshotMethod"
                                                    type="radio"
                                                    value="file"
                                                    class="sr-only"
                                                />
                                                <div
                                                    class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 transition-all duration-200"
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
                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                        />
                                                    </svg>
                                                    <span class="text-sm font-medium">{{
                                                        $t('profile.purchases.disputes.upload_file')
                                                    }}</span>
                                                </div>
                                            </label>
                                            <label
                                                class="flex-1 relative cursor-pointer"
                                                :class="
                                                    screenshotMethod === 'link'
                                                        ? 'screenshot-method-active'
                                                        : 'screenshot-method-inactive'
                                                "
                                            >
                                                <input
                                                    v-model="screenshotMethod"
                                                    type="radio"
                                                    value="link"
                                                    class="sr-only"
                                                />
                                                <div
                                                    class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 transition-all duration-200"
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
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                                        />
                                                    </svg>
                                                    <span class="text-sm font-medium">{{
                                                        $t('profile.purchases.disputes.paste_link')
                                                    }}</span>
                                                </div>
                                            </label>
                                        </div>

                                        <!-- Загрузка файла -->
                                        <div v-if="screenshotMethod === 'file'">
                                            <div class="relative">
                                                <input
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                                    class="w-full px-4 py-3.5 bg-white/50 dark:bg-gray-700/50 border border-gray-300/50 dark:border-gray-600/50 rounded-xl dark:text-white backdrop-blur-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-blue-500 file:to-purple-600 file:text-white hover:file:from-blue-600 hover:file:to-purple-700 file:transition-all file:duration-200"
                                                    @change="handleFileUpload"
                                                />
                                            </div>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1"
                                            >
                                                <svg
                                                    class="w-3.5 h-3.5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                {{
                                                    $t(
                                                        'profile.purchases.disputes.file_requirements'
                                                    )
                                                }}
                                            </p>

                                            <!-- Предпросмотр -->
                                            <div v-if="screenshotPreview" class="mt-4">
                                                <div
                                                    class="relative rounded-xl overflow-hidden border-2 border-gray-200 dark:border-gray-600 shadow-lg"
                                                >
                                                    <img
                                                        :src="screenshotPreview"
                                                        :alt="
                                                            $t('profile.purchases.disputes.preview')
                                                        "
                                                        class="w-full max-h-64 object-contain bg-gray-50 dark:bg-gray-900"
                                                    />
                                                    <div
                                                        class="absolute top-2 right-2 px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-lg flex items-center gap-1"
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M5 13l4 4L19 7"
                                                            />
                                                        </svg>
                                                        {{
                                                            $t(
                                                                'profile.purchases.disputes.uploaded'
                                                            )
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ссылка на скриншот -->
                                        <div v-if="screenshotMethod === 'link'">
                                            <div class="relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                                                >
                                                    <svg
                                                        class="w-5 h-5 text-gray-400"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                                        />
                                                    </svg>
                                                </div>
                                                <input
                                                    v-model="disputeForm.screenshot_link"
                                                    type="url"
                                                    placeholder="https://i.imgur.com/example.png"
                                                    class="w-full pl-12 pr-4 py-3.5 bg-white/50 dark:bg-gray-700/50 border border-gray-300/50 dark:border-gray-600/50 rounded-xl dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all backdrop-blur-sm"
                                                />
                                            </div>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1"
                                            >
                                                <svg
                                                    class="w-3.5 h-3.5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                {{
                                                    $t(
                                                        'profile.purchases.disputes.link_instruction'
                                                    )
                                                }}
                                            </p>

                                            <!-- Предпросмотр по ссылке -->
                                            <div
                                                v-if="
                                                    disputeForm.screenshot_link &&
                                                    !screenshotLinkError
                                                "
                                                class="mt-4"
                                            >
                                                <div
                                                    class="relative rounded-xl overflow-hidden border-2 border-gray-200 dark:border-gray-600 shadow-lg"
                                                >
                                                    <img
                                                        :src="disputeForm.screenshot_link"
                                                        :alt="
                                                            $t('profile.purchases.disputes.preview')
                                                        "
                                                        class="w-full max-h-64 object-contain bg-gray-50 dark:bg-gray-900"
                                                        @error="screenshotLinkError = true"
                                                    />
                                                    <div
                                                        class="absolute top-2 right-2 px-2 py-1 bg-blue-500 text-white text-xs font-semibold rounded-lg flex items-center gap-1"
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M5 13l4 4L19 7"
                                                            />
                                                        </svg>
                                                        {{
                                                            $t(
                                                                'profile.purchases.disputes.link_valid'
                                                            )
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                v-if="screenshotLinkError"
                                                class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl flex items-start gap-2"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                <p class="text-sm text-red-600 dark:text-red-400">
                                                    {{
                                                        $t('profile.purchases.disputes.link_error')
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Кнопки -->
                                    <div class="flex gap-3 pt-6">
                                        <button
                                            type="submit"
                                            class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 hover:shadow-lg hover:shadow-purple-500/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none"
                                            :disabled="isSubmittingDispute"
                                        >
                                            <span
                                                v-if="!isSubmittingDispute"
                                                class="flex items-center justify-center gap-2"
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
                                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                                                    />
                                                </svg>
                                                {{ $t('profile.purchases.disputes.submit') }}
                                            </span>
                                            <span
                                                v-else
                                                class="flex items-center justify-center gap-2"
                                            >
                                                <svg
                                                    class="animate-spin h-5 w-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <circle
                                                        class="opacity-25"
                                                        cx="12"
                                                        cy="12"
                                                        r="10"
                                                        stroke="currentColor"
                                                        stroke-width="4"
                                                    ></circle>
                                                    <path
                                                        class="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                    ></path>
                                                </svg>
                                                {{ $t('profile.purchases.disputes.submitting') }}
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            class="flex-1 bg-gray-100/80 dark:bg-gray-800/80 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold py-4 px-6 rounded-xl transition-all duration-300 border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                            :disabled="isSubmittingDispute"
                                            @click="closeDisputeModal"
                                        >
                                            {{ $t('profile.purchases.disputes.cancel') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Lock, Mail, User } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import { useLoadingStore } from '@/stores/loading';
import axios from '@/bootstrap'; // Используем настроенный axios из bootstrap
import { useProductTitle } from '@/composables/useProductTitle';

const toast = useToast();
const { getProductTitle } = useProductTitle();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const loadingStore = useLoadingStore();
const email = ref(authStore.user.email ?? '');
const name = ref(authStore.user.name ?? '');
const password = ref('');
const password_confirmation = ref('');
type FormErrors = Record<string, string[]>;
const errors = ref<FormErrors>({});
const { t } = useI18n();

// Voucher
const voucherCode = ref('');
const voucherLoading = ref(false);
const voucherError = ref('');
const voucherSuccess = ref('');

// Purchases
const purchases = ref<any[]>([]);
const filteredPurchases = ref<any[]>([]);
const loadingPurchases = ref(false);
const expandedPurchases = ref<Set<number>>(new Set());

// Disputes (Претензии)
const disputes = ref<any[]>([]);
const loadingDisputes = ref(false);
const expandedDisputes = ref<Set<number>>(new Set());

// Filters
const filters = ref({
    date_from: '',
    date_to: '',
    status: ''
});

// Dispute modal
const showDisputeModal = ref(false);
const selectedPurchase = ref<any>(null);
const screenshotMethod = ref<'file' | 'link'>('file');
const screenshotFile = ref<File | null>(null);
const screenshotPreview = ref<string | null>(null);
const screenshotLinkError = ref(false);
const isSubmittingDispute = ref(false);

const disputeForm = ref({
    reason: '',
    description: '',
    screenshot_link: ''
});

const formatBalance = (balance: number | string) => {
    const numBalance = typeof balance === 'string' ? parseFloat(balance) : balance;
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(numBalance);
};

const formatAmount = (amount: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency || 'USD',
        minimumFractionDigits: 2
    }).format(amount);
};

const formatPaymentMethod = (method: string) => {
    const methods: Record<string, string> = {
        credit_card: t('profile.purchases.methods.card'),
        crypto: t('profile.purchases.methods.crypto'),
        free: t('profile.purchases.methods.free'),
        admin_bypass: t('profile.purchases.methods.admin'),
        balance: t('profile.purchases.methods.balance'),
        balance_deduction: t('profile.purchases.methods.balance')
    };
    return methods[method] || method;
};

const formatStatus = (status: string) => {
    const statuses: Record<string, string> = {
        completed: t('profile.purchases.statuses.completed'),
        pending: t('profile.purchases.statuses.pending'),
        failed: t('profile.purchases.statuses.failed'),
        refunded: t('profile.purchases.statuses.refunded')
    };
    return statuses[status] || status;
};

const getStatusClass = (status: string) => {
    const classes: Record<string, string> = {
        completed: 'status-completed',
        pending: 'status-pending',
        failed: 'status-failed',
        refunded: 'status-refunded'
    };
    return classes[status] || 'status-completed';
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

const fetchPurchases = async () => {
    loadingPurchases.value = true;
    try {
        const params: any = {};

        if (filters.value.date_from) {
            params.date_from = filters.value.date_from;
        }
        if (filters.value.date_to) {
            params.date_to = filters.value.date_to;
        }
        if (filters.value.status) {
            params.status = filters.value.status;
        }

        // ИСПРАВЛЕНО: Используем правильный эндпоинт /purchases вместо /transactions
        const { data } = await axios.get('/purchases', { params });

        // Обрабатываем ответ - API возвращает { success: true, purchases: [...] }
        if (data.success && Array.isArray(data.purchases)) {
            purchases.value = data.purchases;
            filteredPurchases.value = data.purchases;
        } else {
            purchases.value = [];
            filteredPurchases.value = [];
        }
    } catch (error) {
        console.error('Error fetching purchases:', error);
        purchases.value = [];
        filteredPurchases.value = [];
    } finally {
        loadingPurchases.value = false;
    }
};

const applyFilters = () => {
    fetchPurchases();
};

const resetFilters = () => {
    filters.value = {
        date_from: '',
        date_to: '',
        status: ''
    };
    fetchPurchases();
};

// Методы для работы с данными покупок
const togglePurchaseDetails = (purchaseId: number) => {
    if (expandedPurchases.value.has(purchaseId)) {
        expandedPurchases.value.delete(purchaseId);
    } else {
        expandedPurchases.value.add(purchaseId);
    }
    // Принудительно обновляем реактивность
    expandedPurchases.value = new Set(expandedPurchases.value);
};

const toggleDisputeDetails = (disputeId: number) => {
    if (expandedDisputes.value.has(disputeId)) {
        expandedDisputes.value.delete(disputeId);
    } else {
        expandedDisputes.value.add(disputeId);
    }
    expandedDisputes.value = new Set(expandedDisputes.value);
};

const formatAccountData = (accountItem: any): string => {
    if (typeof accountItem === 'string') {
        return accountItem;
    }
    if (typeof accountItem === 'object' && accountItem !== null) {
        return Object.entries(accountItem)
            .map(([key, value]) => `${key}: ${value}`)
            .join('\n');
    }
    return String(accountItem);
};

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text);
        toast.success(t('profile.purchases.copy_success'));
    } catch (error) {
        console.error('Failed to copy:', error);
        toast.error(t('profile.purchases.copy_error'));
    }
};

const downloadAsText = (content: string, filename: string) => {
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

const downloadSingleAccount = (purchase: any, accountItem: any, index: number) => {
    const orderNumber = purchase.order_number || `ID${purchase.id}`;
    const productTitle = purchase.service_name
        ? getProductTitle(purchase.service_name)
        : t('profile.purchases.unknown');
    const header = `======================================
${t('profile.purchases.download_labels.order')}: ${orderNumber}
${t('profile.purchases.download_labels.product')}: ${productTitle}
${t('profile.purchases.download_labels.date')}: ${formatDate(purchase.created_at)}
${t('profile.purchases.download_labels.account')}: ${index + 1}
======================================\n\n`;

    const content = formatAccountData(accountItem);
    const filename = `ORDER_${orderNumber}_${index + 1}.txt`;
    downloadAsText(header + content, filename);
};

const downloadAllAccounts = (purchase: any) => {
    const orderNumber = purchase.order_number || `ID${purchase.id}`;
    const productTitle = purchase.service_name
        ? getProductTitle(purchase.service_name)
        : t('profile.purchases.unknown');

    // Заголовок с информацией о заказе
    const header = `======================================
${t('profile.purchases.download_labels.order')}: ${orderNumber}
${t('profile.purchases.download_labels.product')}: ${productTitle}
${t('profile.purchases.download_labels.date')}: ${formatDate(purchase.created_at)}
${t('profile.purchases.download_labels.quantity')}: ${purchase.account_data.length} ${t('profile.purchases.quantity_unit')}
======================================\n\n`;

    const allData = purchase.account_data
        .map(
            (item: any, index: number) =>
                `=== ${t('profile.purchases.account')} ${index + 1} ===\n${formatAccountData(item)}`
        )
        .join('\n\n');

    const filename = `ORDER_${orderNumber}_${productTitle || t('profile.purchases.purchase')}.txt`;
    downloadAsText(header + allData, filename);
};

const activateVoucher = async () => {
    if (!voucherCode.value.trim()) {
        return;
    }

    voucherLoading.value = true;
    voucherError.value = '';
    voucherSuccess.value = '';

    try {
        const response = await axios.post('/vouchers/activate', {
            code: voucherCode.value.trim().toUpperCase()
        });

        voucherSuccess.value = response.data.message;
        voucherCode.value = '';

        // Обновляем баланс пользователя
        await authStore.fetchUser();

        // Показываем toast уведомление
        toast.success(response.data.message, {
            position: 'top-right',
            duration: 5000
        });

        // Очищаем сообщение успеха через 10 секунд
        setTimeout(() => {
            voucherSuccess.value = '';
        }, 10000);
    } catch (error: any) {
        if (error.response?.data?.errors?.code) {
            voucherError.value = error.response.data.errors.code[0];
        } else if (error.response?.data?.message) {
            voucherError.value = error.response.data.message;
        } else {
            voucherError.value = t('profile.voucher.error');
        }

        // Очищаем ошибку через 5 секунд
        setTimeout(() => {
            voucherError.value = '';
        }, 5000);
    } finally {
        voucherLoading.value = false;
    }
};

const handleSubmit = async () => {
    const payload: any = {
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: password_confirmation.value
    };

    const success = await authStore.update(payload);
    errors.value = authStore.errors;

    if (success) {
        toast.success(t('profile.success'));
        password.value = '';
        password_confirmation.value = '';
    }
};

// Функции для работы с претензиями
const canCreateDispute = (purchase: any): boolean => {
    // Только для completed транзакций
    if (purchase.status !== 'completed') return false;

    // Проверяем наличие transaction_id
    if (!purchase.transaction_id) return false;

    // Не старше 30 дней
    const daysSince = Math.floor(
        (new Date().getTime() - new Date(purchase.created_at).getTime()) / (1000 * 60 * 60 * 24)
    );
    if (daysSince > 30) return false;

    // Только если есть данные аккаунтов (это покупка товара, а не подписка)
    if (!purchase.account_data || purchase.account_data.length === 0) return false;

    // Проверяем, нет ли уже претензии на эту покупку
    if (purchase.has_dispute) return false;

    return true;
};

const openDisputeModal = (purchase: any) => {
    selectedPurchase.value = purchase;
    showDisputeModal.value = true;
};

const getDisputeStatusText = (status: string): string => {
    const statuses: Record<string, string> = {
        new: t('profile.purchases.disputes.status.new'),
        in_review: t('profile.purchases.disputes.status.in_review'),
        resolved: t('profile.purchases.disputes.status.resolved'),
        rejected: t('profile.purchases.disputes.status.rejected')
    };
    return statuses[status] || status;
};

const getDisputeStatusClass = (status: string): string => {
    const classes: Record<string, string> = {
        new: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        in_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    };
    return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};

const closeDisputeModal = () => {
    showDisputeModal.value = false;
    selectedPurchase.value = null;
    disputeForm.value = {
        reason: '',
        description: '',
        screenshot_link: ''
    };
    screenshotMethod.value = 'file';
    screenshotFile.value = null;
    screenshotPreview.value = null;
    screenshotLinkError.value = false;
};

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        // Проверка размера (5MB)
        if (file.size > 5 * 1024 * 1024) {
            toast.error(t('profile.purchases.disputes.file_too_large'));
            return;
        }

        // Проверка типа
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            toast.error(t('profile.purchases.disputes.unsupported_format'));
            return;
        }

        screenshotFile.value = file;

        // Создать preview
        const reader = new FileReader();
        reader.onload = e => {
            screenshotPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const submitDispute = async () => {
    if (!selectedPurchase.value) return;

    // Проверка наличия transaction_id
    if (!selectedPurchase.value.transaction_id) {
        toast.error(t('profile.purchases.disputes.transaction_not_found'));
        return;
    }

    // Проверка наличия скриншота
    if (screenshotMethod.value === 'file' && !screenshotFile.value) {
        toast.error(t('profile.purchases.disputes.please_attach_screenshot'));
        return;
    }

    if (screenshotMethod.value === 'link' && !disputeForm.value.screenshot_link) {
        toast.error(t('profile.purchases.disputes.please_provide_link'));
        return;
    }

    isSubmittingDispute.value = true;

    try {
        // ИСПРАВЛЕНО: Используем transaction_id вместо purchase.id
        const formData = new FormData();
        formData.append('transaction_id', selectedPurchase.value.transaction_id.toString());
        formData.append('reason', disputeForm.value.reason);
        formData.append('description', disputeForm.value.description);

        if (screenshotMethod.value === 'file' && screenshotFile.value) {
            formData.append('screenshot_file', screenshotFile.value);
        } else if (screenshotMethod.value === 'link') {
            formData.append('screenshot_link', disputeForm.value.screenshot_link);
        }

        const response = await axios.post('/disputes', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                Authorization: `Bearer ${authStore.token}`
            }
        });

        if (response.data.success) {
            toast.success(t('profile.purchases.disputes.success'));
            closeDisputeModal();
            // Обновляем список покупок и претензий
            await fetchPurchases();
            await fetchDisputes();
        }
    } catch (error: any) {
        const message = error.response?.data?.message || t('profile.purchases.disputes.error');
        toast.error(message);
    } finally {
        isSubmittingDispute.value = false;
    }
};

const fetchDisputes = async () => {
    loadingDisputes.value = true;
    try {
        const { data } = await axios.get('/disputes', {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });

        if (data.disputes && Array.isArray(data.disputes.data)) {
            disputes.value = data.disputes.data;
        } else if (Array.isArray(data.disputes)) {
            disputes.value = data.disputes;
        } else {
            disputes.value = [];
        }
    } catch (error) {
        console.error('Error fetching disputes:', error);
        disputes.value = [];
    } finally {
        loadingDisputes.value = false;
    }
};

const getDecisionColor = (decision: string): string => {
    const colors: Record<string, string> = {
        refund: 'text-green-600 dark:text-green-400',
        replacement: 'text-blue-600 dark:text-blue-400',
        rejected: 'text-red-600 dark:text-red-400'
    };
    return colors[decision] || 'text-gray-600 dark:text-gray-400';
};

onMounted(async () => {
    // УЛУЧШЕНИЕ: Показываем прелоадер при загрузке данных профиля
    loadingStore.start();

    try {
        // Загружаем покупки и претензии параллельно
        await Promise.all([fetchPurchases(), fetchDisputes()]);

        // Проверяем успешное пополнение баланса
        if (route.query.topup === 'success') {
            // Обновляем данные пользователя для актуального баланса
            await authStore.fetchUser();
            toast.success(t('balance_topup.success'));
            // Убираем параметр из URL
            router.replace({ path: '/profile' });
        }
    } finally {
        // Останавливаем прелоадер после загрузки
        loadingStore.stop();
    }
});
</script>

<style scoped>
.balance-card {
    background: linear-gradient(
        135deg,
        rgba(102, 126, 234, 0.15) 0%,
        rgba(118, 75, 162, 0.15) 100%
    );
    backdrop-filter: blur(10px);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(108, 92, 231, 0.12);
}

.dark .balance-card {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
    border-color: rgba(102, 126, 234, 0.3);
}

.balance-content {
    display: flex;
    align-items: center;
    gap: 16px;
    justify-content: space-between;
}

.balance-action {
    margin-left: auto;
}

.topup-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
}

.topup-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(108, 92, 231, 0.4);
}

.topup-button:active {
    transform: translateY(0);
}

.balance-icon {
    background: rgba(102, 126, 234, 0.2);
    border-radius: 12px;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dark .balance-icon {
    background: rgba(102, 126, 234, 0.3);
}

.balance-info {
    flex: 1;
}

.balance-label {
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 2px;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .balance-label {
    color: #9ca3af;
}

.balance-amount {
    font-size: 24px;
    font-weight: 700;
    color: #667eea;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .balance-amount {
    color: #a29bfe;
}

/* Voucher Card Styles */
.voucher-card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
    border: 2px dashed rgba(16, 185, 129, 0.3);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.3s ease;
}

.dark .voucher-card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%);
    border-color: rgba(16, 185, 129, 0.4);
}

.voucher-card:hover {
    border-color: rgba(16, 185, 129, 0.5);
    box-shadow: 0 2px 12px rgba(16, 185, 129, 0.12);
}

.voucher-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.voucher-title {
    font-size: 16px;
    font-weight: 600;
    color: #065f46;
}

.dark .voucher-title {
    color: #10b981;
}

.voucher-description {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 12px;
}

.dark .voucher-description {
    color: #9ca3af;
}

.voucher-form {
    margin-top: 12px;
}

.voucher-input-group {
    display: flex;
    gap: 10px;
}

.voucher-input {
    flex: 1;
    padding: 10px 14px;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.2s ease;
    background: white;
}

.dark .voucher-input {
    background: #1f2937;
    border-color: #4b5563;
    color: #f3f4f6;
}

.voucher-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.voucher-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.voucher-button {
    padding: 10px 20px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.voucher-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.voucher-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.voucher-error {
    margin-top: 12px;
    padding: 12px 16px;
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    border-radius: 8px;
    color: #991b1b;
    font-size: 14px;
    font-weight: 500;
}

.dark .voucher-error {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

.voucher-success {
    margin-top: 12px;
    padding: 12px 16px;
    background: #d1fae5;
    border-left: 4px solid #10b981;
    border-radius: 8px;
    color: #065f46;
    font-size: 14px;
    font-weight: 500;
    animation: slideIn 0.3s ease;
}

.dark .voucher-success {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.purchases-section {
    margin-top: 48px;
}

/* Компактная карточка покупки */
.purchase-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.dark .purchase-card {
    background: #1f2937;
    border-color: #374151;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.purchase-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-color: #d1d5db;
}

.dark .purchase-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    border-color: #4b5563;
}

/* Компактный заголовок */
.purchase-compact-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.purchase-id {
    font-size: 11px;
    color: #9ca3af;
    font-weight: 600;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 6px;
}

.dark .purchase-id {
    background: #374151;
    color: #9ca3af;
}

.purchase-service-name {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.dark .purchase-service-name {
    color: #ffffff;
}

.purchase-quantity-badge {
    font-size: 11px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 6px;
    font-weight: 500;
}

.dark .purchase-quantity-badge {
    background: #374151;
    color: #9ca3af;
}

.purchase-amount {
    font-size: 16px;
    font-weight: 700;
    color: #6c5ce7;
}

.dark .purchase-amount {
    color: #a29bfe;
}

/* Компактный футер */
.purchase-compact-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 6px;
}

.purchase-method {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.dark .purchase-method {
    color: #9ca3af;
}

.purchase-date {
    font-size: 12px;
    color: #9ca3af;
}

.dark .purchase-date {
    color: #6b7280;
}

/* Filters Card */
.filters-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.dark .filters-card {
    background: #1f2937;
    border-color: #374151;
}

/* Status badges */
.purchase-status {
    font-size: 10px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.dark .status-completed {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.dark .status-pending {
    background: rgba(251, 191, 36, 0.2);
    color: #fcd34d;
}

.status-failed {
    background: #fee2e2;
    color: #991b1b;
}

.dark .status-failed {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

.status-refunded {
    background: #e0e7ff;
    color: #3730a3;
}

.dark .status-refunded {
    background: rgba(99, 102, 241, 0.2);
    color: #a5b4fc;
}

@media (max-width: 640px) {
    .balance-card {
        padding: 16px;
    }

    .balance-amount {
        font-size: 22px;
    }

    .purchase-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .purchase-amount {
        font-size: 18px;
    }

    .filters-card {
        padding: 16px;
    }
}

/* Модальное окно претензии - Glass Morphism */
.glass-morphism-dispute {
    backdrop-filter: blur(20px) saturate(180%);
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow:
        0 8px 32px 0 rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.dark .glass-morphism-dispute {
    background: rgba(31, 41, 55, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow:
        0 8px 32px 0 rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

/* Анимация модального окна */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(20px);
}

.modal-enter-to,
.modal-leave-from {
    opacity: 1;
    transform: scale(1) translateY(0);
}

/* Плавная анимация для декоративных элементов */
@keyframes float-gentle {
    0%,
    100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-10px) rotate(5deg);
    }
}

.animate-float-gentle {
    animation: float-gentle 6s ease-in-out infinite;
}

/* Стили для выбора метода скриншота */
.screenshot-method-active {
    color: #3b82f6;
}

.screenshot-method-active > div {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
    border-color: #3b82f6;
    color: #2563eb;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
}

.dark .screenshot-method-active > div {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(147, 51, 234, 0.2));
    border-color: #60a5fa;
    color: #93c5fd;
}

.screenshot-method-inactive {
    color: #6b7280;
}

.screenshot-method-inactive > div {
    background: rgba(243, 244, 246, 0.5);
    border-color: #d1d5db;
    color: #6b7280;
}

.dark .screenshot-method-inactive > div {
    background: rgba(55, 65, 81, 0.5);
    border-color: #4b5563;
    color: #9ca3af;
}

.screenshot-method-inactive:hover > div {
    border-color: #9ca3af;
    background: rgba(243, 244, 246, 0.8);
}

.dark .screenshot-method-inactive:hover > div {
    border-color: #6b7280;
    background: rgba(55, 65, 81, 0.8);
}

/* Анимация dropdown select */
.dispute-select {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dispute-select:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.dark .dispute-select:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Стили для опций select */
.dispute-select option {
    padding: 12px 16px;
    background: white;
    color: #1f2937;
}

.dark .dispute-select option {
    background: #1f2937;
    color: #f3f4f6;
}

.dispute-select option:hover {
    background: #f3f4f6;
}

.dark .dispute-select option:hover {
    background: #374151;
}

/* Карточка претензии */
.dispute-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 16px 20px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.dark .dispute-card {
    background: #1f2937;
    border-color: #374151;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.dispute-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #d1d5db;
}

.dark .dispute-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    border-color: #4b5563;
}

/* Анимация раскрытия деталей претензии */
.expand-enter-active,
.expand-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    max-height: 0;
    opacity: 0;
    transform: translateY(-10px);
}

.expand-enter-to,
.expand-leave-from {
    max-height: 500px;
    opacity: 1;
    transform: translateY(0);
}
</style>
