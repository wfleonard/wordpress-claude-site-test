# saxonwordpress.com

Custom WordPress theme and tooling for saxonwordpress.com.

Author: William Leonard, CTI Global, bill.leonard@cticorp.com

## Layout

```
wp-content/themes/saxon/                Custom "Saxon" theme (copy this folder to the server)
wp-content/plugins/saxon-contact-form/  Contact form plugin
wp-content/plugins/saxon-seo-basics/    SEO basics plugin (meta, social tags, schema)
tools/seed-content.php                  Creates the starter pages, menus and reading settings
tools/router.php                        Router for PHP's built in server (local testing only)
tools/dev-mail-log.php                  Must use plugin that logs wp_mail() to a file (local testing only)
```

## Saxon theme

A classic PHP theme built from scratch: no page builder, no jQuery, no third party libraries.
Markup lives in PHP templates, styles in `assets/css/main.css`, behavior in `assets/js/navigation.js`.

* Responsive layout with a fluid type scale and a collapsible small screen menu (works without JavaScript too).
* Accessibility: skip link, visible focus, landmark labels, `aria-expanded` menu toggle with Escape to close, 44px touch targets, AA colour contrast, reduced motion support.
* Page templates: **About**, **Services** (lists child pages as cards) and **Contact** (content column plus contact details).
* Static home page (`front-page.php`) with an editable hero, the Home page content and the latest posts.
* Customizer: **Home page hero** (heading, text, two buttons) and **Contact details** (phone, email, address, hours) shown in the footer and on the Contact page.
* `theme.json` supplies the editor colour palette and font sizes; a **Card** block style is registered for Group and Column blocks.

## Plugins

Both plugins are written from scratch: plain PHP, separate CSS and JavaScript files, no jQuery, no build step. Each has a `readme.txt` with the full detail.

**Saxon Contact Form** (`[saxon_contact_form]` shortcode or the **Contact form** block, already placed on the seeded Contact page)

* Nonce check, every field sanitized and length checked, all output escaped, Reply-To built only from validated values.
* Spam: honeypot field, signed timestamp that rejects forms sent within 3 seconds, at most 3 links, and 5 valid messages (or 20 invalid ones) per visitor per 10 minutes (IP hashed, never stored). Spam gets a fake success and is dropped.
* Email through `wp_mail()` to the addresses under Settings, Contact form (admin email by default). Use an SMTP plugin or the host's mail service so delivery is reliable.
* Optional copy of each message under **Messages** (admins only), wired into the WordPress personal data export and erase tools.
* Works without JavaScript; with it, inline validation and submit without reload.

**Saxon SEO Basics** covers only what core does not: meta descriptions, Open Graph and Twitter tags, JSON-LD (Organization, WebSite, WebPage, BreadcrumbList, BlogPosting), a per page SEO title, description and noindex box, and lastmod dates in the core sitemap. Core keeps the title tag, canonical links, robots meta and `/wp-sitemap.xml`. It switches itself off if Yoast, Rank Math, All in One SEO, SEOPress or The SEO Framework is active. Set the home page description and default share image under Settings, SEO basics.

## Local development

```
# WordPress root with the SQLite Database Integration plugin as the db.php drop in
ln -s "$PWD/wp-content/themes/saxon" /path/to/wordpress/wp-content/themes/saxon
php -S localhost:8080 -t /path/to/wordpress tools/router.php
ln -s "$PWD/wp-content/plugins/saxon-contact-form" /path/to/wordpress/wp-content/plugins/
ln -s "$PWD/wp-content/plugins/saxon-seo-basics" /path/to/wordpress/wp-content/plugins/
mkdir -p /path/to/wordpress/wp-content/mu-plugins && ln -s "$PWD/tools/dev-mail-log.php" /path/to/wordpress/wp-content/mu-plugins/
php tools/seed-content.php /path/to/wordpress --demo-contact
```

Activate both plugins under Plugins. With `dev-mail-log.php` installed, contact form emails land in `wp-content/dev-mail.log` instead of being sent.

`--demo-contact` fills the contact details with obvious placeholders; leave it off on a real site and set them in the Customizer instead.

## Deploying

Upload `wp-content/themes/saxon` to the server's `wp-content/themes/` and both folders under `wp-content/plugins/` to the server's `wp-content/plugins/` (never `tools/dev-mail-log.php`), activate the theme under Appearance, Themes and the plugins under Plugins, then assign page templates and menus (or run `wp eval-file tools/seed-content.php` after a database backup).
