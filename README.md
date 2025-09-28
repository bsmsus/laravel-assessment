# Laravel Assessment

This repository contains solutions for **Task A** (Bulk Import + Chunked Image Upload) and **Task B** (Reusable User Discounts Package).

---

## Tasks Overview

### Task A: Bulk Import + Chunked Image Upload
- CSV bulk import with upsert by unique key (Users by email / Products by SKU).  
- Result summary: total, imported, updated, invalid, duplicates.  
- Chunked/resumable image uploads with checksum validation.  
- Automatic image variants: 256px, 512px, 1024px (aspect ratio preserved).  
- Primary image linkage per entity, idempotent replacement.  
- Concurrency safe.

### Task B: User Discounts Package
- Laravel package (Composer installable, PSR-4).  
- Migrations: `discounts`, `user_discounts`, `discount_audits`.  
- Core methods: `assign`, `revoke`, `eligibleFor`, `apply`.  
- Configurable stacking, percentage cap, rounding.  
- Events: `DiscountAssigned`, `DiscountRevoked`, `DiscountApplied`.  
- Enforces expiry, usage caps, and concurrency safety.

---

## Local Development (Docker)

### Requirements
- Docker & Docker Compose  
- Composer  
- Node.js (for assets)

### Setup
```bash
git clone https://github.com/bsmsus/laravel-assessment.git
cd laravel-assessment

cp .env.example .env
```

Start containers:
```bash
docker compose up -d --build
```

Install dependencies & prepare app:
```bash
docker compose exec app bash -lc "composer install --no-interaction --prefer-dist && php artisan key:generate && php artisan migrate --force && php artisan storage:link"
```

Build frontend assets:
```bash
npm install
npm run dev
```

Run tests:
```bash
docker compose exec app php artisan test
```

---

## Deployment (Railway)

1. Push this repository to GitHub.  
2. In Railway → Create project → Deploy from GitHub → Add `.env` variables in Railway settings.  
3. Run migrations post-deploy:
   ```bash
   railway run php artisan migrate --force
   ```
4. Verify services and workers as needed.

---

## Custom Domain (Spaceship)

1. Add a **Custom Domain** in Railway → copy provided CNAME target.  
2. In **Spaceship DNS**, create a CNAME record pointing your domain/subdomain to Railway’s target.  
3. For apex domains, ensure ALIAS/flattening support or redirect root to `www`.  
4. Railway provisions SSL automatically after DNS propagation.

---

## Production Notes
- Set `APP_ENV=production`, `APP_DEBUG=false`.  
- Ensure `APP_KEY` is set and `storage/` is linked.  
- Run migrations with `--force`.  
- Confirm queues/workers are configured.  
- Railway handles SSL; verify domain propagation before go-live.

---

## Tests
- Unit tests included for both tasks.  
- Run all tests inside container:
  ```bash
  docker compose exec app php artisan test
  ```
