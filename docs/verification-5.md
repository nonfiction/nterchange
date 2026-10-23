# 5.0 candidate verification

Verified on PHP 8.5.12, Composer 2.10.3 and a separately created MySQL 8.4.13
fixture. PHP probes had no external network. MySQL used an internal Docker
network, no published ports and disposable tmpfs data. No existing site
container, database, upload directory or session was used.

## Results

| Check | Result | Limit |
| --- | --- | --- |
| Clean archive `composer install` and `composer validate --strict` | Pass | Generated loaders, no captured metadata |
| `composer test` | 71 runtime, 18 audit cases, 18 pagination/MIME and 34 cache checks pass | Audit auth/query/clock are fakes; no mail sent |
| Rendered picker scripts | Three callbacks pass | Node VM, not a browser or upload/save test |
| Synthetic MySQL/MDB2/model/Auth | 16 checks per charset pass | `latin1` and `utf8mb4`; legacy MD5 behavior preserved |
| Seven isolated Composer consumers | All install and load overrides | Committed application snapshots, no site config |
| Actual application Text constructors and inherited CRUD | 13 checks for each of seven consumers in each charset pass | Synthetic schema/content/uploads, not site data |
| PHP 8.5 syntax | All 279 archived PHP files pass | Historical `test/` is excluded, not run |
| Secret scan | Gitleaks 8.30.1 reports no findings in archive | Does not replace manual artifact review |

Runtime checks use real package classes for grouped date values, blank/nonempty
exports, sparse-key merges, comparison rules, reached widgets/signatures,
recursive deletion, template compilation/reuse, root overrides, native numeric
HTTP status and generated GIF/FreeType output. They also test YAML loading,
missing-controller lookup and the historical code-caller serializer injection
regression. The upstream advisory CVE-2015-10009 concerns versions before 4.1.1;
its original correction is already in this repository's history. One retained
regression is not a security audit.

Cache checks exercise real menu/tree writes, reads and removal, missing-file
error returns, failed writes through both the old boolean and error-object APIs,
and explicit print/debug error modes. Missing-file removal still returns its
original PEAR error; it no longer makes an illegal static call on PHP 8.5.
Existing PEAR dynamic-property deprecations remain visible in the test logs.

The database tests exercise actual MDB2 schema inspection and model insert,
read, update, repeated SQL NULL clears, UTC conversion, soft delete and error
returns. The application integration adds the real application's Text factory
and constructor, byte round trips, updates and per-record upload cleanup. It
uses an owned temporary directory and a minimal synthetic `text` table. It does
not copy or infer the original site's schema or content.

## Consumer compatibility matrix

The work record maps these labels to the seven original consumers and their
committed revisions. Their application code and generated install metadata are
private test inputs, not part of this package.

| Variant | Original connection policy | Original audit minimum | Load checks | Text CRUD in both charsets |
| --- | --- | --- | ---: | --- |
| A, pre-Composer bundled core | latin1 | 2001 | 19 pass | 13 + 13 pass |
| B, older Composer core | latin1 | current year minus five | 19 pass | 13 + 13 pass |
| C, newer Composer core | utf8mb4 | 2018 | 18 pass | 13 + 13 pass |
| D, older Composer core | latin1 | current year minus five | 19 pass | 13 + 13 pass |
| E, newer Composer core | utf8mb4 | 2001 | 19 pass | 13 + 13 pass |
| F, namespaced package layout | utf8mb4 | 2001 | 18 pass | 13 + 13 pass |
| G, limited public scope | latin1 | 2001 | 17 pass | 13 + 13 pass |

Load checks inspect the reached application controller overrides, representative
model inheritance, Composer dependency metadata and core paths. Constructors
are not run by that test. `consumer-database.php` separately constructs the
actual Text model and exercises inherited CRUD. Audit minimum policies are
covered independently by the real widget with three simulated years and the
configurable 2001, fixed and rolling lower bounds.

No seven-site upgrade or full parity claim follows from this matrix. Public
pages, authenticated HTTP routes, real picker upload/select/save, site-specific
model hooks, external integrations and each site's complete schema still need
separate adoption tests. Retired site features were not revived.

## Reproducing a clean archive install

Use PHP 8.5 with the documented extensions, GD/FreeType and Composer 2. From a
reviewed repository revision:

```sh
archive=$(mktemp -d)
git archive HEAD | tar -x -C "$archive"
cd "$archive"
COMPOSER_ROOT_VERSION=dev-main composer install --no-interaction --no-plugins --no-scripts
COMPOSER_ROOT_VERSION=dev-main composer validate --strict
composer test
php tests/pickers.php | node tests/picker-callbacks.mjs
```

`test/` and Smarty demo/unit-test directories are excluded by `.gitattributes`.
The supported tests are under `tests/`. Remove the disposable archive directory
when finished. Do not run the historical reset suite.

For a consumer, create a new private directory, copy only its committed `app/`
tree with `git archive`, and use this Composer manifest with an absolute path to
the clean package archive:

```json
{
  "name": "fixture/consumer",
  "require": {"nonfiction/nterchange": "dev-main"},
  "repositories": [{
    "type": "path",
    "url": "/absolute/package-archive",
    "options": {
      "symlink": false,
      "versions": {"nonfiction/nterchange": "dev-main"}
    }
  }],
  "autoload": {"classmap": ["app/"]}
}
```

Run Composer update with scripts/plugins disabled in that disposable consumer.
This reads the archived package manifest and generates both consumer and core
loaders. It does not use a hand-written classmap or another site's vendor tree.
Set `NTERCHANGE_TEST_CONSUMER_ROOT` and `NTERCHANGE_TEST_AUTOLOAD` to the
consumer root and its generated `vendor/autoload.php`, then run the installed
package's `tests/consumer.php`.

For database checks, also supply the `DB_*` variables listed in the README and
run `tests/database.php` or `tests/consumer-database.php`. Both refuse database
names outside `nterchange_v5_test_*`. Create fresh isolated schemas, one
`latin1` and one `utf8mb4`, with no site data. The tests create and drop their
named tables. Do not point them at an existing site's database, even a local
copy. Remove the separate database container/network and private consumers
when finished.

## Remaining gates

Reference notices and PHP 8 deprecations remain. Composer's offline advisory
and filter-list fetches are unavailable in the isolated fixture. Bundled code
is not represented as separately versioned Composer dependencies, so a clean
Composer audit would not certify it. Third-party notes describe licensing,
legacy resources and unsupported dependencies.

Legacy password/reset/session behavior, authorization, CSRF and output escaping
have not been accepted as secure. The known future PEAR timezone boundary gap
is not fixed by the year-picker change. Mail transport behavior belongs to the
host; tests here send no mail. Browser parity, human review, stable tagging,
Packagist release approval, site adoption and production deployment remain open.
