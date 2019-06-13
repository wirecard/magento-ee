#!/bin/bash

TARGET_DIRECTORY="."

composer install --no-dev --prefer-dist
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
