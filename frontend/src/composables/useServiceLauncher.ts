import { ref, onUnmounted } from 'vue';

type ServiceWindow = {
    window: Window | null;
    url: string;
};

export function useServiceLauncher() {
    const activeWindows = ref<Record<string, ServiceWindow>>({});

    const launchService = (url: string, serviceName: string) => {
        try {
            const existing = activeWindows.value[serviceName]?.window;
            if (existing && !existing.closed) existing.close();

            const features = [
                'width=1200',
                'height=800',
                'left=50',
                'top=50',
                'menubar=no',
                'toolbar=no',
                'location=yes',
                'status=no',
                'resizable=yes',
                'scrollbars=yes',
                'noopener=yes',
                'noreferrer=yes'
            ].join(',');

            const newWindow = window.open(url, `service_${serviceName}`, features);
            if (!newWindow) return;

            newWindow.opener = null;
            activeWindows.value = {
                ...activeWindows.value,
                [serviceName]: { window: newWindow, url }
            };

            const checkWindow = setInterval(() => {
                if (newWindow.closed) {
                    clearInterval(checkWindow);
                    const updated = { ...activeWindows.value };
                    delete updated[serviceName];
                    activeWindows.value = updated;
                }
            }, 1000);

            newWindow.addEventListener('load', () => {
                try {
                    if (!newWindow.document) return;

                    const metas = [
                        {
                            httpEquiv: 'Content-Security-Policy',
                            content:
                                "default-src 'self' * 'unsafe-inline' 'unsafe-eval'; connect-src *; script-src 'self' * 'unsafe-inline' 'unsafe-eval'; style-src 'self' * 'unsafe-inline'; img-src 'self' * data: blob:; frame-ancestors 'none'; form-action 'self'"
                        },
                        { name: 'referrer', content: 'no-referrer' }
                    ];

                    metas.forEach(attrs => {
                        const m = newWindow.document.createElement('meta');
                        Object.entries(attrs).forEach(([k, v]) => m.setAttribute(k, v));
                        newWindow.document.head.appendChild(m);
                    });

                    const script = newWindow.document.createElement('script');
                    script.textContent = `
            (function(){
              Object.defineProperty(document, 'cookie', {
                get: function(){ return ''; },
                set: function(){ return true; }
              });
              if (window.cookieStore) { try { window.cookieStore = undefined; } catch(_){} }
              setInterval(() => {
                const devtools = /./;
                devtools.toString = function(){ this.opened = true; }
                console.log('%c', devtools);
                if (devtools.opened) window.location.href = 'about:blank';
              }, 1000);
              document.addEventListener('copy', e => e.preventDefault());
              document.addEventListener('cut', e => e.preventDefault());
              document.addEventListener('contextmenu', e => e.preventDefault());
              document.addEventListener('keydown', function(e){
                if ((e.ctrlKey && ['c','v','u','i'].includes(e.key.toLowerCase())) || e.key === 'F12') {
                  e.preventDefault();
                }
              });
            })();
          `;
                    newWindow.document.head.appendChild(script);
                } catch (e) {
                    // cross-origin — игнорируем
                    console.log('Window loaded with security measures', e);
                }
            });
        } catch (error) {
            console.error('Failed to launch service:', error);
        }
    };

    const closeAllWindows = () => {
        Object.values(activeWindows.value).forEach(({ window }) => {
            if (window && !window.closed) window.close();
        });
        activeWindows.value = {};
    };

    onUnmounted(closeAllWindows);

    return { launchService, closeAllWindows, activeWindows };
}
