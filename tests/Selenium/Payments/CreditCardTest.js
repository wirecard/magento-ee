/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { By, until } = require('selenium-webdriver');
const {
  getDriver,
  asyncForEach,
  placeOrder,
  checkConfirmationPage,
  choosePaymentMethod,
  fillOutGuestCheckout,
  addProductToCartAndGotoCheckout,
  chooseFlatRateShipping
} = require('../common');
const { config } = require('../config');

describe('Credit Card test', () => {
  const driver = getDriver();

  const paymentLabel = config.payments.creditCard.label;
  const formFields = config.payments.creditCard.fields;

  it('should check the credit card payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_creditcard', paymentLabel);
    await placeOrder(driver);

    // Fill out credit card iframe
    await driver.wait(until.elementLocated(By.className('wirecard-seamless-frame')), 20000);
    await driver.wait(until.ableToSwitchToFrame(By.className('wirecard-seamless-frame')));
    await driver.wait(until.elementLocated(By.id('account_number')), 20000);
    await asyncForEach(Object.keys(formFields), async field => {
      await driver.findElement(By.id(field)).sendKeys(formFields[field]);
    });
    await driver.findElement(By.css('#expiration_month_list > option[value=\'01\']')).click();
    await driver.findElement(By.css('#expiration_year_list > option[value=\'' + config.payments.creditCard.expirationYear + '\'')).click();
    await driver.switchTo().defaultContent();
    await driver.wait(until.elementLocated(By.id('wirecardee-credit-card--form-submit')));
    await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
