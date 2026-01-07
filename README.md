# Wallet Service API

A production-grade REST API that simulates a Wallet Service built with Laravel 12. The system allows creating wallets, performing deposits, withdrawals, and atomic transfers between wallets, with complete transaction history and idempotency support.

## Table of Contents

- [Features](#features)
- [Architecture & Design Patterns](#architecture--design-patterns)
- [Technology Stack](#technology-stack)
- [Database Schema](#database-schema)
- [Installation](#installation)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Design Decisions](#design-decisions)
- [Performance Considerations](#performance-considerations)

---

## Features

✅ **Wallet Management** - Create and manage wallets with multi-currency support
✅ **Deposits & Withdrawals** - Add and remove funds with full validation
✅ **Atomic Transfers** - Move funds between wallets with ACID guarantees
✅ **Idempotency** - Prevent duplicate transactions using idempotency keys
✅ **Transaction History** - Complete audit trail with filtering and pagination
✅ **Double-Entry Accounting** - Every transfer creates debit and credit records
✅ **Integer Money Handling** - No floating-point errors, stores amounts in minor units (cents)
✅ **Comprehensive Tests** - Full test coverage including race conditions

---

## Architecture & Design Patterns

### **Layered Architecture**

```
┌─────────────────────────────────────┐
│         API Controllers             │  ← HTTP Layer (Validation, Response)
├─────────────────────────────────────┤
│         Service Layer               │  ← Business Logic (WalletService, TransferService)
├─────────────────────────────────────┤
│         Models (Eloquent)           │  ← Data Access Layer
├─────────────────────────────────────┤
│         Database (MySQL)            │  ← Persistence Layer
└─────────────────────────────────────┘
```

### **Key Design Patterns**

1. **Service Layer Pattern**
   - Business logic separated from controllers
   - `WalletService`: Handles deposits/withdrawals
   - `TransferService`: Handles atomic transfers
   - Promotes testability and code reuse

2. **Repository Pattern** (via Eloquent ORM)
   - Models abstract database operations
   - Relationships defined declaratively
   - Enables easy testing with factories

3. **Resource Pattern** (API Transformers)
   - Consistent JSON responses
   - `WalletResource` and `TransactionResource`
   - Separates presentation from domain logic

4. **Exception Handler Pattern**
   - Custom exceptions for domain errors
   - Global exception handling in `bootstrap/app.php`
   - Consistent error responses

### **SOLID Principles Applied**

- **Single Responsibility**: Each service/controller has one clear purpose
- **Open/Closed**: Services extend functionality without modifying existing code
- **Dependency Inversion**: Controllers depend on service interfaces, not concrete implementations
- **Interface Segregation**: Lean, focused interfaces

---

## Technology Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8.0+ (via WAMP)
- **Testing**: PHPUnit with Feature & Unit Tests
- **API**: RESTful JSON API
- **ORM**: Eloquent
- **Validation**: Laravel Form Requests

---

## Database Schema

### **Wallets Table**
```sql
wallets
├── id (PK)
├── owner_name (VARCHAR)
├── currency (CHAR(3))         # ISO 4217: USD, EUR, GBP
├── balance (BIGINT UNSIGNED)  # Stored in minor units (cents)
├── created_at
└── updated_at

Indexes:
- owner_name
- currency
- (owner_name, currency)
```

### **Transactions Table**
```sql
transactions
├── id (PK)
├── wallet_id (FK → wallets)
├── type (ENUM: deposit, withdrawal, transfer_debit, transfer_credit)
├── amount (BIGINT UNSIGNED)
├── related_wallet_id (FK → wallets, nullable)
├── idempotency_key (VARCHAR, unique)
├── metadata (JSON, nullable)
└── created_at

Indexes:
- wallet_id
- type
- created_at
- idempotency_key (UNIQUE)
```

### **Idempotency Logs Table**
```sql
idempotency_logs
├── id (PK)
├── idempotency_key (VARCHAR, unique)
├── request_hash (VARCHAR)  # SHA-256 of request payload
├── response_data (JSON)    # Cached response
└── created_at

Indexes:
- idempotency_key (UNIQUE)
```

---

## Installation

### Prerequisites

- **PHP** 8.2 or higher
- **Composer** 2.x
- **MySQL** 8.0+ (via WAMP or standalone)
- **Git**

### Step-by-Step Setup

1. **Clone the repository**
```bash
git clone <your-repo-url>
cd wallet-service
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database in `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wallet_service
DB_USERNAME=root
DB_PASSWORD=
```

5. **Create database**
```sql
-- In MySQL/phpMyAdmin:
CREATE DATABASE wallet_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Start development server**
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

---

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Endpoints

#### **1. Health Check**
```http
GET /health
```

**Response:**
```json
{
  "status": "ok"
}
```

---

#### **2. Create Wallet**
```http
POST /wallets
Content-Type: application/json

{
  "owner_name": "John Doe",
  "currency": "USD"
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "owner_name": "John Doe",
    "currency": "USD",
    "balance": 0,
    "formatted_balance": "0.00",
    "created_at": "2024-01-07T12:00:00.000000Z",
    "updated_at": "2024-01-07T12:00:00.000000Z"
  }
}
```

---

#### **3. List Wallets**
```http
GET /wallets?owner_name=John&currency=USD
```

**Query Parameters:**
- `owner_name` (optional): Filter by owner name (partial match)
- `currency` (optional): Filter by currency code
- `page` (optional): Pagination page number

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "owner_name": "John Doe",
      "currency": "USD",
      "balance": 10000,
      "formatted_balance": "100.00",
      "created_at": "2024-01-07T12:00:00.000000Z",
      "updated_at": "2024-01-07T12:05:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

#### **4. Get Wallet Details**
```http
GET /wallets/{id}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "owner_name": "John Doe",
    "currency": "USD",
    "balance": 10000,
    "formatted_balance": "100.00",
    "created_at": "2024-01-07T12:00:00.000000Z",
    "updated_at": "2024-01-07T12:05:00.000000Z"
  }
}
```

---

#### **5. Get Wallet Balance**
```http
GET /wallets/{id}/balance
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "wallet_id": 1,
    "balance": 10000,
    "formatted_balance": "100.00",
    "currency": "USD"
  }
}
```

---

#### **6. Deposit Funds**
```http
POST /wallets/{id}/deposit
Content-Type: application/json
Idempotency-Key: unique-key-123

{
  "amount": 10000
}
```

**Note:** Amount is in minor units (cents). 10000 = $100.00

**Response:**
```json
{
  "status": "success",
  "data": {
    "transaction_id": 1,
    "wallet_id": 1,
    "type": "deposit",
    "amount": 10000,
    "new_balance": 10000
  }
}
```

**Idempotency:** If the same `Idempotency-Key` is sent again, the cached response is returned without creating a duplicate transaction.

---

#### **7. Withdraw Funds**
```http
POST /wallets/{id}/withdraw
Content-Type: application/json
Idempotency-Key: unique-key-456

{
  "amount": 3000
}
```

**Response (Success):**
```json
{
  "status": "success",
  "data": {
    "transaction_id": 2,
    "wallet_id": 1,
    "type": "withdrawal",
    "amount": 3000,
    "new_balance": 7000
  }
}
```

**Response (Insufficient Balance - 422):**
```json
{
  "status": "error",
  "message": "Insufficient balance. Available: 7000, Requested: 10000",
  "code": "INSUFFICIENT_BALANCE"
}
```

---

#### **8. Transfer Between Wallets**
```http
POST /transfers
Content-Type: application/json
Idempotency-Key: transfer-unique-789

{
  "source_wallet_id": 1,
  "target_wallet_id": 2,
  "amount": 5000
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "transfer_id": 3,
    "source_wallet_id": 1,
    "target_wallet_id": 2,
    "amount": 5000,
    "source_new_balance": 2000,
    "target_new_balance": 5000,
    "debit_transaction_id": 3,
    "credit_transaction_id": 4
  }
}
```

**Business Rules:**
- Source and target must have the same currency
- Cannot transfer to the same wallet
- Must have sufficient balance
- Atomic operation (both debit and credit happen together)

---

#### **9. Get Transaction History**
```http
GET /wallets/{id}/transactions?type=deposit&from_date=2024-01-01&to_date=2024-01-31&per_page=20
```

**Query Parameters:**
- `type` (optional): Filter by type (deposit, withdrawal, transfer_debit, transfer_credit)
- `from_date` (optional): Start date (Y-m-d format)
- `to_date` (optional): End date (Y-m-d format)
- `per_page` (optional): Results per page (max 100)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "wallet_id": 1,
      "type": "deposit",
      "amount": 10000,
      "formatted_amount": "100.00",
      "related_wallet_id": null,
      "metadata": null,
      "created_at": "2024-01-07T12:00:00.000000Z"
    },
    {
      "id": 3,
      "wallet_id": 1,
      "type": "transfer_debit",
      "amount": 5000,
      "formatted_amount": "50.00",
      "related_wallet_id": 2,
      "metadata": {
        "transfer_to": "Jane Smith",
        "transfer_to_wallet_id": 2
      },
      "created_at": "2024-01-07T12:10:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### Error Responses

All errors follow this format:

```json
{
  "status": "error",
  "message": "Human-readable error message",
  "code": "ERROR_CODE"
}
```

**Common Error Codes:**
- `INSUFFICIENT_BALANCE` (422)
- `INVALID_AMOUNT` (422)
- `SELF_TRANSFER_NOT_ALLOWED` (422)
- `CURRENCY_MISMATCH` (422)
- Validation errors (422)
- Not Found (404)

---

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test File
```bash
php artisan test --filter=WalletTest
php artisan test --filter=TransferTest
```

### Test Coverage

- **Wallet Operations**: Create, list, filter, deposit, withdraw
- **Idempotency**: Duplicate prevention for all operations
- **Transfers**: Atomic transfers, currency validation, balance checks
- **Transaction History**: Filtering, pagination
- **Validation**: Amount, currency, wallet existence
- **Edge Cases**: Insufficient balance, self-transfers, currency mismatch

---

## Design Decisions

### **1. Integer Money Storage**
**Decision:** Store all amounts as integers (minor units).

**Rationale:**
- Eliminates floating-point precision errors
- $100.00 is stored as 10000 (cents)
- Arithmetic operations are exact
- Industry best practice for financial applications

### **2. Row-Level Locking**
**Decision:** Use `lockForUpdate()` on wallet rows during transactions.

**Rationale:**
- Prevents race conditions in concurrent requests
- Ensures balance consistency
- Deadlock prevention by locking wallets in ascending ID order

### **3. Separate Idempotency Log Table**
**Decision:** Dedicated table instead of checking transactions.

**Rationale:**
- Faster lookups with dedicated index
- Can store full response payload
- Cleaner separation of concerns
- Easier to implement request hash verification

### **4. Service Layer Separation**
**Decision:** Business logic in services, not controllers.

**Rationale:**
- Controllers stay thin (validation, response formatting)
- Services are easily testable
- Logic reusable across different entry points
- Follows Single Responsibility Principle

### **5. Double-Entry Accounting**
**Decision:** Create two transaction records for transfers (debit + credit).

**Rationale:**
- Complete audit trail
- Each wallet's history is self-contained
- Easier to reconcile and debug
- Standard accounting practice

### **6. Soft vs Hard Currency Validation**
**Decision:** Validate currency format but accept any 3-letter code.

**Rationale:**
- Simple validation (3 uppercase letters)
- No dependency on external currency lists
- Easy to extend for new currencies
- Task requirements met without over-engineering

---

## Performance Considerations

### **Optimizations Implemented**

1. **Database Indexes**
   - `wallets.owner_name` for filtering
   - `wallets.currency` for filtering
   - `transactions.wallet_id` for history queries
   - `transactions.created_at` for chronological sorting
   - `idempotency_logs.idempotency_key` for fast lookups

2. **Pagination**
   - All list endpoints support pagination
   - Default 15 items per page
   - Prevents memory exhaustion on large datasets

3. **Eager Loading**
   - Relationships can be eager loaded when needed
   - Prevents N+1 query problems

4. **Transaction Isolation**
   - Database transactions ensure ACID properties
   - Minimized transaction scope for better concurrency

### **Scaling Considerations**

**Current Limitations:**
- Single database server
- Row-level locking limits concurrency

**Future Improvements:**
- **Read Replicas**: Offload transaction history queries
- **Caching**: Cache wallet balances with invalidation
- **Queue Workers**: Async processing for non-critical operations
- **Sharding**: Partition wallets by ID range or geography
- **Event Sourcing**: Store events instead of state for ultimate auditability

---

## License

This project is a technical assessment implementation.

---

## Author

Built with attention to clean code, performance, and maintainability.
