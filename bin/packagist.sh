#!/bin/bash

# Creates all the packages on packagist.org.
# Requires 'curl'

echo "Packagist Username?"
read username

echo "API Key? <https://packagist.org/profile/>"
read -s api_key

PACKAGES=$(find src/mantle -type d -exec test -e '{}'/composer.json \;  -print)
ORGANIZATION="mantle-framework"
for path in $PACKAGES; do
	package=$(basename "$path")
	echo "Checking $ORGANIZATION/$package..."

	status_code=$(curl --write-out '%{http_code}' --silent --output /dev/null https://repo.packagist.org/p2/${ORGANIZATION}/${package}.json)

	if [ "$status_code" != "200" ]; then
		echo "Packagist repo not found. Creating..."
		curl -X POST "https://packagist.org/api/create-package?username=${username}&apiToken=${api_key}" -d "{\"repository\":{\"url\":\"https://github.com/${ORGANIZATION}/${package}\"}}"
		printf "\n\n"
	fi
done
