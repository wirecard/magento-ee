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
let driver;

describe('PayPal test', () => {
  before(async () => {
    driver = await getDriver('paypal');
  });

  const paymentLabel = config.payments.paypal.label;
  const formFields = config.payments.paypal.fields;

  const payPalPassword = Object.assign({
    "paypal.password": process.env.PAYPAL_PASSWORD
  });

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
    await driver.findElement(By.id("password")).sendKeys(payPalPassword["paypal.password"], Key.ENTER);
    await driver.wait(until.elementLocated(By.className("btn full confirmButton continueButton")));
    await driver.findElement(By.className("btn full confirmButton continueButton")).click();
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
    await driver.findElement(By.id("password")).sendKeys(payPalPassword["paypal.password"], Key.ENTER);

    await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));
    await driver.wait(until.elementLocated(By.className("btn full confirmButton continueButton")));
    await driver.findElement(By.className("btn full confirmButton continueButton")).click();

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
