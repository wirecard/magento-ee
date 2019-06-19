/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { By } = require('selenium-webdriver');
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

describe('SEPA Direct Debit test', () => {
  before(async () => {
    driver = await getDriver('sepa');
  });

  const paymentLabel = config.payments.sepa.label;
  const formFields = config.payments.sepa.fields;

  it('should check the sepa direct debit payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_sepadirectdebit', paymentLabel, async () => {
      await asyncForEach(Object.keys(formFields), async field => {
        await driver.findElement(By.id(field)).sendKeys(formFields[field]);
      });
      return driver.findElement(By.id('wirecardee-sepa--confirm-mandate')).click();
    });
    await placeOrder(driver);

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
