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

describe('Giropay test', () => {
  const driver = getDriver();

  const paymentLabel = config.payments.giropay.label;
  const formFields = config.payments.giropay.fields;
  const simulatorFields = config.payments.giropay.simulatorFields;

  it('should check the giropay payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_giropay', paymentLabel, async () => {
      await asyncForEach(Object.keys(formFields), async field => {
        await driver.findElement(By.id(field)).sendKeys(formFields[field]);
      });
    });
    await placeOrder(driver);

    await asyncForEach(Object.keys(simulatorFields), async field => {
      await driver.wait(until.elementLocated(By.name(field)));
      await driver.findElement(By.name(field)).sendKeys(simulatorFields[field]);
    });

    await driver.findElement(By.css('input[type="submit"]')).click();

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
