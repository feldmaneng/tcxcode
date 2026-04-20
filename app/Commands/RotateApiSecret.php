<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * php spark api:rotate <api_key> [<new_secret>]
 *
 * - Looks up the row in api_clients (control DB) by api_key.
 * - If no plaintext secret is given, generates a 64-char hex secret.
 * - Encrypts it with the current encryption.key and UPDATEs secret_encrypted + rotated_at.
 * - Prints the plaintext ONCE so you can paste it into Lovable as CI4_SERVICE_SECRET.
 *
 * Use this whenever decryption fails (key changed) or you want to rotate the secret.
 */
class RotateApiSecret extends BaseCommand
{
    protected $group       = 'API';
    protected $name        = 'api:rotate';
    protected $description = 'Rotate an api_clients secret: encrypt with current key and update the row.';
    protected $usage       = 'api:rotate <api_key> [<new_secret>]';
    protected $arguments   = [
        'api_key'    => 'The api_clients.api_key value identifying the client row.',
        'new_secret' => '(optional) Plaintext secret. If omitted, a 64-char hex secret is generated.',
    ];

    public function run(array $params)
    {
        $apiKey = $params[0] ?? CLI::prompt('api_key');
        if ($apiKey === '') {
            CLI::error('api_key is required.');
            return;
        }

        $plain = $params[1] ?? bin2hex(random_bytes(32));

        try {
            $encrypter = service('encrypter');
            $cipher    = base64_encode($encrypter->encrypt($plain));
        } catch (\Throwable $e) {
            CLI::error('Encrypt failed: ' . $e->getMessage());
            CLI::write('Check that encryption.key in .env is set and valid (php spark key:generate).');
            return;
        }

        $db = Database::connect('control');
        $row = $db->table('api_clients')->where('api_key', $apiKey)->get()->getRowArray();
        if (! $row) {
            CLI::error("No api_clients row found with api_key='{$apiKey}'.");
            return;
        }

        $db->table('api_clients')
            ->where('id', $row['id'])
            ->update([
                'secret_encrypted' => $cipher,
                'rotated_at'       => date('Y-m-d H:i:s'),
            ]);

        CLI::newLine();
        CLI::write('Secret rotated successfully.', 'green');
        CLI::write("  client id    : {$row['id']}");
        CLI::write("  api_key      : {$apiKey}");
        CLI::newLine();
        CLI::write('Plaintext secret (shown ONCE — paste into Lovable as CI4_SERVICE_SECRET):', 'yellow');
        CLI::write($plain, 'white', 'blue');
        CLI::newLine();
        CLI::write('Verify round-trip with: php spark api:decrypt ' . escapeshellarg($cipher), 'light_gray');
    }
}
