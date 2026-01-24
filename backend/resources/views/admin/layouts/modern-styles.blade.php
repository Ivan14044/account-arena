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
        
        .modern-table {
            display: block;
            width: 100%;
        }
        
        .modern-table thead {
            display: none;
        }
        
        .modern-table tbody {
            display: block;
            width: 100%;
        }
        
        .modern-table tr {
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
        
        .modern-table tr:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        /* Hide checkbox and drag handle on mobile */
        .modern-table td:nth-child(1),
        .modern-table td:nth-child(2) {
            display: none !important;
        }
        
        .modern-table td {
            display: block;
            width: 100%;
            border: none;
            padding: 0.5rem 0;
            text-align: left !important;
        }
        
        /* Product ID - Top Right Badge */
        .modern-table td:nth-child(3) {
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
        .modern-table td:nth-child(4) {
            font-size: 0.75rem;
            color: #858796;
            margin-bottom: 0.5rem;
        }
        
        /* Product Image */
        .modern-table td:nth-child(5) {
            width: 80px;
            height: 80px;
            float: left;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .modern-table td:nth-child(5) img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        
        /* Product Title */
        .modern-table td:nth-child(6) {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            padding-top: 0;
        }
        
        /* Category */
        .modern-table td:nth-child(7) {
            clear: both;
            font-size: 0.875rem;
            color: #5a6c7d;
            margin-bottom: 0.5rem;
        }
        
        .modern-table td:nth-child(7):before {
            content: "📁 ";
        }
        
        /* Supplier */
        .modern-table td:nth-child(8) {
            font-size: 0.875rem;
            color: #5a6c7d;
            margin-bottom: 0.5rem;
        }
        
        .modern-table td:nth-child(8):before {
            content: "👤 ";
        }
        
        /* Price */
        .modern-table td:nth-child(9) {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1cc88a;
            margin-bottom: 0.5rem;
        }
        
        /* Stock */
        .modern-table td:nth-child(10) {
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
        
        .modern-table td:nth-child(10):before {
            content: "📦 ";
        }
        
        /* Sold */
        .modern-table td:nth-child(11) {
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
        
        .modern-table td:nth-child(11):before {
            content: "🛒 ";
        }
        
        /* Status */
        .modern-table td:nth-child(12) {
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        /* Actions */
        .modern-table td:nth-child(13) {
            padding-top: 1rem;
            border-top: 1px solid #e3e6f0;
            margin-top: 1rem;
        }
        
        .modern-table td:nth-child(13) .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .modern-table td:nth-child(13) .btn {
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
        .modern-table td:nth-child(14) {
            font-size: 0.75rem;
            color: #858796;
            text-align: center;
            padding-top: 0.5rem;
            border-top: 1px solid #f8f9fa;
        }
        
        .modern-table td:nth-child(14):before {
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
</style>




