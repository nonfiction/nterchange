# nterchange 5.0

Shared runtime for existing nterchange sites on PHP 8.5. This repository is a
library, not a complete website. Version 5.0 keeps the global CMS classes,
model/controller factories, QuickForm widgets and Smarty 2 templates. It carries
the PHP 8 fixes tested in existing consumers without importing their content,
configuration or site-specific models.

## Installation

Require the stable release through [Packagist](https://packagist.org/packages/nonfiction/nterchange):

```json
{"require": {"nonfiction/nterchange": "5.0.0"}}
```

No custom Composer repository is needed. Commit the generated `composer.lock`
and deploy with `composer install`, not `composer update`. Packagist supplies
version metadata; GitHub supplies the package archive.

Publishing a release does not authorize a site upgrade or deployment. See the
[migration notes](docs/migration-5.md) before replacing an existing vendor tree.

## Requirements

- PHP 8.5 with ctype, curl, fileinfo, GD, iconv, mysqli, session and SimpleXML.
- GD built with FreeType for generated CMS button text. The font and GIF parts
  remain in `nterchange/images/otf/`.
- MySQL 8.4 through mysqli. Preserve each site's connection charset explicitly.
- Composer 2. Tests use Composer 2.10.3 and PHP 8.5.12. Other PHP minor versions
  are not claimed as supported.
- A local mail transport configured by the host when a site sends mail. PHP 8.5
  may treat sendmail exit 75 as accepted; the host must normalize temporary
  transport failures to a failing exit code. This package installs no mail
  service and contacts no provider during its tests.

Core dependencies are bundled with their existing licenses. `compat/pear/` is a
curated PHP 8 runtime, not a PEAR installer or a replacement for every historical
PEAR package. [Third-party notes](THIRD_PARTY.md) list provenance and omissions.
The obsolete AWS SDK and PHPUnit 3 dependencies are no longer installed.

## Bootstrap and package paths

Consumers require their Composer-generated `vendor/autoload.php` before site
configuration. Autoloading defines `NTERCHANGE_DIR` and loads Smarty, but starts
no session and makes no database, HTTP or mail calls. Site configuration still
owns `ROOT_DIR`, admin routing, cache/upload paths, timezone and environment.

`NTERCHANGE_DIR` is the package directory regardless of its Composer install
path. The installed package is normally `vendor/nonfiction/nterchange`.
Application model/controller/helper overrides retain priority through the
existing `ROOT_DIR` lookup. Package templates remain the fallback. Do not retain
an older CMS or PEAR copy earlier in the include path.

Static resources remain under `nterchange/`. The web server must map the site's
existing resource URLs to that directory. Both `/nterchange/...` and
`/nterchange/assets/...` occur in consumers. Do not expose the package root,
`compat/`, tests or dependency demos as a public document root. Allow PHP only
for the generated-image endpoints that a site actually uses.

## Tests

From a checkout, using PHP 8.5 and Composer 2:

```sh
COMPOSER_ROOT_VERSION=dev-main composer install --no-interaction --no-plugins --no-scripts
composer test
```

The scripts exercise real package classes and templates, form/date export and
validation, sparse-key merges, pagination, MIME creation, generated image bytes,
controller overrides and the historical code-caller injection regression. Run
`php tests/pickers.php | node tests/picker-callbacks.mjs` for rendered picker
callback checks. Node.js is a test tool, not a production PHP requirement. Audit
rollover tests use a namespaced clock and fake auth/query/output. MIME tests do
not send mail. Legacy reference notices and deprecations remain; tests are not a
warning-free or security-audit claim.

Real MySQL checks are separate because they write test data. Create an isolated
schema named `nterchange_v5_test_*`, then set `DB_TYPE=mysqli`, `DB_SERVER`,
`DB_SERVER_USERNAME`, `DB_SERVER_PASSWORD`, `DB_DATABASE` and `DB_CHARSET` before
running `php tests/database.php`. It refuses other schema names. It creates and
drops only its two named tables, tests actual model CRUD and Auth password
matching, and opens no session. Run against both `latin1` and `utf8mb4` schemas.
Never point it at a site database.

To test an installed copy rather than a checkout, set
`NTERCHANGE_TEST_AUTOLOAD=/absolute/consumer/vendor/autoload.php` for the same
scripts. The historical `test/` PHPUnit suite is not the supported runner and
can reset a database. Do not run it against an existing installation. It is
excluded from release archives.

See [verification](docs/verification-5.md) for clean-install commands and exact
coverage. Full security review, browser parity, site adoption and production
approval remain separate work.
