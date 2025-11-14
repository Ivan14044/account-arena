import { createRouter, createWebHistory } from 'vue-router';
import LoginPage from './components/auth/LoginPage.vue';
import RegisterPage from './components/auth/RegisterPage.vue';
import AuthCallback from './components/auth/AuthCallback.vue';
import MainPage from './pages/MainPage.vue';
import ProfilePage from './pages/account/ProfilePage.vue';
import ForgotPasswordPage from './components/auth/ForgotPasswordPage.vue';
import ResetPasswordPage from './components/auth/ResetPasswordPage.vue';
import CheckoutPage from './pages/CheckoutPage.vue';
import OrderSuccessPage from './pages/OrderSuccessPage.vue';
import ContentPage from './pages/ContentPage.vue';
import NotFound from './pages/NotFound.vue';
import ArticlesAll from './pages/articles/ArticlesAll.vue';
import ArticleDetails from './pages/articles/ArticleDetails.vue';
// Stores импортируются лениво внутри guards для избежания циклических зависимостей

const routes = [
    { path: '/', component: MainPage },
    { path: '/account/:id', component: () => import('./pages/account/AccountDetail.vue') },
    { path: '/login', component: LoginPage, meta: { requiresGuest: true } },
    {
        path: '/register',
        component: RegisterPage,
        meta: { requiresGuest: true }
    },
    {
        path: '/forgot-password',
        component: ForgotPasswordPage,
        meta: { requiresGuest: true }
    },
    {
        path: '/reset-password/:token',
        component: ResetPasswordPage,
        meta: { requiresGuest: true },
        props: true
    },
    {
        path: '/auth/callback',
        component: AuthCallback
    },
    {
        path: '/profile',
        component: ProfilePage,
        meta: { requiresAuth: true }
    },
    {
        path: '/balance/topup',
        component: () => import('./pages/BalanceTopUpPage.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/articles',
        component: ArticlesAll,
        meta: { isArticlesList: true }
    },
    {
        path: '/articles/page/:page',
        component: ArticlesAll,
        meta: { isArticlesList: true }
    },
    {
        path: '/categories/:id',
        component: ArticlesAll,
        meta: { isArticlesList: true }
    },
    {
        path: '/categories/:id/page/:page',
        component: ArticlesAll,
        meta: { isArticlesList: true }
    },
    {
        path: '/articles/:id',
        component: ArticleDetails
    },
    {
        path: '/checkout',
        component: CheckoutPage
        // Убрано requiresAuth: теперь гости могут покупать товары
    },
    {
        path: '/order-success',
        component: OrderSuccessPage,
        meta: { requiresAuth: true }
    },
    {
        path: '/:slug(.*)*',
        name: 'dynamic',
        component: ContentPage,
        meta: { isDynamic: true }
    },
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
    // Ленивая загрузка stores для избежания циклических зависимостей
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
