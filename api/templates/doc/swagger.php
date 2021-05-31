<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3.49.0/swagger-ui.css"
          integrity="sha256-qjZQPPHDerYOSQwo59nugII/sOrHfeIR93CPwMfn5tc=" crossorigin="anonymous">
    <link rel="icon" type="image/png" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3.49.0/favicon-32x32.png"
          sizes="32x32" integrity="sha256-PtYS9B4FDKXnAAytbxy+fn2jn2X8qZwC6Z5lkQVuWDc=" crossorigin="anonymous">
    <link rel="icon" type="image/png" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3.49.0/favicon-16x16.png"
          sizes="16x16" integrity="sha256-ryStYE3Xs7zaj5dauXMHX0ovcKQIeUShL474tjo+B8I=" crossorigin="anonymous">
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }
    </style>
</head>

<body>
<div id="swagger-ui"></div>

<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3.49.0/swagger-ui-bundle.js"
        integrity="sha256-nC7Ukx9Ilpahz0CL5IusnOX7B8MGUeTQ9jBpV6Zs93A=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3.49.0/swagger-ui-standalone-preset.js"
        integrity="sha256-adIBVcA+py9J70k9hSlHDX/q1r+phR0adACVefz1w14=" crossorigin="anonymous"></script>
<script>
    window.onload = function () {
        // Begin Swagger UI call region
        const ui = SwaggerUIBundle({
            url: '<?= $url ?>',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout"
        });
        // End Swagger UI call region

        window.ui = ui;
    };
</script>
</body>
</html>
