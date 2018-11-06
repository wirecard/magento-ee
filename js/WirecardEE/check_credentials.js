/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

function WirecardEE_CheckCredentials(payment) {
  var server = jQuery('#payment_wirecardee_payment_gateway_' + payment + '_api_url').val();
  var httpUser = jQuery('#payment_wirecardee_payment_gateway_' + payment + '_api_user').val();
  var httpPassword = jQuery('#payment_wirecardee_payment_gateway_' + payment + '_api_password').val();
  var $testButton = jQuery('#payment_wirecardee_payment_gateway_' + payment + '_check_credentials_button');

  if (!new RegExp("^https?://([^/]+?\.[a-zA-Z]{2,4})/?$", "gm").test(server)) {
    alert($testButton.data('invalid-url-string'));
    return;
  }

  new Ajax.Request('/admin/WirecardEEPaymentGateway/testCredentials', {
    parameters: {
      wirecardElasticEngineServer: server,
      wirecardElasticEngineHttpUser: httpUser,
      wirecardElasticEngineHttpPassword: httpPassword
    },

    onSuccess: function (data) {
      var response = JSON.parse(data.responseText);

      if (response.status === "success") {
        return alert($testButton.data('success-string'));
      }

      return alert($testButton.data('error-string'));
    },

    onFailure: function() {
      return alert($testButton.data('error-string'));
    }
  });
}
