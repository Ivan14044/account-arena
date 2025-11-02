// eslint.config.js (ESLint v9 flat config for Vite + Vue3 + TS + Prettier)
import eslint from "@eslint/js";
import typescriptEslint from "typescript-eslint";
import vuePlugin from "eslint-plugin-vue";
// Импорт объединённой рекомендованной конфигурации Prettier (плагин + отключение конфликтов)
import eslintPluginPrettierRecommended from "eslint-plugin-prettier/recommended";

export default typescriptEslint.config(
    // 1. Глобальные настройки (игнорирование вне папки src)
    {
        ignores: [
            "**/node_modules/**",
            "**/dist/**",
            "**/coverage/**",
            "**/cypress/**"
        ]
    },
    // 2. Основная конфигурация для файлов проекта (только внутри src)
    {
        files: ["src/**/*.{js,jsx,ts,tsx,vue}"],
        ignores: ["*.d.ts"],  // при необходимости игнорируем декларации (опционально)
        extends: [
            eslint.configs.recommended,                  // базовые правила ESLint:contentReference[oaicite:13]{index=13}
            ...typescriptEslint.configs.recommended,     // рекомендации @typescript-eslint:contentReference[oaicite:14]{index=14}
            ...vuePlugin.configs["flat/recommended"]     // рекомендации eslint-plugin-vue для Vue 3:contentReference[oaicite:15]{index=15}
        ],
        languageOptions: {
            ecmaVersion: "latest",
            sourceType: "module",
            globals: { /* Можно подключить глобалы при необходимости */ },
            parserOptions: {
                // Используем vue-eslint-parser для .vue (задаётся в конфиге плагина Vue),
                // а для секций <script> указываем TypeScript-парсер:
                parser: typescriptEslint.parser,           // подключаем @typescript-eslint/parser:contentReference[oaicite:16]{index=16}
                extraFileExtensions: [".vue"]
            }
        },
        // Дополнительные правила или переопределения:
        rules: {
            "vue/require-default-prop": "off",
            "@typescript-eslint/no-explicit-any": "off",
            "vue/no-v-html": "off",
        }
    },
    eslintPluginPrettierRecommended
);
