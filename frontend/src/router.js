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
import ContentPage from './pages/ContentPage.vue';
import NotFound from './pages/NotFound.vue';
import ArticlesAll from './pages/articles/ArticlesAll.vue';
import ArticleDetails from './pages/articles/ArticleDetails.vue';
import { useAuthStore } from './stores/auth';
import { usePageStore } from './stores/pages';

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
        component: CheckoutPage,
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
    if (!authStore.user && authStore.token) {
        await authStore.fetchUser();
    }

    if (to.meta.requiresAuth && !authStore.user) {
        return next({
            path: '/login',
            query: { redirect: to.fullPath }
        });
    } else if (to.meta.requiresGuest && authStore.user) {
        return next('/');
    }

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

export default router;
