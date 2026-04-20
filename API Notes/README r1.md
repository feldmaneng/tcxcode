# CI4 API Bundle — Contacts (Lovable integration)

Drop these files into your existing CodeIgniter 4 project at the matching paths. Then:

1. `composer require firebase/php-jwt`
2. Run migrations: `php spark migrate`
3. Seed an API client for Lovable (see "Provision Lovable client" below).
4. Set env vars in `.env`:
   ```
   app.JWT_SECRET = "<openssl rand -hex 64>"
   app.JWT_TTL_SECONDS = 900
   app.REFRESH_TTL_SECONDS = 604800
   app.HMAC_MAX_SKEW_SECONDS = 300
   app.CORS_ALLOWED_ORIGINS = "https://YOUR-PROJECT.lovable.app,https://id-preview--YOUR-PROJECT.lovable.app"
   ```
5. In production, force HTTPS at the web server and add HSTS:
   `Strict-Transport-Security: max-age=31536000; includeSubDomains`

## Files

- `app/Config/Routes.php` — additions for `/api/v1/...` group
- `app/Config/Filters.php` — register filters
- `app/Filters/HmacAuthFilter.php`
- `app/Filters/JwtAuthFilter.php`
- `app/Filters/CorsFilter.php`
- `app/Filters/ThrottleFilter.php`
- `app/Filters/AuditLogFilter.php`
- `app/Libraries/JwtService.php`
- `app/Libraries/HmacVerifier.php`
- `app/Controllers/Api/V1/BaseApiController.php`
- `app/Controllers/Api/V1/AuthController.php`
- `app/Controllers/Api/V1/ContactsController.php`
- `app/Models/ContactModel.php`
- `app/Models/ApiClientModel.php`
- `app/Models/RefreshTokenModel.php`
- `app/Models/AuditLogModel.php`
- `app/Database/Migrations/2025-01-01-000001_CreateApiClients.php`
- `app/Database/Migrations/2025-01-01-000002_CreateRefreshTokens.php`
- `app/Database/Migrations/2025-01-01-000003_CreateApiAuditLog.php`
- `app/Database/Migrations/2025-01-01-000004_CreateContacts.php` (skip if `contacts` already exists)

## Provision Lovable client

```sql
-- Generate a key + secret on your machine:
--   key:    openssl rand -hex 16     -> e.g. 3f9a...
--   secret: openssl rand -hex 32     -> e.g. b2c7...
-- Hash the secret with bcrypt before storing:
--   php -r 'echo password_hash("PASTE_SECRET_HERE", PASSWORD_BCRYPT) . PHP_EOL;'

INSERT INTO api_clients (name, api_key, secret_hash, active, created_at)
VALUES ('lovable-service', 'PASTE_KEY', 'PASTE_BCRYPT_HASH', 1, NOW());
```

Then give Lovable:
- `CI4_API_BASE_URL`  = https://api.yourdomain.com
- `CI4_SERVICE_KEY`   = the key
- `CI4_SERVICE_SECRET`= the raw secret (NOT the hash)

## HMAC signing scheme

Client computes:
```
bodyHash  = sha256_hex(rawRequestBody)        // empty body -> sha256("")
canonical = METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + bodyHash
signature = hmac_sha256_hex(secret, canonical)
```

Headers sent:
```
X-Api-Key:    <key>
X-Timestamp:  <unix seconds>
X-Signature:  <hex>
Content-Type: application/json
```

Server rejects if:
- key not found / inactive
- |now - timestamp| > 300s
- recomputed signature != provided

## JWT scheme (end-user auth, future)

`POST /api/v1/auth/login` { username, password } -> { access_token, refresh_token }
Access token: HS256, 15 min, claims: { sub, name, roles, iat, exp }
Refresh token: opaque random, stored hashed in `refresh_tokens`, rotated on use.

## Routes added

```
POST   /api/v1/auth/login          (public)
POST   /api/v1/auth/refresh        (public)

GET    /api/v1/contacts            (hmac OR jwt)
GET    /api/v1/contacts/{id}       (hmac OR jwt)
POST   /api/v1/contacts            (hmac OR jwt)
PUT    /api/v1/contacts/{id}       (hmac OR jwt)
DELETE /api/v1/contacts/{id}       (hmac OR jwt)
```
