/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { By, until, Key } = require('selenium-webdriver');
const { Builder } = require('selenium-webdriver');
const {
  placeOrder,
  checkConfirmationPage,
  choosePaymentMethod,
  fillOutGuestCheckout,
  addProductToCartAndGotoCheckout,
  chooseFlatRateShipping
} = require('../common');
const { config } = require('../config');

describe('Sofort. test', () => {
  const driver = new Builder()
    .forBrowser('chrome')
    .build();

  const paymentLabel = config.payments.sofort.label;
  const formFields = config.payments.sofort.fields;

  it('should check the sofort banking payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_sofortbanking', paymentLabel);
    await placeOrder(driver);

    // Wait for Sofort. page and fill out forms
    await driver.wait(until.elementLocated(By.id('MultipaysSessionSenderCountryId')), 20000);
    await driver.findElement(By.css('#MultipaysSessionSenderCountryId > option[value=\'AT\']')).click();
    await driver.findElement(By.id('BankCodeSearch')).sendKeys(formFields.bankCode);

    await driver.wait(until.elementLocated(By.className('js-bank-searcher-result-list')), 5000);
    await driver.findElement(By.css('button.primary')).click();

    await driver.wait(until.elementLocated(By.id('BackendFormLOGINNAMEUSERID')), 20000);
    await driver.findElement(By.id('BackendFormLOGINNAMEUSERID')).sendKeys(formFields.userId);
    await driver.findElement(By.id('BackendFormUSERPIN')).sendKeys(formFields.password, Key.ENTER);

    await driver.wait(until.elementLocated(By.id('account-1')), 20000);
    await driver.findElement(By.id('account-1')).click();
    await driver.findElement(By.css('button.primary')).click();

    await driver.wait(until.elementLocated(By.id('BackendFormTAN')), 20000);
    await driver.findElement(By.id('BackendFormTAN')).sendKeys(formFields.tan, Key.ENTER);

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
