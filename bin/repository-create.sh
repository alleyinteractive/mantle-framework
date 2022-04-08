#!/bin/bash

# Create the mantle-framework repositories for subtree splitting.
# Requires `gh` and `jq` to be installed.

PACKAGES=$(find src/mantle -type d -exec test -e '{}'/composer.json \;  -print)
ORGANIZATION="mantle-framework"

if ! type gh &> /dev/null; then
	echo "gh is not installed. Please install it: brew install gh"
	exit 1
fi

if ! type jq &> /dev/null; then
	echo "jq is not installed. Please install it: brew install jq"
	exit 1
fi

cwd=$(pwd)

for path in $PACKAGES; do
	package=$(basename "$path")
	echo "Checking $ORGANIZATION/$package..."

	# Fetch the package from GitHub.
	if ! gh repo view "$ORGANIZATION/$package" > /dev/null 2>&1; then
		echo "Repository not found. Creating..."
		gh repo create "$ORGANIZATION/$package" -d "[READ ONLY] Subtree split of the Mantle $package package" --public
	fi

	is_empty=$(gh repo view mantle-framework/auth --json=isEmpty | jq -r .isEmpty)

	# Initialize the repository if it is empty.
	if [ "$is_empty" = "true" ]; then
		echo "Creating default branch..."
		clone_dir="/tmp/tmp-$package"

		# Clear the existing directory and clone the repository.
		rm -rf "$clone_dir"

		gh repo clone "$ORGANIZATION/$package" "$clone_dir"

		cd "$clone_dir" || exit

		touch .gitignore
		git add --all . && git commit -m "Initializing repository"
		git push -u origin main

		cd "$cwd" || exit
	fi

	# Update the settings to disable issues and wikis.
	gh repo edit "$ORGANIZATION/$package" --enable-issues=false --enable-wiki=false --homepage https://mantle.alley.co/
done

echo "DONE"
