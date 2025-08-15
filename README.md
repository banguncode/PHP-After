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

For usage examples, see: [HID Global DigitalPersona Sample Web](https://github.com/hidglobal/digitalpersona-sample-web)

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
    $fingerBase64        // Fingerprint minutiae (Base64)
);

print_r($result);
```

3. Register Biometrics **[NOT YET TESTED]**
```php
$result = $fp->register(
    '0001234567890',     // Participant number (13 digits)
    $fingerRightBase64,  // Right fingerprint minutiae (Base64)
    $imgRightBase64,     // Right fingerprint image (Base64 JPEG/PNG)
    $fingerLeftBase64,   // Left fingerprint minutiae (Base64)
    $imgLeftBase64       // Left fingerprint image (Base64 JPEG/PNG)
);

print_r($result);
```

4. Reset Biometrics
```php
$result = $fp->reset(
    '0001234567890', // Participant number
    '02'             // Reason (01 = Re-enrollment, 02 = Fingerprint damaged/disabled)
);

print_r($result);
```

## Disclaimer
**This library is not affiliated with BPJS Kesehatan.
You must be an authorized user with valid credentials to use the BPJS Kesehatan API, and you are responsible for complying with all applicable regulations.**
