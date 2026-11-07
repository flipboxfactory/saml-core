(function ($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.SamlCoreMappingPreview = Garnish.Base.extend({
        /**
         * the selector for the button that is clicked to generate a new keypair
         */
        $button: null,
        $previewEl: null,
        /**
         * sp or idp plugin
         */
        $plugin: null,

        $providerId: null,
        $userIdInput: null,
        $spinner: null,
        init: function ($button, $previewEl, $plugin, $providerId, $userIdInput) {
            this.$button = $button;
            this.$previewEl = $previewEl;
            this.$plugin = $plugin;
            this.$providerId = $providerId;
            this.$userIdInput = $userIdInput;
            this.$spinner = $('<div class="spinner hidden"/>').insertAfter(this.$button.parent());
            this.addListener(this.$button, 'click', 'onClick');
        },
        onClick: function (e) {
            this.$spinner.removeClass('hidden');
            Craft.postActionRequest(
                `${this.$plugin}/cp/view/metadata/preview/mapping`,
                {
                    userId: this.$userIdInput.val(),
                    providerId: this.$providerId,
                },
                $.proxy(function (response, textStatus) {
                    this.$spinner.addClass('hidden');

                    if (textStatus === 'success') {
                        if (response.xml !== undefined) {
                            this.$previewEl.text(response.xml);
                            if (window.Prism) {
                                Prism.highlightElement(this.$previewEl[0]);
                            }
                            this.$previewEl.closest('pre').removeClass('hidden');
                        }
                    }
                }, this)
            );
        }
    })
})(jQuery);
