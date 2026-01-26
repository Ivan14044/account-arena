{{-- Общие современные стили для всех страниц админ-панели --}}
<style>
/* ============================================
   MODERN & STRICT DESIGN SYSTEM
   Единая система дизайна для всех страниц
   ============================================ */

/* ЗАГОЛОВОК СТРАНИЦЫ */
.content-header-modern h1 {
    font-size: 1.75rem;
    color: #2c3e50;
    letter-spacing: -0.5px;
    font-weight: 300;
}

.content-header-modern p {
    color: #858796;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* КНОПКИ */
.btn-modern {
    padding: 0.5rem 1.25rem;
    font-weight: 500;
    letter-spacing: 0.3px;
    border-radius: 0.375rem;
    border: none;
    transition: all 0.2s ease;
}

.btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-modern i {
    font-size: 0.875rem;
}

/* STAT CARDS (Карточки статистики) */
.stat-card {
    background: white;
    border: 1px solid #e3e6f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    min-height: 140px; /* Фиксированная минимальная высота для выравнивания */
    height: 100%; /* Занимать всю высоту колонки */
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.stat-card-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    flex: 1; /* Занимать все доступное пространство */
    min-height: 0; /* Позволить flex-элементам сжиматься */
    position: relative;
}

/* Старый стиль для других страниц (горизонтальный layout) */
.stat-card-body:has(.stat-content) {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stat-card-primary .stat-icon {
    background: rgba(78, 115, 223, 0.1);
    color: #4e73df;
}
.stat-card-success .stat-icon {
    background: rgba(28, 200, 138, 0.1);
    color: #1cc88a;
}
.stat-card-info .stat-icon {
    background: rgba(54, 185, 204, 0.1);
    color: #36b9cc;
}
.stat-card-warning .stat-icon {
    background: rgba(246, 194, 62, 0.1);
    color: #f6c23e;
}
.stat-card-danger .stat-icon {
    background: rgba(231, 74, 59, 0.1);
    color: #e74a3b;
}

.stat-content {
    text-align: right;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 0;
    margin-left: 1rem;
}

.stat-label {
    font-size: 0.75rem;
    color: #858796;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    margin-bottom: 0.75rem;
    line-height: 1.2;
    text-align: center;
    width: 100%;
}

/* Для старого layout (когда есть .stat-content) */
.stat-content .stat-label {
    text-align: right;
    margin-bottom: 0.375rem;
    font-size: 0.7rem;
}

.stat-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1.2;
    margin-bottom: 0.75rem;
    text-align: center;
    width: 100%;
}

/* Для старого layout (когда есть .stat-content) */
.stat-content .stat-value {
    text-align: right;
    margin-bottom: 0.375rem;
    font-size: 1.75rem;
}

.stat-icon-bottom {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
    font-size: 2rem;
    color: #4e73df;
    opacity: 0.8;
}

.stat-card-primary .stat-icon-bottom {
    color: #4e73df;
}
.stat-card-success .stat-icon-bottom {
    color: #1cc88a;
}
.stat-card-info .stat-icon-bottom {
    color: #36b9cc;
}
.stat-card-warning .stat-icon-bottom {
    color: #f6c23e;
}
.stat-card-danger .stat-icon-bottom {
    color: #e74a3b;
}

/* Ссылки "Подробнее" в карточках статистики */
.stat-link {
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    margin-top: auto;
    color: #4e73df;
    transition: all 0.2s ease;
}

.stat-card-primary .stat-link {
    color: #4e73df;
}
.stat-card-success .stat-link {
    color: #1cc88a;
}
.stat-card-info .stat-link {
    color: #36b9cc;
}
.stat-card-warning .stat-link {
    color: #f6c23e;
}
.stat-card-danger .stat-link {
    color: #e74a3b;
}

.stat-link:hover {
    text-decoration: underline;
    opacity: 0.8;
}

.stat-link i {
    margin-left: 0.5rem;
    font-size: 0.75rem;
    transition: transform 0.2s ease;
}

.stat-link:hover i {
    transform: translateX(3px);
}

/* Старые ссылки в .stat-content */
.stat-content a {
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    margin-top: 0.375rem;
    transition: all 0.2s ease;
    align-self: flex-end;
}

.stat-content a:hover {
    text-decoration: underline;
}

.stat-content a i {
    margin-left: 0.5rem;
    font-size: 0.75rem;
    transition: transform 0.2s ease;
}

.stat-content a:hover i {
    transform: translateX(3px);
}

/* Компактные карточки статистики (для страниц без ссылок "Подробнее") */
.stat-card-compact {
    min-height: 120px !important;
    padding: 1.25rem !important;
}

.stat-card-compact .stat-label {
    font-size: 0.7rem !important;
    margin-bottom: 0.5rem !important;
}

.stat-card-compact .stat-value {
    font-size: 1.75rem !important;
    margin-bottom: 0.5rem !important;
}

.stat-card-compact .stat-icon-bottom {
    font-size: 1.5rem !important;
    margin-bottom: 0.5rem !important;
}

.stat-card-compact .stat-link {
    font-size: 0.75rem !important;
    margin-top: auto !important;
}

.stat-card-compact .stat-link i {
    font-size: 0.7rem !important;
    margin-left: 0.375rem !important;
}

/* КАРТОЧКИ */
.card-modern {
    border: 1px solid #e3e6f0;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.card-header-modern {
    background: white;
    border-bottom: 2px solid #e3e6f0;
    padding: 1.25rem 1.5rem;
    overflow: hidden;
}

.card-header-modern.p-0 {
    overflow: visible;
}

.card-header-modern h5,
.card-header-modern h3 {
    color: #2c3e50;
    font-weight: 500;
    margin-bottom: 0;
}

.card-header-modern small {
    color: #858796;
    font-size: 0.875rem;
}

/* Card Header Content Layout */
.card-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.card-header-title {
    flex: 0 0 auto;
}

.card-header-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.sort-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sort-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0;
    white-space: nowrap;
}

.sort-select {
    display: inline-block;
    width: auto;
    min-width: 180px;
}

.card-body-modern {
    padding: 0;
}

/* ФИЛЬТРЫ */
.filters-container {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-group-filter {
    background: #f8f9fc;
    border-radius: 0.375rem;
    padding: 0.25rem;
}

.btn-filter {
    background: transparent;
    border: none;
    color: #5a6c7d;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
}

.btn-filter:hover {
    background: rgba(0,0,0,0.05);
    color: #2c3e50;
}

.btn-filter.active {
    background: white;
    color: #4e73df;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

/* ТАБЛИЦЫ */
.modern-table {
    font-size: 0.875rem;
    margin-bottom: 0;
}

.modern-table thead th {
    background: #f8f9fc;
    border-top: none;
    border-bottom: 2px solid #e3e6f0;
    color: #5a6c7d;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem 1.25rem;
}

.modern-table tbody td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.modern-table tbody tr {
    transition: background-color 0.2s ease;
}

.modern-table tbody tr:hover {
    background-color: #f8f9fc;
}

.modern-table tbody tr:last-child td {
    border-bottom: none;
}

/* БЕЙДЖИ */
.badge-modern {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0.25rem;
    letter-spacing: 0.3px;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.badge-warning {
    background: #fef3c7;
    color: #78350f;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-primary {
    background: #dbeafe;
    color: #1e3a8a;
}

.badge-secondary {
    background: #f3f4f6;
    color: #374151;
}

/* ФОРМЫ */
.form-group-modern {
    margin-bottom: 1.5rem;
}

.form-label-modern {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control-modern {
    border: 1px solid #d1d3e2;
    border-radius: 0.375rem;
    padding: 0.65rem 1rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.form-control-modern:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
    outline: none;
}

/* РАЗДЕЛИТЕЛИ */
.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e3e6f0;
}

.section-divider {
    border-top: 2px solid #e3e6f0;
    margin: 2rem 0;
}

/* МОДАЛЬНЫЕ ОКНА */
.modal-modern {
    border: none;
    border-radius: 0.75rem;
    overflow: hidden;
}

.modal-header-modern {
    background: #f8f9fc;
    border-bottom: 2px solid #e3e6f0;
    padding: 1.5rem 2rem;
}

.modal-header-modern .modal-title {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.125rem;
}

.modal-body-modern {
    padding: 2rem;
}

.modal-footer-modern {
    background: #f8f9fc;
    border-top: 2px solid #e3e6f0;
    padding: 1.25rem 2rem;
}

/* ALERTS */
.alert-modern {
    border: none;
    border-left: 4px solid;
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #d1fae5;
    border-left-color: #10b981;
    color: #065f46;
}

.alert-danger {
    background: #fee2e2;
    border-left-color: #ef4444;
    color: #991b1b;
}

.alert-warning {
    background: #fef3c7;
    border-left-color: #f59e0b;
    color: #78350f;
}

.alert-info {
    background: #dbeafe;
    border-left-color: #3b82f6;
    color: #1e3a8a;
}

/* АНИМАЦИИ */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card {
    animation: fadeIn 0.4s ease;
}

.stat-card:nth-child(1) { animation-delay: 0s; }
.stat-card:nth-child(2) { animation-delay: 0.1s; }
.stat-card:nth-child(3) { animation-delay: 0.2s; }
.stat-card:nth-child(4) { animation-delay: 0.3s; }

/* КНОПКИ ДЕЙСТВИЙ */
.action-buttons .btn {
    border-radius: 0.25rem;
    margin: 0 2px;
    transition: all 0.2s ease;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}

/* ПАГИНАЦИЯ */
.pagination-modern .page-link {
    border: 1px solid #e3e6f0;
    color: #5a6c7d;
    margin: 0 2px;
    border-radius: 0.25rem;
}

.pagination-modern .page-link:hover {
    background: #f8f9fc;
    border-color: #4e73df;
    color: #4e73df;
}

.pagination-modern .page-item.active .page-link {
    background: #4e73df;
    border-color: #4e73df;
}

/* ТИПОГРАФИКА */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #2c3e50;
}

.font-weight-light {
    font-weight: 300 !important;
}

.font-weight-500 {
    font-weight: 500 !important;
}

.text-muted {
    color: #858796 !important;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #858796;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* ТЕНИ */
.shadow-modern {
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.shadow-modern-lg {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

/* ТАБЫ */
.nav-tabs-modern {
    border-bottom: 2px solid #e3e6f0;
    padding: 0 1.5rem;
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 transparent;
}

.nav-tabs-modern::-webkit-scrollbar {
    height: 6px;
}

.nav-tabs-modern::-webkit-scrollbar-track {
    background: transparent;
}

.nav-tabs-modern::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}

.nav-tabs-modern::-webkit-scrollbar-thumb:hover {
    background-color: #a0aec0;
}

.nav-tabs-modern .nav-item {
    margin-bottom: -2px;
    flex-shrink: 0;
}

.nav-tabs-modern .nav-link {
    border: none;
    color: #5a6c7d;
    padding: 1rem 1.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-bottom: 2px solid transparent;
    white-space: nowrap;
}

.nav-tabs-modern .nav-link:hover {
    color: #4e73df;
    border-bottom-color: #e3e6f0;
}

.nav-tabs-modern .nav-link.active {
    color: #4e73df;
    background: transparent;
    border-bottom-color: #4e73df;
}

/* ДОПОЛНИТЕЛЬНЫЕ УТИЛИТЫ */
.border-modern {
    border: 1px solid #e3e6f0 !important;
}

.bg-modern {
    background: #f8f9fc !important;
}

.text-primary-modern {
    color: #4e73df !important;
}

.text-success-modern {
    color: #1cc88a !important;
}

.text-danger-modern {
    color: #e74a3b !important;
}

/* RESPONSIVE - MOBILE OPTIMIZATIONS */
@media (max-width: 768px) {
    /* ========================================
       MOBILE-FIRST RESPONSIVE DESIGN
       ======================================== */
    
    /* Content Header */
    .content-header-modern {
        padding: 1rem 0;
    }
    
    .content-header-modern .d-flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .content-header-modern h1 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .content-header-modern > div > div:last-child {
        width: 100%;
    }
    
    .content-header-modern .btn-modern {
        width: 100%;
        justify-content: center;
        min-height: 44px; /* Touch-friendly */
    }
    
    .content-header-modern .form-control {
        height: auto !important;
        min-height: 44px;
        padding: 0.5rem 1rem;
        font-size: 1rem;
        color: #495057;
        background-color: #fff;
    }
    
    /* ========================================
       STATISTICS CARDS - FULL WIDTH
       ======================================== */
    .row.mb-4 {
        margin-bottom: 1rem !important;
    }
    
    .row.mb-4 > [class*="col-"] {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
        margin-bottom: 0.75rem;
    }
    
    .stat-card {
        padding: 1.25rem;
        margin-bottom: 0;
    }
    
    .stat-card-body {
        flex-direction: row !important;
        align-items: center !important;
        gap: 1rem;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .stat-content {
        text-align: left !important;
        width: 100%;
        flex: 1;
    }
    
    .stat-label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
    }
    
    /* ========================================
       CATEGORY FILTERS - VERTICAL STACK
       ======================================== */
    .card-header-modern .d-flex {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    
    .card-header-modern select,
    .card-header-modern button {
        width: 100% !important;
        min-height: 44px;
        margin-bottom: 0.5rem;
    }
    
    .category-filters-main {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .category-group {
        width: 100%;
    }
    
    .btn-group-filter {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-filter {
        width: 100% !important;
        min-height: 48px;
        border-radius: 0.375rem !important;
        margin: 0 !important;
        margin-bottom: 0.5rem !important;
        text-align: left;
        padding: 0.75rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9375rem;
    }
    
    .btn-filter .badge {
        margin-left: auto;
    }
    
    .btn-filter .category-arrow {
        margin-left: 0.5rem;
    }
    
    .subcategories-container {
        padding-left: 1rem;
        margin-top: 0.5rem !important;
    }
    
    .btn-group-filter-sub {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-category-sub {
        background-color: #f8f9fa;
        border-left: 3px solid #4e73df;
    }
    
    /* ========================================
       TABLE CONTROLS - MOBILE FRIENDLY
       ======================================== */
    .card-header-content {
        flex-direction: column !important;
        gap: 1rem;
    }
    
    .card-header-title,
    .card-header-controls {
        width: 100%;
    }
    
    .filters-container {
        width: 100%;
        margin-bottom: 0.75rem;
    }
    
    .filters-container .btn-group-filter {
        width: 100%;
        display: flex;
    }
    
    .filters-container .btn-filter {
        flex: 1;
        min-height: 44px;
        font-size: 0.875rem;
    }
    
    .sort-container {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .sort-label {
        font-size: 0.875rem;
        margin-bottom: 0;
    }
    
    .sort-select {
        width: 100%;
        min-height: 44px;
    }
    
    /* ========================================
       PRODUCT CARDS - MOBILE LAYOUT
       ======================================== */
    @media (max-width: 575px) {
        .table-responsive {
            border: none;
            overflow: visible !important;
        }
        
        .products-table {
            display: block;
            width: 100%;
        }
        
        .products-table thead {
            display: none;
        }
        
        .products-table tbody {
            display: block;
            width: 100%;
        }
        
        .products-table tr {
            display: block;
            width: 100%;
            border: 1px solid #e3e6f0;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: relative;
        }
        
        .products-table tr:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        /* Hide checkbox and drag handle on mobile */
        .products-table td:nth-child(1),
        .products-table td:nth-child(2) {
            display: none !important;
        }
        
        .products-table td {
            display: block;
            width: 100%;
            border: none;
            padding: 0.5rem 0;
            text-align: left !important;
        }
        
        /* Product ID - Top Right Badge */
        .products-table td:nth-child(3) {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: auto;
            padding: 0.25rem 0.75rem;
            background: #e3e6f0;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #5a6c7d;
        }
        
        /* SKU */
        .products-table td:nth-child(4) {
            font-size: 0.75rem;
            color: #858796;
            margin-bottom: 0.5rem;
        }
        
        /* Product Image */
        .products-table td:nth-child(5) {
            width: 80px;
            height: 80px;
            float: left;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .products-table td:nth-child(5) img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        
        /* Product Title */
        .products-table td:nth-child(6) {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            padding-top: 0;
        }
        
        /* Category */
        .products-table td:nth-child(7) {
            clear: both;
            font-size: 0.875rem;
            color: #5a6c7d;
            margin-bottom: 0.5rem;
        }
        
        .products-table td:nth-child(7):before {
            content: "📁 ";
        }
        
        /* Supplier */
        .products-table td:nth-child(8) {
            font-size: 0.875rem;
            color: #5a6c7d;
            margin-bottom: 0.5rem;
        }
        
        .products-table td:nth-child(8):before {
            content: "👤 ";
        }
        
        /* Price */
        .products-table td:nth-child(9) {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1cc88a;
            margin-bottom: 0.5rem;
        }
        
        /* Stock */
        .products-table td:nth-child(10) {
            display: inline-block;
            width: auto;
            padding: 0.375rem 0.75rem;
            background: #e7f3ff;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4e73df;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .products-table td:nth-child(10):before {
            content: "📦 ";
        }
        
        /* Sold */
        .products-table td:nth-child(11) {
            display: inline-block;
            width: auto;
            padding: 0.375rem 0.75rem;
            background: #fff3e0;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #f6c23e;
            margin-bottom: 0.5rem;
        }
        
        .products-table td:nth-child(11):before {
            content: "🛒 ";
        }
        
        /* Status */
        .products-table td:nth-child(12) {
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        /* Actions */
        .products-table td:nth-child(13) {
            padding-top: 1rem;
            border-top: 1px solid #e3e6f0;
            margin-top: 1rem;
        }
        
        .products-table td:nth-child(13) .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .products-table td:nth-child(13) .btn {
            flex: 1;
            min-width: calc(50% - 0.25rem);
            min-height: 44px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        /* Created Date */
        .products-table td:nth-child(14) {
            font-size: 0.75rem;
            color: #858796;
            text-align: center;
            padding-top: 0.5rem;
            border-top: 1px solid #f8f9fa;
        }
        
        .products-table td:nth-child(14):before {
            content: "🕒 ";
        }
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .action-buttons .btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        min-width: 44px;
        min-height: 44px;
    }
    
    .action-buttons .btn i {
        margin: 0;
    }
    
    /* Forms */
    .form-group-modern {
        margin-bottom: 1.25rem;
    }
    
    .form-control-modern,
    .form-control {
        font-size: 16px; /* Prevents zoom on iOS */
        padding: 0.75rem 1rem;
    }
    
    /* Multi-column forms stack vertically */
    .row > [class*="col-"] {
        margin-bottom: 1rem;
    }
    
    /* Buttons */
    .btn-modern {
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        width: 100%;
        margin-bottom: 0.5rem;
        border-radius: 0.375rem !important;
    }
    
    /* Filters */
    .filters-container {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .btn-group-filter {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-filter {
        width: 100%;
        text-align: left;
        padding: 0.75rem 1rem;
    }
    
    /* Modals */
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
    
    .modal-content {
        border-radius: 0.5rem;
    }
    
    .modal-header-modern {
        padding: 1rem 1.25rem;
    }
    
    .modal-header-modern .modal-title {
        font-size: 1rem;
    }
    
    .modal-body-modern {
        padding: 1.25rem;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
    
    .modal-footer-modern {
        padding: 1rem 1.25rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .modal-footer-modern .btn {
        width: 100%;
        margin: 0;
    }
    
    /* Cards */
    .card-modern {
        margin-bottom: 1rem;
    }
    
    .card-header-modern {
        padding: 1rem 1.25rem;
    }
    
    .card-header-modern h5,
    .card-header-modern h3 {
        font-size: 1rem;
    }
    
    /* Card Header Mobile Layout */
    .card-header-content {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem;
    }
    
    .card-header-controls {
        width: 100%;
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.75rem;
    }
    
    .filters-container {
        width: 100%;
    }
    
    .sort-container {
        width: 100%;
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.5rem;
    }
    
    .sort-label {
        font-size: 0.8rem;
    }
    
    .sort-select {
        width: 100% !important;
        min-width: 100% !important;
    }
    
    /* Tabs */
    .nav-tabs-modern {
        padding: 0 0.75rem;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        flex-wrap: nowrap;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e0 transparent;
    }
    
    .nav-tabs-modern::-webkit-scrollbar {
        height: 4px;
    }
    
    .nav-tabs-modern::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .nav-tabs-modern::-webkit-scrollbar-thumb {
        background-color: #cbd5e0;
        border-radius: 2px;
    }
    
    .nav-tabs-modern .nav-link {
        padding: 0.75rem 0.875rem;
        white-space: nowrap;
        font-size: 0.875rem;
        flex-shrink: 0;
    }
    
    .nav-tabs-modern .nav-link i {
        margin-right: 0.25rem;
    }
    
    /* Pagination */
    .pagination-modern {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination-modern .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    /* DataTables Mobile Controls */
    .dataTables_wrapper .row {
        margin: 0;
    }
    
    .dataTables_wrapper .row > div {
        padding: 0.5rem 0;
    }
    
    .dataTables_length,
    .dataTables_filter {
        width: 100% !important;
        margin-bottom: 1rem;
    }
    
    .dataTables_length label,
    .dataTables_filter label {
        flex-direction: column;
        width: 100%;
        font-size: 0.875rem;
    }
    
    .dataTables_length select {
        width: 100% !important;
        max-width: 100%;
        margin-top: 0.5rem;
    }
    
    .dataTables_filter input {
        width: 100% !important;
        max-width: 100%;
        margin-top: 0.5rem;
    }
    
    .dataTables_info {
        font-size: 0.75rem;
        padding: 0.75rem 0;
        text-align: center;
        width: 100%;
    }
    
    .dataTables_paginate {
        width: 100%;
        text-align: center;
        margin-top: 0.75rem;
    }
    
    .dataTables_paginate .pagination {
        justify-content: center;
        flex-wrap: wrap;
        margin: 0;
    }
    
    .dataTables_paginate .paginate_button {
        padding: 0.5rem 0.625rem;
        font-size: 0.8rem;
        margin: 0.125rem;
        min-width: 44px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    /* DataTables Mobile Optimization */
    .dataTables_wrapper {
        overflow-x: auto;
    }
    
    .dataTables_length,
    .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .dataTables_length label,
    .dataTables_filter label {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
        margin-bottom: 0;
    }
    
    .dataTables_length select,
    .dataTables_filter input {
        width: 100% !important;
        max-width: 100%;
    }
    
    .dataTables_info {
        font-size: 0.8rem;
        padding: 0.5rem 0;
        text-align: center;
    }
    
    .dataTables_paginate {
        text-align: center;
        margin-top: 1rem;
    }
    
    .dataTables_paginate .pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.5rem;
        font-size: 0.875rem;
        margin: 0.125rem;
        min-width: 36px;
    }
    
    /* Alerts */
    .alert-modern {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    /* Breadcrumbs */
    .breadcrumb {
        font-size: 0.8rem;
        padding: 0.5rem 0;
    }
    
    /* Empty State */
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-state i {
        font-size: 2rem;
    }
}

/* Extra Small Devices (phones, 320px and up) */
@media (max-width: 575px) {
    .content-header-modern h1 {
        font-size: 1.25rem;
    }
    
    .stat-value {
        font-size: 1.25rem;
    }
    
    .stat-label {
        font-size: 0.7rem;
    }
    
    .modern-table {
        font-size: 0.75rem;
    }
    
    .modern-table thead th {
        padding: 0.5rem 0.375rem;
        font-size: 0.65rem;
    }
    
    .modern-table tbody td {
        padding: 0.5rem 0.375rem;
    }
    
    .btn-modern {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }
    
    .modal-dialog {
        margin: 0.25rem;
        max-width: calc(100% - 0.5rem);
    }
    
    .modal-body-modern {
        padding: 1rem;
    }
}

/* Tablet Portrait (768px - 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    .stat-card-body {
        flex-direction: row;
    }
    
    .stat-content {
        text-align: right;
    }
    
    .content-header-modern .d-flex {
        flex-direction: row;
    }
    
    .content-header-modern .btn-modern {
        width: auto;
    }
}

/* Touch-friendly improvements for all mobile devices */
@media (hover: none) and (pointer: coarse) {
    /* Increase touch target sizes */
    .btn,
    .btn-modern {
        min-height: 44px; /* iOS recommended touch target */
        min-width: 44px;
    }
    
    .action-buttons .btn {
        min-height: 44px;
        min-width: 44px;
    }
    
    /* Remove hover effects on touch devices */
    .stat-card:hover {
        transform: none;
    }
    
    .btn-modern:hover {
        transform: none;
    }
    
    /* Improve tap feedback */
    .btn:active,
    .btn-modern:active {
        opacity: 0.8;
        transform: scale(0.98);
    }
}

/* ============================================
   УЛУЧШЕНИЯ ДЛЯ ФОРМ И ФИЛЬТРОВ
   ============================================ */

/* Input Group с иконками для date picker */
.input-group-sm .input-group-prepend .input-group-text {
    background-color: #f8f9fc;
    border-color: #d1d3e2;
    color: #5a6c7d;
    padding: 0.375rem 0.75rem;
}

.input-group-sm .input-group-prepend .input-group-text i {
    font-size: 0.875rem;
}

.input-group-sm .form-control {
    border-left: none;
}

.input-group-sm .input-group-prepend + .form-control {
    border-left: 1px solid #d1d3e2;
}

.input-group-sm .form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
}

/* Улучшение spacing для форм с фильтрами */
.form-label {
    font-weight: 500;
    color: #5a6c7d;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

/* Отступы между элементами формы в row */
.row > [class*="col-"].mb-3 {
    margin-bottom: 1rem !important;
}

/* Улучшение внешнего вида кнопок в формах */
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
}

.btn-sm i {
    font-size: 0.75rem;
}

/* Улучшение spacing для card-body с формами */
.card-body.border-bottom {
    padding: 1.25rem;
    border-bottom: 1px solid #e3e6f0 !important;
}

/* Улучшение spacing для элементов в d-flex */
.d-flex.align-items-end {
    gap: 0.5rem;
}

.d-flex.align-items-end .btn {
    margin-right: 0.5rem;
}

.d-flex.align-items-end .btn:last-child {
    margin-right: 0;
}

/* Заголовки секций dashboard */
.dashboard-section-header {
    font-size: 1rem;
    font-weight: 600;
    color: #5a6c7d;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e3e6f0;
}

.dashboard-section-header i {
    color: #4e73df;
    margin-right: 0.5rem;
}

.dashboard-section-header .badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    vertical-align: middle;
}

/* Компактные отступы для dashboard */
.dashboard-section-header + .row {
    margin-top: 0.5rem;
}

/* Улучшенная центровка для stat-card-body */
.stat-card-body {
    align-items: center;
    gap: 1rem;
}

.stat-content {
    min-width: 0; /* Позволяет тексту сжиматься */
}

/* ========================================
   MANUAL DELIVERY PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 768px) {
    /* Filter Form - Full Width Inputs */
    .card-body.border-bottom form.row {
        margin: 0 !important;
    }
    
    .card-body.border-bottom form.row > div[class*="col-"] {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
        padding-left: 0;
        padding-right: 0;
    }
    
    .card-body.border-bottom .form-control,
    .card-body.border-bottom .input-group {
        width: 100%;
        min-height: 44px;
    }
    
    .card-body.border-bottom .btn {
        width: 100%;
        min-height: 44px;
        margin-bottom: 0.5rem;
    }
    
    .card-body.border-bottom .d-flex.align-items-end {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    
    .card-body.border-bottom .btn.mr-2 {
        margin-right: 0 !important;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 575px) {
    /* Manual Delivery Stats - 2x2 Grid Centered (Fixed Specificity) */
    .row.manual-delivery-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    /* Increased specificity to override global .row.mb-4 rules */
    .row.manual-delivery-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }
    
    .manual-delivery-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 0 !important;
    }
    
    .manual-delivery-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }
    
    /* Reset icon positioning */
    .manual-delivery-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        background: rgba(0,0,0,0.05) !important;
        margin: 0 auto 0.5rem auto !important;
        opacity: 1 !important;
    }
    
    .manual-delivery-stats .stat-icon i {
        font-size: 1.25rem !important;
    }
    
    /* Force specific colors */
    .manual-delivery-stats .stat-card-warning .stat-icon i { color: #f6c23e !important; }
    .manual-delivery-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .manual-delivery-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }
    .manual-delivery-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    
    /* Ensure content container is centered and full width */
    .manual-delivery-stats .stat-content {
        width: 100% !important;
        text-align: center !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .manual-delivery-stats .stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.25rem;
        color: #2c3e50;
        text-align: center !important;
        width: 100%;
        display: block;
    }
    
    .manual-delivery-stats .stat-label {
        font-size: 0.7rem !important;
        line-height: 1.2;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center !important;
        width: 100%;
        display: block;
        padding: 0 !important;
    }

    /* Orders Table - Card Layout */
    .card-body .table-responsive {
        border: none;
        overflow: visible !important;
    }
    
    .manual-delivery-table {
        display: block;
        width: 100%;
    }
    
    .manual-delivery-table thead {
        display: none;
    }
    
    .manual-delivery-table tbody {
        display: block;
        width: 100%;
    }
    
    .manual-delivery-table tr {
        display: block;
        width: 100%;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: relative;
    }
    
    .manual-delivery-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0.25rem 0 !important;
        text-align: left !important;
    }
    
    /* Order Number & Status Row */
    .manual-delivery-table td:nth-child(1) {
        font-size: 1.125rem;
        font-weight: 700;
        color: #4e73df;
        border-bottom: 1px solid #f8f9fa;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.5rem;
        display: inline-block;
        width: auto;
    }
    
    .manual-delivery-table td:nth-child(1):before {
        content: "#";
        margin-right: 1px;
    }
    
    /* Status Badge moved next to Order Number */
    .manual-delivery-table td:nth-child(2) {
        display: inline-block;
        width: auto;
        float: right;
        padding: 0 !important;
        margin-top: 0.25rem;
    }
    
    /* Customer */
    .manual-delivery-table td:nth-child(3) {
        clear: both;
        background: #f8f9fa;
        padding: 0.5rem !important;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .manual-delivery-table td:nth-child(3):before {
        content: "👤 ";
        margin-right: 0.25rem;
    }
    
    .manual-delivery-table td:nth-child(3) div {
        display: inline-block;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .manual-delivery-table td:nth-child(3) small {
        display: block;
        font-size: 0.75rem;
        margin-top: 0.125rem;
        color: #858796;
    }
    
    /* Product */
    .manual-delivery-table td:nth-child(4) {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.9375rem;
        color: #2c3e50;
    }
    
    .manual-delivery-table td:nth-child(4):before {
        content: "📦 ";
    }
    
    /* Quantity & Amount - Side by Side */
    .manual-delivery-table td:nth-child(5) {
        display: inline-block;
        width: 48%;
        padding-right: 0.25rem !important;
        vertical-align: middle;
    }
    
    .manual-delivery-table td:nth-child(5) .badge {
        font-size: 0.8rem;
        padding: 0.375rem 0.5rem;
        width: 100%;
        text-align: center;
        background-color: #e7f3ff;
        color: #4e73df;
    }
    
    .manual-delivery-table td:nth-child(6) {
        display: inline-block;
        width: 48%;
        padding-left: 0.25rem !important;
        text-align: right !important;
        vertical-align: middle;
    }
    
    .manual-delivery-table td:nth-child(6) strong {
        font-size: 1.125rem;
        color: #1cc88a;
    }
    
    /* Created */
    .manual-delivery-table td:nth-child(7) {
        font-size: 0.75rem;
        color: #858796;
        margin-top: 0.5rem;
    }
    
    .manual-delivery-table td:nth-child(7):before {
        content: "📅 Создан: ";
    }
    
    /* Processing time */
    .manual-delivery-table td:nth-child(8) {
        display: none;
    }
    
    /* Actions */
    .manual-delivery-table td:nth-child(9) {
        margin-top: 0.75rem;
        padding-top: 0.75rem !important;
        border-top: 1px solid #e3e6f0;
    }
    
    .manual-delivery-table td:nth-child(9) .btn {
        width: 100%;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    /* Pagination */
    .card-footer {
        padding: 1rem;
    }
    
    .card-footer .pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .card-footer .page-link {
        min-width: 40px;
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0.125rem;
    }
}

/* ========================================
   PURCHASES PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 768px) {
    /* Stats - 2x2 Grid on Tablets */
    .row.mb-4 > .col-lg-3.col-md-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
    
    /* Filter Form - Full Width */
    .card-modern .card-body form .row > div[class*="col-"] {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    
    .card-modern .card-body .form-control-modern,
    .card-modern .card-body .input-group {
        min-height: 44px;
    }
    
    .card-modern .card-body .btn-modern {
        width: 100%;
        min-height: 44px;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 575px) {
    /* Stats - Full Width on Mobile */
    .row.mb-4 > .col-lg-3.col-md-6 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    
    /* Purchases Table - Card Layout */
    .purchases-table {
        display: block;
        width: 100%;
    }
    
    .purchases-table thead {
        display: none;
    }
    
    .purchases-table tbody {
        display: block;
        width: 100%;
    }
    
    .purchases-table tr {
        display: block;
        width: 100%;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: relative;
    }
    
    .purchases-table tr:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .purchases-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0.5rem 0 !important;
        text-align: left !important;
    }
    
    /* Purchase ID - Top Right Badge */
    .purchases-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto;
        padding: 0 !important;
    }
    
    .purchases-table td:nth-child(1) .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }
    
    /* Order Number - Top Left */
    .purchases-table td:nth-child(2) {
        font-size: 1.125rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem !important;
        border-bottom: 2px solid #e3e6f0;
    }
    
    .purchases-table td:nth-child(2):before {
        content: "📋 ";
        font-size: 1.25rem;
    }
    
    .purchases-table td:nth-child(2) code {
        font-size: 1rem;
    }
    
    /* Buyer */
    .purchases-table td:nth-child(3) {
        margin-bottom: 0.75rem;
        padding: 0.75rem !important;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }
    
    .purchases-table td:nth-child(3):before {
        content: "👤 Покупатель";
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #5a6c7d;
        text-transform: uppercase;
        margin-bottom: 0.375rem;
    }
    
    .purchases-table td:nth-child(3) .d-flex {
        display: block !important;
    }
    
    .purchases-table td:nth-child(3) .mr-2 {
        display: none;
    }
    
    .purchases-table td:nth-child(3) a,
    .purchases-table td:nth-child(3) span {
        font-size: 0.9375rem;
    }
    
    .purchases-table td:nth-child(3) small {
        font-size: 0.8125rem;
    }
    
    /* Product */
    .purchases-table td:nth-child(4) {
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.75rem;
    }
    
    .purchases-table td:nth-child(4):before {
        content: "📦 ";
    }
    
    .purchases-table td:nth-child(4) a {
        color: #2c3e50 !important;
        text-decoration: none;
    }
    
    /* Quantity */
    .purchases-table td:nth-child(5) {
        display: inline-block;
        width: auto;
        margin-right: 0.5rem;
        margin-bottom: 0.75rem;
    }
    
    .purchases-table td:nth-child(5) .badge {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* Amount */
    .purchases-table td:nth-child(6) {
        display: inline-block;
        width: auto;
        margin-bottom: 0.75rem;
    }
    
    .purchases-table td:nth-child(6) .font-weight-bold {
        font-size: 1.25rem;
        color: #1cc88a;
    }
    
    .purchases-table td:nth-child(6) small {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.75rem;
    }
    
    /* Status */
    .purchases-table td:nth-child(7) {
        margin-bottom: 0.75rem;
    }
    
    .purchases-table td:nth-child(7):before {
        content: "📊 Статус: ";
        font-weight: 600;
        color: #5a6c7d;
        margin-right: 0.5rem;
    }
    
    .purchases-table td:nth-child(7) .badge {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* Date */
    .purchases-table td:nth-child(8) {
        font-size: 0.875rem;
        color: #858796;
        margin-bottom: 1rem;
    }
    
    .purchases-table td:nth-child(8):before {
        content: "🕒 ";
    }
    
    .purchases-table td:nth-child(8) small {
        display: inline;
    }
    
    .purchases-table td:nth-child(8) br {
        display: none;
    }
    
    .purchases-table td:nth-child(8) i {
        margin: 0 0.25rem;
    }
    
    /* Actions */
    .purchases-table td:nth-child(9) {
        padding-top: 1rem !important;
        border-top: 1px solid #e3e6f0;
    }
    
    .purchases-table td:nth-child(9) .btn {
        width: 100%;
        min-height: 48px;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .purchases-table td:nth-child(9) .btn:before {
        content: "Просмотр ";
    }
    
    .purchases-table td:nth-child(9) .btn i {
        font-size: 1.125rem;
    }
}

/* ========================================
   SUPPORT CHATS PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 768px) {
    /* Header Filters - Vertical Stack */
    .content-header-modern .d-flex {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 1rem !important;
    }
    
    .content-header-modern .gap-2 {
        display: flex;
        flex-direction: column;
        gap: 0.5rem !important;
        width: 100%;
    }
    
    .content-header-modern .btn-modern {
        width: 100%;
        min-height: 44px;
        justify-content: center;
    }
}

@media (max-width: 575px) {
    /* Support Chats Table - Card Layout */
    .support-chats-table {
        display: block;
        width: 100%;
    }
    
    .support-chats-table thead {
        display: none;
    }
    
    .support-chats-table tbody {
        display: block;
        width: 100%;
    }
    
    .support-chats-table tr {
        display: block;
        width: 100%;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: relative;
    }
    
    .support-chats-table tr.row-unread {
        background: rgba(78, 115, 223, 0.05);
        border-left: 4px solid #4e73df;
    }
    
    .support-chats-table tr:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .support-chats-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0.5rem 0 !important;
        text-align: left !important;
    }
    
    /* Chat ID - Top Right Badge */
    .support-chats-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto;
        padding: 0 !important;
    }
    
    .support-chats-table td:nth-child(1) .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }
    
    /* User - Top with Avatar */
    .support-chats-table td:nth-child(2) {
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem !important;
        border-bottom: 2px solid #e3e6f0;
    }
    
    .support-chats-table td:nth-child(2) .d-flex {
        display: flex !important;
        align-items: center;
    }
    
    .support-chats-table td:nth-child(2) .avatar-circle-sm {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
        margin-right: 0.75rem;
    }
    
    .support-chats-table td:nth-child(2) .font-weight-bold {
        font-size: 1.125rem;
        margin-bottom: 0.25rem;
    }
    
    .support-chats-table td:nth-child(2) .unread-badge-modern {
        display: inline-block;
        margin-left: 0.5rem;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Email/Contact */
    .support-chats-table td:nth-child(3) {
        font-size: 0.875rem;
        color: #858796;
        margin-bottom: 0.75rem;
    }
    
    .support-chats-table td:nth-child(3):before {
        content: "📧 ";
    }
    
    .support-chats-table td:nth-child(3) .text-info {
        color: #36b9cc !important;
    }
    
    /* Source */
    .support-chats-table td:nth-child(4) {
        display: inline-block;
        width: auto;
        margin-right: 0.5rem;
        margin-bottom: 0.75rem;
    }
    
    .support-chats-table td:nth-child(4) .badge {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* Status */
    .support-chats-table td:nth-child(5) {
        display: inline-block;
        width: auto;
        margin-bottom: 0.75rem;
    }
    
    .support-chats-table td:nth-child(5) .badge {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* Assigned Admin */
    .support-chats-table td:nth-child(6) {
        margin-bottom: 0.75rem;
        padding: 0.75rem !important;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }
    
    .support-chats-table td:nth-child(6):before {
        content: "👤 Назначен: ";
        font-weight: 600;
        color: #5a6c7d;
    }
    
    .support-chats-table td:nth-child(6) .text-dark {
        font-weight: 600;
    }
    
    .support-chats-table td:nth-child(6) .italic {
        font-style: italic;
    }
    
    /* Last Activity */
    .support-chats-table td:nth-child(7) {
        font-size: 0.875rem;
        color: #858796;
        margin-bottom: 0.75rem;
    }
    
    .support-chats-table td:nth-child(7):before {
        content: "🕒 ";
    }
    
    /* Rating */
    .support-chats-table td:nth-child(8) {
        margin-bottom: 1rem;
    }
    
    .support-chats-table td:nth-child(8):before {
        content: "⭐ Оценка: ";
        font-weight: 600;
        color: #5a6c7d;
        margin-right: 0.5rem;
    }
    
    .support-chats-table td:nth-child(8) .d-flex {
        display: inline-flex !important;
        flex-direction: row;
        align-items: center;
    }
    
    .support-chats-table td:nth-child(8) .text-warning {
        font-size: 1rem;
    }
    
    .support-chats-table td:nth-child(8) .fa-comment-dots {
        margin-left: 0.5rem;
    }
    
    /* Actions */
    .support-chats-table td:nth-child(9) {
        padding-top: 1rem !important;
        border-top: 1px solid #e3e6f0;
    }
    
    .support-chats-table td:nth-child(9) .action-buttons {
        display: flex;
        justify-content: stretch;
    }
    
    .support-chats-table td:nth-child(9) .btn {
        width: 100%;
        min-height: 48px;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .support-chats-table td:nth-child(9) .btn i {
        font-size: 1.125rem;
    }
    
    /* Pagination */
    .card-footer-modern .pagination {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .card-footer-modern .page-link {
        min-width: 44px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0.125rem;
    }
}
/* ========================================
   DASHBOARD - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* 2x2 Grid for Dashboard Stats */
    .dashboard-stats-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.5rem;
        margin-left: -0.5rem;
    }
    
    .dashboard-stats-row > div {
        flex: 0 0 50%;
        max-width: 50%;
        padding-right: 0.5rem;
        padding-left: 0.5rem;
    }
    
    .dashboard-stats-row .stat-card {
        padding: 0.75rem !important;
        height: 100%;
        min-height: 110px;
    }
    
    .dashboard-stats-row .stat-card-body {
        position: relative;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.25rem !important;
        height: 100%;
        justify-content: space-between;
    }
    
    .dashboard-stats-row .stat-icon-bg {
        font-size: 1.5rem;
        top: -0.25rem;
        right: -0.25rem;
        opacity: 0.1;
        position: absolute;
    }
    
    .dashboard-stats-row .stat-value {
        font-size: 1.1rem !important;
        font-weight: 700;
        margin-bottom: 0.25rem;
        line-height: 1.2;
    }
    
    .dashboard-stats-row .stat-unit {
        font-size: 0.7em;
        text-transform: uppercase;
        font-weight: 600;
        margin-left: 2px;
        opacity: 0.8;
    }
    
    .dashboard-stats-row .stat-label {
        font-size: 0.7rem !important;
        font-weight: 600;
        color: #5a6c7d;
        white-space: normal;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.1;
        margin-bottom: auto;
        padding-right: 1.5rem; /* Space for icon */
    }
    
    .dashboard-stats-row .stat-link {
        font-size: 0.7rem;
        margin-top: 0.5rem;
    }

    /* Top Products Table - Card Layout */
    .top-products-table {
        display: block;
        width: 100%;
    }
    
    .top-products-table thead {
        display: none;
    }
    
    .top-products-table tbody {
        display: block;
        width: 100%;
    }
    
    .top-products-table tr {
        display: block;
        width: 100%;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: relative;
    }
    
    .top-products-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0.25rem 0 !important;
        text-align: left !important;
    }
    
    /* Title */
    .top-products-table td:nth-child(1) {
        font-weight: 600;
        font-size: 1rem;
        border-bottom: 1px solid #f8f9fa;
        padding-top: 0 !important;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.5rem;
    }
    
    .top-products-table td:nth-child(1) a {
        display: block;
    }
    
    /* Sold */
    .top-products-table td:nth-child(2) {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.25rem;
    }
    
    .top-products-table td:nth-child(2):before {
        content: "Продано:";
        color: #858796;
        font-size: 0.875rem;
    }
    
    /* Revenue */
    .top-products-table td:nth-child(3) {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .top-products-table td:nth-child(3):before {
        content: "Выручка:";
        color: #858796;
        font-size: 0.875rem;
    }
    
    /* Actions */
    .top-products-table td:nth-child(4) {
        margin-top: 0.5rem;
        padding-top: 0.5rem !important;
        border-top: 1px solid #f8f9fa;
    }
    
    .top-products-table td:nth-child(4) .btn {
        width: 100%;
        min-height: 44px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .top-products-table td:nth-child(4) .btn:after {
        content: " Редактировать";
        margin-left: 0.5rem;
    }
}
/* ========================================
   DISPUTES PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    
    /* Disputes Stats - 2x2 Grid (Copied from Manual Delivery v3 logic) */
    .row.disputes-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    /* Increased specificity to override global .row.mb-4 rules */
    .row.disputes-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }
    
    .disputes-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 0 !important;
    }
    
    .disputes-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }
    
    .disputes-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        background: rgba(0,0,0,0.05) !important;
        margin: 0 auto 0.5rem auto !important;
        opacity: 1 !important;
    }
    
    .disputes-stats .stat-icon i {
        font-size: 1.25rem !important;
        color: inherit !important;
    }
    
    /* Force specific colors */
    .disputes-stats .stat-card-warning .stat-icon i { color: #f6c23e !important; }
    .disputes-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .disputes-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }
    .disputes-stats .stat-card-danger .stat-icon i { color: #e74a3b !important; }
    
    .disputes-stats .stat-content {
        width: 100% !important;
        text-align: center !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .disputes-stats .stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.25rem;
        color: #2c3e50;
        text-align: center !important;
        width: 100%;
        display: block;
    }
    
    .disputes-stats .stat-label {
        font-size: 0.7rem !important;
        line-height: 1.2;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center !important;
        width: 100%;
        display: block;
        padding: 0 !important;
    }

    /* Disputes Table - Card Layout */
    .disputes-table {
        display: block;
        width: 100%;
    }
    
    .disputes-table thead {
        display: none;
    }
    
    .disputes-table tbody {
        display: block;
        width: 100%;
    }
    
    .disputes-table tr {
        display: block;
        width: 100%;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: relative;
    }
    
    .disputes-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0.25rem 0 !important;
        text-align: left !important;
    }
    
    /* ID & Date Header */
    .disputes-table td:nth-child(1) { /* ID */
        font-size: 1rem;
        font-weight: 700;
        display: inline-block;
        width: auto;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .disputes-table td:nth-child(9) { /* Date - moved to header row visually via absolute/float */
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto;
        font-size: 0.8rem;
        text-align: right !important;
        color: #858796;
        padding: 0 !important;
    }
    
    /* Status Badge */
    .disputes-table td:nth-child(8) { /* Status */
        margin-bottom: 0.75rem;
    }
    
    .disputes-table td:nth-child(8) .badge {
        font-size: 0.75rem;
        padding: 0.4em 0.8em;
        width: auto;
    }
    
    /* User */
    .disputes-table td:nth-child(2) {
        font-weight: 600;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        padding: 0.5rem !important;
        border-radius: 0.5rem;
    }
    
    .disputes-table td:nth-child(2):before {
        content: "👤 ";
        margin-right: 0.25rem;
    }
    
    /* Order / Product / Supplier - Grouped visually */
    .disputes-table td:nth-child(3), /* Order */
    .disputes-table td:nth-child(4), /* Product */
    .disputes-table td:nth-child(5) { /* Supplier */
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }
    
    .disputes-table td:nth-child(3):before { content: "Заказ: "; color: #858796; font-size: 0.8rem; }
    .disputes-table td:nth-child(4):before { content: "Товар: "; color: #858796; font-size: 0.8rem; }
    .disputes-table td:nth-child(5):before { content: "Поставщик: "; color: #858796; font-size: 0.8rem; }
    
    /* Reason & Amount */
    .disputes-table td:nth-child(6) { /* Reason */
        margin-top: 0.5rem;
        margin-bottom: 0.25rem;
        font-weight: 500;
    }
    .disputes-table td:nth-child(6):before { content: "Причина: "; color: #858796; }
    
    .disputes-table td:nth-child(7) { /* Amount */
        font-weight: 700;
        color: #1cc88a;
        margin-bottom: 0.5rem;
    }
    .disputes-table td:nth-child(7):before { content: "Сумма: "; color: #858796; font-weight: normal; }

    /* Actions */
    .disputes-table td:nth-child(10) {
        margin-top: 0.75rem;
        padding-top: 0.75rem !important;
        border-top: 1px solid #f8f9fa;
    }
    
    .disputes-table td:nth-child(10) .btn {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 44px;
    }
    
    .disputes-table td:nth-child(10) .btn:after {
        content: " Просмотр";
        margin-left: 0.5rem;
    }
}
/* ========================================
   USERS PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* Users Stats - 2x2 Grid */
    .row.users-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    .row.users-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }
    
    .users-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 0 !important;
    }
    
    .users-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }
    
    .users-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        background: rgba(0,0,0,0.05) !important;
        margin: 0 auto 0.5rem auto !important;
        opacity: 1 !important;
    }
    
    .users-stats .stat-icon i {
        font-size: 1.25rem !important;
        color: inherit !important;
    }
    
    /* Specific Colors */
    .users-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .users-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .users-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }
    .users-stats .stat-card-danger .stat-icon i { color: #e74a3b !important; }
    
    .users-stats .stat-content {
        width: 100% !important;
        text-align: center !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .users-stats .stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.25rem;
        color: #2c3e50;
        text-align: center !important;
        width: 100%;
        display: block;
    }
    
    .users-stats .stat-label {
        font-size: 0.7rem !important;
        line-height: 1.2;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center !important;
        width: 100%;
        display: block;
        padding: 0 !important;
    }
    
    /* Filter Buttons - Horizontal Scroll */
    .filters-container {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 0.5rem;
        margin-bottom: 0.5rem;
        -webkit-overflow-scrolling: touch;
    }
    
    .filters-container .btn-group {
        display: inline-flex;
    }
    
    /* DataTables Controls */
    div.dataTables_wrapper div.dataTables_filter {
        text-align: left !important;
        margin-bottom: 1rem;
    }
    
    div.dataTables_wrapper div.dataTables_filter input {
        width: 100% !important;
        margin-left: 0 !important;
        display: block;
        height: 44px; /* Touch friendly */
    }
    
    div.dataTables_wrapper div.dataTables_length {
        display: none !important; /* Hide "Show X entries" on mobile to save space */
    }
    
    /* Users Table - Card Layout */
    .users-table {
        display: block;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 1rem;
    }
    
    .users-table thead {
        display: none;
    }
    
    .users-table tbody {
        display: block;
        width: 100%;
    }
    
    .users-table tr {
        display: block;
        width: 100%;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: relative;
    }
    
    .users-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0.25rem 0 !important;
        text-align: left !important;
    }
    
    /* ID - Absolute Top Right */
    .users-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        padding: 0 !important;
        z-index: 5;
    }
    
    /* User: Avatar + Name + Email */
    .users-table td:nth-child(2) {
        margin-bottom: 1rem;
        padding-right: 3rem !important; /* Space for ID badge */
    }
    
    .users-table td:nth-child(2) .d-flex {
        align-items: center;
    }
    
    .users-table td:nth-child(2) .user-avatar {
        width: 50px;
        height: 50px;
        margin-right: 1rem !important;
    }
    
    .users-table td:nth-child(2) .font-weight-bold {
        font-size: 1.1rem;
        color: #2c3e50;
    }
    
    /* Balance */
    .users-table td:nth-child(3) {
        display: inline-block;
        width: 48%;
        margin-bottom: 0.5rem;
        vertical-align: top;
    }
    
    .users-table td:nth-child(3) .badge {
        font-size: 0.9rem;
        padding: 0.5rem;
        display: flex;
        align-items: center;
        width: fit-content;
    }
    
    /* Status */
    .users-table td:nth-child(4) {
        display: inline-block;
        width: 48%;
        margin-bottom: 0.5rem;
        text-align: right !important;
        vertical-align: top;
    }
    
    .users-table td:nth-child(4) .badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.6rem;
    }
    
    /* Purchases */
    .users-table td:nth-child(5) {
        clear: both;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        padding: 0.75rem !important;
        border-radius: 0.5rem;
    }
    
    .users-table td:nth-child(5) .text-center {
        text-align: left !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .users-table td:nth-child(5):before {
        content: "История покупок:";
        font-weight: 600;
        color: #5a6c7d;
        font-size: 0.9rem;
    }
    
    /* Registered Date */
    .users-table td:nth-child(6) {
        font-size: 0.8rem;
        color: #858796;
        margin-bottom: 1rem;
        padding-top: 0.5rem !important;
    }
    
    .users-table td:nth-child(6) i {
        width: 16px;
        text-align: center;
    }
    
    /* Actions */
    .users-table td:nth-child(7) {
        border-top: 1px solid #f8f9fa;
        padding-top: 1rem !important;
        margin-top: 0.5rem;
    }
    
    .users-table td:nth-child(7) .btn-group {
        display: flex;
        width: 100%;
        gap: 0.5rem;
    }
    
    .users-table td:nth-child(7) .btn {
        flex: 1;
        padding: 0.75rem;
        border-radius: 0.35rem !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .users-table td:nth-child(7) .btn i {
        margin-right: 0;
    }
}
/* ========================================
   PRODUCT CATEGORIES PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    
    /* Stats - Force 2x2 Grid (even for 3 items) */
    .row.product-categories-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    .row.product-categories-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
    }

    /* Adjust Small Box for grid */
    .product-categories-stats .small-box {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        padding: 1rem 0.5rem !important;
    }
    
    .product-categories-stats .small-box .inner {
        padding: 0 !important;
        display: flex;
        flex-direction: column-reverse; /* Icon on top visually via order if needed, but here we just re-layout */
    }
    
    .product-categories-stats .small-box h3 {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
        color: #2c3e50;
    }
    
    .product-categories-stats .small-box p {
        font-size: 0.7rem;
        text-transform: uppercase;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }
     
    .product-categories-stats .small-box .icon {
        position: static !important;
        display: block !important;
        margin: 0 auto 0.5rem auto;
        font-size: 1.5rem;
        opacity: 1 !important;
        color: inherit !important;
        height: auto !important;
        width: auto !important;
    }
    
    .product-categories-stats .small-box .icon i {
         font-size: 2rem;
         opacity: 0.8 !important; /* Make icons visible */
    }
    /* Fix icon colors specifically */
    .product-categories-stats .col-md-4:nth-child(1) .icon i { color: #4e73df !important; }
    .product-categories-stats .col-md-4:nth-child(2) .icon i { color: #36b9cc !important; }
    .product-categories-stats .col-md-4:nth-child(3) .icon i { color: #1cc88a !important; }

    /* Table - Card View */
    .product-categories-table {
        display: block;
        width: 100%;
    }
    
    .product-categories-table thead {
        display: none;
    }
    
    .product-categories-table tbody {
        display: block;
        width: 100%;
    }
    
    .product-categories-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    
    .product-categories-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    
    /* ID - Top Right */
    .product-categories-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        margin: 0;
        z-index: 2;
    }
    
    /* Photo - Top Left (Avatar-like) */
    .product-categories-table td:nth-child(2) {
        position: absolute;
        top: 1rem;
        left: 1rem;
        width: 50px !important;
        height: 50px !important;
        margin: 0;
    }
    
    .product-categories-table td:nth-child(2) img,
    .product-categories-table td:nth-child(2) .d-inline-flex {
        width: 50px !important;
        height: 50px !important;
        border-radius: 0.5rem !important;
    }
    
    /* Name - Next to Photo */
    .product-categories-table td:nth-child(3) {
        margin-left: 60px; /* Space for photo */
        margin-bottom: 1.5rem;
        padding-top: 0.25rem !important;
        min-height: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding-right: 2rem !important; /* Avoid overlap with ID */
    }
    
    .product-categories-table td:nth-child(3) a.text-dark {
        font-size: 1.1rem;
        line-height: 1.2;
    }
    
    .product-categories-table td:nth-child(3) small {
        font-size: 0.8rem;
    }
    
    /* Subcategories */
    .product-categories-table td:nth-child(4) {
        clear: both;
        background: #f8f9fa;
        padding: 0.75rem !important;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .product-categories-table td:nth-child(4):before {
        content: "Подкатегории:";
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #858796;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
    }
    
    /* Products Count */
    .product-categories-table td:nth-child(5) {
        margin-bottom: 1rem;
    }
    
    .product-categories-table td:nth-child(5) .d-flex {
        align-items: flex-start !important;
        text-align: left !important;
    }
    
    .product-categories-table td:nth-child(5):before {
        content: "Товары:";
        font-weight: 600;
        color: #5a6c7d;
        margin-right: 0.5rem;
    }
    
    /* Actions */
    .product-categories-table td:nth-child(6) {
        border-top: 1px solid #e3e6f0;
        padding-top: 0.75rem !important;
        margin-top: 0.5rem;
        margin-bottom: 0;
    }
    
    .product-categories-table td:nth-child(6) .btn-group {
        display: flex;
        width: 100%;
        gap: 0.5rem;
    }
    
    .product-categories-table td:nth-child(6) .btn {
        flex: 1;
        padding: 0.5rem;
    }
}
/* ========================================
   ARTICLES PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    
    /* Stats - Force 2x2 Grid */
    .row.articles-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    .row.articles-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }

    .articles-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 0 !important;
    }
    
    .articles-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }
    
    .articles-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        background: rgba(0,0,0,0.05) !important;
        margin: 0 auto 0.5rem auto !important;
        opacity: 1 !important;
    }

    .articles-stats .stat-icon i {
        font-size: 1.25rem !important;
        color: inherit !important;
    }
    
    /* Specific colors */
    .articles-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .articles-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .articles-stats .stat-card-warning .stat-icon i { color: #f6c23e !important; }
    .articles-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }

    .articles-stats .stat-content {
        width: 100% !important;
        text-align: center !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .articles-stats .stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.25rem;
        color: #2c3e50;
        text-align: center !important;
        width: 100%;
        display: block;
    }
    
    .articles-stats .stat-label {
        font-size: 0.7rem !important;
        line-height: 1.2;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center !important;
        width: 100%;
        display: block;
        padding: 0 !important;
    }

    /* Table - Card View */
    .articles-table {
        display: block;
        width: 100%;
    }
    
    .articles-table thead {
        display: none;
    }
    
    .articles-table tbody {
        display: block;
        width: 100%;
    }
    
    .articles-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    
    .articles-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    
    /* ID - Absolute Top Right */
    .articles-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        margin: 0;
        z-index: 2;
    }
    
    /* Preview Image - Top Left */
    .articles-table td:nth-child(2) {
        position: absolute;
        top: 1rem;
        left: 1rem;
        width: 60px !important;
        height: 60px !important;
        margin: 0;
    }
    
    .articles-table td:nth-child(2) img,
    .articles-table td:nth-child(2) .rounded {
        width: 60px !important;
        height: 60px !important;
        border-radius: 0.5rem !important;
    }
    
    /* Name - Next to Image */
    .articles-table td:nth-child(3) {
        margin-left: 70px;
        margin-bottom: 1rem;
        padding-top: 0 !important;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding-right: 3rem !important; /* Space for ID */
    }
    
    .articles-table td:nth-child(3) {
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.3;
        color: #2c3e50;
    }

    /* Categories */
    .articles-table td:nth-child(4) {
        clear: both;
        margin-bottom: 0.5rem;
    }
    
    .articles-table td:nth-child(4) .badge {
        font-size: 0.75rem;
        padding: 0.4em 0.8em;
        margin-right: 0.25rem;
    }

    /* Status */
    .articles-table td:nth-child(5) {
        display: inline-block;
        width: auto !important;
        margin-bottom: 0.5rem;
    }

    /* Date */
    .articles-table td:nth-child(6) {
        font-size: 0.8rem;
        color: #858796;
        margin-bottom: 1rem;
        display: block;
        border-bottom: 1px solid #f8f9fa;
        padding-bottom: 0.5rem !important;
    }
    
    /* Actions */
    .articles-table td:nth-child(7) {
        padding-top: 0.5rem !important;
        margin-bottom: 0;
    }
    
    .articles-table td:nth-child(7) .action-buttons {
        display: flex;
        width: 100%;
        gap: 0.5rem;
    }
    
    .articles-table td:nth-child(7) .btn {
        flex: 1;
        padding: 0.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
/* ========================================
   ARTICLE CATEGORIES PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    .article-categories-table {
        display: block;
        width: 100%;
    }
    
    .article-categories-table thead {
        display: none;
    }
    
    .article-categories-table tbody {
        display: block;
        width: 100%;
    }
    
    .article-categories-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    
    .article-categories-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    
    /* ID - Absolute Top Right */
    .article-categories-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        margin: 0;
        z-index: 2;
    }
    
    /* Name */
    .article-categories-table td:nth-child(2) {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1rem;
        padding-right: 3rem !important; /* Avoid overlap with ID */
    }
    
    /* Actions */
    .article-categories-table td:nth-child(3) {
        border-top: 1px solid #e3e6f0;
        padding-top: 0.75rem !important;
        margin-top: 0.5rem;
        margin-bottom: 0;
    }
    
    .article-categories-table td:nth-child(3) .action-buttons {
        display: flex;
        width: 100%;
        gap: 0.5rem;
    }
    
    .article-categories-table td:nth-child(3) .btn {
        flex: 1;
        padding: 0.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
/* ========================================
   PAGES PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    
    /* Stats - Force 2x2 Grid */
    .row.pages-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    .row.pages-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }

    .pages-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 0 !important;
    }
    
    .pages-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }
    
    .pages-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        background: rgba(0,0,0,0.05) !important;
        margin: 0 auto 0.5rem auto !important;
        opacity: 1 !important;
    }

    .pages-stats .stat-icon i {
        font-size: 1.25rem !important;
        color: inherit !important;
    }
    
    /* Specific colors */
    .pages-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .pages-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .pages-stats .stat-card-danger .stat-icon i { color: #e74a3b !important; }

    .pages-stats .stat-content {
        width: 100% !important;
        text-align: center !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .pages-stats .stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.25rem;
        color: #2c3e50;
        text-align: center !important;
        width: 100%;
        display: block;
    }
    
    .pages-stats .stat-label {
        font-size: 0.7rem !important;
        line-height: 1.2;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center !important;
        width: 100%;
        display: block;
        padding: 0 !important;
    }

    /* Table - Card View */
    .pages-table {
        display: block;
        width: 100%;
    }
    
    .pages-table thead {
        display: none;
    }
    
    .pages-table tbody {
        display: block;
        width: 100%;
    }
    
    .pages-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    
    .pages-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    
    /* ID - Absolute Top Right */
    .pages-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        margin: 0;
        z-index: 2;
    }
    
    /* Name */
    .pages-table td:nth-child(2) {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0.25rem;
        padding-right: 3rem !important; /* Avoid overlap with ID */
    }
    
    /* Slug */
    .pages-table td:nth-child(3) {
        font-size: 0.85rem;
        margin-bottom: 0.75rem;
        word-break: break-all;
    }
    
    .pages-table td:nth-child(3) a {
        background: #f8f9fa;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
    }

    /* Status */
    .pages-table td:nth-child(4) {
        display: inline-block;
        width: auto !important;
        margin-bottom: 0.5rem;
    }
    
    /* Date */
    .pages-table td:nth-child(5) {
        font-size: 0.8rem;
        color: #858796;
        margin-bottom: 1rem;
        border-bottom: 1px solid #f8f9fa;
        padding-bottom: 0.5rem !important;
        display: block;
    }

    /* Actions */
    .pages-table td:nth-child(6) {
        padding-top: 0.5rem !important;
        margin-bottom: 0;
    }
    
    .pages-table td:nth-child(6) .action-buttons {
        display: flex;
        width: 100%;
        gap: 0.5rem;
    }
    
    .pages-table td:nth-child(6) .btn {
        flex: 1;
        padding: 0.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
/* ========================================
   PROMOCODES PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* Stats - Force 2x2 Grid */
    .row.promocodes-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    .row.promocodes-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }

    .promocodes-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 0 !important;
    }
    
    /* Reuse generic stat styles from previous pages if possible or redefine */
    .promocodes-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }
    
    .promocodes-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        background: rgba(0,0,0,0.05) !important;
        margin: 0 auto 0.5rem auto !important;
        opacity: 1 !important;
    }

    .promocodes-stats .stat-icon i {
        font-size: 1.25rem !important;
        color: inherit !important;
    }
    
    /* Specific Colors */
    .promocodes-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .promocodes-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .promocodes-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }
    .promocodes-stats .stat-card-warning .stat-icon i { color: #f6c23e !important; }

    .promocodes-stats .stat-content {
        width: 100% !important;
        text-align: center !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .promocodes-stats .stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.25rem;
        color: #2c3e50;
        text-align: center !important;
        width: 100%;
        display: block;
    }
    
    .promocodes-stats .stat-label {
        font-size: 0.7rem !important;
        line-height: 1.2;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center !important;
        width: 100%;
        display: block;
        padding: 0 !important;
    }
    
    /* Table - Card View */
    .promocodes-table {
        display: block;
        width: 100%;
    }
    
    .promocodes-table thead {
        display: none;
    }
    
    .promocodes-table tbody {
        display: block;
        width: 100%;
    }
    
    .promocodes-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    
    .promocodes-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    
    /* Checkbox - Top Left Absolute */
    .promocodes-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        left: 1rem;
        width: auto !important;
        margin: 0;
        z-index: 2;
    }
    .promocodes-table td:nth-child(1) input {
        transform: scale(1.5); /* Easier to tap */
    }
    
    /* ID - Top Right Absolute */
    .promocodes-table td:nth-child(2) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        margin: 0;
        z-index: 2;
    }
    
    /* Title/Code - Large Next to Checkbox */
    .promocodes-table td:nth-child(3) {
        margin-left: 30px; /* Space for checkbox */
        margin-bottom: 1rem;
        padding-right: 3rem !important; /* Space for ID */
    }
    
    .promocodes-table td:nth-child(3) code {
        font-size: 1.25rem !important;
        display: inline-block;
        margin-bottom: 0.25rem;
    }
    
    /* Batch - Hidden or Small */
    .promocodes-table td:nth-child(4) {
        font-size: 0.75rem;
        color: #858796;
        margin-bottom: 0.5rem;
        display: block;
        margin-left: 30px;
    }
    .promocodes-table td:nth-child(4):before {
        content: "Партия: ";
    }
    
    /* Type & Discount - Inline Block */
    .promocodes-table td:nth-child(5),
    .promocodes-table td:nth-child(6) {
        display: inline-block;
        width: auto !important;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        vertical-align: middle;
    }
    
    /* Usages - Full block with label */
    .promocodes-table td:nth-child(7) {
        border-top: 1px dotted #e3e6f0;
        border-bottom: 1px dotted #e3e6f0;
        padding: 0.5rem 0 !important;
        margin: 0.5rem 0;
        background: #f8f9fa;
        text-align: center !important;
    }
    .promocodes-table td:nth-child(7):before {
        content: "Использовано: ";
        font-weight: 600;
        color: #5a6c7d;
    }

    /* Status */
    .promocodes-table td:nth-child(8) {
        text-align: center !important;
        margin-bottom: 1rem;
    }

    /* Actions */
    .promocodes-table td:nth-child(9) {
        margin-bottom: 0;
    }
    
    .promocodes-table td:nth-child(9) .btn-group {
        display: flex;
        width: 100%;
        gap: 0.5rem;
    }
    
    .promocodes-table td:nth-child(9) .btn {
        flex: 1;
        padding: 0.6rem;
    }
}
/* ========================================
   BANNERS PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* Stats - Force 2x2 Grid */
    .row.banners-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    
    .row.banners-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }

    .banners-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 0 !important;
    }
    
    .banners-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
    }
    
    .banners-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        background: rgba(0,0,0,0.05) !important;
        margin: 0 auto 0.5rem auto !important;
        opacity: 1 !important;
    }

    .banners-stats .stat-icon i {
        font-size: 1.25rem !important;
        color: inherit !important;
    }
    
    /* Specific Colors */
    .banners-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .banners-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .banners-stats .stat-card-danger .stat-icon i { color: #e74a3b !important; }

    .banners-stats .stat-content {
        width: 100% !important;
        text-align: center !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .banners-stats .stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        margin-bottom: 0.25rem;
        color: #2c3e50;
        text-align: center !important;
        width: 100%;
        display: block;
    }
    
    .banners-stats .stat-label {
        font-size: 0.7rem !important;
        line-height: 1.2;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center !important;
        width: 100%;
        display: block;
        padding: 0 !important;
    }

    /* Table - Card View */
    .banners-table {
        display: block;
        width: 100%;
    }
    
    .banners-table thead {
        display: none;
    }
    
    .banners-table tbody {
        display: block;
        width: 100%;
    }
    
    .banners-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    
    .banners-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }

    /* ID - Absolute Top Right */
    .banners-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        margin: 0;
        z-index: 2;
    }

    /* Image - Top Left Block */
    .banners-table td:nth-child(2) {
        margin-bottom: 1rem;
        text-align: center !important;
    }
    .banners-table td:nth-child(2) img,
    .banners-table td:nth-child(2) .rounded {
        max-width: 100% !important;
        width: auto !important;
        height: auto !important;
        max-height: 120px !important;
        border-radius: 0.5rem !important;
    }

    /* Name */
    .banners-table td:nth-child(3) {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        text-align: center !important;
        padding: 0 1rem !important;
    }
    .banners-table td:nth-child(3) a {
        display: block;
        margin-top: 0.25rem;
    }

    /* Type, Position, Status - In a row */
    .banners-table td:nth-child(4), /* Type */
    .banners-table td:nth-child(5), /* Order */
    .banners-table td:nth-child(6)  /* Status */
    {
        display: inline-block;
        width: auto !important;
        margin-right: 0.25rem;
        margin-bottom: 0.5rem;
        vertical-align: middle;
        text-align: center !important;
    }
    
    /* Centering the badges container logic */
    .banners-table tr {
        text-align: center; /* Helper for inline blocks */
    }

    /* Period */
    .banners-table td:nth-child(7) {
        font-size: 0.8rem;
        color: #858796;
        margin-bottom: 1rem;
        border-top: 1px dotted #e3e6f0;
        border-bottom: 1px dotted #e3e6f0;
        padding: 0.5rem !important;
        background: #f8f9fa;
    }

    /* Actions */
    .banners-table td:nth-child(8) {
        margin-bottom: 0;
    }
    
    .banners-table td:nth-child(8) .action-buttons {
        display: flex;
        width: 100%;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .banners-table td:nth-child(8) .btn {
        flex: 1;
        padding: 0.6rem;
    }
}
/* ========================================
   VOUCHERS PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* Vouchers Stats */
    .row.vouchers-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    .row.vouchers-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
        padding-right: 0.25rem;
        padding-left: 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }
    .vouchers-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 0 !important;
    }
    .vouchers-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
    }
    .vouchers-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 40px !important;
        height: 40px !important;
        border-radius: 50%;
        margin: 0 auto 0.5rem auto !important;
        font-size: 1.25rem !important;
        background: rgba(0,0,0,0.05) !important;
    }
    .vouchers-stats .stat-value {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #2c3e50;
    }
    .vouchers-stats .stat-label {
        font-size: 0.65rem !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
    }
    
    /* Specific Colors */
    .vouchers-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .vouchers-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .vouchers-stats .stat-card-warning .stat-icon i { color: #f6c23e !important; }
    .vouchers-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }

    /* Vouchers Table */
    .vouchers-table, .vouchers-table tbody, .vouchers-table tr, .vouchers-table td {
        display: block;
        width: 100%;
    }
    .vouchers-table thead { display: none; }
    .vouchers-table tr {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    .vouchers-table td {
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    /* ID */
    .vouchers-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
        z-index: 2;
    }
    /* Code */
    .vouchers-table td:nth-child(2) {
        margin-bottom: 0.5rem;
        padding-right: 3rem !important;
    }
    .vouchers-table td:nth-child(2) code {
        font-size: 1.1rem;
    }
    /* Amount & Currency */
    .vouchers-table td:nth-child(3), .vouchers-table td:nth-child(4) {
        display: inline-block;
        width: auto !important;
        font-size: 1.25rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }
    /* User */
    .vouchers-table td:nth-child(5) {
        margin-bottom: 0.75rem;
        border-top: 1px solid #f0f2f5;
        padding-top: 0.75rem !important;
    }
    /* Status & Used At */
    .vouchers-table td:nth-child(6), .vouchers-table td:nth-child(7) {
        display: inline-block;
        width: auto !important;
        margin-right: 0.5rem;
    }
    /* Actions */
    .vouchers-table td:nth-child(8) {
        margin-top: 1rem;
        padding-top: 0.5rem !important;
        border-top: 1px dashed #e3e6f0;
    }
    .vouchers-table td:nth-child(8) .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    .vouchers-table td:nth-child(8) .btn { flex: 1; padding: 0.6rem; }
}

/* ========================================
   SUPPLIERS PAGE - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* Suppliers Stats - reuse Vouchers styles basically, but specific class */
    .row.suppliers-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    .row.suppliers-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 0 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }
    .suppliers-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 0 !important;
    }
    .suppliers-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
    }
    .suppliers-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 40px !important;
        height: 40px !important;
        border-radius: 50%;
        margin: 0 auto 0.5rem auto !important;
        font-size: 1.25rem !important;
        background: rgba(0,0,0,0.05) !important;
    }
    
    .suppliers-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .suppliers-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .suppliers-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }
    
    .suppliers-stats .stat-value {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #2c3e50;
    }
    .suppliers-stats .stat-label {
        font-size: 0.65rem !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
    }

    /* Suppliers Table */
    .suppliers-table, .suppliers-table tbody, .suppliers-table tr, .suppliers-table td {
        display: block;
        width: 100%;
    }
    .suppliers-table thead { display: none; }
    .suppliers-table tr {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
    }
    .suppliers-table td {
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    /* ID */
    .suppliers-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
    }
    /* Name */
    .suppliers-table td:nth-child(2) {
        font-size: 1.2rem;
        font-weight: 700;
        padding-right: 3rem !important;
        color: #2c3e50;
    }
    /* Email */
    .suppliers-table td:nth-child(3) {
        font-size: 0.85rem;
        color: #5a6c7d;
        margin-bottom: 1rem;
        border-bottom: 1px solid #f0f2f5;
        padding-bottom: 0.5rem !important;
    }
    /* Rating, Balance, Commission */
    .suppliers-table td:nth-child(4),
    .suppliers-table td:nth-child(5),
    .suppliers-table td:nth-child(6) {
        display: inline-block;
        width: auto !important;
        margin-right: 1rem;
        margin-bottom: 0.5rem;
    }
    .suppliers-table td:nth-child(5) {
        font-weight: 800;
        font-size: 1.1rem;
    }
    /* Methods */
    .suppliers-table td:nth-child(7) {
        margin-bottom: 1rem;
    }
    /* Actions */
    .suppliers-table td:nth-child(8) .btn {
        width: 100%;
        padding: 0.75rem;
    }
}

/* ========================================
   WITHDRAWAL REQUESTS - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* Withdrawals Stats - reuse Vouchers styles */
    .row.withdrawals-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    .row.withdrawals-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 0 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }
    .withdrawals-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 0 !important;
    }
    .withdrawals-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
    }
    .withdrawals-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 40px !important;
        height: 40px !important;
        border-radius: 50%;
        margin: 0 auto 0.5rem auto !important;
        font-size: 1.25rem !important;
        background: rgba(0,0,0,0.05) !important;
    }
    
    .withdrawals-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .withdrawals-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .withdrawals-stats .stat-card-warning .stat-icon i { color: #f6c23e !important; }
    .withdrawals-stats .stat-card-info .stat-icon i { color: #36b9cc !important; }

    .withdrawals-stats .stat-value {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #2c3e50;
    }
    .withdrawals-stats .stat-label {
        font-size: 0.65rem !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
    }
    
    /* Table */
    .withdrawals-table, .withdrawals-table tbody, .withdrawals-table tr, .withdrawals-table td {
        display: block;
        width: 100%;
    }
    .withdrawals-table thead { display: none; }
    .withdrawals-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        position: relative;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    .withdrawals-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    
    /* ID */
    .withdrawals-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
    }
    
    /* Supplier */
    .withdrawals-table td:nth-child(2) {
        font-size: 1.1rem;
        margin-bottom: 1rem;
        padding-right: 3rem !important;
    }
    
    /* Amount */
    .withdrawals-table td:nth-child(3) {
        font-size: 1.5rem;
        font-weight: 800;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }
    
    /* Method */
    .withdrawals-table td:nth-child(4) {
        display: inline-block;
        width: auto !important;
        margin-bottom: 1rem;
    }
    
    /* Details */
    .withdrawals-table td:nth-child(5) {
        background: #f8f9fa;
        padding: 0.5rem !important;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        word-break: break-all;
        margin-bottom: 1rem;
    }
    
    /* Status & Date */
    .withdrawals-table td:nth-child(6),
    .withdrawals-table td:nth-child(7) {
        display: inline-block;
        width: auto !important;
        margin-right: 1rem;
    }
    
    /* Actions */
    .withdrawals-table td:nth-child(8) {
        margin-top: 0.5rem;
        padding-top: 0.5rem !important;
        border-top: 1px dashed #e3e6f0;
    }
    .withdrawals-table td:nth-child(8) .btn {
        width: 100%;
        padding: 0.6rem;
    }
}

/* ========================================
   NOTIFICATIONS - MOBILE STYLES
   ======================================== */
@media (max-width: 575px) {
    /* Notifications Stats - reuse Vouchers styles */
    .row.notifications-stats {
        display: flex;
        flex-wrap: wrap;
        margin-right: -0.25rem;
        margin-left: -0.25rem;
    }
    .row.notifications-stats > [class*="col-"] {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 0 0.25rem;
        margin-bottom: 0.5rem !important;
        display: flex;
    }
    .notifications-stats .stat-card {
        padding: 1rem 0.5rem !important;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 0 !important;
    }
    .notifications-stats .stat-card-body {
        padding: 0 !important;
        width: 100%;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
    }
    .notifications-stats .stat-icon {
        position: static !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 40px !important;
        height: 40px !important;
        border-radius: 50%;
        margin: 0 auto 0.5rem auto !important;
        font-size: 1.25rem !important;
        background: rgba(0,0,0,0.05) !important;
    }
    
    .notifications-stats .stat-card-primary .stat-icon i { color: #4e73df !important; }
    .notifications-stats .stat-card-success .stat-icon i { color: #1cc88a !important; }
    .notifications-stats .stat-card-warning .stat-icon i { color: #f6c23e !important; }

    .notifications-stats .stat-value {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #2c3e50;
    }
    .notifications-stats .stat-label {
        font-size: 0.65rem !important;
        text-align: center !important;
        width: 100%;
        display: block;
        color: #858796;
        text-transform: uppercase;
        font-weight: 700;
    }

    /* Table */
    .notifications-table, .notifications-table tbody, .notifications-table tr, .notifications-table td {
        display: block;
        width: 100%;
    }
    .notifications-table thead { display: none; }
    .notifications-table tr {
        display: block;
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        position: relative;
    }
    .notifications-table td {
        display: block;
        width: 100%;
        border: none;
        padding: 0 !important;
        margin-bottom: 0.5rem;
        text-align: left !important;
    }
    
    /* ID */
    .notifications-table td:nth-child(1) {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: auto !important;
    }
    
    /* User */
    .notifications-table td:nth-child(2) {
        font-weight: 700;
        font-size: 1.1rem;
        padding-right: 3rem !important;
        margin-bottom: 1rem;
        border-bottom: 1px solid #f0f2f5;
        padding-bottom: 0.5rem !important;
    }
    
    /* Message */
    .notifications-table td:nth-child(3) {
        margin-bottom: 1rem;
    }
    
    /* Status & Date */
    .notifications-table td:nth-child(4),
    .notifications-table td:nth-child(5) {
        display: inline-block;
        width: auto !important;
        margin-right: 1rem;
    }
    
    /* Actions */
    .notifications-table td:nth-child(6) .btn {
        width: 100%;
        margin-top: 0.5rem;
        padding: 0.6rem;
    }
}
</style>





