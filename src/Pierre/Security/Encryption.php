<?php
/**
 * Pierre's encryption utility - he protects sensitive data! 🪨
 * 
 * This class handles encryption and decryption of sensitive data
 * such as Slack webhook URLs.
 * 
 * Uses defuse/php-encryption for secure encryption (recommended by WordPress).
 * Maintains backward compatibility with legacy OpenSSL-encrypted data.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Security;

/**
 * Encryption class - Pierre's data protection! 🪨
 * 
 * @since 1.0.0
 */
class Encryption {
	/**
	 * Encryption method (AES-256-CBC) - legacy only
	 * 
	 * @deprecated 1.0.0 Use defuse/php-encryption instead
	 */
	private const CIPHER = 'AES-256-CBC';

	/**
	 * Get encryption key from WordPress salts
	 * 
	 * @since 1.0.0
	 * @return string Encryption key (for legacy compatibility)
	 */
	private static function get_key(): string {
		// Use WordPress salt for encryption key
		$salt = wp_salt( 'auth' );
		// Ensure key is exactly 32 bytes for AES-256
		return substr( hash( 'sha256', $salt . 'pierre_webhook_encryption', true ), 0, 32 );
	}

	/**
	 * Get or create defuse/php-encryption key
	 * 
	 * Generates a key once and stores it in WordPress options for consistency.
	 * The key is cached using WordPress object cache for performance.
	 * 
	 * @since 1.0.0
	 * @return \Defuse\Crypto\Key Encryption key object
	 * @throws \Exception If key cannot be created or loaded
	 */
	private static function get_defuse_key(): \Defuse\Crypto\Key {
		$cache_key = 'pierre_defuse_encryption_key';
		$cache_group = 'pierre_encryption';
		
		// Try to get from cache first
		$cached_key = wp_cache_get( $cache_key, $cache_group );
		if ( $cached_key !== false && $cached_key instanceof \Defuse\Crypto\Key ) {
			return $cached_key;
		}
		
		// Try to load existing key from WordPress options
		$stored_key = get_option( 'pierre_encryption_key', null );
		
		if ( $stored_key !== null ) {
			try {
				$cached_key = \Defuse\Crypto\Key::loadFromAsciiSafeString( $stored_key );
				// Cache the key (no expiration for encryption keys)
				wp_cache_set( $cache_key, $cached_key, $cache_group, 0 );
				return $cached_key;
			} catch ( \Exception $e ) {
				// Invalid key format, generate new one
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Pierre Encryption: Invalid stored key, generating new one: ' . $e->getMessage() );
				}
			}
		}
		
		// Generate new key and store it
		try {
			$new_key = \Defuse\Crypto\Key::createNewRandomKey();
			$key_string = $new_key->saveToAsciiSafeString();
			update_option( 'pierre_encryption_key', $key_string, false );
			// Invalidate old cache and set new key (no expiration for encryption keys)
			wp_cache_delete( $cache_key, $cache_group );
			wp_cache_set( $cache_key, $new_key, $cache_group, 0 );
			return $new_key;
		} catch ( \Exception $e ) {
			throw new \Exception( 'Unable to create encryption key: ' . $e->getMessage() );
		}
	}

	/**
	 * Encrypt sensitive data
	 * 
	 * Uses defuse/php-encryption for secure encryption (recommended by WordPress).
	 * Falls back to legacy OpenSSL method if defuse is unavailable.
	 * 
	 * @since 1.0.0
	 * @param string $data Data to encrypt
	 * @return string|\WP_Error Encrypted data (base64 encoded) or WP_Error on failure
	 */
	public static function encrypt( string $data ): string|\WP_Error {
		if ( empty( $data ) ) {
			return new \WP_Error(
				'pierre_encrypt_empty_data',
				__( 'Cannot encrypt empty data.', 'wp-pierre' )
			);
		}

		// Try defuse/php-encryption first (recommended by WordPress)
		if ( class_exists( '\Defuse\Crypto\Crypto' ) ) {
			try {
				$key = self::get_defuse_key();
				$encrypted = \Defuse\Crypto\Crypto::encrypt( $data, $key );
				// Prefix with 'defuse:' to identify defuse-encrypted data
				return 'defuse:' . $encrypted;
			} catch ( \Defuse\Crypto\Exception\CryptoException $e ) {
				// If defuse encryption fails, fall back to legacy method
				// Log error for debugging (optional)
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Pierre Encryption: defuse encryption failed: ' . $e->getMessage() );
				}
			} catch ( \Exception $e ) {
				// Key generation or other error
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Pierre Encryption: key error: ' . $e->getMessage() );
				}
			}
		}

		// Fallback to legacy OpenSSL method
		$legacy_result = self::encrypt_legacy( $data );
		if ( $legacy_result === false ) {
			return new \WP_Error(
				'pierre_encrypt_legacy_failed',
				__( 'Legacy encryption method failed.', 'wp-pierre' )
			);
		}
		return $legacy_result;
	}

	/**
	 * Legacy encryption method using OpenSSL
	 * 
	 * @since 1.0.0
	 * @param string $data Data to encrypt
	 * @return string|false Encrypted data (base64 encoded) or false on failure
	 * @internal This method returns false on failure (not WP_Error) for internal use
	 */
	private static function encrypt_legacy( string $data ): string|false {
		// Check if OpenSSL is available
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			// Fallback to simple obfuscation if OpenSSL is not available
			return self::simple_obfuscate( $data, true );
		}

		$key = self::get_key();
		$iv  = self::generate_iv_legacy();

		if ( $iv === false ) {
			// If we can't generate a secure IV, fallback to obfuscation
			return self::simple_obfuscate( $data, true );
		}

		$encrypted = openssl_encrypt( $data, self::CIPHER, $key, 0, $iv );

		if ( $encrypted === false ) {
			return false;
		}

		// Prepend IV to encrypted data and encode
		// Format: base64(IV (16 bytes) + encrypted_data)
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Generate a random initialization vector (legacy method)
	 * 
	 * @since 1.0.0
	 * @return string|false IV (16 bytes for AES-256-CBC) or false on failure
	 */
	private static function generate_iv_legacy(): string|false {
		// Generate a cryptographically secure random IV
		// For AES-256-CBC, IV must be exactly 16 bytes
		if ( function_exists( 'random_bytes' ) ) {
			return random_bytes( 16 );
		} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$iv = openssl_random_pseudo_bytes( 16, $strong );
			if ( $strong === true ) {
				return $iv;
			}
		}
		// Fallback: should not happen on modern PHP, but handle gracefully
		return false;
	}

	/**
	 * Decrypt sensitive data
	 * 
	 * Supports both defuse/php-encryption and legacy OpenSSL formats.
	 * Automatically detects the encryption method used.
	 * 
	 * @since 1.0.0
	 * @param string $encrypted_data Encrypted data (base64 encoded)
	 * @return string|false Decrypted data or false on failure
	 */
	public static function decrypt( string $encrypted_data ): string|false {
		if ( empty( $encrypted_data ) ) {
			return false;
		}

		// Check if this is defuse-encrypted data
		if ( strpos( $encrypted_data, 'defuse:' ) === 0 ) {
			// Try defuse/php-encryption decryption
			if ( class_exists( '\Defuse\Crypto\Crypto' ) ) {
				try {
					$key = self::get_defuse_key();
					$encrypted = substr( $encrypted_data, 7 ); // Remove 'defuse:' prefix
					return \Defuse\Crypto\Crypto::decrypt( $encrypted, $key );
				} catch ( \Defuse\Crypto\Exception\CryptoException $e ) {
					// Decryption failed
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Pierre Encryption: defuse decryption failed: ' . $e->getMessage() );
					}
					return false;
				} catch ( \Exception $e ) {
					// Key or other error
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Pierre Encryption: key error: ' . $e->getMessage() );
					}
					return false;
				}
			}
			// If defuse is not available, cannot decrypt defuse-encrypted data
			return false;
		}

		// Fallback to legacy OpenSSL method
		return self::decrypt_legacy( $encrypted_data );
	}

	/**
	 * Legacy decryption method using OpenSSL
	 * 
	 * @since 1.0.0
	 * @param string $encrypted_data Encrypted data (base64 encoded)
	 * @return string|false Decrypted data or false on failure
	 */
	private static function decrypt_legacy( string $encrypted_data ): string|false {
		// Check if OpenSSL is available
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			// Fallback to simple deobfuscation if OpenSSL is not available
			return self::simple_obfuscate( $encrypted_data, false );
		}

		$decoded = base64_decode( $encrypted_data, true );

		if ( $decoded === false ) {
			// Try fallback for old format (simple obfuscation)
			return self::simple_obfuscate( $encrypted_data, false );
		}

		$key = self::get_key();

		// Extract IV from beginning of encrypted data
		// Format: IV (16 bytes) + encrypted_data
		if ( strlen( $decoded ) <= 16 ) {
			// Invalid format: data too short to contain IV + encrypted data
			// Try fallback for old format (simple obfuscation)
			return self::simple_obfuscate( $encrypted_data, false );
		}

		$extracted_iv = substr( $decoded, 0, 16 );
		$encrypted     = substr( $decoded, 16 );

		$decrypted = openssl_decrypt( $encrypted, self::CIPHER, $key, 0, $extracted_iv );

		if ( $decrypted === false ) {
			// Try fallback for old format
			return self::simple_obfuscate( $encrypted_data, false );
		}

		return $decrypted;
	}

	/**
	 * Simple obfuscation fallback when OpenSSL is not available
	 * 
	 * @since 1.0.0
	 * @param string $data Data to obfuscate/deobfuscate
	 * @param bool   $encrypt True to encrypt, false to decrypt
	 * @return string Obfuscated/deobfuscated data
	 */
	private static function simple_obfuscate( string $data, bool $encrypt ): string {
		$key = self::get_key();
		$result = '';

		for ( $i = 0; $i < strlen( $data ); $i++ ) {
			$char = $data[ $i ];
			$key_char = $key[ $i % strlen( $key ) ];
			$result .= chr( ord( $char ) ^ ord( $key_char ) );
		}

		if ( $encrypt ) {
			return base64_encode( $result );
		} else {
			$decoded = base64_decode( $data, true );
			if ( $decoded === false ) {
				return $data; // Return original if not base64
			}
			$result = '';
			for ( $i = 0; $i < strlen( $decoded ); $i++ ) {
				$char = $decoded[ $i ];
				$key_char = $key[ $i % strlen( $key ) ];
				$result .= chr( ord( $char ) ^ ord( $key_char ) );
			}
			return $result;
		}
	}

	/**
	 * Check if data appears to be encrypted
	 * 
	 * @since 1.0.0
	 * @param string $data Data to check
	 * @return bool True if data appears encrypted
	 */
	public static function is_encrypted( string $data ): bool {
		if ( empty( $data ) ) {
			return false;
		}

		// Check for defuse-encrypted data (prefixed with 'defuse:')
		if ( strpos( $data, 'defuse:' ) === 0 ) {
			return true;
		}

		// Check for legacy OpenSSL-encrypted data (base64 encoded)
		if ( ! preg_match( '/^[A-Za-z0-9+\/]+=*$/', $data ) ) {
			return false;
		}

		$decoded = base64_decode( $data, true );
		return $decoded !== false && strlen( $decoded ) > 16;
	}
}

