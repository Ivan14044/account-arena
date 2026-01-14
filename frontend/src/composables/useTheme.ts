// useTheme.ts
import { ref, onMounted, onBeforeUnmount } from 'vue';

const isDark = ref(false); // глобальный синглтон

export function useTheme() {
    const applyTheme = (dark: boolean) => {
        if (isDark.value === dark) return;
        
        // Используем requestAnimationFrame для плавного применения темы без блокировки UI
        requestAnimationFrame(() => {
            isDark.value = dark;
            document.documentElement.classList.toggle('dark', dark);
            
            // Откладываем localStorage в следующий тик, чтобы не блокировать рендеринг
            setTimeout(() => {
                localStorage.setItem('theme', dark ? 'dark' : 'light');
            }, 0);
        });
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
