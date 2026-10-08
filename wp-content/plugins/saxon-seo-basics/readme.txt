=== Saxon SEO Basics ===
Contributors: William Leonard, Saxon Enterprises, Inc.
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

The SEO pieces WordPress core leaves out, and nothing more. Author: William Leonard, Saxon Enterprises, Inc.

== What core already does (left alone) ==

* `<title>` through the theme's title-tag support.
* `rel="canonical"` on single posts and pages.
* Robots meta (`max-image-preview:large`, and noindex when "Discourage search engines" is on or on search results).
* The XML sitemap at `/wp-sitemap.xml` and its line in `robots.txt`.

== What this plugin adds ==

* Meta description: per page field, else the manual excerpt, else the start of the content. The home page uses the setting under Settings, SEO basics, else the tagline.
* Open Graph and Twitter card tags (title, description, URL, image with size and alt, article dates). Image order: featured image, default share image, custom logo, site icon.
* JSON-LD `@graph`: Organization (logo, phone and email with the Saxon theme's Customizer details as fallback, social profiles), WebSite with search, WebPage (ContactPage and AboutPage on those templates), BreadcrumbList, and BlogPosting for posts.
* Per page "Search and sharing" box: SEO title, meta description, and a noindex switch that also removes the page from the core sitemap.
* `rel="canonical"` on the posts page and archives, which core leaves without one.
* Sitemap tweaks: last modified dates on entries, and the author sitemap removed (filter `saxon_seo_keep_user_sitemap` to keep it).

If Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework is active, this plugin prints nothing on the front end so tags are never duplicated.

== Hooks ==

* `saxon_seo_context` filter: title, description, URL and image for the current request.
* `saxon_seo_schema_graph` filter: the JSON-LD nodes.
* `saxon_seo_conflict` filter: force the conflict check on or off.

== Uninstall ==

Deleting the plugin removes its settings and every page's SEO fields. Deactivating keeps them.
