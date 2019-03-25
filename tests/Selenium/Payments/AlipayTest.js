/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { expect } = require('chai');
const { By, until } = require('selenium-webdriver');
const {
  getDriver,
  placeOrder,
  choosePaymentMethod,
  fillOutGuestCheckout,
  addProductToCartAndGotoCheckout,
  chooseFlatRateShipping
} = require('../common');
const { config } = require('../config');

describe('Alipay test', () => {
  const driver = getDriver();

  const paymentLabel = config.payments.alipay.label;
  //const formFields = config.payments.alipay.fields;

  it('should check the alipay payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_alipay-xborder', paymentLabel);
    await placeOrder(driver);

    await driver.wait(until.elementLocated(By.css('.payAmount-area')), 20000);
    const paymentContent = await driver.findElement(By.css('.payAmount-area')).getText();
    expect(paymentContent).to.include('60.00 EUR');

    // We cannot perform the full payment process because of a captcha at Alipay login page
    /*
    await driver.wait(until.elementLocated(By.css('.mi-input-account')), 20000);
    await driver.findElement(By.css('.mi-input-account')).sendKeys(formFields.email);
    await driver.findElement(By.id('payPasswd_rsainput')).sendKeys(formFields.password);

    console.log('wait for .sixDigitPassword');
    await driver.wait(until.elementLocated(By.css('.sixDigitPassword')), 15000);
    await driver.findElement(By.css('.sixDigitPassword i')).sendKeys(formFields.paymentPasswordDigit);
    await driver.findElement(By.id('J_authSubmit')).click();

    await waitForAlert(driver, 20000);

    await checkConfirmationPage(driver, paymentLabel);
    */
  });

  after(async () => driver.quit());
});
