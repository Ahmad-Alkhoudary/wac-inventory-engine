<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAC Inventory Valuation Engine - Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Vue 3 & Axios CDN -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <style>
        :root {
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #2563eb;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --font-family: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-light);
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.06) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(5, 150, 105, 0.06) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-main);
            font-family: var(--font-family);
            min-height: 100vh;
            padding-bottom: 2rem;
        }

        /* Glassmorphism Card Utility (Light Theme) */
        .glass {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
        }

        header {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .logo-title {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, #4f46e5, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-main);
            border: 1px solid var(--card-border);
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-danger {
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }
        .btn-danger:hover {
            background: #fee2e2;
        }

        .btn-sm {
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .kpi-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .kpi-title {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .kpi-value {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Main Side by Side Layout */
        .main-layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
        }

        .panel-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }

        .panel-title {
            font-family: var(--font-heading);
            font-size: 1.15rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-main);
        }

        .badge {
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-purchase {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-sale {
            background: #f3e8ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .badge-wac {
            background: #e0e7ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }

        /* Products List */
        .product-list {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .product-card {
            padding: 1rem;
            border-radius: 0.75rem;
            background: #ffffff;
            border: 1px solid var(--card-border);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .product-card:hover, .product-card.active {
            border-color: var(--primary);
            background: #f5f3ff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08);
        }

        .product-sku {
            font-size: 0.75rem;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .product-name {
            font-weight: 600;
            font-size: 1rem;
            margin: 0.25rem 0 0.5rem 0;
            color: var(--text-main);
        }

        .product-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.825rem;
            color: var(--text-muted);
        }

        /* Ledger Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }

        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--card-border);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-main);
        }

        tr:hover td {
            background: #f8fafc;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .modal-content {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: var(--text-main);
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 200;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .toast {
            padding: 0.85rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .toast-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <div id="app">
        <!-- Top Navigation -->
        <header>
            <div class="logo-title">
                <i class="fa-solid fa-boxes-stacked"></i>
                WAC Inventory Engine
            </div>

            <div v-if="token" style="display: flex; align-items: center; gap: 1rem;">
                <button @click="openModal('create')" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Record Transaction
                </button>
                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                    <i class="fa-solid fa-user-circle"></i> [[ currentUser?.email ]]
                </div>
                <button @click="logout" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </div>
            <div v-else>
                <button @click="quickLogin" class="btn btn-primary">
                    <i class="fa-solid fa-bolt"></i> Quick Admin Login
                </button>
            </div>
        </header>

        <div class="container">
            <!-- Login View if unauthenticated -->
            <div v-if="!token" class="glass modal-content" style="margin: 4rem auto; text-align: center;">
                <h2 style="font-family: var(--font-heading); margin-bottom: 0.5rem; color: var(--text-main);">Authentication Required</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">Log in to access the Weighted Average Cost inventory engine dashboard.</p>
                
                <form @submit.prevent="login">
                    <div class="form-group">
                        <label class="form-label" style="text-align: left;">Email Address</label>
                        <input v-model="loginForm.email" type="email" class="form-control" required placeholder="admin@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="text-align: left;">Password</label>
                        <input v-model="loginForm.password" type="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 0.5rem;">
                        Sign In to Engine
                    </button>
                </form>
            </div>

            <!-- Main Authenticated Dashboard -->
            <div v-else>
                <!-- KPI Header Stats -->
                <div class="kpi-grid">
                    <div class="glass kpi-card">
                        <div class="kpi-icon" style="background: #e0e7ff; color: #4f46e5;">
                            <i class="fa-solid fa-box"></i>
                        </div>
                        <div>
                            <div class="kpi-title">Total Products</div>
                            <div class="kpi-value">[[ products.length ]]</div>
                        </div>
                    </div>

                    <div class="glass kpi-card">
                        <div class="kpi-icon" style="background: #d1fae5; color: #059669;">
                            <i class="fa-solid fa-cubes"></i>
                        </div>
                        <div>
                            <div class="kpi-title">Total Stock Units</div>
                            <div class="kpi-value">[[ totalStockUnits ]]</div>
                        </div>
                    </div>

                    <div class="glass kpi-card">
                        <div class="kpi-icon" style="background: #fef3c7; color: #d97706;">
                            <i class="fa-solid fa-vault"></i>
                        </div>
                        <div>
                            <div class="kpi-title">Total Inventory Value</div>
                            <div class="kpi-value">RM [[ formatCurrency(totalInventoryValue) ]]</div>
                        </div>
                    </div>

                    <div class="glass kpi-card">
                        <div class="kpi-icon" style="background: #f3e8ff; color: #9333ea;">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <div class="kpi-title">Active Ledger Entries</div>
                            <div class="kpi-value">[[ transactions.length ]]</div>
                        </div>
                    </div>
                </div>

                <!-- Side by Side Main Panel Layout -->
                <div class="main-layout">
                    <!-- Left Column: Products List -->
                    <div class="glass" style="display: flex; flex-direction: column;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fa-solid fa-tags" style="color: var(--primary);"></i> Products Catalog
                            </div>
                            <button @click="loadData" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <div class="product-list">
                            <div 
                                v-for="product in products" 
                                :key="product.id"
                                :class="['product-card', selectedProductId === product.id ? 'active' : '']"
                                @click="filterByProduct(product.id)"
                            >
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div class="product-sku">[[ product.sku ]]</div>
                                    <span class="badge badge-purchase">Stock: [[ product.current_stock_quantity ]]</span>
                                </div>
                                <div class="product-name">[[ product.name ]]</div>
                                
                                <div class="product-stats" style="margin-top: 0.5rem;">
                                    <span>Current WAC:</span>
                                    <strong style="color: #4f46e5;">RM [[ formatCurrency(product.current_wac_cost) ]]</strong>
                                </div>
                                <div class="product-stats" style="margin-top: 0.25rem;">
                                    <span>Total Valuation:</span>
                                    <strong style="color: #059669;">RM [[ formatCurrency(product.current_total_value) ]]</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Transactions History Ledger -->
                    <div class="glass" style="display: flex; flex-direction: column;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="fa-solid fa-list-check" style="color: var(--success);"></i> Ledger Transactions History
                                <span v-if="selectedProductId" class="badge badge-sale" style="margin-left: 0.5rem;">
                                    Filtered by Product #[[ selectedProductId ]]
                                    <i @click.stop="selectedProductId = null" class="fa-solid fa-xmark" style="cursor: pointer; margin-left: 0.25rem;"></i>
                                </span>
                            </div>

                            <div style="display: flex; gap: 0.5rem;">
                                <button @click="openModal('create', 'purchase')" class="btn btn-secondary btn-sm" style="color: #15803d; background: #dcfce7; border-color: #bbf7d0;">
                                    + Purchase
                                </button>
                                <button @click="openModal('create', 'sale')" class="btn btn-secondary btn-sm" style="color: #7e22ce; background: #f3e8ff; border-color: #e9d5ff;">
                                    + Sale
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Cost / Price</th>
                                        <th>COGS Snapshot</th>
                                        <th>Running Stock</th>
                                        <th>Running Value</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="filteredTransactions.length === 0">
                                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                                            No stock transactions recorded yet.
                                        </td>
                                    </tr>
                                    <tr v-for="tx in filteredTransactions" :key="tx.id">
                                        <td style="font-weight: 500;">[[ tx.transaction_date ]]</td>
                                        <td>
                                            <div style="font-weight: 600;">[[ tx.product?.name || 'Product #' + tx.product_id ]]</div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">[[ tx.product?.sku ]]</div>
                                        </td>
                                        <td>
                                            <span :class="['badge', tx.type === 'purchase' ? 'badge-purchase' : 'badge-sale']">
                                                [[ tx.type.toUpperCase() ]]
                                            </span>
                                        </td>
                                        <td style="font-weight: 600;">[[ tx.quantity ]]</td>
                                        <td>
                                            <span v-if="tx.type === 'purchase'">RM [[ formatCurrency(tx.unit_cost) ]]</span>
                                            <span v-else>RM [[ formatCurrency(tx.unit_price) ]]</span>
                                        </td>
                                        <td>
                                            <div v-if="tx.type === 'sale'">
                                                <div style="color: #7e22ce; font-weight: 600;">Total: RM [[ formatCurrency(tx.total_cogs) ]]</div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">Unit WAC: RM [[ formatCurrency(tx.cogs_unit_cost) ]]</div>
                                            </div>
                                            <span v-else style="color: var(--text-muted);">-</span>
                                        </td>
                                        <td style="font-weight: 600;">[[ tx.running_qty ]]</td>
                                        <td style="color: #059669; font-weight: 600;">RM [[ formatCurrency(tx.running_total_value) ]]</td>
                                        <td style="text-align: right;">
                                            <button @click="openModal('edit', null, tx)" class="btn btn-secondary btn-sm" style="margin-right: 0.25rem;">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button @click="deleteTransaction(tx.id)" class="btn btn-danger btn-sm">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Create / Edit Transaction -->
        <div v-if="modal.show" class="modal-overlay" @click.self="closeModal">
            <div class="glass modal-content">
                <h3 style="font-family: var(--font-heading); margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; color: var(--text-main);">
                    <span>[[ modal.mode === 'create' ? 'Record New Transaction' : 'Edit Transaction #' + modal.txId ]]</span>
                    <i @click="closeModal" class="fa-solid fa-xmark" style="cursor: pointer; font-size: 1.25rem; color: var(--text-muted);"></i>
                </h3>

                <form @submit.prevent="saveTransaction">
                    <div class="form-group" v-if="modal.mode === 'create'">
                        <label class="form-label">Product</label>
                        <select v-model="form.product_id" class="form-control" required>
                            <option value="" disabled>Select Product</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                [[ p.name ]] ([[ p.sku ]])
                            </option>
                        </select>
                    </div>

                    <div class="form-group" v-if="modal.mode === 'create'">
                        <label class="form-label">Transaction Type</label>
                        <select v-model="form.type" class="form-control" required>
                            <option value="purchase">Purchase (Ingest Stock)</option>
                            <option value="sale">Sale (Deplete Stock & Snapshot COGS)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Transaction Date (YYYY-MM-DD)</label>
                        <input v-model="form.transaction_date" type="date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input v-model.number="form.quantity" type="number" min="1" class="form-control" required>
                    </div>

                    <div class="form-group" v-if="form.type === 'purchase'">
                        <label class="form-label">Unit Purchase Cost (RM)</label>
                        <input v-model="form.unit_cost" type="number" step="0.0001" min="0.0001" class="form-control" required placeholder="0.0000">
                    </div>

                    <div class="form-group" v-if="form.type === 'sale'">
                        <label class="form-label">Unit Selling Price (RM)</label>
                        <input v-model="form.unit_price" type="number" step="0.0001" min="0.0001" class="form-control" required placeholder="0.0000">
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="button" @click="closeModal" class="btn btn-secondary" style="flex: 1; justify-content: center;">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                            [[ modal.mode === 'create' ? 'Save Transaction' : 'Update & Recalculate' ]]
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div class="toast-container">
            <div 
                v-for="t in toasts" 
                :key="t.id"
                :class="['toast', t.type === 'success' ? 'toast-success' : 'toast-error']"
            >
                <i :class="['fa-solid', t.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation']"></i>
                <span>[[ t.message ]]</span>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            delimiters: ['[[', ']]'],
            data() {
                return {
                    token: localStorage.getItem('jwt_token') || '',
                    currentUser: null,
                    products: [],
                    transactions: [],
                    selectedProductId: null,
                    loginForm: {
                        email: 'admin@example.com',
                        password: 'password123'
                    },
                    modal: {
                        show: false,
                        mode: 'create', // 'create' or 'edit'
                        txId: null
                    },
                    form: {
                        product_id: '',
                        type: 'purchase',
                        transaction_date: new Date().toISOString().split('T')[0],
                        quantity: 1,
                        unit_cost: '',
                        unit_price: ''
                    },
                    toasts: []
                };
            },
            computed: {
                totalStockUnits() {
                    return this.products.reduce((acc, p) => acc + (p.current_stock_quantity || 0), 0);
                },
                totalInventoryValue() {
                    return this.products.reduce((acc, p) => acc + parseFloat(p.current_total_value || 0), 0);
                },
                filteredTransactions() {
                    if (!this.selectedProductId) return this.transactions;
                    return this.transactions.filter(t => t.product_id === this.selectedProductId);
                }
            },
            methods: {
                showToast(message, type = 'success') {
                    const id = Date.now();
                    this.toasts.push({ id, message, type });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 4000);
                },
                formatCurrency(val) {
                    if (val === null || val === undefined) return '0.0000';
                    return parseFloat(val).toFixed(4);
                },
                async quickLogin() {
                    this.loginForm.email = 'admin@example.com';
                    this.loginForm.password = 'password123';
                    await this.login();
                },
                async login() {
                    try {
                        const response = await axios.post('/api/auth/login', this.loginForm);
                        this.token = response.data.access_token;
                        localStorage.setItem('jwt_token', this.token);
                        this.currentUser = response.data.user;
                        this.showToast('Successfully authenticated!');
                        this.loadData();
                    } catch (err) {
                        this.showToast(err.response?.data?.message || 'Login failed', 'error');
                    }
                },
                logout() {
                    localStorage.removeItem('jwt_token');
                    this.token = '';
                    this.currentUser = null;
                    this.showToast('Logged out successfully.');
                },
                async loadData() {
                    if (!this.token) return;

                    const config = {
                        headers: { Authorization: `Bearer ${this.token}` }
                    };

                    try {
                        // Load Products and Transactions in parallel
                        const [prodRes, purchRes, salesRes] = await Promise.all([
                            axios.get('/api/products', config),
                            axios.get('/api/purchases', config),
                            axios.get('/api/sales', config)
                        ]);

                        this.products = prodRes.data.data || prodRes.data;
                        
                        const purchases = (purchRes.data.data || purchRes.data).map(p => ({ ...p, type: 'purchase' }));
                        const sales = (salesRes.data.data || salesRes.data).map(s => ({ ...s, type: 'sale' }));
                        
                        // Merge and sort chronologically by date DESC, ID DESC
                        this.transactions = [...purchases, ...sales].sort((a, b) => {
                            if (a.transaction_date === b.transaction_date) {
                                return b.id - a.id;
                            }
                            return b.transaction_date.localeCompare(a.transaction_date);
                        });
                    } catch (err) {
                        if (err.response?.status === 401) {
                            this.logout();
                        } else {
                            this.showToast('Failed to load inventory data', 'error');
                        }
                    }
                },
                filterByProduct(productId) {
                    if (this.selectedProductId === productId) {
                        this.selectedProductId = null;
                    } else {
                        this.selectedProductId = productId;
                    }
                },
                openModal(mode, defaultType = 'purchase', tx = null) {
                    this.modal.mode = mode;
                    this.modal.show = true;

                    if (mode === 'create') {
                        this.modal.txId = null;
                        this.form = {
                            product_id: this.selectedProductId || (this.products[0]?.id || ''),
                            type: defaultType,
                            transaction_date: new Date().toISOString().split('T')[0],
                            quantity: 1,
                            unit_cost: defaultType === 'purchase' ? '10.00' : '',
                            unit_price: defaultType === 'sale' ? '20.00' : ''
                        };
                    } else if (mode === 'edit' && tx) {
                        this.modal.txId = tx.id;
                        this.form = {
                            product_id: tx.product_id,
                            type: tx.type,
                            transaction_date: tx.transaction_date,
                            quantity: tx.quantity,
                            unit_cost: tx.unit_cost || '',
                            unit_price: tx.unit_price || ''
                        };
                    }
                },
                closeModal() {
                    this.modal.show = false;
                },
                async saveTransaction() {
                    const config = {
                        headers: { Authorization: `Bearer ${this.token}` }
                    };

                    try {
                        if (this.modal.mode === 'create') {
                            const endpoint = this.form.type === 'purchase' ? '/api/purchases' : '/api/sales';
                            await axios.post(endpoint, this.form, config);
                            this.showToast(`${this.form.type.toUpperCase()} recorded successfully!`);
                        } else {
                            await axios.put(`/api/transactions/${this.modal.txId}`, this.form, config);
                            this.showToast(`Transaction #${this.modal.txId} updated and timeline recalculated!`);
                        }

                        this.closeModal();
                        this.loadData();
                    } catch (err) {
                        const errMsg = err.response?.data?.message || err.response?.data?.error || 'Operation failed';
                        this.showToast(errMsg, 'error');
                    }
                },
                async deleteTransaction(txId) {
                    if (!confirm(`Are you sure you want to soft-delete Transaction #${txId}? Downstream balances will be recalculated automatically.`)) return;

                    const config = {
                        headers: { Authorization: `Bearer ${this.token}` }
                    };

                    try {
                        await axios.delete(`/api/transactions/${txId}`, config);
                        this.showToast(`Transaction #${txId} soft-deleted successfully.`);
                        this.loadData();
                    } catch (err) {
                        const errMsg = err.response?.data?.message || err.response?.data?.error || 'Delete failed';
                        this.showToast(errMsg, 'error');
                    }
                }
            },
            mounted() {
                if (this.token) {
                    this.loadData();
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
