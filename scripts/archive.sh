#!/bin/bash

# Define the source directory and ZIP file name
SOURCE_DIR="$(dirname "$(dirname "$0")")"
ZIP_FILE="block-editor-assets-endpoint.zip"

# Create a temporary directory to store the files to be archived
TEMP_DIR=$(mktemp -d)

# Copy all files and directories from the source directory to the temporary directory,
# excluding the node_modules directory and .gitignore file
rsync -av \
  --exclude='.DS_Store' \
  --exclude='node_modules' \
  --exclude='.gitignore' \
  --exclude='.git' \
  --exclude='bin' \
  --exclude='.editorconfig' \
  --exclude='package-lock.json' \
	--exclude='scripts' \
  "$SOURCE_DIR/" "$TEMP_DIR/" \
  > /dev/null

# Change to the temporary directory
pushd "$TEMP_DIR" > /dev/null

# Create the ZIP file
zip -r "$TEMP_DIR/$ZIP_FILE" ./* > /dev/null

# Change back to the original directory
popd > /dev/null

# Move the ZIP file to the desired location
mv -f "$TEMP_DIR/$ZIP_FILE" "$SOURCE_DIR"

# Clean up the temporary directory
rm -rf "$TEMP_DIR"

echo "Archive created successfully at $(realpath "$SOURCE_DIR/$ZIP_FILE")"
