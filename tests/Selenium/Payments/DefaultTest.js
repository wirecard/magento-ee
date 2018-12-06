/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { Builder } = require('selenium-webdriver');
const { placeOrder, checkConfirmationPage, choosePaymentMethod, fillOutGuestCheckout, addProductToCartAndGotoCheckout, chooseFlatrateShipping } = require('../common');

describe('default test', () => {
  const driver = new Builder()
    .forBrowser('chrome')
    .build();

  it('should check the default checkout', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/eyewear/aviator-sunglasses.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatrateShipping(driver);
    await choosePaymentMethod(driver,'p_method_cashondelivery');
    await placeOrder(driver);
    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  });

  after(async () => driver.quit());
});
