import { watch, onBeforeUnmount } from 'vue';

/**
 * Composable для инъекции JSON-LD структурированных данных в <head>
 * Автоматически удаляет старые скрипты при смене данных
 */
export function useStructuredData(data: () => object | object[]) {
    let injectedScripts: HTMLScriptElement[] = [];
    
    const injectStructuredData = () => {
        // Удаляем все ранее добавленные скрипты
        injectedScripts.forEach(script => {
            if (script.parentNode) {
                script.parentNode.removeChild(script);
            }
        });
        injectedScripts = [];
        
        // Получаем данные
        const dataValue = data();
        
        if (!dataValue) {
            return;
        }
        
        // Поддерживаем как один объект, так и массив объектов
        const dataArray = Array.isArray(dataValue) ? dataValue : [dataValue];
        
        // Создаём скрипты для каждого объекта
        dataArray.forEach((item, index) => {
            if (!item || typeof item !== 'object') {
                return;
            }
            
            const script = document.createElement('script');
            script.type = 'application/ld+json';
            script.id = `structured-data-${index}`;
            
            try {
                script.textContent = JSON.stringify(item, null, 2);
                document.head.appendChild(script);
                injectedScripts.push(script);
            } catch (error) {
                console.error('[useStructuredData] Error serializing JSON-LD:', error);
            }
        });
    };
    
    // Следим за изменениями данных
    watch(data, injectStructuredData, { immediate: true, deep: true });
    
    // Очищаем при размонтировании
    onBeforeUnmount(() => {
        injectedScripts.forEach(script => {
            if (script.parentNode) {
                script.parentNode.removeChild(script);
            }
        });
        injectedScripts = [];
    });
    
    return { 
        injectStructuredData,
        clear: () => {
            injectedScripts.forEach(script => {
                if (script.parentNode) {
                    script.parentNode.removeChild(script);
                }
            });
            injectedScripts = [];
        }
    };
}
