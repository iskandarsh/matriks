<?php
namespace App\Helpers;

class EncryptHelper
{
    public static function encryptName(string $name, string $uuid): string
    {
        $key = substr(hash('sha256', $uuid), 0, 32);
        $iv  = substr(hash('sha256', $uuid . 'iv'), 0, 16);
        return openssl_encrypt($name, 'AES-256-CBC', $key, 0, $iv);
    }

    public static function decryptName(string $encrypted, string $uuid): string
    {
        $key = substr(hash('sha256', $uuid), 0, 32);
        $iv  = substr(hash('sha256', $uuid . 'iv'), 0, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}
?>