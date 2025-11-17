#!/bin/bash

# Package WordPress Plugin for Deployment
# This script creates a zip file ready for WordPress installation

PLUGIN_NAME="squash-court-stats"
VERSION="1.5.0"
OUTPUT_FILE="${PLUGIN_NAME}.zip"  # Remove version from filename

echo "Packaging ${PLUGIN_NAME} plugin (v${VERSION})..."

# Create temporary directory
mkdir -p temp/${PLUGIN_NAME}/includes

# Copy plugin files
cp squash-court-stats.php temp/${PLUGIN_NAME}/
cp readme.txt temp/${PLUGIN_NAME}/
cp PLUGIN-README.md temp/${PLUGIN_NAME}/README.md
cp includes/class-plugin-updater.php temp/${PLUGIN_NAME}/includes/

# Create zip file
cd temp
zip -r ../${OUTPUT_FILE} ${PLUGIN_NAME}
cd ..

# Cleanup
rm -rf temp

echo "Plugin packaged successfully: ${OUTPUT_FILE}"
echo ""
echo "To install:"
echo "1. Upload ${OUTPUT_FILE} to WordPress (Plugins -> Add New -> Upload Plugin)"
echo "2. Or extract to wp-content/plugins/"
echo "3. Activate in WordPress Admin"
echo ""
echo "To use:"
echo "1. Create a new WordPress page (or edit an existing one)"
echo "2. Add the shortcode: [squash_court_stats]"
echo "3. Publish!"
echo ""
echo "Optional parameters:"
echo "  [squash_court_stats height='2000px']"
echo "  [squash_court_stats class='my-custom-class']"

