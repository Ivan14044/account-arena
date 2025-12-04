import { createRouter, createWebHistory } from 'vue-router';

// Synchronous imports - critical pages that should load immediately
import MainPage from './pages/MainPage.vue';
import LoginPage from './components/auth/LoginPage.vue';
import RegisterPage from './components/auth/RegisterPage.vue';
import AuthCallback from './components/auth/AuthCallback.vue';
import NotFound from './pages/NotFound.vue';

// Stores - imported statically since they're already in main bundle via App.vue
// Dynamic imports in guards are kept for conditional loading only

const routes = [
    // Home page - always load synchronously
    { path: '/', component: MainPage },

    // Auth pages - load synchronously for fast access
    { path: '/login', component: LoginPage, meta: { requiresGuest: true } },
    {
        path: '/register',
        component: RegisterPage,
        meta: { requiresGuest: true }
    },
    {
        path: '/forgot-password',
        component: () => import('./components/auth/ForgotPasswordPage.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/reset-password/:token',
        component: () => import('./components/auth/ResetPasswordPage.vue'),
        meta: { requiresGuest: true },
        props: true
    },
    {
        path: '/auth/callback',
        component: AuthCallback
    },

    // User pages - lazy load (require auth, less frequent)
    {
        path: '/profile',
        component: () => import('./pages/account/ProfilePage.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/account/:id',
        component: () => import('./pages/account/AccountDetail.vue')
    },
    {
        path: '/balance/topup',
        component: () => import('./pages/BalanceTopUpPage.vue'),
        meta: { requiresAuth: true }
    },

    // Articles - lazy load (can be large)
    {
        path: '/articles',
        component: () => import('./pages/articles/ArticlesAll.vue'),
        meta: { isArticlesList: true }
    },
    {
        path: '/articles/page/:page',
        component: () => import('./pages/articles/ArticlesAll.vue'),
        meta: { isArticlesList: true }
    },
    {
        path: '/categories/:id',
        component: () => import('./pages/articles/ArticlesAll.vue'),
        meta: { isArticlesList: true }
    },
    {
        path: '/categories/:id/page/:page',
        component: () => import('./pages/articles/ArticlesAll.vue'),
        meta: { isArticlesList: true }
    },
    {
        path: '/articles/:id',
        component: () => import('./pages/articles/ArticleDetails.vue')
    },

    // Checkout - keep synchronous (critical for conversion)
    {
        path: '/checkout',
        component: () => import('./pages/CheckoutPage.vue')
        // Убрано requiresAuth: теперь гости могут покупать товары
    },
    {
        path: '/order-success',
        component: () => import('./pages/OrderSuccessPage.vue'),
        meta: { requiresAuth: true }
    },

    // Marketing pages - lazy load
    {
        path: '/become-supplier',
        component: () => import('./pages/BecomeSupplierPage.vue')
    },

    // Dynamic content - lazy load
    {
        path: '/:slug(.*)*',
        name: 'dynamic',
        component: () => import('./pages/ContentPage.vue'),
        meta: { isDynamic: true }
    },

    // 404 - keep synchronous (critical error page)
    {
        path: '/404',
        component: NotFound
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        // 1) Back/forward — use browser-saved position
        if (savedPosition) {
            try {
                sessionStorage.setItem('articlesUsedSavedPosition', '1');
            } catch {
                // ignore storage errors
            }
            return savedPosition;
        }

        // Defer scrolling for article/category list pages until data is loaded in component
        if (to.meta && to.meta.isArticlesList) {
            return false;
        }

        // 2) Anchors — scroll to element
        if (to.hash) {
            return { el: to.hash, top: 0, left: 0, behavior: 'auto' };
        }

        // 3) Default — ensure layout is ready (Firefox quirk)
        return new Promise(resolve => {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    resolve({ top: 0, left: 0, behavior: 'auto' });
                });
            });
        });
    }
});

router.beforeEach(async (to, from, next) => {
    // Import stores - using dynamic import to avoid circular dependencies
    // Note: These stores are also statically imported in App.vue, so they're in main bundle
    // Dynamic import here is for conditional access only
    const { useAuthStore } = await import('./stores/auth');
    const { useLoadingStore } = await import('./stores/loading');

    const authStore = useAuthStore();
    const loadingStore = useLoadingStore();

    // Показываем прелоадер при переходе между разными страницами
    // Исключаем первую загрузку приложения (когда from.path пустой)
    if (from.path && from.path !== to.path) {
        loadingStore.start();
    }

    // Загружаем пользователя если есть токен, но нет данных пользователя
    if (!authStore.user && authStore.token) {
        try {
            await authStore.fetchUser();
        } catch {
            // Если не удалось загрузить пользователя, очищаем токен
            await authStore.logout();
        }
    }

    // Проверка для страниц, требующих авторизации
    if (to.meta.requiresAuth) {
        if (!authStore.isAuthenticated) {
            return next({
                path: '/login',
                query: { redirect: to.fullPath }
            });
        }
    }

    // Проверка для страниц только для гостей (login, register и т.д.)
    if (to.meta.requiresGuest) {
        if (authStore.isAuthenticated) {
            // Не делаем редирект если уже на /
            if (to.path === '/') {
                return next();
            }
            return next('/');
        }
    }

    // Save the route user came from when first entering articles/categories list
    try {
        const wasInArticles = Boolean(from?.meta?.isArticlesList);
        const isGoingToArticles = Boolean(to?.meta?.isArticlesList);
        if (!wasInArticles && isGoingToArticles) {
            // store the entry point before entering the list
            sessionStorage.setItem('articlesEntryFrom', from?.fullPath || '/');
        }
    } catch {
        // ignore storage errors (Safari private mode, etc.)
    }

    if (to.meta.isDynamic) {
        const { usePageStore } = await import('./stores/pages');
        const slug = to.path.replace(/^\/|\/$/g, '');
        const pageStore = usePageStore();
        if (!pageStore.pages.length) {
            await pageStore.fetchData();
        }

        if (typeof pageStore.pages[slug] !== 'undefined') {
            pageStore.setPage(pageStore.pages[slug]);
            return next();
        } else {
            return next('/404');
        }
    }

    next();
});

// Останавливаем прелоадер после завершения перехода
router.afterEach(async () => {
    // Dynamic import for conditional access (store is already in main bundle)
    const { useLoadingStore } = await import('./stores/loading');
    const loadingStore = useLoadingStore();

    // Используем nextTick и requestAnimationFrame для гарантии, что контент отрендерен
    setTimeout(() => {
        // Даём компоненту время отрендериться
        requestAnimationFrame(() => {
            loadingStore.stop();
        });
    }, 150);
});

export default router;
