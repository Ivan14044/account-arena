import { defineStore } from 'pinia';

export const useHeaderStore = defineStore('header', {
    state: () => ({
        isReady: false,
        showMenu: false,
        printedText: ''
    })
});
