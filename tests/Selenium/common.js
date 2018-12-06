/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

const { expect } = require('chai');
const { config } = require('./config');
const { By, until } = require('selenium-webdriver');

exports.addProductToCartAndGotoCheckout = async (driver, url) => {
  await driver.get(`${config.url}${url}`);
  await driver.wait(until.elementLocated(By.id('product_addtocart_form'))).submit();

  await driver.findElement(By.className('btn-proceed-checkout')).click();
};

exports.fillOutGuestCheckout = async (driver) => {
  await driver.findElement(By.id('onepage-guest-register-button')).click();
  await driver.wait(until.elementLocated(By.id('checkout-step-billing')));

  await driver.findElement(By.id('billing:firstname')).sendKeys('Firstname');
  await driver.findElement(By.id('billing:lastname')).sendKeys('Lastname');
  await driver.findElement(By.id('billing:email')).sendKeys('firstname.lastname@example.com');
  await driver.findElement(By.id('billing:street1')).sendKeys('Street');
  await driver.findElement(By.id('billing:city')).sendKeys('Examplecity');
  await driver.findElement(By.id('billing:country_id')).sendKeys('Austria');
  await driver.findElement(By.id('billing:region_id')).sendKeys('Steiermark');
  await driver.findElement(By.id('billing:postcode')).sendKeys('8020');
  await driver.findElement(By.id('billing:telephone')).sendKeys('123456789');

  await driver.findElement(By.id('co-billing-form')).submit();
};

exports.chooseFlatRateShipping = async (driver) => {
  await driver.wait(until.elementLocated(By.id('s_method_flatrate_flatrate')));
  await driver.wait(until.elementIsVisible(driver.findElement(By.id('s_method_flatrate_flatrate'))));
  await driver.findElement(By.id('s_method_flatrate_flatrate')).click();
  await driver.findElement(By.id('co-shipping-method-form')).submit();
};

exports.choosePaymentMethod = async (driver, id) => {
  await driver.wait(until.elementLocated(By.id('checkout-step-payment')));
  await driver.wait(until.elementIsVisible(driver.findElement(By.id('co-payment-form'))));
  await driver.findElement(By.id(id)).click();
  await driver.findElement(By.id('co-payment-form')).click();
};

exports.checkConfirmationPage = async (driver, title) => {
  await driver.wait(until.elementLocated(By.className('sub-title')));
  const panelTitle = await driver.findElement(By.className('sub-title')).getText();
  expect(panelTitle.toLowerCase()).to.equal((title).toLowerCase());
};

exports.placeOrder = async (driver) => {
  await driver.wait(until.elementLocated(By.id('payment-buttons-container')));
  await driver.findElement(By.xpath('//*[@id="payment-buttons-container"]/button')).click();

  await driver.wait(until.elementLocated(By.xpath('//*[@id="review-buttons-container"]/button')));
  await driver.findElement(By.xpath('//*[@id="review-buttons-container"]/button')).click();
};

exports.asyncForEach = async (arr, cb) => {
  for (let i = 0; i < arr.length; i++) {
    await cb(arr[i], i, arr);
  }
};
