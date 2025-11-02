export function pluralizeDays(count: number, locale: string = 'ru'): string {
    switch (locale) {
        case 'ru': {
            const mod10 = count % 10;
            const mod100 = count % 100;
            if (mod10 === 1 && mod100 !== 11) return `${count} день`;
            if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return `${count} дня`;
            return `${count} дней`;
        }
        case 'uk': {
            const mod10 = count % 10;
            const mod100 = count % 100;
            if (mod10 === 1 && mod100 !== 11) return `${count} день`;
            if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return `${count} дні`;
            return `${count} днів`;
        }
        case 'en':
            return count === 1 ? `${count} day` : `${count} days`;
        case 'es':
            return count === 1 ? `${count} día` : `${count} días`;
        case 'zh':
            return `${count} 天`;
        default:
            return `${count} days`;
    }
}
