<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Consent Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Cookie Consent Test Page</h1>
    <p>This page is used to test the Cookie Consent plugin functionality.</p>
    <?php
    // This would normally be included by WordPress
    // For testing purposes, we're simulating the banner here
    ?>
    <div id="custom-cookie-consent-banner" style="position: fixed; bottom: 0; left: 0; right: 0; background: #f1f1f1; padding: 20px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);">
        <h2>This website uses cookies</h2>
        <p>We use cookies to ensure you get the best experience on our website.</p>
        <button id="custom-cookie-consent-accept">Accept All</button>
        <button id="custom-cookie-consent-settings">Cookie Settings</button>
    </div>
    
    <div class="custom-cookie-consent-settings-panel" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.2); width: 80%; max-width: 600px;">
        <h2>Cookie Settings</h2>
        <div>
            <h3>Necessary Cookies</h3>
            <p>These cookies are required for the website to function and cannot be disabled.</p>
            <input type="checkbox" checked disabled> Enabled
        </div>
        <div>
            <h3>Analytics Cookies</h3>
            <p>These cookies help us improve our website by collecting anonymous information.</p>
            <input type="checkbox" id="analytics-cookies"> Enable
        </div>
        <div>
            <h3>Marketing Cookies</h3>
            <p>These cookies are used to track visitors across websites to display relevant advertisements.</p>
            <input type="checkbox" id="marketing-cookies"> Enable
        </div>
        <button id="save-settings">Save Settings</button>
        <button id="close-settings">Close</button>
    </div>
    
    <script>
        // Simple JavaScript to simulate banner functionality
        document.getElementById('custom-cookie-consent-accept').addEventListener('click', function() {
            document.getElementById('custom-cookie-consent-banner').style.display = 'none';
        });
        
        document.getElementById('custom-cookie-consent-settings').addEventListener('click', function() {
            document.querySelector('.custom-cookie-consent-settings-panel').style.display = 'block';
        });
        
        document.getElementById('close-settings').addEventListener('click', function() {
            document.querySelector('.custom-cookie-consent-settings-panel').style.display = 'none';
        });
        
        document.getElementById('save-settings').addEventListener('click', function() {
            document.querySelector('.custom-cookie-consent-settings-panel').style.display = 'none';
            document.getElementById('custom-cookie-consent-banner').style.display = 'none';
        });
    </script>
</body>
</html>
