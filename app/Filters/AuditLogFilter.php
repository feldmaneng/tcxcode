<?php
namespace App\Filters;

use App\Libraries\ApiAuthContext;
use App\Models\AuditLogModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuditLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null) {}

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        try {
            $auth = ApiAuthContext::get();
            $body = (string) $request->getBody();
            (new AuditLogModel())->insert([
                'client_id'    => $auth['client_id']    ?? null,
                'user_id'      => $auth['user_id']      ?? null,
                'method'       => $request->getMethod(),
                'path'         => '/' . ltrim($request->getUri()->getPath(), '/'),
                'payload_hash' => $body !== '' ? hash('sha256', $body) : null,
                'ip'           => $request->getIPAddress(),
                'status'       => $response->getStatusCode(),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'audit_log failed: ' . $e->getMessage());
        }
    }
}
