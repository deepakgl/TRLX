(function($, Drupal) {
  Drupal.behaviors.MarketBehavior = {
    attach: function(context, settings) {
      $('.user-register-form .term-reference-tree-button, .user-form.term-reference-tree button, .user-form .term-reference-tree-button, .user-form.term-reference-tree button').next('div').find('input[type="checkbox"]').attr('disabled', 'disabled');
      $('.user-register-form .term-reference-tree-button, .user-form.term-reference-tree button, .user-form .term-reference-tree-button, .user-form.term-reference-tree button').next('div').find('input[type="checkbox"]').prop("checked", false);

      if(window.location.href.indexOf('translations') == -1) {
        // Js for select all markets by default.
        $('.node-level-interactive-content-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-product-detail-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-stories-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-tools-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-faq-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-brand-story-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-t-c-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-welcome-message-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
        $('.node-privacy-policy-form .term-reference-tree-level .form-type-checkbox input:checkbox').prop("checked", true);
      }
      $(".form-item-send-notifications .translation-entity-all-languages").hide();
      // Js for select all markets on select of region.
      var $region = $('.path-node .term-reference-tree-button').next('div').find('input[type="checkbox"]');
      $region.on('click', function() {
        // Get the closest markets of selected region.
        var $market = $(this).parent().siblings('.term-reference-tree-level').find('li');
        var $checked = $market.find('input[type="checkbox"]');
        // Select/deselect all the markets, if region is selectd/deselected.
        if ($(this).is(":checked")) {
          $market.each(function() {
            $checked.prop("checked", true);
          });
        }
        else {
          $market.each(function() {
            $checked.prop("checked", false);
          });
        }
      });
      // Hide administrator and anonymous role from access by role field.
      $('.form-item-field-access-by-role-administrator, .form-item-field-access-by-role-anonymous').css('display', 'none');
      // Disable all bulk update content fields except the selected one.
      var $bulk_class = '.bulk-update-fields-form .responsive-enabled .form-checkbox';
      $('.bulk-update-fields-form .select-all input').remove();
      $($bulk_class).on('click', function() {
        var $this = $(this);
        if ($(this).is(":checked")) {
          $($bulk_class).each(function() {
            if ($this[0].id !== $(this)[0].id) {
              $(this).attr('disabled', 'disabled');
            }
          })
        }
        else {
          $($bulk_class).each(function() {
            $(this).removeAttr('disabled');
          })
        }
    });
    $('.form-item-field-badges select').on('change', function() {
      if ($('.form-item-field-badges select').val() != '_none') {
        $('.form-item-field-percentage-0-value input').val(100);
      }
      if ($('.form-item-field-badges select').val() == '_none') {
        $('.form-item-field-percentage-0-value input').val("");
      }
    });
  }
};
})(jQuery, Drupal);
