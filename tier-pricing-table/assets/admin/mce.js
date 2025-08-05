
(function () {
    tinymce.PluginManager.add('tiered-pricing-custom-mce-buttons', function (editor, url) {

        if (TPTAvailableCustomButtons !== undefined) {
            Object.keys(TPTAvailableCustomButtons).forEach(function(buttonKey) {
                editor.addButton(buttonKey, {
                    text: TPTAvailableCustomButtons[buttonKey].name,
                    tooltip: TPTAvailableCustomButtons[buttonKey].description,
                    icon: false,
                    onclick: function () {
                        editor.insertContent(TPTAvailableCustomButtons[buttonKey].variableKey);
                    }
                });
            });
        }
    });
})();