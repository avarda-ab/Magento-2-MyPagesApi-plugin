define([], function () {
    "use strict";

    function loadPayFrameScript(scriptUrl) {
        return new Promise((resolve, reject) => {
            if (window.avardaPayFrameInit) {
                return resolve();
            }

            const script = document.createElement("script");
            script.async = true;
            script.type = "module";
            script.src = scriptUrl + "?ts=" + Date.now();

            script.onload = resolve;
            script.onerror = (error) => {
                reject(error);
            };

            document.body.prepend(script);
        });
    }

    function initPayFrame(siteKey, language = "en") {
        if (!window.avardaPayFrameInit) {
            console.error("Payframe script not loaded");
            return;
        }

        window.avardaPayFrameInit({
            rootNode: document.getElementById("pay-frame"),
            siteKey: siteKey,
            language: language
        });
    }

    return {
        loadPayFrame: function (scriptUrl, siteKey, language) {
            loadPayFrameScript(scriptUrl)
                .then(() => initPayFrame(siteKey, language))
                .catch((error) => console.error("Error loading PayFrame"));
        }
    };
});
