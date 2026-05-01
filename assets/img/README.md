# Payment Gateway Images

This folder contains payment gateway logos.

## Required Images:

1. **esewa_logo.png** - eSewa payment gateway logo
   - Recommended size: 200x80px (or similar aspect ratio)
   - Format: PNG with transparent background
   - Download from: eSewa official website or branding guidelines

2. **khalti_logo.webp** - Khalti payment gateway logo
   - Recommended size: 200x80px (or similar aspect ratio)
   - Format: WebP (or PNG if WebP not available)
   - Download from: Khalti official website or branding guidelines

## Usage:

These images are used in:
- `user/make_payment.php` - Payment method selection page

## Installation:

Simply copy your logo files to this directory:
```bash
cp esewa_logo.png /var/www/html/ev_charging_system/assets/img/
cp khalti_logo.webp /var/www/html/ev_charging_system/assets/img/
```

Set proper permissions:
```bash
chmod 644 /var/www/html/ev_charging_system/assets/img/esewa_logo.png
chmod 644 /var/www/html/ev_charging_system/assets/img/khalti_logo.webp
```

## Note:

Please use official logos from the respective payment providers to maintain brand consistency and avoid copyright issues.

