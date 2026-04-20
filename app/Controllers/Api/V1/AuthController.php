<?php
namespace App\Controllers\Api\V1;

use App\Libraries\JwtService;
use App\Models\RefreshTokenModel;

class AuthController extends BaseApiController
{
    /**
     * POST /api/v1/auth/login  { username, password }
     * Replace the verifyUserCredentials() body with your real user lookup.
     */
    public function login()
    {
        $rules = [
            'username' => 'required|string|max_length[100]',
            'password' => 'required|string|min_length[1]|max_length[200]',
        ];
        if (!$this->validate($rules)) return $this->jsonError(422, 'validation_failed', $this->validator->getErrors());

        $username = $this->request->getJsonVar('username') ?? $this->request->getPost('username');
        $password = $this->request->getJsonVar('password') ?? $this->request->getPost('password');

        $user = $this->verifyUserCredentials($username, $password);
        if (!$user) return $this->jsonError(401, 'invalid_credentials');

        return $this->response->setJSON($this->issueTokenPair($user));
    }

    public function refresh()
    {
        $rt = $this->request->getJsonVar('refresh_token');
        if (!$rt) return $this->jsonError(422, 'refresh_token_required');

        $hash  = hash('sha256', $rt);
        $model = new RefreshTokenModel();
        $row   = $model->where('token_hash', $hash)->first();
        if (!$row || $row['revoked_at'] !== null || strtotime($row['expires_at']) < time()) {
            return $this->jsonError(401, 'refresh_invalid');
        }
        // Rotate
        $model->update($row['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
        return $this->response->setJSON($this->issueTokenPair(['id' => $row['user_id']]));
    }

    private function issueTokenPair(array $user): array
    {
        $accessTtl  = (int) env('app.JWT_TTL_SECONDS', 900);
        $refreshTtl = (int) env('app.REFRESH_TTL_SECONDS', 604800);
        $access     = JwtService::issue(['sub' => (string) $user['id']], $accessTtl);
        $refresh    = bin2hex(random_bytes(32));
        (new RefreshTokenModel())->insert([
            'user_id'    => (string) $user['id'],
            'token_hash' => hash('sha256', $refresh),
            'expires_at' => date('Y-m-d H:i:s', time() + $refreshTtl),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['access_token' => $access, 'refresh_token' => $refresh, 'expires_in' => $accessTtl, 'token_type' => 'Bearer'];
    }

    /**
     * REPLACE THIS with your real user lookup against your existing users table.
     * Must return ['id' => <stable id>, ...] on success or null on failure.
     */
    private function verifyUserCredentials(string $username, string $password): ?array
    {
        // Example placeholder — wire to your actual auth table:
        // $u = (new \App\Models\UserModel())->where('username', $username)->first();
        // if (!$u || !password_verify($password, $u['password_hash'])) return null;
        // return $u;
        return null;
    }
}
