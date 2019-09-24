(function($, Drupal) {
    Drupal.behaviors.QueueBehavior = {
      attach: function(context, settings) {
        $('#edit-field-market-0-target-id').on('autocompletechange change', function () {
          window.history.pushState({path:'?market=' + $(this).val()},'','?market=' + $(this).val());
        }).change();
      }
    };
})(jQuery, Drupal);
