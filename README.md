# saxonwordpress.com

Custom WordPress theme and tooling for saxonwordpress.com.

Author: William Leonard, CTI Global, bill.leonard@cticorp.com

## Layout

```
wp-content/themes/saxon/   Custom "Saxon" theme (copy this folder to the server)
tools/seed-content.php     Creates the starter pages, menus and reading settings
tools/router.php           Router for PHP's built in server (local testing only)
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

## Local development

```
# WordPress root with the SQLite Database Integration plugin as the db.php drop in
ln -s "$PWD/wp-content/themes/saxon" /path/to/wordpress/wp-content/themes/saxon
php -S localhost:8080 -t /path/to/wordpress tools/router.php
php tools/seed-content.php /path/to/wordpress --demo-contact
```

`--demo-contact` fills the contact details with obvious placeholders; leave it off on a real site and set them in the Customizer instead.

## Deploying

Upload `wp-content/themes/saxon` to the server's `wp-content/themes/`, activate it under Appearance, Themes, then assign page templates and menus (or run `wp eval-file tools/seed-content.php` after a database backup).
