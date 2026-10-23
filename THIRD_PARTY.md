# Third-party runtime and resources

Core nterchange code retains the PHP License 3.01 in `LICENSE`. Bundled code
keeps its own copyright notices and licenses. The root Composer license does
not relicense these dependencies.

## PHP runtime provenance

`compat/pear/` is a selected runtime subset of the historical
`nonfiction/nterchange_pear` tree at `4c12e16`, with the PHP 8 compatibility
changes already tested by existing nterchange consumers. It is bundled here so
a clean install does not fetch an unpatched dependency. It includes PEAR/PEAR5,
Auth with its MDB2 container, MDB2 and MySQL modules, Date, HTML QuickForm,
Cache Lite, file logging, Net URL, Text Password and MIME/PHP-mail support.
It is not a PEAR installation tool or a complete package repository.

- PEAR 1.9.4, Date, MDB2, Mail MIME and Net URL keep their BSD notices in source.
- Auth 1.5.4 and HTML QuickForm 3.2.9 retain PHP License 3.01 notices. The root
  `LICENSE` supplies that text. Text Password identifies PHP License 3.0;
  `licenses/PHP-3.0.txt` supplies that text.
- Mail's older PHP License 2.02 notice remains in source; the license text is in
  `licenses/PHP-2.02.txt`.
- Cache Lite is LGPL 2.1. Its original upstream license is copied to
  `licenses/LGPL-2.1.txt`. Upstream Cache_Lite 1.7.11 package metadata confirms
  that license. Its existing authorship notice remains in source.
- Log's MIT license is in `licenses/Log-MIT.txt`, copied from upstream pear/Log.
  Historical Log 1.12.7 package metadata also identifies MIT. Existing source
  identifies Chuck Hagenbuch and Jon Parise.

The bundled PHP 8 changes cover constructors, static APIs, reached signatures,
removed PHP syntax/functions and existing error-return contracts. Mail/MIME's
ported subset retains binary attachments, multipart messages and UTF-8 header
encoding. Untested PEAR tools and adapters are not added merely to reproduce an
old installation tree. SMTP/sendmail drivers, alternate Auth containers,
CAPTCHA, spreadsheet export and the PEAR installer are outside this candidate's
support. The PHP-mail transport is included; tests compose MIME and exercise
error paths but send no message.

## Smarty and YAML

`vendor/Smarty/` is the existing bundled Smarty 2 library, with its LGPL text in
`vendor/Smarty/COPYING.lib`. PHP 8 changes replace removed constructors,
`each()` and evaluated regex replacements. `vendor/SmartyPaginate/` retains its
LGPL 2.1-or-later notice; its existing static API is now declared static. The
same LGPL text is also in `licenses/LGPL-2.1.txt`. Modified library files carry
a dated compatibility notice. Complete modified source remains distributed.

`vendor/spyc.php` retains Chris Wanstrath's 2005 MIT notice. The license text is
in `licenses/Spyc-MIT.txt`. Its small existing YAML API remains; the 5.0 port
updates constructor/static/offset syntax and initializes parse state. This is
not a new YAML parser implementation or a claim of complete YAML support.

## Existing browser resources

`nterchange/` retains the repository's existing browser resources, images and
fonts. CKEditor and its bundled plugins keep their license files in place.
Resource licensing and legacy browser-library security still need review before
a stable release. Do not serve demo/test directories or expose the package root.

The CMS head serves the same Prototype 1.7.3 and Scriptaculous 1.9.0 versions
previously requested from cdnjs, now under local versioned JavaScript paths.
Code bytes and dependency order are unchanged. No CDN allowance is needed in
CSP. This is an availability fix, not a library upgrade or security review.
Older root-level browser copies remain untouched.

- `prototype/1.7.3/prototype.js` is copied from
  <https://cdnjs.cloudflare.com/ajax/libs/prototype/1.7.3/prototype.js>.
  Its copyright notice remains in the file. `licenses/Prototype-MIT.txt` is
  the upstream LICENSE text from Git blob
  `05789cf01905528ef9d9eda7be54c61dda350fdc` in `prototypejs/prototype`.
- `scriptaculous/1.9.0/` contains `scriptaculous.min.js`, `builder.min.js`,
  `effects.min.js`, `dragdrop.min.js`, `slider.min.js` and `controls.min.js`,
  copied from <https://cdnjs.cloudflare.com/ajax/libs/scriptaculous/1.9.0/>.
  `licenses/Scriptaculous-MIT.txt` is copied from the upstream
  [v1.9.0 MIT-LICENSE](https://github.com/madrobby/scriptaculous/blob/v1.9.0/MIT-LICENSE).

Paths above are relative to `nterchange/javascripts/`. The retained
`tests/layout-assets.php` checks each shipped file's SHA-256 against its pinned
CDN bytes and renders all three CMS head variants through real NView/Smarty.

The old `vendor/s3.class.php` and remote adapter source remain historical code,
not tested provider support. The obsolete AWS SDK dependency is no longer
installed. No remote provider is called by the retained package tests.
