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
let driver;

describe('eps test', () => {
  before(async () => {
    driver = await getDriver('eps');
  });

  const paymentLabel = config.payments.eps.label;
  const formFields = config.payments.eps.fields;

  it('should check the eps payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_eps', paymentLabel, async () => {
      await asyncForEach(Object.keys(formFields), async field => {
        await driver.findElement(By.id(field)).sendKeys(formFields[field]);
      });
    });
    await placeOrder(driver);

    await driver.wait(until.elementLocated(By.id('sbtnLogin')), 5000);
    await driver.findElement(By.id('sbtnLogin')).click();

    await driver.wait(until.elementLocated(By.id('sbtnSign')), 5000);
    await driver.findElement(By.id('sbtnSign')).click();

    await driver.wait(until.elementLocated(By.id('sbtnSignCollect')), 5000);
    await driver.findElement(By.id('sbtnSignCollect')).click();

    await driver.wait(until.elementLocated(By.id('sbtnOk')), 5000);
    await driver.findElement(By.id('sbtnOk')).click();

    await driver.wait(until.elementLocated(By.name('back2Shop')), 5000);
    await driver.findElement(By.name('back2Shop')).click();

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
