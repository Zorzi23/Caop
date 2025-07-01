<?php

declare(strict_types=1);

namespace CaOp\Security;

use ObjectFlow\Trait\InstanceTrait;

/**
 * Provides symmetric masking and unmasking of strings using AES-256-CBC.
 */
final class SymmetricMasker
{
    use InstanceTrait;

    /**
     * @var string $sKey Binary encryption key derived from the input key and salt.
     */
    private string $sKey;

    /**
     * @var string $sCipher The cipher method used for encryption/decryption.
     */
    private string $sCipher = 'aes-256-cbc';

    /**
     * Constructor.
     *
     * @param string $sRawKey The raw input key (will be hashed with salt to 32 bytes).
     */
    public function __construct(?string $sRawKey = 'CaOp')
    {
        $sSalt = getenv('CAOP_MASKING_SALT') ?: 'default_salt';

        if ($sSalt === 'default_salt') {
            error_log('SymmetricMasker: CAOP_MASKING_SALT not set, using default salt');
        }

        $this->sKey = hash('sha256', $sRawKey . $sSalt, true);
    }

    /**
     * Masks a plaintext string into a base64-encoded encrypted string.
     *
     * @param string $sPlainText The string to mask.
     * @return string The masked (encrypted and encoded) string.
     */
    public function mask(string $sPlainText): string
    {
        $sIv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->sCipher));
        $sEncrypted = openssl_encrypt($sPlainText, $this->sCipher, $this->sKey, OPENSSL_RAW_DATA, $sIv);

        return base64_encode($sIv . $sEncrypted);
    }

    /**
     * Unmasks a previously masked string.
     *
     * @param string $sMaskedText The masked (encrypted and encoded) string.
     * @return string The original unmasked string.
     */
    public function unmask(string $sMaskedText): string
    {
        $xData = base64_decode($sMaskedText);
        $iIvLength = openssl_cipher_iv_length($this->sCipher);
        $sIv = substr($xData, 0, $iIvLength);
        $sEncrypted = substr($xData, $iIvLength);

        return openssl_decrypt($sEncrypted, $this->sCipher, $this->sKey, OPENSSL_RAW_DATA, $sIv);
    }
}
