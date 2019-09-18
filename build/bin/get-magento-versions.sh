#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento-ee/blob/master/LICENSE

#get last 3  releases
curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/OpenMage/magento-mirror/releases | jq -r '.[] | .tag_name' | head -3 > tmp.txt
git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

# sort versions in descending order
sort -nr tmp.txt > ${MAGENTO_RELEASES_FILE}

if [[ $(git diff HEAD ${MAGENTO_RELEASES_FILE}) != '' ]]; then
    git add  ${MAGENTO_RELEASES_FILE}
    git commit -m "${MANUAL_UITEST_TRIGGER_COMMIT}"
    git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
fi
