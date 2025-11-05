use aes_gcm::{
    aead::{Aead, KeyInit},
    Aes256Gcm, Nonce,
};
use base64::{engine::general_purpose, Engine as _};
use sha2::{Digest, Sha256};

use crate::activity::ActivitySnapshot;

/// Encrypt activity snapshot using AES-256-GCM
pub fn encrypt_activity(snapshot: &ActivitySnapshot, key: &str) -> Result<String, String> {
    // Serialize snapshot to JSON
    let plaintext = serde_json::to_string(snapshot).map_err(|e| e.to_string())?;

    // Derive encryption key from API key
    let encryption_key = derive_key(key);

    // Create cipher
    let cipher = Aes256Gcm::new(&encryption_key.into());

    // Generate nonce (12 bytes for AES-GCM)
    let nonce_bytes = generate_nonce();
    let nonce = Nonce::from_slice(&nonce_bytes);

    // Encrypt
    let ciphertext = cipher
        .encrypt(nonce, plaintext.as_bytes())
        .map_err(|e| format!("Encryption failed: {}", e))?;

    // Combine nonce + ciphertext and encode as base64
    let mut result = nonce_bytes.to_vec();
    result.extend_from_slice(&ciphertext);

    Ok(general_purpose::STANDARD.encode(&result))
}

/// Decrypt activity snapshot (for testing/debugging)
#[allow(dead_code)]
pub fn decrypt_activity(encrypted: &str, key: &str) -> Result<ActivitySnapshot, String> {
    // Decode base64
    let data = general_purpose::STANDARD
        .decode(encrypted)
        .map_err(|e| format!("Base64 decode failed: {}", e))?;

    if data.len() < 12 {
        return Err("Invalid encrypted data: too short".to_string());
    }

    // Split nonce and ciphertext
    let (nonce_bytes, ciphertext) = data.split_at(12);
    let nonce = Nonce::from_slice(nonce_bytes);

    // Derive encryption key
    let encryption_key = derive_key(key);

    // Create cipher
    let cipher = Aes256Gcm::new(&encryption_key.into());

    // Decrypt
    let plaintext = cipher
        .decrypt(nonce, ciphertext)
        .map_err(|e| format!("Decryption failed: {}", e))?;

    // Deserialize
    let snapshot: ActivitySnapshot =
        serde_json::from_slice(&plaintext).map_err(|e| format!("JSON parse failed: {}", e))?;

    Ok(snapshot)
}

/// Derive 256-bit encryption key from API key using SHA-256
fn derive_key(api_key: &str) -> [u8; 32] {
    let mut hasher = Sha256::new();
    hasher.update(api_key.as_bytes());
    hasher.update(b"solidtime-desktop-encryption-v1");
    let result = hasher.finalize();

    let mut key = [0u8; 32];
    key.copy_from_slice(&result);
    key
}

/// Generate a random nonce for AES-GCM
fn generate_nonce() -> [u8; 12] {
    use std::time::SystemTime;

    let mut nonce = [0u8; 12];

    // Use timestamp + counter for nonce generation
    // In production, should use proper random number generator
    let timestamp = SystemTime::now()
        .duration_since(SystemTime::UNIX_EPOCH)
        .unwrap()
        .as_nanos();

    nonce[0..8].copy_from_slice(&timestamp.to_le_bytes()[0..8]);

    // Add some pseudo-randomness
    let random_part = ((timestamp >> 32) ^ (timestamp & 0xFFFFFFFF)) as u32;
    nonce[8..12].copy_from_slice(&random_part.to_le_bytes());

    nonce
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_encrypt_decrypt() {
        let snapshot = ActivitySnapshot {
            app_name: "Test App".to_string(),
            window_title: "Test Window".to_string(),
            active_seconds: 10,
            idle_seconds: 0,
            keyboard_count: 5,
            mouse_count: 10,
            timestamp: 1234567890,
        };

        let key = "test-api-key-12345";

        let encrypted = encrypt_activity(&snapshot, key).unwrap();
        let decrypted = decrypt_activity(&encrypted, key).unwrap();

        assert_eq!(snapshot.app_name, decrypted.app_name);
        assert_eq!(snapshot.window_title, decrypted.window_title);
        assert_eq!(snapshot.active_seconds, decrypted.active_seconds);
    }

    #[test]
    fn test_encryption_produces_different_output() {
        let snapshot = ActivitySnapshot {
            app_name: "Test".to_string(),
            window_title: "Test".to_string(),
            active_seconds: 10,
            idle_seconds: 0,
            keyboard_count: 0,
            mouse_count: 0,
            timestamp: 1234567890,
        };

        let key = "test-key";

        let encrypted1 = encrypt_activity(&snapshot, key).unwrap();
        let encrypted2 = encrypt_activity(&snapshot, key).unwrap();

        // Should be different due to different nonces
        assert_ne!(encrypted1, encrypted2);
    }
}
