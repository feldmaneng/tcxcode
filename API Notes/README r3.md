# CI4 API Bundle — Contacts (Lovable integration)

Drop these files into your existing CodeIgniter 4 project at the matching paths. Then:

1. `composer require firebase/php-jwt`
2. **Configure the `control` database group** (required — the API tables live in
   a separate database from your default app DB). Add to `app/Config/Database.php`:
   ```php
   public array $control = [
       'DSN'      => '',
       'hostname' => 'localhost',
       'username' => 'control_user',
       'password' => '...',
       'database' => 'control',
       'DBDriver' => 'MySQLi',
       'DBPrefix' => '',
       'pConnect' => false,
       'DBDebug'  => true,
       'charset'  => 'utf8mb4',
       'DBCollat' => 'utf8mb4_general_ci',
       'swapPre'  => '',
       'encrypt'  => false,
       'compress' => false,
       'strictOn' => false,
       'failover' => [],
       'port'     => 3306,
   ];
   ```
   The migrations and models for `api_clients`, `api_audit_log`, and
   `refresh_tokens` all set `$DBGroup = 'control'` and will use this connection.
3. **Configure encryption** (required — secrets are stored encrypted at rest):
   ```
   php spark key:generate
   ```
   This sets `encryption.key` in `.env`. The HMAC filter uses CI4's `Encrypter`
   service (which reads this key) to decrypt each client's stored secret in-memory
   for signature verification. **If this key is lost, all stored API client secrets
   become unrecoverable and must be rotated.** Back it up alongside your DB credentials.
4. Run migrations against the `control` group:
   ```
   php spark migrate --all
   ```
   (`--all` runs every configured DB group's migrations. Or target it explicitly:
   `php spark migrate -g control`.)
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
- `app/Models/ApiClientModel.php` *(uses `control` DB group)*
- `app/Models/RefreshTokenModel.php` *(uses `control` DB group)*
- `app/Models/AuditLogModel.php` *(uses `control` DB group)*
- `app/Database/Migrations/2025-01-01-000001_CreateApiClients.php` *(control)*
- `app/Database/Migrations/2025-01-01-000002_CreateRefreshTokens.php` *(control)*
- `app/Database/Migrations/2025-01-01-000003_CreateApiAuditLog.php` *(control)*

## Provision Lovable client

HMAC auth requires the raw secret on both sides, so the server stores it
encrypted (not hashed) using CI4's `Encrypter`.

### Encrypt the secret (one-liner, no CI4 bootstrap needed)

This command takes your `encryption.key` from `.env` and a plaintext secret,
and prints the base64 ciphertext that goes in `secret_encrypted`. It matches
CI4's OpenSSL Encrypter exactly (AES-256-CTR + HMAC-SHA512, HKDF subkeys).

```bash
ENC_KEY='hex2bin:PASTE_YOUR_64_HEX_KEY_HERE' \
SECRET="$(openssl rand -hex 32)" \
php -r '
$k = getenv("ENC_KEY");
$k = str_starts_with($k,"hex2bin:") ? hex2bin(substr($k,8)) : $k;
$msg = getenv("SECRET");
$encKey  = hash_hkdf("sha512", $k, 0, "encryption");
$authKey = hash_hkdf("sha512", $k, 0, "authentication");
$iv = random_bytes(16);
$ct = openssl_encrypt($msg, "aes-256-ctr", $encKey, OPENSSL_RAW_DATA, $iv);
$payload = $iv . $ct;
$hmac = hash_hmac("sha512", $payload, $authKey, true);
echo "SECRET (give to Lovable): " . $msg . PHP_EOL;
echo "ENCRYPTED (paste in DB):  " . base64_encode($hmac . $payload) . PHP_EOL;
'
```

### Insert the row (in the `control` database)

```sql
INSERT INTO control.api_clients (name, api_key, secret_encrypted, active, created_at)
VALUES ('lovable-service', 'PASTE_KEY', 'PASTE_ENCRYPTED', 1, NOW());
```

Generate the API key with `openssl rand -hex 16` (or any unique identifier).

Then give Lovable:
- `CI4_API_BASE_URL`  = https://api.yourdomain.com
- `CI4_SERVICE_KEY`   = the api_key value
- `CI4_SERVICE_SECRET`= the raw plaintext secret (NOT the encrypted blob)

### Verify round-trip

```bash
php spark shell
>>> service('encrypter')->decrypt(base64_decode('PASTE_ENCRYPTED'));
```
Should print the original plaintext secret.

### Rotation

Generate a new secret, encrypt it with the one-liner above, then:
```sql
UPDATE control.api_clients
SET secret_encrypted = 'NEW_ENCRYPTED', rotated_at = NOW()
WHERE api_key = 'EXISTING_KEY';
```
Update `CI4_SERVICE_SECRET` in Lovable.

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
- `encryption.key` is missing or wrong (decrypt fails -> 500 `secret_decrypt_failed`)

## JWT scheme (end-user auth, future)

`POST /api/v1/auth/login` { username, password } -> { access_token, refresh_token }
Access token: HS256, 15 min, claims: { sub, name, roles, iat, exp }
Refresh token: opaque random, stored hashed in `control.refresh_tokens`, rotated on use.

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
