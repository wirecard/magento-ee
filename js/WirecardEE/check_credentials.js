/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

function WirecardEECheckCredentials(payment) {
  var server = document.getElementById("payment_wirecardee_paymentgateway_" + payment + "_api_url").value;
  var httpUser = document.getElementById("payment_wirecardee_paymentgateway_" + payment + "_api_user").value;
  var httpPassword = document.getElementById("payment_wirecardee_paymentgateway_" + payment + "_api_password").value;
  var testButton = document.getElementById("payment_wirecardee_paymentgateway_" + payment + "_check_credentials_button");

  if (!new RegExp("^https?://([^/]+?\.[a-zA-Z]{2,4})/?$", "gm").test(server)) {
    alert(testButton.getAttribute("data-invalid-url-string"));
    return;
  }

  new Ajax.Request("/admin/WirecardEEPaymentGateway/testCredentials", {
    parameters: {
      wirecardElasticEngineServer: server,
      wirecardElasticEngineHttpUser: httpUser,
      wirecardElasticEngineHttpPassword: httpPassword
    },

    onSuccess: function (data) {
      var response = JSON.parse(data.responseText);

      if (response.status === "success") {
        return alert(testButton.getAttribute("data-success-string"));
      }

      return alert(testButton.getAttribute("data-error-string"));
    },

    onFailure: function () {
      return alert(testButton.getAttribute("data-error-string"));
    }
  });
}
