/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { expect } = require('chai');
const { config, browsers } = require('./config');
const { Builder, By, until } = require('selenium-webdriver');
const mysql = require('mysql');
const { exec } = require('child_process');

const createDatabaseConnection = () => {
  return mysql.createConnection({
    host: '127.0.0.1',
    user: 'travis',
    password: '',
    database: 'magento'
  });
};

const runInShell = async function (cmd) {
  return new Promise(function (resolve, reject) {
    exec(cmd, (err, stdout, stderr) => {
      if (err) {
        reject(err);
      } else {
        resolve({ stdout, stderr });
      }
    });
  });
};

exports.updateDatabaseTransactionType = async function(databaseTransactionValue, databaseTransactionName) {
  let con = createDatabaseConnection();
  let sql = 'UPDATE core_config_data SET value = ? WHERE path = ?';
  let data = [databaseTransactionValue, databaseTransactionName];

  con.query(sql, data, (error) => {
    if (error) {
      return console.error(error.message);
    }
    console.log('database transaction type has been updated.');
  });

  con.end();

  // delete cache to update database
  await runInShell('php -r \'require("/home/travis/magento/app/Mage.php"); Mage::app()->getCacheInstance()->flush();\'');

};

exports.clearDatabaseRows = async function(databaseTransactionValue) {
  let con = createDatabaseConnection();
  let sql = 'DELETE FROM sales_payment_transaction WHERE txn_type <> ?';
  let data = [databaseTransactionValue];

  con.query(sql, data, (error) => {
    if (error) {
      return console.error(error.message);
    }
    console.log('database successfully cleared.');
  });

  con.end();

  // delete cache to update database
  await runInShell('php -r \'require("/home/travis/magento/app/Mage.php"); Mage::app()->getCacheInstance()->flush();\'');

};

exports.checkTransactionTypeInDatabase = function(transactionType) {
  let con = createDatabaseConnection();
  con.query('SELECT txn_type FROM sales_payment_transaction ORDER BY transaction_id DESC LIMIT 1', function (err, result) {
    if (err) {
      return console.error(err.message);
    }
    Object.values(result).forEach(function(row) {
      expect(row.txn_type).to.equal(transactionType);
      console.log('I can see ' + transactionType + ' in transaction table!');
    });
  });
  con.end();
};

exports.addProductToCartAndGotoCheckout = async (driver, url) => {
  await driver.get(`${config.url}${url}`);
  await driver.wait(until.elementLocated(By.id('product_addtocart_form'))).submit();

  await driver.findElement(By.className('btn-proceed-checkout')).click();
};

exports.fillOutGuestCheckout = async (driver) => {
  await driver.findElement(By.id('onepage-guest-register-button')).click();
  await driver.wait(until.elementLocated(By.id('checkout-step-billing')));

  await driver.findElement(By.id('billing:firstname')).sendKeys('John');
  await driver.findElement(By.id('billing:lastname')).sendKeys('Doe');
  await driver.findElement(By.id('billing:email')).sendKeys('john.doe@example.com');
  await driver.findElement(By.id('billing:street1')).sendKeys('Hauptplatz 1');
  await driver.findElement(By.id('billing:city')).sendKeys('Graz');
  await driver.findElement(By.id('billing:country_id')).sendKeys('Österreich ');
  await driver.findElement(By.id('billing:region_id')).sendKeys('Steiermark');
  await driver.findElement(By.id('billing:postcode')).sendKeys('8020');
  await driver.findElement(By.id('billing:telephone')).sendKeys('03168720');

  await driver.findElement(By.id('co-billing-form')).submit();
};

exports.chooseFlatRateShipping = async (driver) => {
  await driver.wait(until.elementLocated(By.id('s_method_flatrate_flatrate')));
  const element = driver.findElement(By.id('s_method_flatrate_flatrate'));
  if (!await element.getAttribute('checked')) {
    await driver.wait(until.elementIsVisible(element));
    await element.click();
  }
  await driver.findElement(By.id('co-shipping-method-form')).submit();
};

exports.choosePaymentMethod = async (driver, id, paymentLabel, additionalFieldsCallback) => {
  await driver.wait(until.elementLocated(By.id('checkout-step-payment')));
  await driver.wait(until.elementIsVisible(driver.findElement(By.id('co-payment-form'))));
  await driver.findElement(By.xpath("//*[contains(text(), '" + paymentLabel + "')]")).click();
  await driver.findElement(By.id(id)).click();
  additionalFieldsCallback && await additionalFieldsCallback();
  await driver.findElement(By.id('co-payment-form')).click();
};

exports.checkConfirmationPage = async (driver, title) => {
  await driver.wait(until.elementLocated(By.className('sub-title')));
  const panelTitle = await driver.findElement(By.className('sub-title')).getText();
  expect(panelTitle.toLowerCase()).to.equal((title).toLowerCase());
  const orderId = await driver.findElement(By.xpath('//p[contains(text(), "Your order # is")]')).getText();
  console.log(orderId);
};

exports.placeOrder = async (driver) => {
  await driver.wait(until.elementLocated(By.id('payment-buttons-container')));
  await driver.findElement(By.xpath('//*[@id="payment-buttons-container"]/button')).click();

  await driver.wait(until.elementLocated(By.xpath('//*[@id="review-buttons-container"]/button')));
  await driver.findElement(By.xpath('//*[@id="review-buttons-container"]/button')).click();
};

exports.waitUntilOverlayIsNotVisible = async function (driver, locator) {
  const overlay = await driver.findElements(locator);
  if (overlay.length) {
    await driver.wait(until.elementIsNotVisible(overlay[0]));
  }
};

exports.waitForAlert = async function (driver, timeout) {
  try {
    console.log('wait for alert');
    const alert = await driver.wait(until.alertIsPresent(), timeout);
    console.log('accept alert');
    await alert.accept();
    await driver.switchTo().defaultContent();
  } catch (e) {
    console.log('no alert popup');
  }
};

exports.asyncForEach = async (arr, cb) => {
  for (let i = 0; i < arr.length; i++) {
    await cb(arr[i], i, arr);
  }
};

exports.getDriver = async (testCase = 'generic') => {
  if (global.driver) {
    return global.driver;
  }

  const browser = browsers[0];
  const bsConfig = Object.assign({
    'browserstack.user': process.env.BROWSERSTACK_USER,
    'browserstack.key': process.env.BROWSERSTACK_KEY,
    'browserstack.local': 'true',
    'browserstack.localIdentifier': process.env.BROWSERSTACK_LOCAL_IDENTIFIER
  }, browser);

  let builder = await new Builder()
    .usingServer('http://hub-cloud.browserstack.com/wd/hub')
    .withCapabilities(Object.assign({
      name: testCase,
      build: process.env.TRAVIS ? `${process.env.TRAVIS_JOB_NUMBER}` : 'local',
      project: `Magento1:WirecardElasticEngine-${process.env.GATEWAY}-${process.env.MAGENTO_VERSION}`
    }, bsConfig))
    .build();

  return builder;
};
