import { createRouter, createWebHistory } from 'vue-router';
import LoginPage from './components/auth/LoginPage.vue';
import RegisterPage from './components/auth/RegisterPage.vue';
import AuthCallback from './components/auth/AuthCallback.vue';
import MainPage from './pages/MainPage.vue';
import ProfilePage from './pages/account/ProfilePage.vue';
import ServicePage from './pages/ServicePage.vue';
import SessionStart from './pages/SessionStart.vue';
import ForgotPasswordPage from './components/auth/ForgotPasswordPage.vue';
import ResetPasswordPage from './components/auth/ResetPasswordPage.vue';
import CheckoutPage from './pages/CheckoutPage.vue';
import OrderSuccessPage from './pages/OrderSuccessPage.vue';
import ContentPage from './pages/ContentPage.vue';
import NotFound from './pages/NotFound.vue';
import ArticlesAll from './pages/articles/ArticlesAll.vue';
import ArticleDetails from './pages/articles/ArticleDetails.vue';
import { useAuthStore } from './stores/auth';
import { usePageStore } from './stores/pages';
import { useLoadingStore } from './stores/loading';

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
        path: '/service/:id',
        component: ServicePage
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
        path: '/session-start/:id?',
        component: SessionStart,
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
            } catch (_) {}
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
    const authStore = useAuthStore();
    const loadingStore = useLoadingStore();
    
    // УЛУЧШЕНИЕ: Показываем прелоадер при переходе между страницами
    // Исключаем переходы на главную и небольшие навигационные переходы
    if (from.path !== to.path && to.path !== '/') {
        loadingStore.start();
    }
    
    console.log('[ROUTER] ============================================');
    console.log('[ROUTER] Переход:', from.path, '->', to.path);
    console.log('[ROUTER] Требует авторизацию:', to.meta.requiresAuth);
    console.log('[ROUTER] Требует гостя:', to.meta.requiresGuest);
    console.log('[ROUTER] Auth состояние:', {
        hasUser: !!authStore.user,
        hasToken: !!authStore.token,
        isAuthenticated: authStore.isAuthenticated,
        userEmail: authStore.user?.email,
        userName: authStore.user?.name
    });
    
    // ИСПРАВЛЕНО: Загружаем пользователя если есть токен, но нет данных пользователя
    if (!authStore.user && authStore.token) {
        console.log('[ROUTER] Обнаружен токен без пользователя, загружаем данные...');
        try {
            await authStore.fetchUser();
            console.log('[ROUTER] Пользователь успешно загружен:', {
                email: authStore.user?.email,
                name: authStore.user?.name,
                balance: authStore.user?.balance
            });
        } catch (error) {
            console.error('[ROUTER] Ошибка загрузки пользователя:', error);
            // Если не удалось загрузить пользователя, очищаем токен
            await authStore.logout();
        }
    }

    // Проверка для страниц, требующих авторизации
    if (to.meta.requiresAuth) {
        // ИСПРАВЛЕНО: Используем isAuthenticated вместо только проверки user
        if (!authStore.isAuthenticated) {
            console.log('[ROUTER] ❌ Доступ запрещен: требуется авторизация');
            console.log('[ROUTER] Редирект на /login');
            return next({
                path: '/login',
                query: { redirect: to.fullPath }
            });
        }
        console.log('[ROUTER] ✅ Доступ разрешен: пользователь авторизован');
    }
    
    // Проверка для страниц только для гостей (login, register и т.д.)
    if (to.meta.requiresGuest) {
        if (authStore.isAuthenticated) {
            console.log('[ROUTER] Пользователь уже авторизован, редирект на /');
            // Не делаем редирект если уже на /
            if (to.path === '/') {
                return next();
            }
            return next('/');
        }
    }
    
    console.log('[ROUTER] Переход разрешен');
    console.log('[ROUTER] ============================================');

    // Save the route user came from when first entering articles/categories list
    try {
        const wasInArticles = Boolean(from?.meta?.isArticlesList);
        const isGoingToArticles = Boolean(to?.meta?.isArticlesList);
        if (!wasInArticles && isGoingToArticles) {
            // store the entry point before entering the list
            sessionStorage.setItem('articlesEntryFrom', from?.fullPath || '/');
        }
    } catch (_) {
        // ignore storage errors (Safari private mode, etc.)
    }

    if (to.meta.isDynamic) {
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

// УЛУЧШЕНИЕ: Останавливаем прелоадер после завершения перехода
router.afterEach((to, from) => {
    const loadingStore = useLoadingStore();
    
    // Небольшая задержка для плавности (чтобы страница успела отрендериться)
    setTimeout(() => {
        loadingStore.stop();
    }, 100);
    
    console.log('[ROUTER] Переход завершен:', to.path);
});

export default router;
