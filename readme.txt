=== One NGO Fundraising ===
Contributors: uppfy
Donate link: https://1-ngo.uppfy.com
Tags: donations, fundraising, nonprofit, campaigns, events
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serve 1 NGO donate pages, campaigns, events, and stories on your WordPress site. No 1 NGO DNS records required.

== Description ==

1 NGO is a fundraising workspace for nonprofits. This plugin is an interface to that service: keep your existing WordPress site and domain, while campaigns, events, and stories stay in 1 NGO.

Dedicated plugin pages (Add donation / campaigns / events / stories, plus virtual singles such as `/campaigns/your-slug`) take over the WordPress content area with the full 1 NGO layout. Your theme header and footer stay. Shortcodes, the Gutenberg block, and the Elementor widget remain compact widgets you can drop onto any other page.

A page is only visible on WordPress after **Publish on WordPress** in the 1 NGO editor. Publishing on a 1 NGO domain or Main Website URL is separate.

= Service =

This plugin talks to the 1 NGO API (the URL you paste from Integrations → WordPress) and loads public fundraising pages from https://1-ngo.uppfy.com. Card checkout uses Paystack, then returns donors to your WordPress URLs.

Terms of use: https://uppfy.com/1-ngo/terms-of-use
Privacy policy: https://uppfy.com/1-ngo/privacy-policy
Setup guide: https://1-ngo.uppfy.com/docs/integrations/wordpress

= Install =

1. In 1 NGO go to Integrations → WordPress and download the plugin zip, or install from this directory when listed.
2. Copy Organization ID, API URL, and a read-only token.
3. Paste them into Settings → 1 NGO and Save.
4. Set parent slugs (for example `/donate`, `/campaigns`, `/events`, `/blogs`) and click Add donation page / Add campaigns page.
5. Return to 1 NGO and click Confirm connection.
6. In each editor, click Publish on WordPress.

= Shortcodes =

`[1ngo donate]`
`[1ngo campaigns limit="3"]`
`[1ngo campaign slug="clean-water"]`
`[1ngo events limit="3"]`
`[1ngo event slug="gala"]`
`[1ngo stories limit="3"]`
`[1ngo story slug="field-update"]`

= Data the plugin sends =

Connecting the plugin (saving a token and clicking Save) is consent to use the 1 NGO service. The plugin then sends:

* Your WordPress site URL and public slug map, so share links and checkout can return here.
* The read-only token and Organization ID, only to the API URL you configure.

It does not inject credits or “powered by” links on the public site. It does not load admin screens inside an iframe. Uninstall removes the saved token and settings; WordPress pages you created are left in place.

Source: https://github.com/juppfy/one-ngo-wp-plugin

== Installation ==

1. Install and activate the plugin.
2. Open Settings → 1 NGO.
3. Paste Organization ID, API URL, and the read-only token from https://1-ngo.uppfy.com/settings/integrations/wordpress
4. Save, add pages, then confirm the connection in 1 NGO.

== Frequently Asked Questions ==

= Do I need to point DNS at 1 NGO? =

No. The plugin serves donate, campaigns, events, and stories on this WordPress site.

= Can the token change my 1 NGO dashboard? =

No. The WordPress plugin token is read-only except for reporting this site’s public URL map.

= Where are the docs, privacy policy, and terms? =

Docs: https://1-ngo.uppfy.com/docs/integrations/wordpress
Privacy: https://uppfy.com/1-ngo/privacy-policy
Terms: https://uppfy.com/1-ngo/terms-of-use

== Changelog ==

= 1.2.4 =
* Public source repository URL, contributor list, uninstall transient key validation, and Tested up to 7.1 for WordPress.org review.

= 1.2.3 =
* Official plugin name is One NGO Fundraising. Text domain and zip folder are one-ngo-fundraising.

= 1.2.2 =
* Plugin Check fixes: ordered translation placeholders, valid plugin header name, sanitized request paths, no direct database deletes, and WordPress.org translation loading.

= 1.2.1 =
* Settings links for docs, privacy, and terms. Plugin icon branding. Token is no longer printed back into the settings form. Height messages from embeds only apply from the 1 NGO origin.

= 1.2.0 =
* Dedicated 1 NGO pages now serve the full donate/campaign/event/story layout (theme header and footer stay). Shortcodes remain widgets.

= 1.1.0 =
* WordPress public URL map, Add page actions, native indexes/singles, and in-page checkout.

= 1.0.0 =
* Initial release.
