# Package WordPress Plugin for Self-Hosted Deployment (with auto-updater)
# This version includes the GitHub auto-updater for self-hosted installations

$PLUGIN_NAME = "squash-stats-dashboard"
$VERSION = "1.4.0"
$OUTPUT_FILE = "$PLUGIN_NAME-selfhosted.zip"

Write-Host "Packaging $PLUGIN_NAME plugin (v$VERSION) - SELF-HOSTED VERSION with auto-updater..." -ForegroundColor Green

# Create temporary directory with correct structure
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME" | Out-Null
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME\includes" | Out-Null
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME\assets\admin" | Out-Null

# Copy plugin files (including updater and admin assets)
Copy-Item "squash-stats-dashboard-plugin.php" -Destination "temp\$PLUGIN_NAME\"
Copy-Item "readme.txt" -Destination "temp\$PLUGIN_NAME\"
Copy-Item "PLUGIN-README.md" -Destination "temp\$PLUGIN_NAME\README.md"
Copy-Item "includes\class-plugin-updater.php" -Destination "temp\$PLUGIN_NAME\includes\"
Copy-Item "includes\class-admin-settings.php" -Destination "temp\$PLUGIN_NAME\includes\"
Copy-Item "assets\admin\admin-styles.css" -Destination "temp\$PLUGIN_NAME\assets\admin\"
Copy-Item "assets\admin\admin-scripts.js" -Destination "temp\$PLUGIN_NAME\assets\admin\"

# Create zip file with correct structure
Push-Location temp
Compress-Archive -Path $PLUGIN_NAME -DestinationPath "..\$OUTPUT_FILE" -Force
Pop-Location

# Cleanup
Remove-Item -Path "temp" -Recurse -Force

Write-Host "Self-hosted plugin packaged successfully: $OUTPUT_FILE" -ForegroundColor Green
Write-Host ""
Write-Host "This version includes auto-updater for GitHub releases." -ForegroundColor Yellow
Write-Host ""
Write-Host "To install:" -ForegroundColor Cyan
Write-Host "1. Upload $OUTPUT_FILE to WordPress (Plugins -> Add New -> Upload Plugin)" -ForegroundColor White
Write-Host "2. Or extract to wp-content/plugins/" -ForegroundColor White
Write-Host "3. Activate in WordPress Admin -> Plugins" -ForegroundColor White
Write-Host ""
Write-Host "To use:" -ForegroundColor Cyan
Write-Host "1. Create a new WordPress page (or edit an existing one)" -ForegroundColor White
Write-Host "2. Add the shortcode: [squash_stats_dashboard]" -ForegroundColor White
Write-Host "3. Publish!" -ForegroundColor White

