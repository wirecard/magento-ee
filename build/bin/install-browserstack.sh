#!/bin/bash

wget BrowserStackLocal-linux-x64.zip https://www.browserstack.com/browserstack-local/BrowserStackLocal-linux-x64.zip

unzip BrowserStackLocal-linux-x64.zip

echo "run ./BrowserStackLocal --key ${BROWSERSTACK_KEY} --local-identifier ${BROWSERSTACK_LOCAL_IDENTIFIER} --force"

./BrowserStackLocal --key "${BROWSERSTACK_KEY}" --local-identifier ${BROWSERSTACK_LOCAL_IDENTIFIER} --force > /dev/null &

#wait for browserstack to initialize
sleep 30

