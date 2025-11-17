# Package WordPress Plugin for Deployment
# This script creates a zip file ready for WordPress installation

$PLUGIN_NAME = "squash-court-stats"
$VERSION = "1.5.0"
$OUTPUT_FILE = "$PLUGIN_NAME.zip"  # Remove version from filename

Write-Host "Packaging $PLUGIN_NAME plugin (v$VERSION)..." -ForegroundColor Green

# Create temporary directory with correct structure
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME" | Out-Null
New-Item -ItemType Directory -Force -Path "temp\$PLUGIN_NAME\includes" | Out-Null

# Copy plugin files
Copy-Item "squash-court-stats.php" -Destination "temp\$PLUGIN_NAME\"
Copy-Item "readme.txt" -Destination "temp\$PLUGIN_NAME\"
Copy-Item "PLUGIN-README.md" -Destination "temp\$PLUGIN_NAME\README.md"
Copy-Item "includes\class-plugin-updater.php" -Destination "temp\$PLUGIN_NAME\includes\"

# Create zip file with correct structure
# We need: squash-stats-dashboard/ at root of ZIP with files inside
# Change to temp directory to zip the folder correctly
Push-Location temp
Compress-Archive -Path $PLUGIN_NAME -DestinationPath "..\$OUTPUT_FILE" -Force
Pop-Location

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
Write-Host "2. Add a shortcode:"
Write-Host "   - Dashboard: [squash_stats_dashboard]"
Write-Host "   - Trivia: [squash_trivia]"
Write-Host "3. Publish!"
Write-Host ""
Write-Host "Optional parameters:" -ForegroundColor Gray
Write-Host "  [squash_stats_dashboard height='2000px']"
Write-Host "  [squash_stats_dashboard class='my-custom-class']"
Write-Host "  [squash_trivia section='high-altitude']"

