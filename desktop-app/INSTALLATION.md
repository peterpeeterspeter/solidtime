# Solidtime Desktop - Installation Guide

Welcome to Solidtime Desktop! This guide will help you install and get started with the privacy-first automatic time tracking app.

## System Requirements

### Minimum Requirements

**macOS**:
- macOS 10.13 (High Sierra) or later
- Intel or Apple Silicon processor
- 200 MB free disk space
- 4 GB RAM

**Windows**:
- Windows 10 (64-bit) or later
- x64 processor
- 200 MB free disk space
- 4 GB RAM
- WebView2 Runtime (automatically installed if needed)

**Linux**:
- Ubuntu 20.04+ / Debian 11+ / Fedora 36+ / Arch Linux
- x86_64 processor
- 200 MB free disk space
- 4 GB RAM
- GTK 3.24+ and WebKit2GTK

---

## Installation

### macOS

#### Method 1: DMG Installer (Recommended)

1. **Download**
   - Go to [solidtime.io/download](https://solidtime.io/download)
   - Click "Download for macOS"
   - Save `Solidtime-1.0.0.dmg` to your Downloads folder

2. **Install**
   - Double-click the downloaded DMG file
   - Drag the Solidtime app to the Applications folder
   - Eject the DMG

3. **Launch**
   - Open Finder → Applications
   - Double-click Solidtime
   - If you see "Solidtime can't be opened because Apple cannot check it for malicious software":
     - Right-click the app
     - Select "Open"
     - Click "Open" in the dialog
     - (This only happens on first launch)

4. **Grant Permissions**
   - Solidtime will request permissions for:
     - **Accessibility**: To track active applications (required)
     - **Screen Recording**: For activity monitoring (optional)
     - **Notifications**: For focus session alerts (optional)
   - Click "Open System Preferences" and enable required permissions

#### Method 2: Homebrew Cask

```bash
# Install via Homebrew
brew install --cask solidtime

# Launch
open -a Solidtime
```

---

### Windows

#### Method 1: NSIS Installer (Recommended)

1. **Download**
   - Go to [solidtime.io/download](https://solidtime.io/download)
   - Click "Download for Windows"
   - Save `Solidtime-1.0.0-setup.exe` to your Downloads folder

2. **Install**
   - Double-click the installer
   - If you see "Windows protected your PC":
     - Click "More info"
     - Click "Run anyway"
   - Follow the installation wizard
   - Choose installation location (default: `C:\Program Files\Solidtime`)
   - Click "Install"

3. **Launch**
   - Installer will launch Solidtime automatically
   - Or: Start Menu → Solidtime

4. **Grant Permissions**
   - Allow firewall access if prompted (for API connectivity)

#### Method 2: MSI Installer (Enterprise)

1. **Download MSI**
   - Download `Solidtime-1.0.0-x64.msi`

2. **Install**
   ```powershell
   # Install via PowerShell (silent install)
   msiexec /i Solidtime-1.0.0-x64.msi /quiet

   # Install with logs
   msiexec /i Solidtime-1.0.0-x64.msi /l*v install.log
   ```

3. **Deploy via Group Policy** (IT Admins)
   - Use MSI for domain-wide deployment
   - See [Enterprise Deployment Guide](#enterprise-deployment)

#### Method 3: Portable Version

```bash
# Extract ZIP archive
Expand-Archive -Path Solidtime-1.0.0-portable.zip -DestinationPath C:\Tools\Solidtime

# Run
C:\Tools\Solidtime\Solidtime.exe
```

---

### Linux

#### Method 1: AppImage (Universal)

1. **Download**
   - Download `solidtime_1.0.0_amd64.AppImage`

2. **Make Executable**
   ```bash
   chmod +x solidtime_1.0.0_amd64.AppImage
   ```

3. **Run**
   ```bash
   ./solidtime_1.0.0_amd64.AppImage
   ```

4. **Optional: Integrate with Desktop**
   ```bash
   # Install AppImageLauncher for desktop integration
   # Ubuntu/Debian:
   sudo add-apt-repository ppa:appimagelauncher-team/stable
   sudo apt update
   sudo apt install appimagelauncher

   # Then double-click the AppImage
   # AppImageLauncher will ask to integrate it
   ```

#### Method 2: Debian/Ubuntu (.deb)

1. **Download**
   - Download `solidtime_1.0.0_amd64.deb`

2. **Install**
   ```bash
   # Install via dpkg
   sudo dpkg -i solidtime_1.0.0_amd64.deb

   # Fix dependencies if needed
   sudo apt-get install -f
   ```

3. **Run**
   ```bash
   solidtime
   # Or: Applications menu → Solidtime
   ```

4. **Uninstall**
   ```bash
   sudo apt remove solidtime
   ```

#### Method 3: Fedora/RHEL (.rpm)

1. **Download**
   - Download `solidtime-1.0.0-1.x86_64.rpm`

2. **Install**
   ```bash
   # Fedora
   sudo dnf install solidtime-1.0.0-1.x86_64.rpm

   # RHEL/CentOS
   sudo yum install solidtime-1.0.0-1.x86_64.rpm
   ```

3. **Run**
   ```bash
   solidtime
   ```

4. **Uninstall**
   ```bash
   sudo dnf remove solidtime  # Fedora
   sudo yum remove solidtime  # RHEL
   ```

#### Method 4: Arch Linux (AUR)

```bash
# Install via yay
yay -S solidtime-bin

# Or paru
paru -S solidtime-bin

# Run
solidtime
```

---

## First Launch Setup

### 1. Create Account / Sign In

On first launch:
- **New Users**: Click "Create Account" and sign up
- **Existing Users**: Click "Sign In" with your credentials
- **Self-Hosted**: Click "Custom Server" and enter your server URL

### 2. Grant Permissions

#### macOS
1. System Preferences → Security & Privacy → Privacy
2. Enable for Solidtime:
   - **Accessibility** (required for app tracking)
   - **Screen Recording** (optional, for screenshots)
   - **Full Disk Access** (optional, for file tracking)

#### Windows
1. Allow firewall access when prompted
2. No additional permissions needed

#### Linux
1. No additional permissions needed
2. For Wayland: Some distributions may require additional setup

### 3. Configure Settings

1. **Tracking Preferences**:
   - Auto-start tracking on launch
   - Idle detection threshold (default: 5 minutes)
   - Privacy filters (exclude certain apps)

2. **Focus Sessions**:
   - Enable focus mode notifications
   - Set deep work duration (default: 40 minutes)

3. **Privacy**:
   - Data retention period
   - Encrypted local storage
   - Offline mode

---

## Updating Solidtime

### Automatic Updates (Recommended)

Solidtime checks for updates automatically:
1. Update notification appears in system tray
2. Click "Update Now"
3. App downloads and installs update
4. Restart to complete

### Manual Updates

#### macOS
1. Download latest DMG
2. Drag new version to Applications (replace old)
3. Launch new version

#### Windows
1. Download latest installer
2. Run installer (will upgrade existing installation)

#### Linux
```bash
# AppImage: Download new version and replace old

# Debian/Ubuntu
sudo dpkg -i solidtime_1.1.0_amd64.deb

# Fedora
sudo dnf upgrade solidtime-1.1.0-1.x86_64.rpm

# Arch
yay -Syu solidtime-bin
```

---

## Uninstallation

### macOS

1. **Via Finder**:
   - Open Applications folder
   - Drag Solidtime to Trash
   - Empty Trash

2. **Remove User Data** (optional):
   ```bash
   rm -rf ~/Library/Application\ Support/io.solidtime.desktop
   rm -rf ~/Library/Caches/io.solidtime.desktop
   rm -rf ~/Library/Preferences/io.solidtime.desktop.plist
   ```

3. **Via Homebrew**:
   ```bash
   brew uninstall --cask solidtime
   ```

### Windows

1. **Via Settings**:
   - Settings → Apps → Solidtime → Uninstall

2. **Via Control Panel**:
   - Control Panel → Programs → Uninstall a program → Solidtime

3. **Remove User Data** (optional):
   - Delete `C:\Users\YourName\AppData\Roaming\io.solidtime.desktop`
   - Delete `C:\Users\YourName\AppData\Local\io.solidtime.desktop`

### Linux

```bash
# Debian/Ubuntu
sudo apt remove solidtime
sudo apt purge solidtime  # Also removes config files

# Fedora
sudo dnf remove solidtime

# Arch
yay -Rns solidtime-bin

# AppImage: Just delete the file

# Remove user data (optional)
rm -rf ~/.config/io.solidtime.desktop
rm -rf ~/.local/share/io.solidtime.desktop
```

---

## Troubleshooting

### macOS

#### "Solidtime can't be opened"
**Problem**: macOS Gatekeeper blocking unsigned app

**Solution**:
1. Right-click app → Open
2. Click "Open" in dialog

Or disable Gatekeeper (not recommended):
```bash
sudo spctl --master-disable
```

#### App doesn't track activity
**Problem**: Accessibility permissions not granted

**Solution**:
1. System Preferences → Security & Privacy → Privacy → Accessibility
2. Unlock (click padlock)
3. Add Solidtime and check the box

#### High CPU usage
**Problem**: Background tracking consuming resources

**Solution**:
1. Increase tracking interval in settings
2. Disable screenshot capture
3. Update to latest version

---

### Windows

#### "Windows protected your PC"
**Problem**: SmartScreen blocking installer

**Solution**:
1. Click "More info"
2. Click "Run anyway"

Or verify signature:
```powershell
Get-AuthenticodeSignature "Solidtime-1.0.0-setup.exe"
```

#### App doesn't start
**Problem**: Missing WebView2 Runtime

**Solution**:
1. Download WebView2 from [Microsoft](https://go.microsoft.com/fwlink/p/?LinkId=2124703)
2. Install WebView2 Runtime
3. Restart Solidtime

#### Activity tracking not working
**Problem**: Antivirus blocking app

**Solution**:
1. Add Solidtime to antivirus whitelist
2. Temporarily disable antivirus
3. Check Windows Firewall settings

---

### Linux

#### AppImage won't run
**Problem**: Missing FUSE

**Solution**:
```bash
# Ubuntu/Debian
sudo apt install libfuse2

# Fedora
sudo dnf install fuse-libs

# Arch
sudo pacman -S fuse2
```

Or extract and run:
```bash
./solidtime_1.0.0_amd64.AppImage --appimage-extract
./squashfs-root/solidtime
```

#### Missing dependencies
**Problem**: GTK/WebKit libraries missing

**Solution**:
```bash
# Ubuntu/Debian
sudo apt install libgtk-3-0 libwebkit2gtk-4.0-37

# Fedora
sudo dnf install gtk3 webkit2gtk3

# Arch
sudo pacman -S gtk3 webkit2gtk
```

#### Wayland compatibility
**Problem**: App window issues on Wayland

**Solution**:
```bash
# Force X11 backend
GDK_BACKEND=x11 solidtime
```

---

## Enterprise Deployment

### Windows (Group Policy)

1. **Prepare MSI**:
   - Download `Solidtime-1.0.0-x64.msi`
   - Test on pilot machines

2. **Create GPO**:
   - Open Group Policy Management
   - Create new GPO: "Solidtime Desktop Deployment"
   - Edit GPO

3. **Add Software**:
   - Computer Configuration → Policies → Software Settings → Software Installation
   - Right-click → New → Package
   - Select Solidtime MSI from network share
   - Choose "Assigned"

4. **Configure Settings** (optional):
   - Create MST transform file for custom settings
   - Apply transform in GPO

5. **Deploy**:
   - Link GPO to target OU
   - Wait for Group Policy refresh
   - Or force: `gpupdate /force`

### macOS (Jamf Pro / Munki)

**Jamf Pro**:
1. Upload DMG to Jamf Pro
2. Create new policy
3. Add Solidtime package
4. Set scope (computers/users)
5. Deploy

**Munki**:
```bash
# Import DMG
munkiimport Solidtime-1.0.0.dmg

# Edit pkginfo
# Set name, version, category

# Add to catalog
makecatalogs
```

### Linux (Ansible / Puppet)

**Ansible**:
```yaml
- name: Install Solidtime
  apt:
    deb: /path/to/solidtime_1.0.0_amd64.deb
  when: ansible_os_family == "Debian"

- name: Install Solidtime (RPM)
  yum:
    name: /path/to/solidtime-1.0.0-1.x86_64.rpm
  when: ansible_os_family == "RedHat"
```

---

## Privacy & Security

### Data Storage

**Local Data**:
- Activity data stored locally (encrypted at rest)
- SQLite database in user data directory
- AES-256-GCM encryption

**Cloud Sync** (optional):
- End-to-end encrypted
- Only you can decrypt your data
- HTTPS/TLS in transit

### Permissions

Solidtime only requests necessary permissions:
- **Accessibility**: Required for tracking active apps
- **Network**: For API sync (optional, can work offline)
- **Storage**: For local database

We **never**:
- Capture keystrokes
- Record screen without consent
- Access files outside app directory
- Share data with third parties

---

## Getting Help

### Documentation
- [User Guide](https://solidtime.io/docs)
- [FAQ](https://solidtime.io/faq)
- [Video Tutorials](https://solidtime.io/tutorials)

### Support
- Email: support@solidtime.io
- Community Forum: https://community.solidtime.io
- GitHub Issues: https://github.com/solidtime/solidtime/issues

### Social
- Twitter: [@solidtime](https://twitter.com/solidtime)
- Discord: [Join our server](https://discord.gg/solidtime)

---

## License

Solidtime Desktop is open source software licensed under the MIT License.
See LICENSE file for details.

---

Thank you for using Solidtime Desktop! 🚀
