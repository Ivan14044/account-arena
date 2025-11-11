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
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    border-left: 4px solid;
}

.stat-card-primary { border-left-color: #4e73df; }
.stat-card-success { border-left-color: #1cc88a; }
.stat-card-info { border-left-color: #36b9cc; }
.stat-card-warning { border-left-color: #f6c23e; }
.stat-card-danger { border-left-color: #e74a3b; }

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.stat-card-body {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
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
}

.stat-label {
    font-size: 0.75rem;
    color: #858796;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
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
}

.nav-tabs-modern .nav-item {
    margin-bottom: -2px;
}

.nav-tabs-modern .nav-link {
    border: none;
    color: #5a6c7d;
    padding: 1rem 1.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-bottom: 2px solid transparent;
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

/* RESPONSIVE */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .content-header-modern h1 {
        font-size: 1.5rem;
    }
}
</style>




