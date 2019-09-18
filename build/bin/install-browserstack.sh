#!/bin/bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento-ee/blob/master/LICENSE

wget -q BrowserStackLocal-linux-x64.zip https://www.browserstack.com/browserstack-local/BrowserStackLocal-linux-x64.zip

unzip -q BrowserStackLocal-linux-x64.zip

echo "run ./BrowserStackLocal --key ${BROWSERSTACK_KEY} --local-identifier ${BROWSERSTACK_LOCAL_IDENTIFIER} --force"

./BrowserStackLocal --key "${BROWSERSTACK_KEY}" --local-identifier ${BROWSERSTACK_LOCAL_IDENTIFIER} --force > /dev/null &

#wait for browserstack to initialize
sleep 30

