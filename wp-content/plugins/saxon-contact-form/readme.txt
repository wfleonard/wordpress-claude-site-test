=== Saxon Contact Form ===
Contributors: William Leonard, CTI Global
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Accessible contact form for saxonwordpress.com. Author: William Leonard, CTI Global, bill.leonard@cticorp.com

== Description ==

Add the form with the **Contact form** block or the `[saxon_contact_form]` shortcode.

* Fields: name, email, phone (optional), message and a consent checkbox linked to the privacy policy page.
* Security: WordPress nonce, every value sanitized and length checked, output escaped, Reply-To header built only from sanitized values.
* Spam, bots: hidden honeypot field; a signed, random, single use form token that rejects forms sent within 3 seconds of loading, forms older than a day and replayed forms. Bots get a fake success response; nothing is sent or stored.
* Spam, limits: 5 valid submissions (or 20 invalid ones) per visitor per 10 minutes, 3 per email address per hour and 30 for the whole form per hour. Counters are atomic database rows, so parallel requests cannot slip past them, and visitors and addresses are only stored as salted hashes. Behind a CDN or proxy every visitor shares one address, so set the real client IP at the server.
* Spam, content: disposable inbox domains and domains that cannot receive mail are refused with a clear message. Links or addresses in the name, link markup, link shorteners, mostly non Latin text, known spam phrases and Akismet (when active with a key) mark a message as suspected spam: it is not emailed, and with "Keep a copy" on it is saved under Messages as Pending with a "Spam?" title so nothing genuine is lost. At most 3 links per message.
* Settings, Contact form shows how many submissions were blocked and why. Delivered emails carry an X-Saxon-Contact header.
* Email addresses in page content (plain text and mailto links) are encoded so simple scrapers cannot harvest them; browsers show them normally.
* Delivery: plain text email through `wp_mail()` to the addresses under Settings, Contact form (site admin email by default). Install an SMTP plugin or use the host's mail service so `wp_mail()` is reliable.
* Optional copy of each message under **Messages** in the dashboard (on by default, admins only), included in the WordPress personal data export and erase tools.
* Works without JavaScript (post, redirect, get). With JavaScript it validates inline and submits without a page reload. No jQuery.

== Hooks ==

* `saxon_cf_fields` filter: change the field list.
* `saxon_cf_mail` filter: change the recipients, subject, body or headers.
* `saxon_cf_submitted` action: runs after every valid submission.

== Uninstall ==

Deleting the plugin from the Plugins screen removes its settings and all stored messages. Deactivating keeps them.
