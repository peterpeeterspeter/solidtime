// Prevents additional console window on Windows in release
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

mod activity;
mod encryption;
mod idle;
mod storage;
mod sync;

use tauri::{CustomMenuItem, Manager, SystemTray, SystemTrayEvent, SystemTrayMenu, SystemTrayMenuItem};
use std::sync::Mutex;
use std::time::Duration;

#[derive(Default)]
struct AppState {
    is_tracking: Mutex<bool>,
    api_key: Mutex<Option<String>>,
    api_url: Mutex<String>,
}

#[tauri::command]
fn start_timer(state: tauri::State<AppState>) -> Result<(), String> {
    let mut is_tracking = state.is_tracking.lock().unwrap();
    *is_tracking = true;
    log::info!("Timer started");
    Ok(())
}

#[tauri::command]
fn stop_timer(state: tauri::State<AppState>) -> Result<(), String> {
    let mut is_tracking = state.is_tracking.lock().unwrap();
    *is_tracking = false;
    log::info!("Timer stopped");
    Ok(())
}

#[tauri::command]
async fn collect_activity(state: tauri::State<'_, AppState>) -> Result<activity::ActivitySnapshot, String> {
    let snapshot = activity::ActivitySnapshot::collect().await?;
    log::debug!("Activity collected: {:?}", snapshot);
    Ok(snapshot)
}

#[tauri::command]
async fn get_idle_time() -> Result<u64, String> {
    idle::get_idle_seconds().await.map_err(|e| e.to_string())
}

#[tauri::command]
async fn sync_activity(
    state: tauri::State<'_, AppState>,
    snapshot: activity::ActivitySnapshot,
) -> Result<(), String> {
    let api_key = state.api_key.lock().unwrap().clone();
    let api_url = state.api_url.lock().unwrap().clone();

    if let Some(key) = api_key {
        // Encrypt activity data before sending
        let encrypted = encryption::encrypt_activity(&snapshot, &key)
            .map_err(|e| format!("Encryption error: {}", e))?;

        // Send to API
        sync::send_to_api(&api_url, &key, encrypted).await
            .map_err(|e| format!("Sync error: {}", e))?;

        log::info!("Activity synced successfully");
        Ok(())
    } else {
        Err("API key not configured".to_string())
    }
}

#[tauri::command]
fn set_api_credentials(
    state: tauri::State<AppState>,
    api_key: String,
    api_url: Option<String>,
) -> Result<(), String> {
    *state.api_key.lock().unwrap() = Some(api_key);
    if let Some(url) = api_url {
        *state.api_url.lock().unwrap() = url;
    }
    log::info!("API credentials configured");
    Ok(())
}

#[tauri::command]
fn get_app_state(state: tauri::State<AppState>) -> Result<serde_json::Value, String> {
    let is_tracking = *state.is_tracking.lock().unwrap();
    let has_api_key = state.api_key.lock().unwrap().is_some();

    Ok(serde_json::json!({
        "is_tracking": is_tracking,
        "is_authenticated": has_api_key,
    }))
}

fn main() {
    env_logger::init();

    let quit = CustomMenuItem::new("quit".to_string(), "Quit");
    let show = CustomMenuItem::new("show".to_string(), "Show");
    let start = CustomMenuItem::new("start".to_string(), "Start Timer");
    let stop = CustomMenuItem::new("stop".to_string(), "Stop Timer");

    let tray_menu = SystemTrayMenu::new()
        .add_item(show)
        .add_native_item(SystemTrayMenuItem::Separator)
        .add_item(start)
        .add_item(stop)
        .add_native_item(SystemTrayMenuItem::Separator)
        .add_item(quit);

    let system_tray = SystemTray::new().with_menu(tray_menu);

    tauri::Builder::default()
        .manage(AppState {
            is_tracking: Mutex::new(false),
            api_key: Mutex::new(None),
            api_url: Mutex::new("https://api.solidtime.io".to_string()),
        })
        .system_tray(system_tray)
        .on_system_tray_event(|app, event| match event {
            SystemTrayEvent::LeftClick {
                position: _,
                size: _,
                ..
            } => {
                let window = app.get_window("main").unwrap();
                window.show().unwrap();
                window.set_focus().unwrap();
            }
            SystemTrayEvent::MenuItemClick { id, .. } => {
                match id.as_str() {
                    "quit" => {
                        std::process::exit(0);
                    }
                    "show" => {
                        let window = app.get_window("main").unwrap();
                        window.show().unwrap();
                        window.set_focus().unwrap();
                    }
                    "start" => {
                        let state = app.state::<AppState>();
                        *state.is_tracking.lock().unwrap() = true;
                    }
                    "stop" => {
                        let state = app.state::<AppState>();
                        *state.is_tracking.lock().unwrap() = false;
                    }
                    _ => {}
                }
            }
            _ => {}
        })
        .invoke_handler(tauri::generate_handler![
            start_timer,
            stop_timer,
            collect_activity,
            get_idle_time,
            sync_activity,
            set_api_credentials,
            get_app_state,
        ])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
