# PHP After – BPJS Kesehatan Fingerprint Integration

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**PHP After** is a simple PHP library for **BPJS Kesehatan Fingerprint** integration.  
It provides functions to **register, verify, and reset BPJS participants’ fingerprints** via the official BPJS Kesehatan API.

## Features
- **Authenticate** to BPJS Fingerprint server
- **Register** participant’s fingerprints (two fingers)
- **Verify** fingerprint using NIK or participant number
- **Reset** (delete) participant’s fingerprint

## Important Information
If you are using a web application with a DigitalPersona/HID device,  
you **must** use the [HID Authentication Device Client SDK](https://digitalpersona.hidglobal.com/lite-client/)  
and completely uninstall any currently installed SDK before installation.  
If this is not done, the integration will **not** work.  

For usage examples:

```html
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>vanilla</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
  <div class="container py-4">
    <header class="mb-4">
      <h1 class="h4">Digitalpersona Fingerprint</h1>
    </header>

    <main>
      <div id="error" class="alert alert-danger d-none"></div>

      <div class="card">
        <div class="card-header">Captured Sample</div>
        <div class="card-body" id="samples">
        </div>
      </div>
    </main>
  </div>

  <script src="https://unpkg.com/@digitalpersona/websdk@v1"></script>
  <script src="https://unpkg.com/@digitalpersona/fingerprint@v1"></script>
  <script>
    const api = new Fingerprint.WebApi();

    api.onSamplesAcquired = (event) => {
      const samples =
        typeof event.samples === "string" ?
        JSON.parse(event.samples) :
        event.samples;
      const sample = samples[0]?.Data || sample[0];

      const data = sample.replace(/-/g, '+').replace(/_/g, '/');

      document.getElementById("samples").innerHTML = data;

      // do your magic with data
    };

    api.onCommunicationFailed = (event) => {
      const errorEl = document.getElementById("error");
      errorEl.textContent = event.error?.message || "Communication failed";
      errorEl.classList.remove("d-none");
    };

    api.onDeviceDisconnected = (event) => {
      const errorEl = document.getElementById("error");
      errorEl.textContent = "Device disconnected";
      errorEl.classList.remove("d-none");
    };

    api.onErrorOccurred = (event) => {
      const errorEl = document.getElementById("error");
      errorEl.textContent = event.error?.message || "Error occurred";
      errorEl.classList.remove("d-none");
    };

    window.onload = async () => {
      try {
        /**
         * 1 = Raw = Input for feature extraction or conversion to WSQ/ISO templates.
         * 2 = Intermediate = Required for template generation (ANSI/ISO/FMR).
         * 3 = Compressed = Fingerprint image is compressed (lossy, FBI/NIST-approved).
         * 5 = PngImage = For display/preview in user interfaces.
         */

        await api.startAcquisition(Fingerprint.SampleFormat.Intermediate);
      } catch (error) {
        const errorEl = document.getElementById("error");
        errorEl.textContent = error.message || "Failed to start";
        errorEl.classList.remove("d-none");
      }
    };
  </script>
</body>

</html>
```

## Installation

Install the library via **Composer**:

```bash
composer require banguncode/php-after
```

Ensure PHP has the following extensions enabled:
- curl
- openssl

## Requirements

- PHP >= 5.5
- Extensions: ```ext-curl``` and ```ext-openssl```
- Valid BPJS Fingerprint account credentials

## Usage
1. Initialization & Authentication
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use PHPAfter\Fingerprint;

$fp = new Fingerprint();
$fp->init('VCLAIM_USERNAME', 'VCLAIM_PASSWORD');
```

2. Verify Biometrics
```php
$result = $fp->verify(
    '327102xxxxxxxxxx',  // NIK (16 digits) or participant number (13 digits)
    'AOh...'             // Fingerprint minutiae (Base64)
);

print_r($result);
```

3. Register Biometrics **[NOT YET TESTED]**
```php
$result = $fp->register(
    '0001234567890',     // Participant number (13 digits)
    'APi...',            // Right fingerprint minutiae (Base64)
    'iVBOR...',          // Right fingerprint image (Base64 JPEG/PNG)
    'APi...',            // Left fingerprint minutiae (Base64)
    'iVBOR...'           // Left fingerprint image (Base64 JPEG/PNG)
);

print_r($result);
```

4. Reset Biometrics
```php
$result = $fp->reset(
    '0001234567890',     // Participant number
    '02'                 // Reason (01 = Re-enrollment, 02 = Fingerprint damaged/disabled)
);

print_r($result);
```

## Appendix
For the Postman collection, please check it [HERE](https://github.com/banguncode/PHP-After/blob/main/collections/BPJS%20Fingerprint%20(After).postman_collection.json)

## Disclaimer
**This library is not affiliated with BPJS Kesehatan.
You must be an authorized user with valid credentials to use the BPJS Kesehatan API, and you are responsible for complying with all applicable regulations.**
