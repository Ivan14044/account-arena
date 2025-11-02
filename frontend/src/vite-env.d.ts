/// <reference types="vite/client" />


interface ImportMetaEnv {
    readonly VITE_APP_DOMAIN?: string;
    // add other env vars as needed
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
