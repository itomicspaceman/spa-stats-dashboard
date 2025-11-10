# Create GitHub Release v1.2.0
# This script creates a GitHub release and uploads the plugin ZIP

$owner = "itomicspaceman"
$repo = "spa-stats-dashboard"
$tag = "v1.2.0"
$name = "v1.2.0 - Auto-Update Support"
$body = @"
## What's New in v1.2.0

### ✨ New Features
- **Automatic Update Checking**: Plugin now checks GitHub for new releases every 12 hours
- **WordPress Auto-Update Support**: Users can enable/disable auto-updates from the Plugins page
- **Update Notifications**: Get notified when new versions are available

### 🐛 Bug Fixes
- Fixed API URLs to use absolute paths for cross-domain embedding
- Improved caching for better performance

### 📦 Installation
1. Download ``squash-stats-dashboard.zip``
2. Upload to WordPress (Plugins → Add New → Upload Plugin)
3. Activate and use ``[squash_stats_dashboard]`` shortcode

### 🔄 Upgrading from v1.1.0
- Simply install v1.2.0 over the existing installation
- All settings and shortcodes will continue to work
- Future updates will be automatic (if enabled)

---

## How Auto-Updates Work

1. **Automatic Checking**: Every 12 hours, the plugin checks GitHub for new releases
2. **Update Notification**: When a new version is available, WordPress shows an "Update available" message
3. **One-Click Update**: Click "Update now" to install the latest version
4. **Auto-Update Option**: Enable "Enable auto-updates" to have WordPress automatically install updates

---

**Full Changelog**: https://github.com/itomicspaceman/spa-stats-dashboard/compare/v1.1.0...v1.2.0
"@

Write-Host "Creating GitHub release $tag..." -ForegroundColor Green

# Create the release
$releaseData = @{
    tag_name = $tag
    name = $name
    body = $body
    draft = $false
    prerelease = $false
} | ConvertTo-Json

$headers = @{
    "Accept" = "application/vnd.github+json"
    "Authorization" = "Bearer $env:GITHUB_TOKEN"
    "X-GitHub-Api-Version" = "2022-11-28"
}

try {
    $response = Invoke-RestMethod -Uri "https://api.github.com/repos/$owner/$repo/releases" `
        -Method Post `
        -Headers $headers `
        -Body $releaseData `
        -ContentType "application/json"
    
    Write-Host "Release created successfully!" -ForegroundColor Green
    Write-Host "Release ID: $($response.id)" -ForegroundColor Cyan
    Write-Host "Upload URL: $($response.upload_url)" -ForegroundColor Cyan
    
    # Upload the ZIP file
    $uploadUrl = $response.upload_url -replace '\{\?name,label\}', "?name=squash-stats-dashboard.zip"
    $zipPath = "squash-stats-dashboard.zip"
    
    Write-Host "`nUploading $zipPath..." -ForegroundColor Green
    
    $uploadHeaders = @{
        "Accept" = "application/vnd.github+json"
        "Authorization" = "Bearer $env:GITHUB_TOKEN"
        "Content-Type" = "application/zip"
    }
    
    $uploadResponse = Invoke-RestMethod -Uri $uploadUrl `
        -Method Post `
        -Headers $uploadHeaders `
        -InFile $zipPath
    
    Write-Host "Asset uploaded successfully!" -ForegroundColor Green
    Write-Host "Download URL: $($uploadResponse.browser_download_url)" -ForegroundColor Cyan
    Write-Host "`nRelease URL: $($response.html_url)" -ForegroundColor Yellow
    
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
    Write-Host "Response: $($_.Exception.Response)" -ForegroundColor Red
}

