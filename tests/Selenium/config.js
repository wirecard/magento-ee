/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

exports.config = {
  url: 'http://localhost:9000',
  payments: {
    creditCard: {
      fields: {
        last_name: 'Lastname',
        account_number: '4012000300001003',
        card_security_code: '003',
        expiration_year_list: ((new Date()).getFullYear() + 1)
      }
    }
  }
};
