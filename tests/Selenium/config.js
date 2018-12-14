/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

exports.config = {
  url: 'http://magento-ee.localhost',
  payments: {
    creditCard: {
      label: 'Wirecard Credit Card',
      fields: {
        last_name: 'Lastname',
        account_number: '4012000300001003',
        card_security_code: '003'
      },
      expirationYear: ((new Date()).getFullYear() + 1)
    },
    sepa: {
      label: 'Wirecard SEPA Direct Debit',
      fields: {
        'wirecardee-sepa--first-name': 'Firstname',
        'wirecardee-sepa--last-name': 'Lastname',
        'wirecardee-sepa--iban': 'DE42512308000000060004'
      }
    },
    sofort: {
      label: 'Wirecard Sofort.',
      fields: {
        bankCode: '00000',
        userId: '1234',
        password: 'passwd',
        tan: '12345'
      }
    }
  }
};
