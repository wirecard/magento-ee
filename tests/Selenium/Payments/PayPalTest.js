/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { By, until, Key } = require('selenium-webdriver');
const {
  getDriver,
  placeOrder,
  checkConfirmationPage,
  choosePaymentMethod,
  fillOutGuestCheckout,
  addProductToCartAndGotoCheckout,
  waitUntilOverlayIsNotVisible,
  waitForAlert,
  chooseFlatRateShipping
} = require('../common');
const { config } = require('../config');

describe('PayPal test', () => {
  const driver = getDriver();

  const paymentLabel = config.payments.paypal.label;
  const formFields = config.payments.paypal.fields;

  it('should check the paypal payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_paypal', paymentLabel);
    await placeOrder(driver);

  try {
    console.log('wait for #email');
    await driver.wait(until.elementLocated(By.id('email')), 10000);
    await driver.findElement(By.id('email')).sendKeys(formFields.email);
    console.log('wait for #password');
    await driver.wait(until.elementLocated(By.id('password')), 10000);
    await driver.findElement(By.id('password')).sendKeys(formFields.password, Key.ENTER);
    console.log('wait for #confirmButtonTop');
    await driver.wait(until.elementLocated(By.id('confirmButtonTop')));
    console.log('#confirmButtonTop located');
    await driver.findElement(By.id('confirmButtonTop')).click();
    console.log('#confirmButtonTop clicked');

    await waitForAlert(driver, 30000);

    await checkConfirmationPage(driver, paymentLabel);
  } catch (e) {

    // Enter PayPal credentials
    console.log('wait for #btnNext');
    await driver.wait(until.elementLocated(By.id('btnNext')), 10000);
    console.log('wait for #email');
    await driver.wait(until.elementLocated(By.id('email')), 10000);
    await driver.findElement(By.id('email')).sendKeys(formFields.email, Key.ENTER);

    await waitUntilOverlayIsNotVisible(driver, By.className('spinnerWithLockIcon'));

    console.log('wait for #btnLogin');
    await driver.wait(until.elementLocated(By.id('btnLogin')), 25000);
    console.log('wait for #password');
    await driver.wait(until.elementLocated(By.id('password')), 10000);
    await driver.findElement(By.id('password')).sendKeys(formFields.password, Key.ENTER);

    await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));

    console.log('wait for #confirmButtonTop');
    await driver.wait(until.elementLocated(By.id('confirmButtonTop')), 25000);
    await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));
    console.log('click #confirmButtonTop');
    await driver.wait(driver.findElement(By.id('confirmButtonTop')).click(), 10000);

    await waitForAlert(driver, 25000);

    await checkConfirmationPage(driver, 'Thank you for your purchase!');
  }
  });

  after(async () => driver.quit());
});
