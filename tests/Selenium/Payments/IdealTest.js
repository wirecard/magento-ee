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
  placeOrder,
  checkConfirmationPage,
  choosePaymentMethod,
  fillOutGuestCheckout,
  addProductToCartAndGotoCheckout,
  chooseFlatRateShipping,
  asyncForEach
} = require('../common');
const { config } = require('../config');

describe('iDEAL test', () => {
  const driver = getDriver();

  const paymentLabel = config.payments.ideal.label;
  const formFields = config.payments.ideal.fields;

  it('should check the ideal payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_ideal', paymentLabel, async () => {
      await asyncForEach(Object.keys(formFields), async field => {
        await driver.findElement(By.id(field)).sendKeys(formFields[field]);
      });
    });
    await placeOrder(driver);

    await driver.wait(until.elementLocated(By.css('form .btnLink')), 20000);
    await driver.findElement(By.css('form .btnLink')).click();

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
