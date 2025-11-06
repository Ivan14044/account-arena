import { defineStore } from 'pinia';

export interface CartItem {
    id: number;
    title: string;
    price: number;
    quantity: number;
    image_url?: string;
    max_quantity: number; // Максимальное доступное количество
}

export const useProductCartStore = defineStore('productCart', {
    state: () => ({
        items: [] as CartItem[],
    }),
    
    getters: {
        // Получить количество уникальных товаров в корзине
        itemCount: (state) => state.items.length,
        
        // Получить общее количество всех товаров
        totalQuantity: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
        
        // Получить общую стоимость корзины
        totalAmount: (state) => state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0),
        
        // Проверить, есть ли товар в корзине
        hasProduct: (state) => (productId: number) => {
            return state.items.some(item => item.id === productId);
        },
        
        // Получить товар из корзины
        getProduct: (state) => (productId: number) => {
            return state.items.find(item => item.id === productId);
        },
        
        // Получить количество конкретного товара в корзине
        getProductQuantity: (state) => (productId: number) => {
            const item = state.items.find(item => item.id === productId);
            return item?.quantity || 0;
        },
    },
    
    actions: {
        // Добавить товар в корзину
        addItem(product: { id: number; title: string; price: number; image_url?: string; quantity: number }, quantity: number = 1) {
            const existingItem = this.items.find(item => item.id === product.id);
            
            if (existingItem) {
                // Товар уже в корзине - увеличиваем количество
                const newQuantity = existingItem.quantity + quantity;
                if (newQuantity <= product.quantity) {
                    existingItem.quantity = newQuantity;
                } else {
                    // Достигнут максимум
                    existingItem.quantity = product.quantity;
                }
            } else {
                // Добавляем новый товар
                this.items.push({
                    id: product.id,
                    title: product.title,
                    price: product.price,
                    quantity: Math.min(quantity, product.quantity),
                    image_url: product.image_url,
                    max_quantity: product.quantity,
                });
            }
            
            this.saveToLocalStorage();
        },
        
        // Удалить товар из корзины
        removeItem(productId: number) {
            this.items = this.items.filter(item => item.id !== productId);
            this.saveToLocalStorage();
        },
        
        // Обновить количество товара
        updateQuantity(productId: number, quantity: number) {
            const item = this.items.find(item => item.id === productId);
            if (item) {
                item.quantity = Math.max(1, Math.min(quantity, item.max_quantity));
                this.saveToLocalStorage();
            }
        },
        
        // Увеличить количество товара
        increaseQuantity(productId: number) {
            const item = this.items.find(item => item.id === productId);
            if (item && item.quantity < item.max_quantity) {
                item.quantity++;
                this.saveToLocalStorage();
            }
        },
        
        // Уменьшить количество товара
        decreaseQuantity(productId: number) {
            const item = this.items.find(item => item.id === productId);
            if (item && item.quantity > 1) {
                item.quantity--;
                this.saveToLocalStorage();
            }
        },
        
        // Очистить корзину
        clearCart() {
            this.items = [];
            this.saveToLocalStorage();
        },
        
        // Сохранить в localStorage
        saveToLocalStorage() {
            try {
                localStorage.setItem('product_cart', JSON.stringify(this.items));
            } catch (error) {
                console.error('Error saving cart to localStorage:', error);
            }
        },
        
        // Загрузить из localStorage
        loadFromLocalStorage() {
            try {
                const stored = localStorage.getItem('product_cart');
                if (stored) {
                    this.items = JSON.parse(stored);
                }
            } catch (error) {
                console.error('Error loading cart from localStorage:', error);
                this.items = [];
            }
        },
    },
    
    // Автоматическое сохранение в localStorage
    persist: {
        key: 'product_cart',
        storage: localStorage,
    },
});


