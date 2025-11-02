interface TelegramUser {
    id: number;
    first_name: string;
    last_name?: string;
    username?: string;
    photo_url?: string;
    auth_date: number;
    hash: string;
}

interface TelegramLoginWidget {
    auth: (options: { bot_id: string }, callback: (user: TelegramUser | null) => void) => void;
}

interface Window {
    Telegram?: {
        Login: TelegramLoginWidget;
    };
}
