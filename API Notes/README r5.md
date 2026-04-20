# CI4 API Bundle — Contacts (Lovable integration)

Drop these files into your existing CodeIgniter 4 project at the matching paths. Then:

1. `composer require firebase/php-jwt`
2. **Configure encryption** (required — secrets are stored encrypted at rest):
   ```
   php spark key:generate
   ```
   This sets `app.encryption.key` in `.env`. The HMAC filter uses CI4's `Encrypter`
   service (which reads this key) to decrypt each client's stored secret in-memory
   for signature verification. **If this key is lost, all stored API client secrets
   become unrecoverable and must be rotated.** Back it up alongside your DB credentials.
3. **Configure the `control` database group** — only the `api_clients` table lives in a separate database (so credentials are isolated from app data). `refresh_tokens` and `api_audit_log` stay on the `default` group.

   In `app/Config/Database.php`, add a `$control` property alongside `$default`:
   ```php
   public array $control = [
       'DSN'      => '',
       'hostname' => 'localhost',
       'username' => '',
       'password' => '',
       'database' => 'control',
       'DBDriver' => 'MySQLi',
       // ...same shape as $default
   ];
   ```
   Or via `.env`:
   ```
   database.control.hostname = localhost
   database.control.database = control
   database.control.username = ...
   database.control.password = ...
   database.control.DBDriver = MySQLi
   ```
4. Run migrations against both groups:
   ```
   php spark migrate                 # default group: refresh_tokens, api_audit_log
   php spark migrate -g control      # control group: api_clients
   ```
   The `CreateApiClients` migration explicitly binds its `$db` and `$forge` to the `control` group in its constructor (using `\Config\Database::forge('control')`), so the table is always created in the control DB even if you accidentally omit the `-g control` flag. Verify with `SHOW TABLES;` on the `control` database — `api_clients` should appear there, not in `default`.
5. Seed an API client for Lovable (see "Provision Lovable client" below).
6. Set env vars in `.env`:
   ```
   app.JWT_SECRET = "<openssl rand -hex 64>"
   app.JWT_TTL_SECONDS = 900
   app.REFRESH_TTL_SECONDS = 604800
   app.HMAC_MAX_SKEW_SECONDS = 300
   app.CORS_ALLOWED_ORIGINS = "https://YOUR-PROJECT.lovable.app,https://id-preview--YOUR-PROJECT.lovable.app"
   ```
7. In production, force HTTPS at the web server and add HSTS:
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

## Provision Lovable client

HMAC auth requires the raw secret on both sides, so the server stores it
encrypted (not hashed) using CI4's `Encrypter`. Generate the encrypted blob with
a one-off CLI script, then insert the row.

```bash
# 1. Generate a fresh key + secret
KEY=$(openssl rand -hex 16)
SECRET=$(openssl rand -hex 32)
echo "API key:    $KEY"
echo "Raw secret: $SECRET   <-- give this to Lovable, store nowhere else"

# 2. Encrypt the secret with CI4's Encrypter (run from your CI4 project root)
php -r '
  require "vendor/autoload.php";
  $app = require "app/Config/Paths.php";
  define("FCPATH", __DIR__ . "/public/");
  define("HOMEPATH", __DIR__ . "/");
  define("APPPATH", __DIR__ . "/app/");
  define("ROOTPATH", __DIR__ . "/");
  define("SYSTEMPATH", __DIR__ . "/vendor/codeigniter4/framework/system/");
  require SYSTEMPATH . "Boot.php";
  CodeIgniter\Boot::preloadEnvironment();
  $enc = \Config\Services::encrypter();
  echo base64_encode($enc->encrypt(getenv("SECRET"))) . PHP_EOL;
' SECRET="$SECRET"
# -> copy the base64 output as PASTE_ENCRYPTED below
```

```sql
INSERT INTO api_clients (name, api_key, secret_encrypted, active, created_at)
VALUES ('lovable-service', 'PASTE_KEY', 'PASTE_ENCRYPTED', 1, NOW());
```

Then give Lovable:
- `CI4_API_BASE_URL`  = https://api.yourdomain.com
- `CI4_SERVICE_KEY`   = the key
- `CI4_SERVICE_SECRET`= the raw secret (NOT the encrypted blob)

### Rotation

To rotate: generate a new secret, encrypt it, `UPDATE api_clients SET secret_encrypted = ?, rotated_at = NOW() WHERE api_key = ?`, then update `CI4_SERVICE_SECRET` in Lovable.

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
- `app.encryption.key` is missing or wrong (decrypt fails -> 500 `secret_decrypt_failed`)

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
