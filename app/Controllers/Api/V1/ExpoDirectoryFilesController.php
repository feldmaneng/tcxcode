<?php
namespace App\Controllers\Api\V1;

use App\Libraries\ApiAuthContext;
use App\Libraries\ModuleAccess;
use App\Models\UserModuleModel;

/**
 * Exhibitor artwork file manager — browses / uploads image files that live on
 * the web server under /public_html/EXPOdirectory.
 *
 * Only admins and holders of the explicit `expo` module (event managers) may
 * call anything here. Coordinators can see the images on the exhibitor page
 * but cannot browse the directory or upload.
 *
 * Security posture (see README.expo-files.md for the server-side setup):
 *   - every path is realpath()-resolved and must stay inside the root
 *   - names are allow-listed to [A-Za-z0-9._-]
 *   - type is decided by image content (getimagesize), not by the extension
 *   - only PNG / JPEG are stored, always as 0644, never overwriting
 *   - no rename, no delete — nothing here can destroy an existing file
 */
class ExpoDirectoryFilesController extends BaseApiController
{
    private const MAX_BYTES     = 10 * 1024 * 1024; // 10 MB per file
    private const MAX_WIDTH     = 2000;             // down-scale wider images
    private const MAX_NAME_LEN  = 100;
    private const LOGO_DIR      = 'logos';

    /** Root of the exhibitor artwork tree, without a trailing slash. */
    private function root(): ?string
    {
        $configured = getenv('EXPO_DIRECTORY_PATH');
        $candidates = [];
        if (is_string($configured) && $configured !== '') $candidates[] = $configured;
        $candidates[] = FCPATH . 'EXPOdirectory';
        $candidates[] = rtrim(FCPATH, '/\\') . '/../public_html/EXPOdirectory';

        foreach ($candidates as $c) {
            $real = realpath($c);
            if ($real !== false && is_dir($real)) return rtrim($real, '/');
        }
        return null;
    }

    // ------------------------------------------------------------------ auth

    /** Admin or explicit `expo` module holder. Service calls are trusted. */
    private function denyUnlessPrivileged()
    {
        $userId = ApiAuthContext::actingUserId();
        if ($userId === null) return null;
        $codes = ModuleAccess::codesForUser($userId);
        if (in_array('admin', $codes, true)) return null;
        if ((new UserModuleModel())->userHasModule($userId, 'expo')) return null;
        return $this->jsonError(403, 'forbidden');
    }

    // ------------------------------------------------------------ path safety

    private function isSafeName(string $name): bool
    {
        if ($name === '' || strlen($name) > self::MAX_NAME_LEN) return false;
        if ($name[0] === '.') return false;
        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name);
    }

    /**
     * Resolve a client-supplied relative directory to an absolute path inside
     * the root. Returns null when the directory is unsafe or missing.
     */
    private function resolveDir(string $root, string $rel): ?string
    {
        $rel = trim(str_replace('\\', '/', $rel), '/');
        if ($rel === '') return $root;
        foreach (explode('/', $rel) as $seg) {
            if (!$this->isSafeName($seg)) return null;
        }
        $real = realpath($root . '/' . $rel);
        if ($real === false || !is_dir($real)) return null;
        $real = rtrim($real, '/');
        if ($real !== $root && strpos($real . '/', $root . '/') !== 0) return null;
        return $real;
    }

    private function relPath(string $root, string $abs): string
    {
        return ltrim(substr($abs, strlen($root)), '/');
    }

    private function isImageName(string $name): bool
    {
        return (bool) preg_match('/\.(png|jpe?g)$/i', $name);
    }

    // ----------------------------------------------------------------- routes

    /**
     * GET /api/v1/expo-directory/files?dir=logos
     * Lists sub-folders and PNG/JPG files in one directory.
     */
    public function index()
    {
        if ($deny = $this->denyUnlessPrivileged()) return $deny;
        $root = $this->root();
        if ($root === null) return $this->jsonError(500, 'expo_directory_missing');

        $rel = (string) ($this->request->getGet('dir') ?? '');
        $dir = $this->resolveDir($root, $rel);
        if ($dir === null) return $this->jsonError(422, 'invalid_directory');

        $dirs  = [];
        $files = [];
        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..' || $name[0] === '.') continue;
            $abs = $dir . '/' . $name;
            if (is_dir($abs)) {
                $dirs[] = ['name' => $name, 'path' => $this->relPath($root, $abs)];
                continue;
            }
            if (!is_file($abs) || !$this->isImageName($name)) continue;
            $size = @filesize($abs) ?: 0;
            $dim  = @getimagesize($abs);
            $files[] = [
                'name'   => $name,
                'path'   => $this->relPath($root, $abs),
                'size'   => (int) $size,
                'width'  => $dim ? (int) $dim[0] : null,
                'height' => $dim ? (int) $dim[1] : null,
            ];
        }
        usort($dirs, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $this->response->setJSON([
            'dir'     => $this->relPath($root, $dir),
            'parent'  => $dir === $root ? null : $this->relPath($root, dirname($dir)),
            'dirs'    => $dirs,
            'files'   => $files,
        ]);
    }

    /**
     * POST /api/v1/expo-directory/files/mkdir
     * Body: { dir?: string, name: string }
     */
    public function mkdir()
    {
        if ($deny = $this->denyUnlessPrivileged()) return $deny;
        $root = $this->root();
        if ($root === null) return $this->jsonError(500, 'expo_directory_missing');

        $payload = (array) $this->request->getJSON(true);
        $parent  = $this->resolveDir($root, (string) ($payload['dir'] ?? ''));
        if ($parent === null) return $this->jsonError(422, 'invalid_directory');

        $name = trim((string) ($payload['name'] ?? ''));
        if (!$this->isSafeName($name)) return $this->jsonError(422, 'invalid_name');
        // The logos folder stays flat.
        if ($this->relPath($root, $parent) === self::LOGO_DIR) return $this->jsonError(422, 'logos_is_flat');

        $target = $parent . '/' . $name;
        if (file_exists($target)) return $this->jsonError(409, 'already_exists');

        // Most failures here are filesystem permissions: the PHP user does not
        // own the artwork tree. Say so instead of a bare mkdir_failed.
        if (!is_writable($parent)) {
            return $this->jsonError(500, 'directory_not_writable', [
                'dir'   => $this->relPath($root, $parent),
                'owner' => function_exists('posix_getpwuid') && ($o = @fileowner($parent)) !== false
                    ? (posix_getpwuid($o)['name'] ?? (string) $o) : null,
                'perms' => substr(sprintf('%o', @fileperms($parent) ?: 0), -4),
                'hint'  => 'Grant the PHP/web user write access to this folder (see README.expo-files.md).',
            ]);
        }

        $old = umask(0022);
        $ok  = @mkdir($target, 0755);
        $err = $ok ? null : (error_get_last()['message'] ?? null);
        umask($old);
        if (!$ok) {
            return $this->jsonError(500, 'directory_not_writable', [
                'dir'    => $this->relPath($root, $parent),
                'reason' => $err,
                'hint'   => 'Grant the PHP/web user write access to this folder (see README.expo-files.md).',
            ]);
        }
        @chmod($target, 0755);

        return $this->response->setStatusCode(201)->setJSON([
            'data' => ['name' => $name, 'path' => $this->relPath($root, $target)],
        ]);
    }

    /**
     * POST /api/v1/expo-directory/files/upload
     * Body: { dir?: string, files: [{ name, content_base64 }] }
     *
     * Base64 keeps the request inside the HMAC-signed JSON envelope used by
     * every other endpoint. Content is validated as a real PNG/JPEG before
     * anything touches disk.
     */
    public function upload()
    {
        if ($deny = $this->denyUnlessPrivileged()) return $deny;
        $root = $this->root();
        if ($root === null) return $this->jsonError(500, 'expo_directory_missing');

        $payload = (array) $this->request->getJSON(true);
        $dir     = $this->resolveDir($root, (string) ($payload['dir'] ?? ''));
        if ($dir === null) return $this->jsonError(422, 'invalid_directory');
        if (!is_writable($dir)) return $this->jsonError(500, 'directory_not_writable');

        $items = $payload['files'] ?? [];
        if (!is_array($items) || $items === []) return $this->jsonError(422, 'no_files');
        if (count($items) > 25) return $this->jsonError(422, 'too_many_files');

        $saved  = [];
        $errors = [];
        $old    = umask(0022);
        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            $b64  = (string) ($item['content_base64'] ?? '');
            $res  = $this->storeOne($dir, $name, $b64);
            if (isset($res['error'])) {
                $errors[$name !== '' ? $name : '(unnamed)'] = $res['error'];
                continue;
            }
            $saved[] = [
                'name'   => $res['name'],
                'path'   => $this->relPath($root, $res['abs']),
                'size'   => (int) (@filesize($res['abs']) ?: 0),
                'width'  => $res['width'],
                'height' => $res['height'],
            ];
        }
        umask($old);

        if ($saved === []) return $this->jsonError(422, 'upload_failed', $errors);
        return $this->response->setStatusCode(201)->setJSON([
            'data'   => $saved,
            'errors' => (object) $errors,
        ]);
    }

    // --------------------------------------------------------------- storage

    /** @return array{error?:string,name?:string,abs?:string,width?:?int,height?:?int} */
    private function storeOne(string $dir, string $name, string $b64): array
    {
        if ($name === '') return ['error' => 'missing_name'];

        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        // Single extension only; strip anything else out of the base name.
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base ?? '');
        $base = trim((string) $base, '-');
        if ($base === '') $base = 'image';
        if (strlen($base) > 60) $base = substr($base, 0, 60);
        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) return ['error' => 'unsupported_extension'];

        $raw = base64_decode($b64, true);
        if ($raw === false || $raw === '') return ['error' => 'invalid_content'];
        if (strlen($raw) > self::MAX_BYTES) return ['error' => 'too_large'];

        // Decide the type by content, never by what the client claimed.
        $info = @getimagesizefromstring($raw);
        if (!$info) return ['error' => 'not_an_image'];
        $type = (int) $info[2];
        if ($type === IMAGETYPE_PNG) {
            $ext = 'png';
        } elseif ($type === IMAGETYPE_JPEG) {
            $ext = $ext === 'jpeg' ? 'jpeg' : 'jpg';
        } else {
            return ['error' => 'unsupported_image_type'];
        }

        $width  = (int) $info[0];
        $height = (int) $info[1];

        // Down-scale oversized artwork when GD is available.
        if ($width > self::MAX_WIDTH && function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring($raw);
            if ($src) {
                $newW = self::MAX_WIDTH;
                $newW = (int) $newW;
                $newH = (int) round($height * ($newW / $width));
                $dst  = imagecreatetruecolor($newW, $newH);
                if ($type === IMAGETYPE_PNG) {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
                ob_start();
                if ($type === IMAGETYPE_PNG) imagepng($dst); else imagejpeg($dst, null, 88);
                $resized = (string) ob_get_clean();
                imagedestroy($dst);
                imagedestroy($src);
                if ($resized !== '') {
                    $raw    = $resized;
                    $width  = $newW;
                    $height = $newH;
                }
            }
        }

        // Never overwrite: append -1, -2, … until the name is free.
        $final = $base . '.' . $ext;
        $i     = 0;
        while (file_exists($dir . '/' . $final)) {
            $i++;
            if ($i > 500) return ['error' => 'name_collision'];
            $final = $base . '-' . $i . '.' . $ext;
        }

        $abs = $dir . '/' . $final;
        if (@file_put_contents($abs, $raw, LOCK_EX) === false) return ['error' => 'write_failed'];
        @chmod($abs, 0644);

        return ['name' => $final, 'abs' => $abs, 'width' => $width, 'height' => $height];
    }
}
