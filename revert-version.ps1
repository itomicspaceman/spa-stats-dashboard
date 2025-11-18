# Revert version back to 1.5.0 after testing

Write-Host "Reverting version back to 1.5.0..." -ForegroundColor Yellow

# Revert plugin file
$pluginFile = "squash-court-stats.php"
$content = Get-Content $pluginFile -Raw
$content = $content -replace '\* Version: 1\.6\.0', '* Version: 1.5.0'
Set-Content -Path $pluginFile -Value $content -NoNewline
Write-Host "✓ Plugin version reverted to 1.5.0" -ForegroundColor Green

# Revert package script
$packageFile = "package-plugin.ps1"
$packageContent = Get-Content $packageFile -Raw
$packageContent = $packageContent -replace '\$VERSION = "1\.6\.0"', '$VERSION = "1.5.0"'
Set-Content -Path $packageFile -Value $packageContent -NoNewline
Write-Host "✓ Package script version reverted to 1.5.0" -ForegroundColor Green

Write-Host ""
Write-Host "Version reverted successfully!" -ForegroundColor Green

