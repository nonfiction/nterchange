# 5.0 verification

Verified on PHP 8.5.12, Composer 2.10.3 and a separately created MySQL 8.4.13
fixture. PHP probes had no external network. MySQL used an internal Docker
network, no published ports and disposable tmpfs data. No existing site
container, database, upload directory or session was used.

## Add New Content insert rejection

`php tests/page-content-insert.php` exercises the actual controller and
QuickForm with simulated persistence/auth. All 26 checks pass on PHP 8.5.12.
A false or zero insert result returns HTTP 503 with a visible error and an
editable form instead of a success redirect. Headline, body and return URL
survive for retry. Browsers cannot retain the file input, so the error asks
users to select files again. Successful saves keep their existing redirect.

The PetroChina consumer also tests an actual multipart Feature submission
against a rejecting MySQL trigger in an independent no-egress copy. No original
site database, upload, session or mail service is used for failure injection.

## Results

| Check | Result | Limit |
| --- | --- | --- |
| Clean archive `composer install` and `composer validate --strict` | Pass | Generated loaders, no captured metadata |
| `composer test` | 71 runtime, 18 audit cases, 18 pagination/MIME, 34 cache and 75 layout/resource checks pass | Audit auth/query/clock are fakes; no mail sent |
| Rendered picker scripts | Three callbacks pass | Node VM, not a browser or upload/save test |
| Synthetic MySQL/MDB2/model/Auth | 16 checks per charset pass | `latin1` and `utf8mb4`; legacy MD5 behavior preserved |
| Save as Draft byte preservation | 73 checks per charset pass | Real form/draft/model/MySQL path; synthetic rows, no HTTP or login |
| Failed form insert boundary | 22 checks per charset pass | Real SQL rejection and form/model writes; zero result simulated, file-processing calls recorded, no upload or HTTP |
| Seven isolated Composer consumers | All install and load overrides | Committed application snapshots, no site config |
| Actual application Text constructors and inherited CRUD | 13 checks for each of seven consumers in each charset pass | Synthetic schema/content/uploads, not site data |
| PHP 8.5 syntax | All 282 archived PHP files pass | Historical `test/` is excluded, not run |
| Pinned browser dependencies | Seven shipped files match CDN SHA-256 and pass Node syntax checks | Same Prototype/Scriptaculous versions, not a browser or security audit |
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

Layout checks render the actual default, plain and simple CMS heads through
NView/Smarty. They verify local dependency URLs, loading order and shipped file
bytes. Existing resource-helper warnings remain visible. Actual browser script
execution, CSP responses and application interaction still need consumer tests.

### Flat CMS presentation

`php tests/layout-branding.php` passes 106 actual NView/Smarty checks on PHP
8.5.12 without authentication, sessions or site data. It renders login/default/
plain/simple layouts, text branding, one header/navigation, the right-class
Admin link, all four Site Admin permission combinations and unchanged action
URLs. Existing Users/Templates/Assets controls remain inside their panes.
The rendered screen URL includes its file timestamp; changed layout/list
imports use `5-flat-2`. Compatibility GIF files remain shipped.

An owned, sessionless HTTP fixture also rendered the shared layout and Alaris's
committed default/navigation overrides through the normally generated Composer
loader. Chromium checks covered the 76px desktop header, right-aligned Admin,
horizontal actions, keyboard focus, 240px toolbar wrapping and long login names.
The orange action background stays `#ee7722`. The initial dark labels gave
5.06:1 contrast. Jon explicitly requested production-matching white labels and
a white/orange logo during visual review; those colors now replace the dark
variant. White button labels have only 2.88:1 contrast on this orange. Keyboard
focus outlines remain dark and visible. Body/row fonts and padding are unchanged. Narrow 480px viewports retain
the legacy 600px shell and horizontal scrolling. A 702px CSS viewport at DPR 2
checks the layout equivalent of 200% zoom on a 1404px viewport; this is not a
claim of native browser zoom-control testing or whole-CMS responsiveness.

Normal warm reload changed the rendered screen timestamp and both imported
CSS keys, replacing a cached 96px raster header with the flat 76px header.
CKEditor 4.10, the Alaris form-upload configuration and image/media picker
shells loaded in that fixture. These checks do not test site login, real
uploads, database saves or production. Retained picker/Surf to Edit tests cover
the unchanged callbacks and toolbar rules separately.

### Temporal model conditions

`php tests/conditions-database.php` passes 23 checks per charset on PHP 8.5.12
with real MDB2 and MySQL 8.4.13. Schema flags now select the existing driver
quote call for DATE, DATETIME, TIME and TIMESTAMP predicates. The test covers
negative 25-hour TIME, compact numeric DATE, soft-delete filtering, combined
predicates, explicit filters, existing SQL conditions and escaped malformed
values. Integer, decimal, text, PHP-null skip and literal-null behavior remain
unchanged. The same test fails eight checks before the correction.

The fixture uses synthetic rows in a caller-created disposable schema. No
site import, HTTP, provider, mail or timezone policy is tested here. New
Brighton's current program lookup uses explicitly quoted SQL; the reproduction
is a generic model API defect, not a demonstrated active importer failure.

The database tests exercise actual MDB2 schema inspection and model insert,
read, update, repeated SQL NULL clears, UTC conversion, soft delete and error
returns. The application integration adds the real application's Text factory
and constructor, byte round trips, updates and per-record upload cleanup. It
uses an owned temporary directory and a minimal synthetic `text` table. It does
not copy or infer the original site's schema or content.

`draft-database.php` exercises the real form processor and `AppController::saveDraft`
with MDB2 RTRIM still enabled. It bypasses route/auth construction, not form hooks
or SQL. Initial and repeated draft saves preserve every non-headline published
column, including content ending in CRLF, spaces, tabs or a NUL, literal `null`,
empty content and a non-CMS datetime. The changed headline retains quotes,
backslashes and trailing spaces; serialized drafts retain submitted bytes.
The same regression fails on the previous implementation in both charsets.
Only the immediate draft-headline write changed. Normal Save still needs
consumer HTTP tests. This test neither covers workflow nor proves browser parity.

`form-insert-database.php` rejects inserts through a trigger in its owned table.
Failed inserts return the original `false` or `0` before file processing, model
update or post-processing hooks. A posted primary key cannot overwrite the
existing synthetic row after rejection. The zero-result case is simulated;
file-processing and update call recorders forward to real methods. Successful
inserts still assign a new ID and run file processing, update and post-processing
in order. Route/auth construction is bypassed and no multipart upload is made.
Consumer HTTP tests must separately verify upload files and mail stay unchanged
on insert failure. The same regression fails against the previous core.

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
run `tests/database.php`, `tests/draft-database.php`,
`tests/form-insert-database.php`, `tests/conditions-database.php` or
`tests/consumer-database.php`.
All five refuse database names outside
`nterchange_v5_test_*`. Create fresh isolated schemas, one
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
host; tests here send no mail. A stable package release does not establish
site acceptance. Each consumer still needs functional and human review at its
installed release before production deployment.
