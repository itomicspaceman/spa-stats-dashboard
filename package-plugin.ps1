# Package WordPress Plugin for Deployment
# This script creates a zip file ready for WordPress installation

$PLUGIN_NAME = "squash-stats-dashboard"
$VERSION = "1.1.0"
$OUTPUT_FILE = "$PLUGIN_NAME-$VERSION.zip"

Write-Host "Packaging $PLUGIN_NAME plugin..." -ForegroundColor Green

# Create temporary directory with correct structure
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME" | Out-Null

# Copy plugin files
Copy-Item "squash-stats-dashboard-plugin.php" -Destination "temp\$PLUGIN_NAME\"
Copy-Item "PLUGIN-README.md" -Destination "temp\$PLUGIN_NAME\README.md"

# Create zip file with correct structure (folder should be at root of zip)
Compress-Archive -Path "temp\$PLUGIN_NAME" -DestinationPath $OUTPUT_FILE -Force

# Cleanup
Remove-Item -Path "temp" -Recurse -Force

Write-Host "Plugin packaged successfully: $OUTPUT_FILE" -ForegroundColor Green
Write-Host ""
Write-Host "To install:" -ForegroundColor Yellow
Write-Host "1. Upload $OUTPUT_FILE to WordPress (Plugins -> Add New -> Upload Plugin)"
Write-Host "2. Or extract to wp-content/plugins/"
Write-Host "3. Activate in WordPress Admin -> Plugins"
Write-Host ""
Write-Host "To use:" -ForegroundColor Cyan
Write-Host "1. Create a new WordPress page (or edit an existing one)"
Write-Host "2. Add the shortcode: [squash_stats_dashboard]"
Write-Host "3. Publish!"
Write-Host ""
Write-Host "Optional parameters:" -ForegroundColor Gray
Write-Host "  [squash_stats_dashboard height='2000px']"
Write-Host "  [squash_stats_dashboard class='my-custom-class']"

