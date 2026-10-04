=== Saxon Contact Form ===
Contributors: William Leonard, Saxon Enterprises, Inc.
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Accessible contact form for saxonwordpress.com. Author: William Leonard, Saxon Enterprises, Inc.

== Description ==

Add the form with the **Contact form** block or the `[saxon_contact_form]` shortcode.

* Fields: name, email, phone (optional), message and a consent checkbox linked to the privacy policy page.
* Security: WordPress nonce, every value sanitized and length checked, output escaped, Reply-To header built only from sanitized values.
* Spam: hidden honeypot field, a signed timestamp that rejects forms sent within 3 seconds of loading, at most 3 links per message, and 5 valid submissions (or 20 invalid ones) per visitor per 10 minutes (the visitor's IP is hashed, never stored; behind a CDN or proxy every visitor shares one address, so set the real client IP at the server). Spam gets a fake success response and is neither sent nor stored.
* Delivery: plain text email through `wp_mail()` to the addresses under Settings, Contact form (site admin email by default). Install an SMTP plugin or use the host's mail service so `wp_mail()` is reliable.
* Optional copy of each message under **Messages** in the dashboard (on by default, admins only), included in the WordPress personal data export and erase tools.
* Works without JavaScript (post, redirect, get). With JavaScript it validates inline and submits without a page reload. No jQuery.

== Hooks ==

* `saxon_cf_fields` filter: change the field list.
* `saxon_cf_mail` filter: change the recipients, subject, body or headers.
* `saxon_cf_submitted` action: runs after every valid submission.

== Uninstall ==

Deleting the plugin from the Plugins screen removes its settings and all stored messages. Deactivating keeps them.
