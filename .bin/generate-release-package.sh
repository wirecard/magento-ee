#!/bin/bash

TARGET_DIRECTORY="."

echo -e "\e[33mCreating release package...\e[0m"
echo "Installing dependencies ..."
composer install --no-dev --prefer-dist

echo "Zipping up required files ..."
zip -r magento-wirecard-ee.zip ${TARGET_DIRECTORY} composer.json \
    -x "tests*" \
    -x "phpcs.xml*" \
    -x "phpunit.xml*" \
    -x "README.md*" \
    -x "SHOPVERSIONS*" \
    -x ".bin*" \
    -x ".gitignore*" \
    -x ".editorconfig*" \
    -x ".travis.yml"

echo -e "\e[32mSuccessfully created release package\e[0m"
