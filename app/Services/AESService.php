// app/Services/AESService.php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class AESService
{
    public function encrypt($data, $key)
    {
        // We will use openssl for AES encryption
        $iv = substr($key, 0, 16);  // Using the first 16 bytes of the key as the IV
        $algo = 'aes-128-cbc'; // AES algorithm with 128 bit key, CBC mode

        // Encrypt the data
        $cipherText = openssl_encrypt(
            $data,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Encode the encrypted data to base64
        return base64_encode($cipherText);
    }

    public function decrypt($cipherText, $key)
    {
        // We will use openssl for AES decryption
        $iv = substr($key, 0, 16);  // Using the first 16 bytes of the key as the IV
        $algo = 'aes-128-cbc'; // AES algorithm with 128 bit key, CBC mode

        // Decode the base64 encoded ciphertext
        $cipherText = base64_decode($cipherText);

        // Decrypt the data
        return openssl_decrypt(
            $cipherText,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
