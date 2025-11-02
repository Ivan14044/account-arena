export function scrollToElement(
    selector: string,
    options: ScrollIntoViewOptions = { behavior: 'smooth', block: 'start' }
): void {
    const el = document.querySelector(selector);
    if (el) {
        try {
            el.scrollIntoView(options);
        } catch {
            const top = el.getBoundingClientRect().top + window.scrollY;
            window.scrollTo({
                top,
                behavior: 'smooth'
            });
        }
    }
}
