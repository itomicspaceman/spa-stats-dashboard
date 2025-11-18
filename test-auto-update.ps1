# Test Auto-Update Functionality
# This script helps test the WordPress plugin auto-update system

Write-Host "=== WordPress Plugin Auto-Update Test ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Update version number
Write-Host "Step 1: Updating version to 1.6.0 (test version)..." -ForegroundColor Yellow
$pluginFile = "squash-court-stats.php"
$content = Get-Content $pluginFile -Raw
$content = $content -replace '\* Version: 1\.5\.0', '* Version: 1.6.0'
Set-Content -Path $pluginFile -Value $content -NoNewline
Write-Host "✓ Version updated to 1.6.0" -ForegroundColor Green
Write-Host ""

# Step 2: Update package script version
Write-Host "Step 2: Updating package script version..." -ForegroundColor Yellow
$packageFile = "package-plugin.ps1"
$packageContent = Get-Content $packageFile -Raw
$packageContent = $packageContent -replace '\$VERSION = "1\.5\.0"', '$VERSION = "1.6.0"'
Set-Content -Path $packageFile -Value $packageContent -NoNewline
Write-Host "✓ Package script version updated" -ForegroundColor Green
Write-Host ""

# Step 3: Package the plugin
Write-Host "Step 3: Packaging plugin..." -ForegroundColor Yellow
& .\package-plugin.ps1
Write-Host ""

# Step 4: Instructions
Write-Host "=== Next Steps ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Create a GitHub Release:" -ForegroundColor Yellow
Write-Host "   - Go to: https://github.com/itomic/squash-court-stats/releases/new" -ForegroundColor White
Write-Host "   - Tag: v1.6.0" -ForegroundColor White
Write-Host "   - Title: v1.6.0 (Test Release)" -ForegroundColor White
Write-Host "   - Description: Test release for auto-update functionality" -ForegroundColor White
Write-Host "   - Attach: squash-court-stats.zip (from current directory)" -ForegroundColor White
Write-Host "   - Click: Publish release" -ForegroundColor White
Write-Host ""
Write-Host "2. Force WordPress to check for updates:" -ForegroundColor Yellow
Write-Host "   - Go to: https://wordpress.test/wp-admin/update-core.php" -ForegroundColor White
Write-Host "   - Click: 'Check Again' button" -ForegroundColor White
Write-Host "   OR" -ForegroundColor White
Write-Host "   - Go to: https://wordpress.test/wp-admin/plugins.php" -ForegroundColor White
Write-Host "   - Look for 'Update available' badge on Squash Court Stats plugin" -ForegroundColor White
Write-Host ""
Write-Host "3. Test the update:" -ForegroundColor Yellow
Write-Host "   - Click 'Update now' button" -ForegroundColor White
Write-Host "   - Verify plugin updates to 1.6.0" -ForegroundColor White
Write-Host ""
Write-Host "4. After testing, revert version back to 1.5.0:" -ForegroundColor Yellow
Write-Host "   - Run: .\revert-version.ps1" -ForegroundColor White
Write-Host ""

