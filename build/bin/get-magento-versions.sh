#!/bin/bash

#get last 3  releases
curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/OpenMage/magento-mirror/releases | jq -r '.[] | .tag_name' | head -3 > ${MAGENTO_RELEASES_FILE}
git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

git add  ${MAGENTO_RELEASES_FILE}
git commit -m "[skip ci] Update latest shop releases"
git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:TPWDCEE-3884-reporting
