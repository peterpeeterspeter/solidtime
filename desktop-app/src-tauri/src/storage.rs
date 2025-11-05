use crate::activity::ActivitySnapshot;
use std::fs::{create_dir_all, File};
use std::io::{BufWriter, Write};
use std::path::PathBuf;

/// Get the storage directory for activity snapshots
pub fn get_storage_dir() -> Result<PathBuf, String> {
    let app_dir = dirs::data_local_dir()
        .ok_or_else(|| "Could not determine app data directory".to_string())?
        .join("Solidtime");

    create_dir_all(&app_dir).map_err(|e| format!("Failed to create storage directory: {}", e))?;

    Ok(app_dir)
}

/// Store encrypted activity snapshot locally (for offline mode)
pub async fn store_snapshot(encrypted: &str) -> Result<(), String> {
    let storage_dir = get_storage_dir()?;
    let filename = format!("activity_{}.json", chrono::Utc::now().timestamp());
    let filepath = storage_dir.join(filename);

    let file =
        File::create(&filepath).map_err(|e| format!("Failed to create storage file: {}", e))?;

    let mut writer = BufWriter::new(file);
    writer
        .write_all(encrypted.as_bytes())
        .map_err(|e| format!("Failed to write to storage: {}", e))?;

    writer
        .flush()
        .map_err(|e| format!("Failed to flush storage: {}", e))?;

    log::debug!("Stored snapshot to {:?}", filepath);
    Ok(())
}

/// Get all pending snapshots for sync (offline mode recovery)
pub async fn get_pending_snapshots() -> Result<Vec<(PathBuf, String)>, String> {
    let storage_dir = get_storage_dir()?;

    let mut snapshots = Vec::new();

    let entries =
        std::fs::read_dir(&storage_dir).map_err(|e| format!("Failed to read directory: {}", e))?;

    for entry in entries {
        let entry = entry.map_err(|e| format!("Failed to read entry: {}", e))?;
        let path = entry.path();

        if path.is_file() && path.extension().and_then(|s| s.to_str()) == Some("json") {
            let content = std::fs::read_to_string(&path)
                .map_err(|e| format!("Failed to read file: {}", e))?;

            snapshots.push((path, content));
        }
    }

    Ok(snapshots)
}

/// Remove synced snapshot from storage
pub async fn remove_snapshot(path: PathBuf) -> Result<(), String> {
    std::fs::remove_file(&path).map_err(|e| format!("Failed to remove file: {}", e))?;
    log::debug!("Removed snapshot {:?}", path);
    Ok(())
}

/// Clear all stored snapshots (use with caution)
#[allow(dead_code)]
pub async fn clear_all_snapshots() -> Result<usize, String> {
    let snapshots = get_pending_snapshots().await?;
    let count = snapshots.len();

    for (path, _) in snapshots {
        remove_snapshot(path).await?;
    }

    Ok(count)
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    async fn test_storage_operations() {
        let encrypted = "test_encrypted_data";

        // Store
        store_snapshot(encrypted).await.unwrap();

        // Retrieve
        let pending = get_pending_snapshots().await.unwrap();
        assert!(pending.len() > 0);

        // Clean up
        for (path, _) in pending {
            remove_snapshot(path).await.unwrap();
        }
    }
}
