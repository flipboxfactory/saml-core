(function ($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.ProviderStatus = Garnish.Base.extend(
        {
            $enabledStatusSelector: null,
            $environmentsListSelector: null,
            $spinner: null,

            init: function ($enabledStatusSelector, $environmentsListSelector) {
                this.$enabledStatusSelector = $enabledStatusSelector;
                this.$environmentsListSelector = $environmentsListSelector;

                console.log(this.$environmentsListSelector,this.$enabledStatusSelector);

                this.addListener(this.$enabledStatusSelector, 'change', 'onChange');
            },

            onChange: function (ev) {
                const input = $(this.$enabledStatusSelector).find('input');
                console.log(input.val());
                if(input.val() == 1) {
                    this.$environmentsListSelector.removeClass('hidden');
                }else{
                    this.$environmentsListSelector.addClass('hidden');
                }
            }

        }
    );
})(jQuery);
