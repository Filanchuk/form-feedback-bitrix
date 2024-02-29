(function (window) {
    'use strict';
    if (window.FpFeedback)
        return;

    window.FpFeedback = function (params) {
        this.signedParams = params.signedParams ?? {};
        this.formId = params.formId;
        this.buttonId = params.buttonId;
        this.componentName = params.componentName;

        BX.bind(BX(this.buttonId), 'click', BX.proxy(this.send,this));
    };
    window.FpFeedback.prototype = {
        send: function () {
            const formData = new FormData(document.getElementById(this.formId));
            formData.append('URL', location.href);
            BX.ajax.runComponentAction(
                this.componentName,
                'send', {
                    mode: 'class',
                    signedParameters: this.signedParams,
                    data: formData,
                })
                .then(function (response) {
                    console.log(response);
                }, function (response) {
                    console.log(response);
                });
            return false;
        }
    }
})(window);
