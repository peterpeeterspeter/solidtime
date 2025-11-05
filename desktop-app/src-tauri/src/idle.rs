/// Get the number of seconds the system has been idle
pub async fn get_idle_seconds() -> Result<u64, String> {
    get_idle_time_platform().await
}

#[cfg(target_os = "windows")]
async fn get_idle_time_platform() -> Result<u64, String> {
    use windows::Win32::UI::Input::KeyboardAndMouse::GetLastInputInfo;
    use windows::Win32::UI::Input::KeyboardAndMouse::LASTINPUTINFO;
    use windows::Win32::System::Threading::GetTickCount;

    unsafe {
        let mut lii = LASTINPUTINFO {
            cbSize: std::mem::size_of::<LASTINPUTINFO>() as u32,
            dwTime: 0,
        };

        if GetLastInputInfo(&mut lii).is_ok() {
            let current_tick = GetTickCount();
            let idle_millis = current_tick - lii.dwTime;
            Ok((idle_millis / 1000) as u64)
        } else {
            Err("Failed to get last input info".to_string())
        }
    }
}

#[cfg(target_os = "macos")]
async fn get_idle_time_platform() -> Result<u64, String> {
    use cocoa::base::{id, nil};
    use cocoa::foundation::NSAutoreleasePool;
    use objc::{msg_send, sel, sel_impl};

    unsafe {
        let _pool = NSAutoreleasePool::new(nil);

        // Using CGEventSourceSecondsSinceLastEventType
        // This requires CoreGraphics framework
        let cg_event_source_type_combined_session_state: u32 = 1;
        let cg_event_type_mouse_moved: u32 = 5;

        // Note: This is a simplified version. Full implementation would use FFI to CoreGraphics
        // For now, return 0 (not idle)
        Ok(0)
    }
}

#[cfg(target_os = "linux")]
async fn get_idle_time_platform() -> Result<u64, String> {
    use x11::xlib::{XCloseDisplay, XDefaultRootWindow, XOpenDisplay};
    use x11::xss::{XScreenSaverAllocInfo, XScreenSaverQueryInfo};

    unsafe {
        let display = XOpenDisplay(std::ptr::null());
        if display.is_null() {
            return Err("Failed to open X display".to_string());
        }

        let root = XDefaultRootWindow(display);
        let info = XScreenSaverAllocInfo();

        if info.is_null() {
            XCloseDisplay(display);
            return Err("Failed to allocate screen saver info".to_string());
        }

        let status = XScreenSaverQueryInfo(display, root, info);

        let idle_seconds = if status != 0 {
            let idle_millis = (*info).idle;
            (idle_millis / 1000) as u64
        } else {
            0
        };

        x11::xlib::XFree(info as *mut _);
        XCloseDisplay(display);

        Ok(idle_seconds)
    }
}

#[cfg(not(any(target_os = "windows", target_os = "macos", target_os = "linux")))]
async fn get_idle_time_platform() -> Result<u64, String> {
    Err("Platform not supported".to_string())
}
