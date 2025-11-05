# Code Signing Guide for Solidtime Desktop

This guide covers code signing setup for all platforms (macOS, Windows, Linux).

## Overview

Code signing ensures that:
1. Users can verify the app's authenticity
2. Operating systems trust the app (no "unverified developer" warnings)
3. Auto-updates work securely
4. The app passes platform security checks

## macOS Code Signing

### Prerequisites
- Apple Developer Account ($99/year)
- macOS machine (or GitHub Actions with macOS runner)
- Xcode Command Line Tools installed

### Step 1: Create Certificates

1. **Log in to Apple Developer Portal**
   - Go to https://developer.apple.com
   - Navigate to Certificates, Identifiers & Profiles

2. **Create App ID**
   - Create a new App ID with identifier: `io.solidtime.desktop`
   - Enable required capabilities (if any)

3. **Create Distribution Certificate**
   - Type: "Developer ID Application"
   - Download the certificate (.cer file)
   - Double-click to add to Keychain

4. **Export Certificate for CI/CD**
   ```bash
   # Export certificate to .p12 file
   # Open Keychain Access, find your certificate
   # Right-click → Export → Save as .p12 with password
   ```

### Step 2: Configure Signing Identity

**Option A: Local Development**
```bash
# List available signing identities
security find-identity -v -p codesigning

# Copy the identity hash (e.g., "Developer ID Application: Your Name (TEAM_ID)")
# Update tauri.conf.json:
{
  "bundle": {
    "macOS": {
      "signingIdentity": "Developer ID Application: Your Name (TEAM_ID)"
    }
  }
}
```

**Option B: CI/CD (GitHub Actions)**
```yaml
# Store these as GitHub Secrets:
# - APPLE_CERTIFICATE: Base64 encoded .p12 file
# - APPLE_CERTIFICATE_PASSWORD: Password for .p12
# - APPLE_ID: Your Apple ID email
# - APPLE_PASSWORD: App-specific password
# - APPLE_TEAM_ID: Your team ID

# In workflow:
- name: Import Code Signing Certificate
  env:
    APPLE_CERTIFICATE: ${{ secrets.APPLE_CERTIFICATE }}
    APPLE_CERTIFICATE_PASSWORD: ${{ secrets.APPLE_CERTIFICATE_PASSWORD }}
  run: |
    # Create variables
    CERTIFICATE_PATH=$RUNNER_TEMP/build_certificate.p12
    KEYCHAIN_PATH=$RUNNER_TEMP/app-signing.keychain-db

    # Decode certificate
    echo -n "$APPLE_CERTIFICATE" | base64 --decode --output $CERTIFICATE_PATH

    # Create temporary keychain
    security create-keychain -p "$KEYCHAIN_PASSWORD" $KEYCHAIN_PATH
    security set-keychain-settings -lut 21600 $KEYCHAIN_PATH
    security unlock-keychain -p "$KEYCHAIN_PASSWORD" $KEYCHAIN_PATH

    # Import certificate
    security import $CERTIFICATE_PATH -P "$APPLE_CERTIFICATE_PASSWORD" -A -t cert -f pkcs12 -k $KEYCHAIN_PATH
    security list-keychain -d user -s $KEYCHAIN_PATH

    # Set codesigning identity
    security set-key-partition-list -S apple-tool:,apple:,codesign: -s -k "$KEYCHAIN_PASSWORD" $KEYCHAIN_PATH
```

### Step 3: Notarization

macOS Catalina+ requires notarization:

```bash
# After building, notarize the app
xcrun notarytool submit "path/to/Solidtime.dmg" \
  --apple-id "your@email.com" \
  --password "app-specific-password" \
  --team-id "TEAM_ID" \
  --wait

# Staple the notarization ticket
xcrun stapler staple "path/to/Solidtime.dmg"
```

**Automated Notarization (in build script)**:
```bash
#!/bin/bash
# Set environment variables
export APPLE_ID="your@email.com"
export APPLE_PASSWORD="app-specific-password"  # Use app-specific password
export APPLE_TEAM_ID="YOUR_TEAM_ID"

# Build will automatically notarize if credentials are set
npm run tauri build
```

### Step 4: Generate App-Specific Password

1. Go to https://appleid.apple.com
2. Sign in with your Apple ID
3. Navigate to "Security" → "App-Specific Passwords"
4. Generate a new password for "Solidtime Desktop Notarization"
5. Save this password securely (you'll need it for CI/CD)

---

## Windows Code Signing

### Prerequisites
- Code signing certificate (from DigiCert, Sectigo, etc.)
- Windows machine (or GitHub Actions with Windows runner)

### Step 1: Obtain Certificate

**Option A: Buy from Certificate Authority**
- Purchase from DigiCert, Sectigo, GlobalSign, etc.
- Standard code signing: ~$100-300/year
- EV code signing: ~$300-500/year (instant SmartScreen reputation)

**Option B: Self-Signed Certificate (Development Only)**
```powershell
# Create self-signed certificate (NOT for production)
New-SelfSignedCertificate `
  -Type CodeSigningCert `
  -Subject "CN=Solidtime Development" `
  -CertStoreLocation Cert:\CurrentUser\My `
  -NotAfter (Get-Date).AddYears(3)
```

### Step 2: Export Certificate

```powershell
# Export certificate to .pfx file
$cert = Get-ChildItem -Path Cert:\CurrentUser\My -CodeSigningCert
Export-PfxCertificate -Cert $cert -FilePath "solidtime-cert.pfx" -Password (ConvertTo-SecureString -String "YourPassword" -Force -AsPlainText)
```

### Step 3: Configure Signing

**Option A: Local Development**
```bash
# Get certificate thumbprint
certutil -user -store My

# Update tauri.conf.json:
{
  "bundle": {
    "windows": {
      "certificateThumbprint": "YOUR_CERTIFICATE_THUMBPRINT",
      "timestampUrl": "http://timestamp.digicert.com"
    }
  }
}
```

**Option B: CI/CD (GitHub Actions)**
```yaml
# Store these as GitHub Secrets:
# - WINDOWS_CERTIFICATE: Base64 encoded .pfx file
# - WINDOWS_CERTIFICATE_PASSWORD: Certificate password

# In workflow:
- name: Import Code Signing Certificate
  run: |
    New-Item -ItemType directory -Path certificate
    Set-Content -Path certificate\tempCert.txt -Value $env:WINDOWS_CERTIFICATE
    certutil -decode certificate\tempCert.txt certificate\certificate.pfx

    # Import to certificate store
    $pwd = ConvertTo-SecureString -String $env:WINDOWS_CERTIFICATE_PASSWORD -Force -AsPlainText
    Import-PfxCertificate -FilePath certificate\certificate.pfx -CertStoreLocation Cert:\CurrentUser\My -Password $pwd
  env:
    WINDOWS_CERTIFICATE: ${{ secrets.WINDOWS_CERTIFICATE }}
    WINDOWS_CERTIFICATE_PASSWORD: ${{ secrets.WINDOWS_CERTIFICATE_PASSWORD }}
```

### Step 4: Sign Executable

Tauri automatically signs during build if certificate is configured:
```bash
npm run tauri build
```

Manual signing (if needed):
```bash
signtool sign /f "solidtime-cert.pfx" /p "password" /t http://timestamp.digicert.com /v "Solidtime.exe"
```

---

## Linux Packaging

Linux doesn't use code signing in the same way, but you should:

### Step 1: GPG Key for Package Signing

```bash
# Generate GPG key
gpg --full-generate-key
# Choose: (1) RSA and RSA
# Key size: 4096
# Valid: 0 (no expiration) or set expiration
# Real name: Solidtime
# Email: releases@solidtime.io

# Export public key
gpg --armor --export releases@solidtime.io > solidtime-public.asc

# Export private key (store securely)
gpg --armor --export-secret-keys releases@solidtime.io > solidtime-private.asc
```

### Step 2: Sign Debian Package

```bash
# Sign .deb package
dpkg-sig --sign builder solidtime_1.0.0_amd64.deb

# Verify signature
dpkg-sig --verify solidtime_1.0.0_amd64.deb
```

### Step 3: Sign AppImage

```bash
# Create checksum file
sha256sum Solidtime.AppImage > Solidtime.AppImage.sha256

# Sign checksum
gpg --armor --detach-sign Solidtime.AppImage.sha256
```

### Step 4: Publish Public Key

Users can verify downloads:
```bash
# Import public key
wget https://releases.solidtime.io/solidtime-public.asc
gpg --import solidtime-public.asc

# Verify signature
gpg --verify Solidtime.AppImage.sha256.asc Solidtime.AppImage.sha256
sha256sum -c Solidtime.AppImage.sha256
```

---

## Auto-Updater Signing

The Tauri updater requires signing update manifests.

### Step 1: Generate Updater Key Pair

```bash
# Install Tauri CLI
cargo install tauri-cli

# Generate key pair
cargo tauri signer generate -w ~/.tauri/solidtime.key

# This creates:
# - Private key: ~/.tauri/solidtime.key (keep secret!)
# - Public key: printed to console (paste in tauri.conf.json)
```

### Step 2: Configure Public Key

Copy the public key to `tauri.conf.json`:
```json
{
  "updater": {
    "active": true,
    "pubkey": "dW50cnVzdGVkIGNvbW1lbnQ6IG1pbmlzaWduIHB1YmxpYyBrZXk6IEFCQ0RFRjEyMzQ1Njc4OQpSV1FRNV..."
  }
}
```

### Step 3: Sign Updates

When releasing a new version:
```bash
# Sign the update
cargo tauri signer sign ~/.tauri/solidtime.key \
  target/release/bundle/macos/Solidtime.app.tar.gz

# This creates .sig file: Solidtime.app.tar.gz.sig
```

### Step 4: Upload to Release Server

Upload both files to your release server:
- `Solidtime.app.tar.gz`
- `Solidtime.app.tar.gz.sig`

The updater endpoint should return:
```json
{
  "version": "1.1.0",
  "notes": "Bug fixes and improvements",
  "pub_date": "2025-11-05T12:00:00Z",
  "platforms": {
    "darwin-x86_64": {
      "signature": "dW50cnVzdGVkIGNvbW1lbnQ6...",
      "url": "https://releases.solidtime.io/desktop/Solidtime-1.1.0.app.tar.gz"
    },
    "darwin-aarch64": {
      "signature": "dW50cnVzdGVkIGNvbW1lbnQ6...",
      "url": "https://releases.solidtime.io/desktop/Solidtime-1.1.0-arm64.app.tar.gz"
    },
    "windows-x86_64": {
      "signature": "dW50cnVzdGVkIGNvbW1lbnQ6...",
      "url": "https://releases.solidtime.io/desktop/Solidtime-1.1.0-setup.exe"
    }
  }
}
```

---

## Security Best Practices

### Secrets Management

1. **Never commit certificates to git**
   - Add to `.gitignore`: `*.pfx`, `*.p12`, `*.key`

2. **Use CI/CD secrets**
   - GitHub Secrets (encrypted)
   - Environment variables
   - Vault systems (HashiCorp Vault, AWS Secrets Manager)

3. **Rotate certificates before expiration**
   - Set calendar reminders
   - Monitor expiration dates

4. **Limit certificate access**
   - Only CI/CD and release managers
   - Use hardware security modules (HSM) for EV certs

### Certificate Storage

```bash
# .gitignore
desktop-app/src-tauri/*.pfx
desktop-app/src-tauri/*.p12
desktop-app/src-tauri/*.key
desktop-app/src-tauri/certificate/
.tauri/
*.cer
*.pem
```

---

## Troubleshooting

### macOS: "Code signing failed"
```bash
# Check signing identity
security find-identity -v -p codesigning

# Ensure certificate is in login keychain
# Unlock keychain
security unlock-keychain ~/Library/Keychains/login.keychain-db
```

### Windows: "Certificate not found"
```powershell
# List certificates
certutil -user -store My

# Import certificate manually
certutil -user -importpfx My certificate.pfx
```

### Linux: "GPG signature invalid"
```bash
# Check GPG keys
gpg --list-keys

# Re-import public key
gpg --import solidtime-public.asc
```

### Updater: "Invalid signature"
```bash
# Regenerate updater keys
cargo tauri signer generate -w ~/.tauri/solidtime-new.key

# Update tauri.conf.json with new public key
# Re-sign all existing releases with new key
```

---

## Summary Checklist

### macOS
- [ ] Apple Developer Account
- [ ] Developer ID Application certificate
- [ ] Signing identity in tauri.conf.json
- [ ] App-specific password for notarization
- [ ] Entitlements.plist configured
- [ ] Notarization credentials set

### Windows
- [ ] Code signing certificate purchased
- [ ] Certificate thumbprint in tauri.conf.json
- [ ] Timestamp URL configured
- [ ] Certificate in Windows certificate store

### Linux
- [ ] GPG key pair generated
- [ ] Public key published
- [ ] Package signing script
- [ ] Checksum generation

### Auto-Updater
- [ ] Updater key pair generated
- [ ] Public key in tauri.conf.json
- [ ] Private key stored securely
- [ ] Update signing process documented
- [ ] Release server configured

---

## Resources

- [Tauri Code Signing Guide](https://tauri.app/v1/guides/distribution/sign-macos)
- [Apple Developer - Code Signing](https://developer.apple.com/support/code-signing/)
- [Microsoft - Code Signing](https://docs.microsoft.com/en-us/windows/win32/seccrypto/cryptography-tools)
- [Tauri Updater](https://tauri.app/v1/guides/distribution/updater)
