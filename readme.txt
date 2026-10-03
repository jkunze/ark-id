=== ARK Identifier Resolver ===
Contributors: josefrankpl-hue, jkunze
Tags: ark, identifiers, resolver, redirects, permalink
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Resolves ARK-style identifiers in the format /ark:/xxxxx/name and /ark:/xxxxx/name/qualifier.

== Description ==

ARK Identifier Resolver provides a WordPress solution for mapping ARK identifiers to final URLs using either:

* an embedded page that keeps the ARK URL in the browser, or
* native HTTP redirects (302 or 303).

This plugin was created by Josefrank Pernalete Lugo (josefrankpl-hue).
It is useful for persistent identifiers, scholarly metadata links, and legacy URL aliasing.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ark-id/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings → ARK Resolver.
4. Add your ARK mappings using the form.

== Frequently Asked Questions ==

= What URL format does the plugin support? =

The plugin supports URLs in the form:

* `/ark:/12345/example-name`
* `/ark:/12345/example-name/qualifier`

= Can I keep the ARK URL visible in the browser? =

Yes. Choose the embedded-page mode from the admin settings.

= Can I redirect with HTTP 303? =

Yes. Choose the redirect mode and select 302 or 303.

== Changelog ==

= 2.1.0 =
* Initial public release.

== License ==

This plugin is licensed under the MIT License.
