# chyly-gdpr-cookies

Wordpress plugin to help with GDPR compliance.

## Installation

1. Download latest release from [here](https://github.com/tomaschyly/GdprCookies/releases)
2. Unzip to your WP plugins folder.
3. Active the plugin, then you can go to it's settings page wher you can use various options.

## Important Notes

This plugin was developed and tested using **Wordpress 4.9-5.2** with **PHP 5.5 - 7.1**.

It should work with various scripts, e.g. Google Analytics, Facebook, etc. Some of this scripts may need small modification that any Web Developer should have no issue implementing.

This plugin is not designed to be used by someone with no Web Development experience.

## How to Use

There are 2 filters for scripts, **Analytics** and **Marketing**. They work depending on User's consent.

This plugins has support for **GA Google Analytics plugin**, so if you use it, you can just enable filter for it.

**Analytics Scripts** and **Marketing Scripts** are fields where you write script identifiers, one per line, and it will filter this scripts. They need to be aded using **wp_enqueue_script**.

**Custom Analytics Scripts** and **Custom Marketing Scripts** fields enable you to add scripts from admin, you can use it for e.g. GA or FB Pixel scripts.

There is option to use GeoIp service which will show the consent only to EU countries. Others will be treated as if they gave full consent.
