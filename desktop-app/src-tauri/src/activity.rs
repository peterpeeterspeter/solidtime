use serde::{Deserialize, Serialize};
use std::time::SystemTime;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ActivitySnapshot {
    pub app_name: String,
    pub window_title: String,
    pub active_seconds: u64,
    pub idle_seconds: u64,
    pub keyboard_count: u32,
    pub mouse_count: u32,
    pub timestamp: u64,
}

impl ActivitySnapshot {
    pub async fn collect() -> Result<Self, String> {
        let (app_name, window_title) = get_active_window().await?;
        let idle_seconds = crate::idle::get_idle_seconds().await.unwrap_or(0);

        let timestamp = SystemTime::now()
            .duration_since(SystemTime::UNIX_EPOCH)
            .unwrap()
            .as_secs();

        Ok(ActivitySnapshot {
            app_name,
            window_title,
            active_seconds: if idle_seconds == 0 { 10 } else { 0 },
            idle_seconds,
            keyboard_count: 0, // TODO: Implement keyboard event counting
            mouse_count: 0,    // TODO: Implement mouse event counting
            timestamp,
        })
    }
}

#[cfg(target_os = "windows")]
async fn get_active_window() -> Result<(String, String), String> {
    use windows::Win32::Foundation::HWND;
    use windows::Win32::UI::WindowsAndMessaging::{GetForegroundWindow, GetWindowTextW};

    unsafe {
        let hwnd: HWND = GetForegroundWindow();
        if hwnd.0 == 0 {
            return Ok(("Unknown".to_string(), "".to_string()));
        }

        let mut buffer = [0u16; 512];
        let length = GetWindowTextW(hwnd, &mut buffer);

        if length == 0 {
            return Ok(("Unknown".to_string(), "".to_string()));
        }

        let title = String::from_utf16_lossy(&buffer[..length as usize]);

        // Get process name (simplified - would need more Windows API calls for full implementation)
        let app_name = "Application".to_string(); // TODO: Get actual process name

        Ok((app_name, title))
    }
}

#[cfg(target_os = "macos")]
async fn get_active_window() -> Result<(String, String), String> {
    use cocoa::appkit::NSWorkspace;
    use cocoa::base::{id, nil};
    use cocoa::foundation::{NSAutoreleasePool, NSString};
    use objc::{msg_send, sel, sel_impl};

    unsafe {
        let _pool = NSAutoreleasePool::new(nil);

        let workspace: id = msg_send![class!(NSWorkspace), sharedWorkspace];
        let active_app: id = msg_send![workspace, frontmostApplication];

        if active_app == nil {
            return Ok(("Unknown".to_string(), "".to_string()));
        }

        let app_name: id = msg_send![active_app, localizedName];
        let app_name_str = if app_name != nil {
            let c_str: *const i8 = msg_send![app_name, UTF8String];
            std::ffi::CStr::from_ptr(c_str)
                .to_string_lossy()
                .into_owned()
        } else {
            "Unknown".to_string()
        };

        // Getting window title on macOS requires accessibility permissions
        // For now, return just the app name
        Ok((app_name_str, "".to_string()))
    }
}

#[cfg(target_os = "linux")]
async fn get_active_window() -> Result<(String, String), String> {
    use x11::xlib::{
        XCloseDisplay, XDefaultRootWindow, XFetchName, XFree, XGetInputFocus, XGetWindowProperty,
        XOpenDisplay,
    };

    unsafe {
        let display = XOpenDisplay(std::ptr::null());
        if display.is_null() {
            return Err("Failed to open X display".to_string());
        }

        let mut focus_window = 0;
        let mut revert_to = 0;
        XGetInputFocus(display, &mut focus_window, &mut revert_to);

        let mut window_name: *mut i8 = std::ptr::null_mut();
        let status = XFetchName(display, focus_window, &mut window_name);

        let title = if status != 0 && !window_name.is_null() {
            let c_str = std::ffi::CStr::from_ptr(window_name);
            let title = c_str.to_string_lossy().into_owned();
            XFree(window_name as *mut _);
            title
        } else {
            "".to_string()
        };

        XCloseDisplay(display);

        // App name would require more complex X11 property queries
        Ok(("Application".to_string(), title))
    }
}

#[cfg(not(any(target_os = "windows", target_os = "macos", target_os = "linux")))]
async fn get_active_window() -> Result<(String, String), String> {
    Err("Platform not supported".to_string())
}
