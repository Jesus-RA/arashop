# Arashop — Database Schema

Current database structure as defined by the committed migrations
(`2026-09-07`).

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

    stock_adjustments {
        bigint id PK
        bigint variant_id FK
        integer quantity_change
        string reason
        text notes
        bigint user_id FK
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
    product_variants ||--o{ stock_adjustments : "stock ledger / ON DELETE CASCADE"
    users o|--o{ stock_adjustments : "recorded by / ON DELETE SET NULL"
    users o|--o{ sessions : "session owner / soft link, no FK"
```

## Notes

- **Domain tables**: `products`, `product_variants`, `stock_adjustments`, `settings`.
- **Auth tables**: `users`, `sessions`, `password_reset_tokens` (Laravel default, from the
  `0001_01_01_000000` migration).
- **Framework tables**: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`
  (Laravel queue & cache scaffolding; no business relationships).
- `products.sku` and `settings.key` are unique; `settings.value` stores the dotted-key
  config (e.g. `fx.usd_to_mxn`, `pricing.margin_percent`) as JSON.
- `product_variants.attributes` is free-form JSON (`key` → `value`).
- Soft deletes are enabled on `products` and `product_variants` (`deleted_at`).
