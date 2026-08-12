<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finishing up…</title>
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .wrap { text-align: center; color: #444; }
        .spinner { width: 36px; height: 36px; margin: 0 auto 14px; border: 3px solid #E5F6FD; border-top-color: #009EE3; border-radius: 50%; animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { font-size: 14px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="spinner"></div>
        <p>Finishing up your payment…</p>
    </div>
    <script>
        (function () {
            var payload = {
                source:   'gathr-pay',
                status:   @json($status),
                redirect: @json($redirect),
                message:  @json($message),
            };

            // Inside the card modal: tell the parent page the outcome so it can
            // close the iframe and navigate (or show the error inline).
            try {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage(payload, window.location.origin);
                }
            } catch (e) { /* ignore */ }

            // Fallback (also covers the full-page case): break out of the frame.
            setTimeout(function () {
                try {
                    window.top.location.replace(payload.redirect);
                } catch (e) {
                    window.location.replace(payload.redirect);
                }
            }, 600);
        })();
    </script>
</body>
</html>
