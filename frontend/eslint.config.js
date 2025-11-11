// ESLint v9 flat config for Vite + Vue 3 + TypeScript + Prettier
import eslint from "@eslint/js";
import typescriptEslint from "typescript-eslint";
import vuePlugin from "eslint-plugin-vue";
import eslintPluginPrettierRecommended from "eslint-plugin-prettier/recommended";

export default typescriptEslint.config(
    {
        ignores: [
            "**/node_modules/**",
            "**/dist/**",
            "**/coverage/**",
            "**/cypress/**",
            "**/*.d.ts"
        ]
    },
    {
        files: ["src/**/*.{js,jsx,ts,tsx,vue}"],
        extends: [
            eslint.configs.recommended,
            ...typescriptEslint.configs.recommended,
            ...vuePlugin.configs["flat/recommended"]
        ],
        languageOptions: {
            ecmaVersion: "latest",
            sourceType: "module",
            parserOptions: {
                parser: typescriptEslint.parser,
                extraFileExtensions: [".vue"]
            }
        },
        rules: {
            "vue/require-default-prop": "off",
            "@typescript-eslint/no-explicit-any": "off",
            "vue/no-v-html": "off",
            "vue/multi-word-component-names": "off",
            "no-console": process.env.NODE_ENV === "production" ? "warn" : "off",
            "no-debugger": process.env.NODE_ENV === "production" ? "error" : "off"
        }
    },
    eslintPluginPrettierRecommended
);
