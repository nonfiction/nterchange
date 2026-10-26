## 5.0.1 (2026-10-26)

* Keep rejected Add New Content submissions on an editable form with HTTP 503 and a visible error instead of a success redirect. Ask users to reselect files before retrying.
* Preserve blank date selections when redisplaying forms without changing explicit midnight values.

## 5.0.0 (2026-10-26)

* Publish the shared PHP 8.5 runtime as `nonfiction/nterchange` on Packagist.
* Keep site overrides, bundled Smarty and the curated PEAR runtime.
* Include the reviewed form, draft, timed-date, cache, editor and CMS layout fixes.
* Require separate site acceptance and production deployment review. See the migration and verification notes under `docs/`.

## 4.0.0

* Reboot repository. Remove old SQL, passwords, deprecated files

## 3.3.0

* Many changes to support grids
* NModel now implements `Array Access`
* Update bootstrap to 3.1
* Test suite refactored

## 3.2.0

* Update to CKEditor 4.0
* Config class with static properties added as an alternative to constants for configuration

## 3.1.22

* NAsset - dynamic coffeescript & less compiling
* NModel::$_delete_uploads flag on model to delete upload files along with records
