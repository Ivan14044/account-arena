// useTheme.ts
import { ref, onMounted, onBeforeUnmount } from 'vue';

const isDark = ref(false); // глобальный синглтон

export function useTheme() {
    const applyTheme = (dark: boolean) => {
        if (isDark.value === dark) return;
        isDark.value = dark;

        // Отключаем переходы для всего сайта, кроме переключателя
        document.documentElement.classList.add('disable-transitions');

        // Используем requestAnimationFrame для синхронизации с циклом отрисовки браузера
        requestAnimationFrame(() => {
            document.documentElement.classList.toggle('dark', dark);
            
            // В следующем кадре включаем переходы обратно
            requestAnimationFrame(() => {
                document.documentElement.classList.remove('disable-transitions');
            });
        });

        // Сохранение в localStorage делаем в setTimeout, чтобы не блокировать UI
        setTimeout(() => {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        }, 0);
    };

    const toggleTheme = () => applyTheme(!isDark.value);

    const handleStorage = (e: StorageEvent) => {
        if (e.key === 'theme' && e.newValue) {
            applyTheme(e.newValue === 'dark');
        }
    };

    onMounted(() => {
        const saved = localStorage.getItem('theme');
        if (saved) applyTheme(saved === 'dark');
        else applyTheme(window.matchMedia('(prefers-color-scheme: dark)').matches);

        window.addEventListener('storage', handleStorage);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('storage', handleStorage);
    });

    return { isDark, toggleTheme, applyTheme };
}
