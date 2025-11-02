/**
 * Composable для проверки установки плагина
 * Единая функция для всех компонентов
 */

interface PluginDetectionResult {
    isInstalled: boolean;
    method: 'meta' | 'runtime' | 'none';
}

export function usePluginDetection() {
    const EXTENSION_ID =
        (import.meta.env.VITE_EXTENSION_ID as string) || 'knddmcedhofaofphehlcobnceedelfjl';

    /**
     * Проверяет установку плагина
     * Сначала по meta тегу, затем по runtime API
     */
    const checkPluginInstalled = async (
        timeoutMs: number = 1200
    ): Promise<PluginDetectionResult> => {
        console.log('🔍 Starting plugin detection...');

        // 1. Проверяем по meta тегу (самый быстрый способ)
        const meta = document.querySelector(
            'meta[name="subcloudy-extension"]'
        ) as HTMLMetaElement | null;
        if (meta?.content === 'installed') {
            console.log('✅ Plugin detected by meta tag');
            return { isInstalled: true, method: 'meta' };
        }

        // 2. Проверяем по extension ID через runtime API (самый надежный способ)
        if (
            typeof (window as any).chrome !== 'undefined' &&
            (window as any).chrome.runtime &&
            (window as any).chrome.runtime.sendMessage
        ) {
            try {
                console.log('🔧 Checking plugin via runtime API...');

                const result = await new Promise<boolean>(resolve => {
                    const timeout = setTimeout(() => {
                        resolve(false);
                    }, timeoutMs);

                    (window as any).chrome.runtime.sendMessage(
                        EXTENSION_ID,
                        { type: 'SC_EXT_RUNTIME_PING' },
                        (response: any) => {
                            clearTimeout(timeout);
                            const hasError = !!(window as any).chrome?.runtime?.lastError;
                            resolve(!hasError);
                        }
                    );
                });

                if (result) {
                    console.log('✅ Plugin detected by runtime API');
                    return { isInstalled: true, method: 'runtime' };
                } else {
                    console.log('❌ Plugin not detected by runtime API');
                    return { isInstalled: false, method: 'none' };
                }
            } catch (error) {
                console.log('💥 Runtime check failed:', error);
                return { isInstalled: false, method: 'none' };
            }
        }

        console.log('❌ Plugin not detected by any method');
        return { isInstalled: false, method: 'none' };
    };

    /**
     * Быстрая проверка только по meta тегу
     * Для случаев когда нужно быстро определить статус
     */
    const checkPluginByMeta = (): boolean => {
        const meta = document.querySelector(
            'meta[name="subcloudy-extension"]'
        ) as HTMLMetaElement | null;
        return meta?.content === 'installed';
    };

    return {
        checkPluginInstalled,
        checkPluginByMeta,
        EXTENSION_ID
    };
}
