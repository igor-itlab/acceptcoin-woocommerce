/**
 * Checkout dynamic update
 */
jQuery(document).ready(function () {

  /**
   * Update on payment method change
   */
  function acceptcoin_update_checkout_data () {
    jQuery("body").trigger("update_checkout");
  }

  jQuery("body").on({
    "payment_method_selected": acceptcoin_update_checkout_data
  });

  jQuery("form.checkout").on("checkout_place_order_success", acceptcoin_update_checkout_data);

});
