# Arashop — Database Schema

Current database structure as defined by the committed migrations
(`2026-09-11`).

## ER Diagram

```mermaid
erDiagram
    %% -------------------------------------------------- domain
    products {
        bigint id PK
        string name
        text description
        string sku UK
        string status "default: draft"
        string image_path
        string source_url
        timestamp last_scraped_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    product_variants {
        bigint id PK
        bigint product_id FK
        string sku
        string name
        json attributes
        decimal cost_usd "10,2"
        decimal price_mxn "10,2"
        unsignedInteger stock_quantity "default: 0"
        string image_path
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    inventory_transactions {
        bigint id PK
        bigint variant_id FK
        integer quantity_change
        string transaction_type "SALE | RESTOCK | MANUAL_ADJUSTMENT | ORDER_CANCELLATION"
        bigint reference_id "orders.id for SALE / ORDER_CANCELLATION"
        string reason "required for MANUAL_ADJUSTMENT"
        text notes
        bigint user_id FK
        timestamp created_at "append-only, no updated_at"
    }

    orders {
        bigint id PK
        string order_number UK "ORD-0001"
        string status "default: pending"
        decimal total_amount "10,2"
        bigint user_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    order_items {
        bigint id PK
        bigint order_id FK
        bigint variant_id FK "RESTRICT on delete"
        unsignedInteger quantity
        decimal unit_price_mxn "10,2 snapshot"
        decimal unit_cost_usd "10,2 snapshot"
        decimal subtotal "10,2"
        timestamp created_at
        timestamp updated_at
    }

    settings {
        bigint id PK
        string key UK
        json value
        timestamp created_at
        timestamp updated_at
    }

    %% -------------------------------------------------- auth
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    sessions {
        string id PK
        bigint user_id "index only, no FK"
        string ip_address "45"
        text user_agent
        longText payload
        integer last_activity
    }

    password_reset_tokens {
        string email PK
        string token
        timestamp created_at
    }

    %% -------------------------------------------------- framework
    cache {
        string key PK
        mediumText value
        bigint expiration
    }

    cache_locks {
        string key PK
        string owner
        bigint expiration
    }

    jobs {
        bigint id PK
        string queue
        longText payload
        unsignedSmallInteger attempts
        unsignedInteger reserved_at
        unsignedInteger available_at
        unsignedInteger created_at
    }

    job_batches {
        string id PK
        string name
        integer total_jobs
        integer pending_jobs
        integer failed_jobs
        longText failed_job_ids
        mediumText options
        integer cancelled_at
        integer created_at
        integer finished_at
    }

    failed_jobs {
        bigint id PK
        string uuid UK
        string connection
        string queue
        longText payload
        longText exception
        timestamp failed_at
    }

    %% -------------------------------------------------- relations
    products ||--o{ product_variants : "has variants / ON DELETE CASCADE"
    product_variants ||--o{ inventory_transactions : "stock ledger / ON DELETE CASCADE"
    orders ||--o{ order_items : "line items / ON DELETE CASCADE"
    order_items }o--|| product_variants : "snapshot source / ON DELETE RESTRICT"
    orders o|--o{ inventory_transactions : "SALE / ORDER_CANCELLATION via reference_id"
    users o|--o{ inventory_transactions : "recorded by / ON DELETE SET NULL"
    users o|--o{ sessions : "session owner / soft link, no FK"
```

## Notes

- **Domain tables**: `products`, `product_variants`, `inventory_transactions`, `orders`,
  `order_items`, `settings`.
- **Auth tables**: `users`, `sessions`, `password_reset_tokens` (Laravel default, from the
  `0001_01_01_000000` migration).
- **Framework tables**: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`
  (Laravel queue & cache scaffolding; no business relationships).
- `products.sku` and `settings.key` are unique; `settings.value` stores the dotted-key
  config (e.g. `fx.usd_to_mxn`, `pricing.margin_percent`) as JSON.
- `product_variants.attributes` is free-form JSON (`key` → `value`).
- `inventory_transactions` is an append-only ledger: `created_at` only, no `updated_at`.
  `quantity_change` is positive (restock/cancellation) or negative (sale), the transaction
  type is a string enum, and `reason` is mandated for `MANUAL_ADJUSTMENT`.
- `order_items.unit_price_mxn` / `unit_cost_usd` are immutable snapshots captured at
  checkout, so later price edits never rewrite order history.
- Soft deletes are enabled on `products`, `product_variants`, and `orders` (`deleted_at`).
  `order_items.variant_id` uses `ON DELETE RESTRICT` so variants with order history can
  never be hard-deleted.
