#!/bin/bash
# This script will generate release package and keep composer.json if 'test' parameter is passed

TARGET_DIRECTORY="."

composer install --no-dev --prefer-dist
if [[ $1 == 'test' ]]; then
    zip -r magento-wirecard-ee.zip ${TARGET_DIRECTORY} composer.json -x "*tests*" -x "*Test*" -x "*codeception*"
else
    zip -r magento-wirecard-ee.zip ${TARGET_DIRECTORY} -x "*tests*" -x "*Test*" -x "*codeception*"
fi

