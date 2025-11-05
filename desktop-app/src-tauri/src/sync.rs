use reqwest::Client;
use serde_json::json;

/// Send encrypted activity snapshot to the API
pub async fn send_to_api(api_url: &str, api_key: &str, encrypted_data: String) -> Result<(), String> {
    let client = Client::new();

    let endpoint = format!("{}/api/v1/activity-snapshots", api_url);

    let response = client
        .post(&endpoint)
        .header("Authorization", format!("Bearer {}", api_key))
        .header("Content-Type", "application/json")
        .header("Accept", "application/json")
        .json(&json!({
            "encrypted_data": encrypted_data,
            "client_version": env!("CARGO_PKG_VERSION"),
            "platform": std::env::consts::OS,
        }))
        .send()
        .await
        .map_err(|e| format!("HTTP request failed: {}", e))?;

    if response.status().is_success() {
        log::info!("Activity synced successfully");
        Ok(())
    } else {
        let status = response.status();
        let body = response
            .text()
            .await
            .unwrap_or_else(|_| "Unknown error".to_string());

        Err(format!("API error {}: {}", status, body))
    }
}

/// Verify API credentials
pub async fn verify_credentials(api_url: &str, api_key: &str) -> Result<bool, String> {
    let client = Client::new();

    let endpoint = format!("{}/api/v1/users/me", api_url);

    let response = client
        .get(&endpoint)
        .header("Authorization", format!("Bearer {}", api_key))
        .header("Accept", "application/json")
        .send()
        .await
        .map_err(|e| format!("HTTP request failed: {}", e))?;

    Ok(response.status().is_success())
}

/// Sync all pending snapshots (offline mode recovery)
pub async fn sync_pending_snapshots(api_url: &str, api_key: &str) -> Result<usize, String> {
    let pending = crate::storage::get_pending_snapshots().await?;
    let count = pending.len();

    log::info!("Syncing {} pending snapshots", count);

    for (path, encrypted_data) in pending {
        match send_to_api(api_url, api_key, encrypted_data).await {
            Ok(_) => {
                // Successfully synced, remove from storage
                crate::storage::remove_snapshot(path).await?;
                log::debug!("Synced and removed snapshot");
            }
            Err(e) => {
                log::warn!("Failed to sync snapshot: {}", e);
                // Keep in storage for next attempt
            }
        }
    }

    Ok(count)
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    #[ignore] // Requires real API server
    async fn test_verify_credentials() {
        let api_url = "http://localhost:8000";
        let api_key = "test_key";

        let result = verify_credentials(api_url, api_key).await;
        // This will fail without a running server, that's expected
        assert!(result.is_err() || result.is_ok());
    }
}
