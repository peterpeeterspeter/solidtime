# Release Workflow for Solidtime Desktop

This document describes the complete release process for building, signing, and distributing the Solidtime Desktop application.

## Overview

The release process includes:
1. Version bumping
2. Building for all platforms
3. Code signing
4. Creating update manifests
5. Publishing to distribution channels
6. Notifying users

## Prerequisites

### Development Environment

**macOS** (required for macOS builds):
- macOS 10.15+
- Xcode Command Line Tools
- Rust 1.70+
- Node.js 18+
- Apple Developer Account

**Windows** (required for Windows builds):
- Windows 10/11
- Visual Studio 2019+ with C++ tools
- Rust 1.70+
- Node.js 18+
- Code signing certificate

**Linux** (required for Linux builds):
- Ubuntu 20.04+ / Debian 11+
- Rust 1.70+
- Node.js 18+
- Build dependencies: `libwebkit2gtk-4.0-dev`, `build-essential`, `curl`, `wget`, `libssl-dev`, `libgtk-3-dev`, `libayatana-appindicator3-dev`, `librsvg2-dev`

### Install Dependencies

```bash
# Install Rust
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh

# Install Node.js dependencies
cd desktop-app
npm install

# Install Tauri CLI
cargo install tauri-cli
```

---

## Release Process

### Step 1: Prepare Release

#### 1.1 Update Version Numbers

Update version in multiple files:

**desktop-app/src-tauri/Cargo.toml**:
```toml
[package]
version = "1.1.0"  # Update this
```

**desktop-app/src-tauri/tauri.conf.json**:
```json
{
  "package": {
    "version": "1.1.0"  // Update this
  }
}
```

**desktop-app/package.json**:
```json
{
  "version": "1.1.0"  // Update this
}
```

#### 1.2 Update Changelog

Create/update `CHANGELOG.md`:
```markdown
# Changelog

## [1.1.0] - 2025-11-05

### Added
- New team analytics dashboard
- Focus session heatmap visualization
- Member rankings leaderboard

### Fixed
- Fixed activity tracking on Windows 11
- Improved memory usage

### Changed
- Updated dependencies
- Enhanced UI responsiveness
```

#### 1.3 Commit Version Bump

```bash
git add .
git commit -m "chore: bump version to 1.1.0"
git push origin main
```

---

### Step 2: Build for All Platforms

#### 2.1 macOS Build

```bash
cd desktop-app

# Clean previous builds
cargo clean

# Build for macOS (both architectures)
npm run tauri build -- --target universal-apple-darwin

# Outputs:
# - src-tauri/target/universal-apple-darwin/release/bundle/dmg/Solidtime_1.1.0_universal.dmg
# - src-tauri/target/universal-apple-darwin/release/bundle/macos/Solidtime.app
```

**Note**: Universal build includes both x86_64 and aarch64 (Apple Silicon).

Alternative - Build for specific architecture:
```bash
# Intel only
npm run tauri build -- --target x86_64-apple-darwin

# Apple Silicon only
npm run tauri build -- --target aarch64-apple-darwin
```

#### 2.2 Windows Build

```bash
cd desktop-app

# Clean previous builds
cargo clean

# Build for Windows
npm run tauri build

# Outputs:
# - src-tauri/target/release/bundle/msi/Solidtime_1.1.0_x64_en-US.msi
# - src-tauri/target/release/bundle/nsis/Solidtime_1.1.0_x64-setup.exe
```

#### 2.3 Linux Build

```bash
cd desktop-app

# Clean previous builds
cargo clean

# Build for Linux
npm run tauri build

# Outputs:
# - src-tauri/target/release/bundle/deb/solidtime_1.1.0_amd64.deb
# - src-tauri/target/release/bundle/appimage/solidtime_1.1.0_amd64.AppImage
# - src-tauri/target/release/bundle/rpm/solidtime-1.1.0-1.x86_64.rpm
```

---

### Step 3: Code Signing

#### 3.1 Sign macOS Build

```bash
# Notarize DMG (automatic if credentials are set)
export APPLE_ID="your@email.com"
export APPLE_PASSWORD="app-specific-password"
export APPLE_TEAM_ID="YOUR_TEAM_ID"

# Re-run build with credentials (will auto-notarize)
npm run tauri build -- --target universal-apple-darwin

# Verify notarization
spctl -a -vvv -t install Solidtime_1.1.0_universal.dmg
```

#### 3.2 Sign Windows Build

If not auto-signed during build:
```powershell
# Sign MSI
signtool sign /f "certificate.pfx" /p "password" /t http://timestamp.digicert.com /v "Solidtime_1.1.0_x64_en-US.msi"

# Sign NSIS installer
signtool sign /f "certificate.pfx" /p "password" /t http://timestamp.digicert.com /v "Solidtime_1.1.0_x64-setup.exe"

# Verify signature
signtool verify /pa "Solidtime_1.1.0_x64-setup.exe"
```

#### 3.3 Sign Linux Packages

```bash
# Sign Debian package
dpkg-sig --sign builder solidtime_1.1.0_amd64.deb

# Create checksums
sha256sum solidtime_1.1.0_amd64.deb > solidtime_1.1.0_amd64.deb.sha256
sha256sum solidtime_1.1.0_amd64.AppImage > solidtime_1.1.0_amd64.AppImage.sha256

# Sign checksums
gpg --armor --detach-sign solidtime_1.1.0_amd64.deb.sha256
gpg --armor --detach-sign solidtime_1.1.0_amd64.AppImage.sha256
```

---

### Step 4: Create Update Manifests

#### 4.1 Package Builds for Auto-Updater

```bash
# macOS - Create .tar.gz archive
cd src-tauri/target/universal-apple-darwin/release/bundle/macos
tar -czf Solidtime_1.1.0_universal.app.tar.gz Solidtime.app

# Windows - Use NSIS installer directly
# (Already in correct format)

# Linux - Use AppImage directly
# (Already in correct format)
```

#### 4.2 Sign Update Archives

```bash
# Generate signatures for auto-updater
cd desktop-app

# macOS
cargo tauri signer sign ~/.tauri/solidtime.key \
  src-tauri/target/universal-apple-darwin/release/bundle/macos/Solidtime_1.1.0_universal.app.tar.gz

# Windows
cargo tauri signer sign ~/.tauri/solidtime.key \
  src-tauri/target/release/bundle/nsis/Solidtime_1.1.0_x64-setup.exe

# Linux
cargo tauri signer sign ~/.tauri/solidtime.key \
  src-tauri/target/release/bundle/appimage/solidtime_1.1.0_amd64.AppImage
```

This creates `.sig` files next to each archive.

#### 4.3 Create Update JSON

Create `latest.json` for the updater endpoint:

```json
{
  "version": "1.1.0",
  "notes": "Bug fixes and improvements. See full changelog at https://solidtime.io/changelog",
  "pub_date": "2025-11-05T12:00:00Z",
  "platforms": {
    "darwin-x86_64": {
      "signature": "dW50cnVzdGVkIGNvbW1lbnQ6IHNpZ25hdHVyZSBmcm9tIHRhdXJpIHNlY3JldCBrZXkKUldRNVlVaDg...",
      "url": "https://releases.solidtime.io/desktop/v1.1.0/Solidtime_1.1.0_x64.app.tar.gz"
    },
    "darwin-aarch64": {
      "signature": "dW50cnVzdGVkIGNvbW1lbnQ6IHNpZ25hdHVyZSBmcm9tIHRhdXJpIHNlY3JldCBrZXkKUldRNVlVaDg...",
      "url": "https://releases.solidtime.io/desktop/v1.1.0/Solidtime_1.1.0_aarch64.app.tar.gz"
    },
    "linux-x86_64": {
      "signature": "dW50cnVzdGVkIGNvbW1lbnQ6IHNpZ25hdHVyZSBmcm9tIHRhdXJpIHNlY3JldCBrZXkKUldRNVlVaDg...",
      "url": "https://releases.solidtime.io/desktop/v1.1.0/solidtime_1.1.0_amd64.AppImage.tar.gz"
    },
    "windows-x86_64": {
      "signature": "dW50cnVzdGVkIGNvbW1lbnQ6IHNpZ25hdHVyZSBmcm9tIHRhdXJpIHNlY3JldCBrZXkKUldRNVlVaDg...",
      "url": "https://releases.solidtime.io/desktop/v1.1.0/Solidtime_1.1.0_x64-setup.exe"
    }
  }
}
```

**Extract signatures from .sig files**:
```bash
# Read signature content
cat Solidtime_1.1.0_universal.app.tar.gz.sig
```

---

### Step 5: Upload to Release Server

#### 5.1 Organize Release Files

```bash
mkdir -p releases/v1.1.0

# Copy all build artifacts
cp src-tauri/target/universal-apple-darwin/release/bundle/dmg/*.dmg releases/v1.1.0/
cp src-tauri/target/universal-apple-darwin/release/bundle/macos/*.app.tar.gz* releases/v1.1.0/
cp src-tauri/target/release/bundle/nsis/*.exe* releases/v1.1.0/
cp src-tauri/target/release/bundle/msi/*.msi releases/v1.1.0/
cp src-tauri/target/release/bundle/deb/*.deb* releases/v1.1.0/
cp src-tauri/target/release/bundle/appimage/*.AppImage* releases/v1.1.0/
cp latest.json releases/v1.1.0/
```

#### 5.2 Upload to Server

**Option A: AWS S3**
```bash
aws s3 sync releases/v1.1.0/ s3://releases.solidtime.io/desktop/v1.1.0/ \
  --acl public-read \
  --cache-control "max-age=3600"

# Update latest.json endpoint
aws s3 cp releases/v1.1.0/latest.json \
  s3://releases.solidtime.io/desktop/darwin-x86_64/latest.json \
  --acl public-read
```

**Option B: GitHub Releases**
```bash
# Install GitHub CLI
brew install gh

# Create release
gh release create v1.1.0 \
  --title "Solidtime Desktop v1.1.0" \
  --notes "$(cat CHANGELOG.md)" \
  releases/v1.1.0/*
```

**Option C: Custom Server (rsync)**
```bash
rsync -avz --progress releases/v1.1.0/ \
  user@releases.solidtime.io:/var/www/releases/desktop/v1.1.0/
```

---

### Step 6: Create Git Release

```bash
# Tag the release
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0

# Create GitHub release (if using GitHub)
gh release create v1.1.0 \
  --title "Solidtime Desktop v1.1.0" \
  --notes-file CHANGELOG.md \
  releases/v1.1.0/Solidtime_1.1.0_universal.dmg \
  releases/v1.1.0/Solidtime_1.1.0_x64-setup.exe \
  releases/v1.1.0/solidtime_1.1.0_amd64.deb \
  releases/v1.1.0/solidtime_1.1.0_amd64.AppImage
```

---

### Step 7: Verify Auto-Updater

#### 7.1 Test Update Endpoint

```bash
# Test updater endpoint
curl https://releases.solidtime.io/desktop/darwin-x86_64/1.0.0

# Should return latest.json with new version
```

#### 7.2 Test Update in App

1. Open Solidtime Desktop v1.0.0
2. App should detect new version automatically
3. User sees update dialog
4. Click "Update Now"
5. App downloads, verifies signature, and installs

---

### Step 8: Announce Release

#### 8.1 Update Website

Update download links on https://solidtime.io/download:
- macOS DMG link
- Windows EXE link
- Linux DEB/AppImage links

#### 8.2 Social Media

Post on:
- Twitter/X
- LinkedIn
- Product Hunt (if major release)
- Reddit r/productivity

#### 8.3 Email Newsletter

Send release announcement to users:
- Highlight new features
- Link to changelog
- Download links

---

## Automated CI/CD Workflow

### GitHub Actions Workflow

Create `.github/workflows/release.yml`:

```yaml
name: Release Desktop App

on:
  push:
    tags:
      - 'v*'

jobs:
  create-release:
    runs-on: ubuntu-latest
    outputs:
      release_id: ${{ steps.create-release.outputs.result }}
    steps:
      - uses: actions/checkout@v4
      - name: Create release
        id: create-release
        uses: actions/github-script@v6
        with:
          script: |
            const { data } = await github.rest.repos.createRelease({
              owner: context.repo.owner,
              repo: context.repo.repo,
              tag_name: `${context.ref.replace('refs/tags/', '')}`,
              name: `Solidtime Desktop ${context.ref.replace('refs/tags/', '')}`,
              body: 'See CHANGELOG.md for details',
              draft: true,
              prerelease: false
            })
            return data.id

  build-tauri:
    needs: create-release
    strategy:
      fail-fast: false
      matrix:
        platform: [macos-latest, ubuntu-20.04, windows-latest]
    runs-on: ${{ matrix.platform }}

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: 18

      - name: Install Rust
        uses: dtolnay/rust-toolchain@stable

      - name: Install dependencies (Ubuntu only)
        if: matrix.platform == 'ubuntu-20.04'
        run: |
          sudo apt-get update
          sudo apt-get install -y libgtk-3-dev libwebkit2gtk-4.0-dev libappindicator3-dev librsvg2-dev patchelf

      - name: Install app dependencies
        working-directory: ./desktop-app
        run: npm ci

      - name: Import Code Signing Certificate (macOS)
        if: matrix.platform == 'macos-latest'
        env:
          APPLE_CERTIFICATE: ${{ secrets.APPLE_CERTIFICATE }}
          APPLE_CERTIFICATE_PASSWORD: ${{ secrets.APPLE_CERTIFICATE_PASSWORD }}
        run: |
          # Import certificate (see CODE_SIGNING.md for full script)
          echo "Importing certificate..."

      - name: Import Code Signing Certificate (Windows)
        if: matrix.platform == 'windows-latest'
        env:
          WINDOWS_CERTIFICATE: ${{ secrets.WINDOWS_CERTIFICATE }}
          WINDOWS_CERTIFICATE_PASSWORD: ${{ secrets.WINDOWS_CERTIFICATE_PASSWORD }}
        run: |
          # Import certificate (see CODE_SIGNING.md for full script)
          echo "Importing certificate..."

      - name: Build Tauri App
        working-directory: ./desktop-app
        env:
          APPLE_ID: ${{ secrets.APPLE_ID }}
          APPLE_PASSWORD: ${{ secrets.APPLE_PASSWORD }}
          APPLE_TEAM_ID: ${{ secrets.APPLE_TEAM_ID }}
          TAURI_PRIVATE_KEY: ${{ secrets.TAURI_PRIVATE_KEY }}
          TAURI_KEY_PASSWORD: ${{ secrets.TAURI_KEY_PASSWORD }}
        run: npm run tauri build

      - name: Upload Release Assets
        uses: actions/github-script@v6
        with:
          github-token: ${{ secrets.GITHUB_TOKEN }}
          script: |
            const fs = require('fs');
            const path = require('path');
            const artifactPath = 'desktop-app/src-tauri/target/release/bundle';

            // Upload all build artifacts
            // (platform-specific logic here)

  publish-release:
    needs: build-tauri
    runs-on: ubuntu-latest
    steps:
      - name: Publish release
        uses: actions/github-script@v6
        with:
          script: |
            github.rest.repos.updateRelease({
              owner: context.repo.owner,
              repo: context.repo.repo,
              release_id: ${{ needs.create-release.outputs.release_id }},
              draft: false
            })
```

---

## Quick Release Checklist

- [ ] Update version in Cargo.toml, tauri.conf.json, package.json
- [ ] Update CHANGELOG.md
- [ ] Commit version bump
- [ ] Build for macOS (universal or specific arch)
- [ ] Build for Windows
- [ ] Build for Linux
- [ ] Sign macOS build (notarize DMG)
- [ ] Sign Windows build (MSI + EXE)
- [ ] Sign Linux packages (GPG)
- [ ] Package builds for auto-updater (.tar.gz)
- [ ] Sign update archives with Tauri signer
- [ ] Create latest.json manifest
- [ ] Upload all files to release server
- [ ] Test updater endpoint
- [ ] Create Git tag and GitHub release
- [ ] Update website download links
- [ ] Announce on social media
- [ ] Send email newsletter

---

## Rollback Procedure

If a release has critical bugs:

1. **Revert auto-updater**:
   ```bash
   # Update latest.json to previous version
   aws s3 cp releases/v1.0.0/latest.json \
     s3://releases.solidtime.io/desktop/darwin-x86_64/latest.json
   ```

2. **Remove problematic release**:
   ```bash
   # Delete from S3
   aws s3 rm s3://releases.solidtime.io/desktop/v1.1.0/ --recursive

   # Delete GitHub release
   gh release delete v1.1.0 --yes
   ```

3. **Notify users**:
   - Post on status page
   - Email users about the issue
   - Provide rollback instructions

---

## Resources

- [Tauri Build Guide](https://tauri.app/v1/guides/building/)
- [Tauri Updater](https://tauri.app/v1/guides/distribution/updater)
- [GitHub Actions](https://docs.github.com/en/actions)
- [AWS S3 Static Hosting](https://docs.aws.amazon.com/AmazonS3/latest/userguide/WebsiteHosting.html)
