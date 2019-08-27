// Js to uncheck & disable the regions by default.
// Js to disable the markets which are not assigned to MA.
(function($, Drupal) {
  Drupal.behaviors.MarketAdminBehavior = {
    attach: function(context, settings) {
     $('.term-reference-tree-level .form-type-checkbox [data-drupal-selector!="disable_other_markets_ma"]').attr('disabled', 'disabled');
   }
 };
})(jQuery, Drupal);
