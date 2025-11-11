<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Индексы для purchases таблицы
        Schema::table('purchases', function (Blueprint $table) {
            if (! $this->indexExists('purchases', 'purchases_guest_email_index')) {
                $table->index('guest_email'); // Поиск гостевых покупок
            }
            if (! $this->indexExists('purchases', 'purchases_order_number_unique')) {
                $table->unique('order_number'); // Быстрый поиск по номеру заказа
            }
            if (! $this->indexExists('purchases', 'purchases_user_id_status_index')) {
                $table->index(['user_id', 'status']); // Фильтрация покупок пользователя по статусу
            }
            if (! $this->indexExists('purchases', 'purchases_created_at_index')) {
                $table->index('created_at'); // Сортировка по дате
            }
        });

        // Индексы для transactions таблицы
        Schema::table('transactions', function (Blueprint $table) {
            if (! $this->indexExists('transactions', 'transactions_service_account_id_status_index')) {
                $table->index(['service_account_id', 'status']); // Транзакции товара по статусу
            }
            if (! $this->indexExists('transactions', 'transactions_user_id_status_index')) {
                $table->index(['user_id', 'status']); // Транзакции пользователя по статусу
            }
            if (! $this->indexExists('transactions', 'transactions_guest_email_index')) {
                $table->index('guest_email'); // Гостевые транзакции
            }
            if (! $this->indexExists('transactions', 'transactions_created_at_index')) {
                $table->index('created_at'); // Сортировка по дате
            }
        });

        // Индексы для service_accounts таблицы
        Schema::table('service_accounts', function (Blueprint $table) {
            if (! $this->indexExists('service_accounts', 'service_accounts_category_id_is_active_index')) {
                $table->index(['category_id', 'is_active']); // Каталог товаров по категории
            }
            if (! $this->indexExists('service_accounts', 'service_accounts_supplier_id_is_active_index')) {
                $table->index(['supplier_id', 'is_active']); // Товары поставщика
            }
            if (! $this->indexExists('service_accounts', 'service_accounts_is_active_index')) {
                $table->index('is_active'); // Быстрый фильтр активных товаров
            }
            if (! $this->indexExists('service_accounts', 'service_accounts_created_at_index')) {
                $table->index('created_at'); // Сортировка новинок
            }
        });

        // Индексы для users таблицы
        Schema::table('users', function (Blueprint $table) {
            if (! $this->indexExists('users', 'users_is_admin_index')) {
                $table->index('is_admin'); // Быстрый поиск админов
            }
            if (! $this->indexExists('users', 'users_is_supplier_index')) {
                $table->index('is_supplier'); // Быстрый поиск поставщиков
            }
            if (! $this->indexExists('users', 'users_is_blocked_index')) {
                $table->index('is_blocked'); // Фильтр заблокированных
            }
            if (! $this->indexExists('users', 'users_created_at_index')) {
                $table->index('created_at'); // Сортировка по дате регистрации
            }
        });

        // Индексы для promocodes таблицы
        Schema::table('promocodes', function (Blueprint $table) {
            if (! $this->indexExists('promocodes', 'promocodes_code_expires_at_index')) {
                $table->index(['code', 'expires_at']); // Проверка валидности промокода
            }
            if (! $this->indexExists('promocodes', 'promocodes_type_index')) {
                $table->index('type'); // Фильтр по типу
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if ($this->indexExists('purchases', 'purchases_guest_email_index')) {
                $table->dropIndex('purchases_guest_email_index');
            }
            if ($this->indexExists('purchases', 'purchases_order_number_unique')) {
                $table->dropUnique('purchases_order_number_unique');
            }
            if ($this->indexExists('purchases', 'purchases_user_id_status_index')) {
                $table->dropIndex('purchases_user_id_status_index');
            }
            if ($this->indexExists('purchases', 'purchases_created_at_index')) {
                $table->dropIndex('purchases_created_at_index');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if ($this->indexExists('transactions', 'transactions_service_account_id_status_index')) {
                $table->dropIndex('transactions_service_account_id_status_index');
            }
            if ($this->indexExists('transactions', 'transactions_user_id_status_index')) {
                $table->dropIndex('transactions_user_id_status_index');
            }
            if ($this->indexExists('transactions', 'transactions_guest_email_index')) {
                $table->dropIndex('transactions_guest_email_index');
            }
            if ($this->indexExists('transactions', 'transactions_created_at_index')) {
                $table->dropIndex('transactions_created_at_index');
            }
        });

        Schema::table('service_accounts', function (Blueprint $table) {
            if ($this->indexExists('service_accounts', 'service_accounts_category_id_is_active_index')) {
                $table->dropIndex('service_accounts_category_id_is_active_index');
            }
            if ($this->indexExists('service_accounts', 'service_accounts_supplier_id_is_active_index')) {
                $table->dropIndex('service_accounts_supplier_id_is_active_index');
            }
            if ($this->indexExists('service_accounts', 'service_accounts_is_active_index')) {
                $table->dropIndex('service_accounts_is_active_index');
            }
            if ($this->indexExists('service_accounts', 'service_accounts_created_at_index')) {
                $table->dropIndex('service_accounts_created_at_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_is_admin_index')) {
                $table->dropIndex('users_is_admin_index');
            }
            if ($this->indexExists('users', 'users_is_supplier_index')) {
                $table->dropIndex('users_is_supplier_index');
            }
            if ($this->indexExists('users', 'users_is_blocked_index')) {
                $table->dropIndex('users_is_blocked_index');
            }
            if ($this->indexExists('users', 'users_created_at_index')) {
                $table->dropIndex('users_created_at_index');
            }
        });

        Schema::table('promocodes', function (Blueprint $table) {
            if ($this->indexExists('promocodes', 'promocodes_code_expires_at_index')) {
                $table->dropIndex('promocodes_code_expires_at_index');
            }
            if ($this->indexExists('promocodes', 'promocodes_type_index')) {
                $table->dropIndex('promocodes_type_index');
            }
        });
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();

        $indexes = array_change_key_case($schemaManager->listTableIndexes($table), CASE_LOWER);

        return array_key_exists(strtolower($indexName), $indexes);
    }
};
