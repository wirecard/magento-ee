#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento-ee/blob/master/LICENSE

set -e
set -x

#get last 3  releases
curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/OpenMage/magento-mirror/releases | jq -r '.[] | .tag_name' | head -3 > tmp.txt
git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

# sort versions in descending order
sort -nr tmp.txt > ${MAGENTO_COMPATIBILITY_FILE}

if [[ $(git diff HEAD ${MAGENTO_COMPATIBILITY_FILE}) != '' ]]; then
    git add  ${MAGENTO_COMPATIBILITY_FILE}
    git commit -m "${SHOP_SYSTEM_UPDATE_COMMIT}"
    git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
fi
