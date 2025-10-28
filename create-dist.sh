#!/bin/bash
# Pierre's distribution script - he creates clean packages! 🪨

set -e

echo "Pierre is creating a clean distribution package! 🪨"

# Clean up any existing dist directory
rm -rf dist/

# Create dist directory
mkdir -p dist/wp-pierre

# Copy files, excluding those in .distignore
rsync -av --exclude-from=.distignore . dist/wp-pierre/

# Ensure vendor/ is included (it's needed for autoload.php)
if [ ! -d "dist/wp-pierre/vendor" ]; then
    echo "Error: vendor/ directory not found! Run 'composer install --no-dev' first."
    exit 1
fi

# Create zip file
cd dist
zip -r wp-pierre.zip wp-pierre/
cd ..

echo "Distribution package created: dist/wp-pierre.zip 🪨"
echo "Package contents:"
ls -la dist/wp-pierre/

echo ""
echo "Pierre's distribution package is ready! 🪨"
