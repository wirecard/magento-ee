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
let driver;

describe('Masterpass test', () => {
  before(async () => {
    driver = await getDriver('masterpass');
  });

  const paymentLabel = config.payments.masterpass.label;
  //const formFields = config.payments.masterpass.fields;

  it('should check the Masterpass payment process', async () => {
    await addProductToCartAndGotoCheckout(driver, '/accessories/jewelry/blue-horizons-bracelets.html');
    await fillOutGuestCheckout(driver);
    await chooseFlatRateShipping(driver);
    await choosePaymentMethod(driver, 'p_method_wirecardee_paymentgateway_masterpass', paymentLabel);
    await placeOrder(driver);

    await driver.wait(until.elementLocated(By.id('MasterPass_frame')), 20000);
    console.log('switch to iframe #MasterPass_frame');
    await driver.wait(until.ableToSwitchToFrame(By.id('MasterPass_frame')));
    await driver.sleep(1000);

    await driver.wait(until.elementLocated(By.className('text-announcements')));
    const welcomeText = await driver.findElement(By.className('text-announcements')).getText();

    expect(welcomeText.toLowerCase()).to.equal('welcome to masterpass');

    // Due to missing credentials and a required captcha we're unable to continue at this point
  });

  after(async () => driver.quit());
});
