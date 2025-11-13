export default {
    mounted(el: HTMLElement, binding: any) {
        const animationClass = binding.value || 'animate-fade-in-up';
        el.classList.add('opacity-0', 'translate-y-8', 'transition-all', 'duration-1000');

        // Support object binding: { class: 'animate-fade-in-up', once: true, threshold: 0.2, rootMargin: '0px 0px -20% 0px' }
        const options =
            typeof binding.value === 'object' && binding.value !== null
                ? binding.value
                : { class: animationClass };

        const targetClass = options.class || animationClass;
        const once = options.once !== false; // default true

        let hasAppeared = false; // guard against multiple triggers (iOS Safari can fire twice)
        let observer: IntersectionObserver | null = null;

        const initObserver = () => {
            // Earlier reveal on small screens: lower threshold and negative bottom rootMargin (pulls boundary up slightly)
            const isSmallScreen = window.innerWidth < 768;
            const baseThreshold =
                typeof options.threshold === 'number'
                    ? options.threshold
                    : isSmallScreen
                      ? 0.05
                      : 0.2;

            // rootMargin pulls the viewport bottom edge upward on mobile to trigger sooner
            const baseRootMargin =
                typeof options.rootMargin === 'string'
                    ? options.rootMargin
                    : isSmallScreen
                      ? '0px 0px -30% 0px'
                      : '0px 0px -10% 0px';

            // IntersectionObserver init
            observer = new IntersectionObserver(
                entries => {
                    const entry = entries[0];
                    if (!entry) return;

                    // Some Safari versions can report isIntersecting=false yet ratio>0; use either condition
                    const isVisible = entry.isIntersecting || entry.intersectionRatio > 0;
                    if (!isVisible) return;

                    // Guard to ensure one-time activation
                    if (hasAppeared) {
                        if (once && observer) observer.unobserve(el);
                        return;
                    }

                    // Apply only when ratio crosses our threshold
                    if (entry.intersectionRatio >= baseThreshold) {
                        hasAppeared = true;
                        el.classList.add(targetClass);
                        el.classList.remove('opacity-0', 'translate-y-8');
                        if (once && observer) {
                            observer.unobserve(el);
                            observer.disconnect();
                            observer = null;
                        }
                    }
                },
                {
                    root: null,
                    threshold: [0, baseThreshold, 0.9999],
                    rootMargin: baseRootMargin
                }
            );

            observer.observe(el);
        };

        initObserver();

        // If the element initially has zero height (lazy content), retry once after layout settles
        if (el.clientHeight === 0) {
            setTimeout(() => {
                if (observer) observer.disconnect();
                initObserver();
            }, 500);
        }
    }
};
