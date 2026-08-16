# Weighted Average Cost (WAC) Inventory Valuation Engine

A high-precision, event-driven inventory ledger and valuation engine built with **PHP 8.4**, **Laravel 13**, **MySQL 8.0**, and **Docker Compose**.

This application implements the **Perpetual Weighted Average Cost (WAC)** valuation method, automatically calculating point-in-time Cost of Goods Sold (COGS) for sales, handling backdated transaction ingestions via an $O(K)$ cascading recalculation algorithm, preventing negative stock levels, and enforcing single active daily transaction constraints.

---

## 🛠️ Architecture & Tech Stack

* **Language**: PHP 8.4 (with BCMath 4-decimal precision arithmetic)
* **Framework**: Laravel 13 (`laravel/framework`)
* **Database**: MySQL 8.0 (InnoDB with row-level pessimistic locking)
* **Authentication**: JWT (`php-open-source-saver/jwt-auth`)
* **Containerization**: Docker & Docker Compose (Nginx + PHP-FPM + MySQL)
* **API Transformers**: Laravel API Resources (`ProductResource`, `StockTransactionResource`, `UserResource`)
* **Testing**: PHPUnit (`php artisan test`)

---

## 🏛️ Key Architectural & Design Decisions (and Why)

### 1. Unified `stock_transactions` Ledger Table
* **Decision**: Both purchases and sales are stored in a single `stock_transactions` table with a `type` column (`purchase` | `sale`) and a composite index `idx_product_txdate` on `(product_id, transaction_date ASC, id ASC)`.
* **Why**: Eliminates multi-table JOINs and enables a clean, single-pass chronological stream scan during inventory recalculations.

### 2. BCMath 4-Decimal Fixed-Point Arithmetic
* **Decision**: All financial calculations use PHP's `bcadd`, `bcsub`, `bcmul`, and `bcdiv` with scale 4, paired with database `DECIMAL(14,4)` column types.
* **Why**: IEEE 754 floating-point arithmetic (e.g., `0.1 + 0.2 !== 0.3`) causes rounding drift in financial ledgers. BCMath guarantees exact fixed-point precision.

### 3. Pessimistic Row Locking (`lockForUpdate()`)
* **Decision**: All transaction creations, updates, and soft-deletions execute inside database transactions (`DB::transaction()`) with a pessimistic lock on the product (`Product::lockForUpdate()`).
* **Why**: Prevents concurrency race conditions when multiple API requests try to modify stock levels for the same product simultaneously.

### 4. $O(K)$ Downstream Recalculation Cascade
* **Decision**: Modifying or backdating a transaction on date $T$ fetches the single prior baseline transaction before $T$, then recalculates only active downstream transactions where `transaction_date >= T`.
* **Why**: Optimizes performance from $O(N)$ (rebuilding full history) down to $O(K)$ (recalculating only affected downstream entries).

### 5. Non-Negative Stock Invariant & DB Rollback
* **Decision**: If any historical state drops below zero stock (`running_qty < 0`) during recalculation, an `InsufficientStockException` is thrown.
* **Why**: Immediately aborts the database transaction, preventing negative physical inventory and returning a clear HTTP 422 error response.

### 6. Soft-Delete Aware Single Active Daily Transaction Rule
* **Decision**: Enforces a maximum of 1 active transaction per product per date (`unique` validation rule ignoring soft-deleted rows where `deleted_at IS NOT NULL`).
* **Why**: Prevents ambiguous intraday ordering while allowing users to re-submit a transaction on a date where a previous entry was soft-deleted.

### 7. Clean Separation of Concerns (Layered Architecture)
* **Decision**: Business logic resides strictly in `App\Services\InventoryService`, HTTP validation in `App\Http\Requests`, and JSON formatting in `App\Http\Resources`.
* **Why**: Keeps controllers thin, ensures core WAC logic is reusable (API, CLI, tests), and decouples internal database columns from external API contracts.

### 8. Dedicated MySQL Testing Database (`wac_inventory_test`)
* **Decision**: Configured `phpunit.xml` to execute test suites against a dedicated `wac_inventory_test` MySQL database.
* **Why**: Ensures tests run against real MySQL InnoDB storage engines while preventing `RefreshDatabase` from wiping local development data (`wac_inventory`).

---

## ⚡ Quick Start (1-Step Automated Setup)

Clone the repository and run the automated setup script. It will automatically build containers, install dependencies, generate keys, run migrations/seeders, and set up the dedicated test database:

```bash
git clone <repository-url> wac-inventory-engine
cd wac-inventory-engine

# Run the automated setup script
chmod +x setup.sh
./setup.sh
```

---

## 📋 Manual Installation Step-by-Step (Alternative)

If you prefer to run the setup steps manually instead of using `./setup.sh`:

### Step 1: Environment File Setup
```bash
cp .env.example .env
```

### Step 2: Start Docker Containers
```bash
docker compose up -d --build
```

### Step 3: Install PHP Dependencies
```bash
docker compose exec app composer install
```

### Step 4: Generate Application Key & JWT Secret
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret --force
```

### Step 5: Run Database Migrations & Seeders
```bash
docker compose exec app php artisan migrate:fresh --seed
```

### Step 6: Create Test Database & Grant Permissions
```bash
docker compose exec db mysql -u root -proot_password -e "CREATE DATABASE IF NOT EXISTS wac_inventory_test;"
docker compose exec db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON \`wac_inventory_test\`.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"
```

---

## 🧪 Running Automated Tests

Run all 12 PHPUnit test cases inside Docker:

```bash
docker compose exec app php artisan test
```

Expected Output:
```text
  PASS  Tests\Unit\InventoryServiceTest
  ✓ wac arithmetic precision calculation
  ✓ backdated purchase ingestion cascades downstream sales
  ✓ historical mutation recalculates downstream ledger
  ✓ historical soft delete recalculates downstream ledger
  ✓ insufficient stock exception thrown and rolls back database
  ✓ zero stock depletion resets running value and wac to zero

  PASS  Tests\Feature\WacApiTest
  ✓ jwt authentication flow register login me logout
  ✓ store purchase transaction via api
  ✓ store sale transaction allocates cogs snapshot via api
  ✓ single active daily transaction rule validation
  ✓ update and soft delete transaction via api
  ✓ unauthenticated request rejected with 401

  Tests:    12 passed (60 assertions)
```

---

## 📡 REST API Endpoint Reference

All endpoints (except login/register) require a JWT Bearer token in the header:
`Authorization: Bearer <your_jwt_access_token>`

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/auth/register` | Register a new user |
| `POST` | `/api/auth/login` | Login and receive JWT access token |
| `GET` | `/api/auth/me` | Get authenticated user profile |
| `POST` | `/api/auth/logout` | Invalidate current JWT token |
| `GET` | `/api/products` | List all products with current stock & valuation |
| `GET` | `/api/purchases` | List purchase ledger transactions |
| `POST` | `/api/purchases` | Record purchase transaction |
| `GET` | `/api/sales` | List sale transactions with snapshot COGS |
| `POST` | `/api/sales` | Record sale transaction |
| `PUT` | `/api/transactions/{id}` | Update / backdate transaction date or quantity |
| `DELETE` | `/api/transactions/{id}` | Soft-delete transaction & cascade recalculate |

---

## 💻 cURL Usage Examples

### 1. Authentication (Login)
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

### 2. Record Purchase Transaction
```bash
curl -X POST http://localhost:8080/api/purchases \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "product_id": 1,
    "transaction_date": "2026-01-01",
    "quantity": 150,
    "unit_cost": "2.00"
  }'
```

### 3. Record Sale Transaction
```bash
curl -X POST http://localhost:8080/api/sales \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "product_id": 1,
    "transaction_date": "2026-01-05",
    "quantity": 5,
    "unit_price": "10.00"
  }'
```

### 4. Fetch Products Catalog & Cached Valuations
```bash
curl -X GET http://localhost:8080/api/products \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## 📜 License
The Weighted Average Cost (WAC) Inventory Engine is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
