# Package WordPress Plugin for WordPress.org Submission (without auto-updater)
# This version excludes the GitHub auto-updater for WordPress.org compatibility

$PLUGIN_NAME = "squash-court-stats"
$VERSION = "1.4.0"
$OUTPUT_FILE = "$PLUGIN_NAME-wporg.zip"

Write-Host "Packaging $PLUGIN_NAME plugin (v$VERSION) - WORDPRESS.ORG VERSION (no auto-updater)..." -ForegroundColor Green

# Create temporary directory with correct structure
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME" | Out-Null
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME\includes" | Out-Null
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME\assets\admin" | Out-Null

# Copy plugin files (excluding updater, but including admin assets)
Copy-Item "squash-court-stats.php" -Destination "temp\$PLUGIN_NAME\"
Copy-Item "readme.txt" -Destination "temp\$PLUGIN_NAME\"
Copy-Item "PLUGIN-README.md" -Destination "temp\$PLUGIN_NAME\README.md"
Copy-Item "includes\class-admin-settings.php" -Destination "temp\$PLUGIN_NAME\includes\"
Copy-Item "assets\admin\admin-styles.css" -Destination "temp\$PLUGIN_NAME\assets\admin\"
Copy-Item "assets\admin\admin-scripts.js" -Destination "temp\$PLUGIN_NAME\assets\admin\"
# Explicitly NOT copying includes\class-plugin-updater.php

# Create zip file with correct structure
Push-Location temp
Compress-Archive -Path $PLUGIN_NAME -DestinationPath "..\$OUTPUT_FILE" -Force
Pop-Location

# Cleanup
Remove-Item -Path "temp" -Recurse -Force

Write-Host "WordPress.org plugin packaged successfully: $OUTPUT_FILE" -ForegroundColor Green
Write-Host ""
Write-Host "This version EXCLUDES auto-updater for WordPress.org compatibility." -ForegroundColor Yellow
Write-Host "It will pass WordPress Plugin Check validation." -ForegroundColor Yellow
Write-Host ""
Write-Host "To test with Plugin Check:" -ForegroundColor Cyan
Write-Host "1. Upload $OUTPUT_FILE to WordPress" -ForegroundColor White
Write-Host "2. Activate the plugin" -ForegroundColor White
Write-Host "3. Run Plugin Check - should pass all tests" -ForegroundColor White

