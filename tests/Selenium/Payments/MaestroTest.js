/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { By, until, Key } = require('selenium-webdriver');
const {
  waitForAlert,
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

describe('Maestro SecureCode test', () => {
  const driver = getDriver();

  const paymentLabel = config.payments.maestro.label;
  const formFields = config.payments.maestro.fields;

  it('should check the maestro payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/flapover-briefcase.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_maestro', paymentLabel);
    await placeOrder(driver);

    // Fill out credit card iframe
    await driver.wait(until.elementLocated(By.className('wirecard-seamless-frame')), 20000);
    await driver.wait(until.ableToSwitchToFrame(By.className('wirecard-seamless-frame')));
    await driver.wait(until.elementLocated(By.id('account_number')), 20000);
    await asyncForEach(Object.keys(formFields), async field => {
      await driver.findElement(By.id(field)).sendKeys(formFields[field]);
    });
    await driver.findElement(By.css('#expiration_month_list > option[value=\'01\']')).click();
    await driver.findElement(By.css('#expiration_year_list > option[value=\'' + config.payments.maestro.expirationYear + '\'')).click();
    await driver.switchTo().defaultContent();
    await driver.wait(until.elementLocated(By.id('wirecardee-credit-card--form-submit')));
    await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

    // Enter 3d secure password
    await driver.wait(until.elementLocated(By.id('password')), 20000);
    await driver.findElement(By.id('password')).sendKeys(config.payments.maestro.password, Key.ENTER);

    await waitForAlert(driver, 10000);

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
