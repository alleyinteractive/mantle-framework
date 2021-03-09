#!/bin/bash

# Run phpcs against modified files.
files=$(git ls-files -om --exclude-standard)

if [ -z "$files" ]; then
    echo 'No files to check';
else
	./vendor/bin/phpcs --standard=./phpcs.xml $files
fi
