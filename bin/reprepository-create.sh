#!/bin/bash

# Create the mantle-framework repositories for subtree splitting.
# Requires `gh` and `git` to be installed.

PACKAGES=$(find src/mantle -type d -exec test -e '{}'/composer.json \;  -print)
ORGANIZATION="mantle-framework"

if ! type gh &> /dev/null; then
	echo "gh is not installed. Please install it: brew install gh"
	exit 1
fi

for path in $PACKAGES; do
	package=$(basename "$path")
	echo "Checking $ORGANIZATION/$package..."

	# Fetch the package from GitHub.
	if ! gh repo view "$ORGANIZATION/$package" > /dev/null 2>&1; then
		echo "Repository not found. Creating..."
		gh repo create "$ORGANIZATION/$package" -d "[READ ONLY] Subtree split of the Mantle $package package" --public
	fi
done

echo "DONE"
