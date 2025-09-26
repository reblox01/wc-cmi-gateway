#!/bin/bash

# Exit if any command fails
set -e

# Directory containing your plugin
PLUGIN_SLUG="wc-cmi-gateway"
CURRENT_DIR=$(pwd)
PLUGIN_DIR="$CURRENT_DIR"
BUILD_DIR="$CURRENT_DIR/build"
VERSION=$(grep "Version:" "$PLUGIN_DIR/$PLUGIN_SLUG.php" | awk -F': ' '{print $2}' | tr -d '\r')

# Clean up any previous build
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

# Install composer dependencies
composer install --no-dev --optimize-autoloader

# Copy plugin files to build directory
cp -R "$PLUGIN_DIR"/* "$BUILD_DIR/$PLUGIN_SLUG/"

# Remove unnecessary files/folders from build
cd "$BUILD_DIR/$PLUGIN_SLUG"
rm -rf .git
rm -rf .github
rm -rf node_modules
rm -rf tests
rm -rf .gitignore
rm -rf .gitattributes
rm -rf .editorconfig
rm -rf phpunit.xml
rm -rf composer.json
rm -rf composer.lock
rm -rf package.json
rm -rf package-lock.json
rm -rf webpack.config.js
rm -rf deploy.sh

# Create zip file
cd "$BUILD_DIR"
zip -r "${PLUGIN_SLUG}.${VERSION}.zip" "$PLUGIN_SLUG/"

echo "Plugin has been built and packaged to: ${BUILD_DIR}/${PLUGIN_SLUG}.${VERSION}.zip"
