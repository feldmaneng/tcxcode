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

   > **Known quirk / troubleshooting note:** Running `php spark migrate -g control` may fail with `Table 'refresh_tokens' already exists`. The `-g` flag forces *every* pending migration through the named group, ignoring each migration's internal forge binding, so it tries to re-create `refresh_tokens` in `control`. In practice the `api_clients` table still ends up in the `control` database (created by an earlier `php spark migrate` run via the constructor binding). If you hit this:
   > 1. Confirm `api_clients` exists in `control` (`SHOW TABLES;` on the control DB) — if so, you're done.
   > 2. If not, run plain `php spark migrate` (no `-g`) and let the constructor binding route `api_clients` to `control`.
   >
   > A cleaner long-term fix is to move `CreateApiClients` into its own migration namespace (e.g. `App\Database\ControlMigrations`) and run it with `php spark migrate -g control -n 'App\Database\ControlMigrations'`. Not done in this bundle to keep the layout simple.
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

### Verify the stored secret decrypts

The bundle ships a small spark command at `app/Commands/DecryptSecret.php`
(CI4 auto-discovers anything in `app/Commands/` — no config needed; create the
directory if it does not exist). Use it to confirm that `app.encryption.key`
in `.env` matches the key used to encrypt the row:

```bash
php spark api:decrypt 'PASTE_BASE64_FROM_secret_encrypted'
# -> Decrypted:
#    <your raw secret>
```

Note: stock CI4 has no `php spark shell` command — that's a third-party
package. Use `php spark api:decrypt` instead.

### Rotation

The bundle ships `app/Commands/RotateApiSecret.php` which automates the
generate → encrypt → UPDATE flow. Use it whenever you need to rotate a
secret OR when `api:decrypt` fails (typically because `encryption.key`
changed and the old `secret_encrypted` blob can no longer be decrypted).

```bash
# Generate a new 64-char hex secret, encrypt with current encryption.key,
# update api_clients.secret_encrypted + rotated_at, print plaintext ONCE.
php spark api:rotate <api_key>

# Or supply your own plaintext secret:
php spark api:rotate <api_key> 'my-chosen-secret'
```

The command prints the new plaintext secret a single time. Paste it into
Lovable as `CI4_SERVICE_SECRET` immediately — it is not recoverable later
(only the encrypted blob is stored). Then verify with:

```bash
php spark api:decrypt '<base64 from the new secret_encrypted>'
```

If `api:decrypt` was failing with "authentication failed", running
`api:rotate` will fix it: the new ciphertext is written with the *current*
`encryption.key`, so subsequent decrypts will succeed.

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
