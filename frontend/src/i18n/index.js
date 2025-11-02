import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import uk from './locales/uk.json';
import ru from './locales/ru.json';
import zh from './locales/zh.json';
import es from './locales/es.json';

const getBrowserLocale = () => {
    const browserLang = navigator.language || navigator.userLanguage;

    const languageCode = browserLang.split('-')[0];

    const supportedLanguages = ['en', 'uk', 'ru', 'zh', 'es'];

    return supportedLanguages.includes(languageCode) ? languageCode : 'uk';
};

const savedLanguage = localStorage.getItem('user-language');

const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: savedLanguage ?? getBrowserLocale(),
    fallbackLocale: 'en',
    warnHtmlMessage: false,
    messages: {
        en: en,
        uk: uk,
        ru: ru,
        zh: zh,
        es: es
    }
});

export default i18n;
