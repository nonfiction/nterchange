# Moving an existing site to 5.0

This is an adoption checklist, not permission to update a site. Work in a new,
isolated copy first. Preserve the site's database, uploads and sessions. Do not
run the historical PHPUnit reset scripts or a blind Composer update in a live
installation.

## Composer and bootstrap

1. Replace the old `nonfiction/nterchange_backend` or captured `vendor/nterchange`
   dependency with `"nonfiction/nterchange": "5.0.2"`. Use Packagist, with no
   nterchange VCS or local-path repository override. In an isolated source and
   dependency tree, run `composer update nonfiction/nterchange --no-plugins
   --no-scripts --prefer-dist` and review the generated lockfile. Preserve
   unrelated dependencies. Commit the lockfile and use `composer install` for
   subsequent installs. `dev-main` remains a development branch, not a release.
2. Remove old CMS/PEAR classmaps, eager includes and include paths from the
   consumer manifest. Remove the old AWS SDK requirement if the site uses only
   local uploads. Do not remove a separately used dependency without checking
   the site's code. Generate a new loader with Composer, not a copied classmap.
3. Require the consumer's `vendor/autoload.php` first. Define `ROOT_DIR` for the
   application, its admin `APP_DIR`, cache/upload/document roots and timezone in
   site configuration. Load core configuration by its absolute package path:
   `require NTERCHANGE_DIR . '/config/config.php';`.
4. Keep `BASE_DIR` pointing at `NTERCHANGE_DIR` if the site defines it explicitly.
   It is the core fallback, not the application root. Remove obsolete `PEAR_DIR`
   and `SMARTY_DIR` overrides or point them at the bundled runtime. Root
   application models/controllers/helpers and templates retain lookup priority.
5. Keep site classes in the consumer's own Composer classmap. Test every reached
   override against the new parent signatures. A successful autoload check does
   not prove constructor, rendering or save behavior.

No compatibility symlink is needed for PHP loading. Resource URLs are a separate
web-server contract. Keep the existing admin prefix and map the resource paths
that the site uses to the installed package's `nterchange/` directory. Existing
applications use both `/nterchange/` and `/nterchange/assets/`. Core does not
rewrite their HTML or replace site JavaScript. Preserve both mappings where
needed. Restrict PHP execution to reached generated-image endpoints.

## Policies that stay with the site

The audit picker now defaults to 2001 through next year. Set
`NConfig::$auditTrailMinYear` in site configuration before rendering when a site
has a different lower bound. An explicit value of `2018` keeps that fixed policy.
A value computed as `(int) date('Y') - 5` keeps the existing rolling five-year
policy. Do not copy a private lower bound into the package controller.

Preserve the connection charset with `DB_CHARSET`. The default is `utf8mb4`;
legacy `latin1` consumers must opt in. Set `DB_TYPE=mysqli`, not the removed PHP
mysql extension. The package does not change table collations, recode data,
normalize zero dates or change SQL mode. The fixture used MySQL 8.4 with
`NO_ENGINE_SUBSTITUTION`, matching the existing local compatibility runtime.

Keep each site's public scope, hidden content, upload models, image-field names,
cache rules and external integrations. Package picker templates no longer need
jQuery or a CDN, but an application override still wins. The package does not
replace those overrides automatically. Schema-specific picker URL mappings need
site-level select/upload/save tests. An unfinished consumer patch is not a
shared runtime fix.

## Deliberate compatibility boundaries

- The former base class named `Object` is `NObject`, because `Object` is reserved
  in PHP 8. Existing consumer classes must extend `NObject` or their current
  compatible parent. Constructors and reached parent/widget signatures use PHP
  8 contracts. No reserved-name alias is provided.
- QuickForm comparison rules keep their existing string equality and numeric
  ordering casts. Strict-looking aliases still use the historical behavior.
  Sparse numeric form keys remain keys, not reindexed list positions.
- Grouped date controls export their complete values through the form instance.
  A blank timed value remains blank; model tests cover repeated SQL NULL clears.
- `NView::render(['status'=>404])` uses PHP's native numeric HTTP status API.
  It no longer loads the obsolete PEAR HTTP header parser.
- Core JSON output uses native `json_encode`. The unused `Services_JSON` copy is
  removed. Code that instantiated that class must use PHP's JSON functions.
- Bundled PEAR supports the reached Auth/MDB2, QuickForm, Date, Cache Lite,
  file logging and MIME/PHP-mail paths. It is not a general PEAR distribution.
  Installers, alternate database drivers, CAPTCHA, hierarchy widgets, spreadsheet
  export and unused mail transports are outside this release's support.
- The old AWS SDK is not installed. Legacy S3/mirror adapter source remains for
  reference, but remote upload/mirroring is unsupported and untested. Local
  upload configuration remains site-owned. No provider was contacted.
- Site-only PHPMailer, feed, stock, subscription and other integrations are not
  imported into this package. Retired features stay retired.

## Gates before adoption

Use the clean-install tests, then run the site's actual public and authenticated
flows against disposable data. Check rendered content and assets, login/logout,
page/user CRUD, timed NULL/nonempty saves, draft publication, picker callbacks
and URL persistence, uploads and mail capture/failure. Check the site's original
rows/files/sessions before and after cleanup. Do not infer site parity from the
shared synthetic database fixture or class inheritance checks.

PHP 8 notices/deprecations and legacy security debt remain. In particular, this
release preserves legacy password/reset behavior and does not claim a new
CSRF, authorization, escaping or session-security review. The historical PEAR
future-timezone boundary discrepancy also remains. Full security acceptance,
human parity and production deployment need separate review.
