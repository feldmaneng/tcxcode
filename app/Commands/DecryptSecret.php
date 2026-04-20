<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Decrypts a base64-encoded payload produced by service('encrypter')->encrypt().
 *
 * Usage:
 *   php spark api:decrypt 'PASTE_BASE64_HERE'
 *
 * Useful for verifying that a row in api_clients.secret_encrypted decrypts
 * back to the raw secret you stored in CI4_SERVICE_SECRET on the Lovable side.
 */
class DecryptSecret extends BaseCommand
{
    protected $group       = 'API';
    protected $name        = 'api:decrypt';
    protected $description = 'Decrypt a base64-encoded encrypter payload (e.g. api_clients.secret_encrypted).';
    protected $usage       = 'api:decrypt <base64>';
    protected $arguments   = ['base64' => 'Base64-encoded ciphertext from CI4 Encrypter'];

    public function run(array $params)
    {
        $b64 = $params[0] ?? CLI::prompt('Base64 ciphertext');

        if (! is_string($b64) || $b64 === '') {
            CLI::error('No ciphertext provided.');
            return;
        }

        $raw = base64_decode($b64, true);
        if ($raw === false) {
            CLI::error('Input is not valid base64.');
            return;
        }

        try {
            $plain = service('encrypter')->decrypt($raw);
            CLI::write('Decrypted:', 'yellow');
            CLI::write($plain, 'green');
        } catch (\Throwable $e) {
            CLI::error('Decrypt failed: ' . $e->getMessage());
            CLI::write('Check that app.encryption.key in .env matches the key used to encrypt.', 'light_gray');
        }
    }
}
