---
name: migrate-mantle-package-to-psr4
description: Use when converting a mantle-framework monorepo package under src/mantle/* to PSR-4 file naming (issue #812) — renaming class-*.php / trait-*.php / interface-*.php files and lowercase directories so they match their declared class/trait names. Symptoms: "convert <pkg> to PSR-4", "PSR-4 file rename", continuing #812 / following PR #891.
---

# Migrate a mantle-framework package to PSR-4

## Overview

mantle-framework is a monorepo (`src/mantle/*`). Today classes load through the
`alleyinteractive/wordpress-autoloader` catch-all in the **root** `composer.json`
(`extra.wordpress-autoloader` → `Mantle\ → src/mantle/`), which resolves a class
**only** by looking for a `class-<hyphenated>.php` / `trait-` / `interface-` file.

**Core principle:** Renaming files to PSR-4 (`Class_Name.php`) makes the WordPress
autoloader unable to find them, so each migrated namespace **must** be registered
in Composer's native PSR-4 in **two places**: the root `composer.json` (resolves
the monorepo) **and** the package's own `composer.json` (resolves the split/standalone
package). The catch-all stays and keeps serving un-migrated packages.

**Class and trait names never change** — only file/directory names. So it is never
a breaking change, and **no consumer edits are needed** (everything refers to classes
by name, not path).

## When to use / not

- Use for any `src/mantle/<pkg>` package still using `class-*.php` naming under #812.
- The reference implementation is PR #891 (testing); #893 batched 7 leaf packages.
- Do **not** do the root `src/mantle/ → src/Mantle/` PascalCase rename here — that is
  the separate capstone task at the end of #812.

## Procedure (per package)

1. **Survey.** For every PHP file, get the declared symbol and namespace:
   `grep -rEn "^(final |abstract )?(class|trait|interface|enum) |^namespace " src/mantle/<pkg>/`
   The target filename is the **exact declared name** + `.php` (`class Log_Manager` →
   `Log_Manager.php`; `trait WordPress_Action` → `WordPress_Action.php`).

2. **Confirm no explicit requires** of the files being renamed (autoloading aside):
   `grep -rEn "require|include" src/mantle/<pkg>/` — comment hits are fine. Also grep
   the repo for the old basenames to confirm nothing references the paths.

3. **Rename files** with `git mv class-<x>.php <Declared_Name>.php`.

4. **Rename nested namespace directories to PascalCase**, two-step on macOS (the FS is
   case-insensitive; `git add -A` silently reverts case-only renames):
   `git mv src/mantle/<pkg>/sub src/mantle/<pkg>/Sub-tmp && git mv …/Sub-tmp …/Sub`.
   PSR-4 maps `Mantle\Pkg\Sub` → `Sub/` exactly, so casing must match.

5. **Package `composer.json`** — *replace* the wordpress-autoloader block with PSR-4
   (don't just delete it). Keep any existing `autoload.files`:
   ```json
   "autoload": { "psr-4": { "Mantle\\Pkg\\": "./" } }
   ```
   Leave `alleyinteractive/composer-wordpress-autoloader` in `require`/`allow-plugins`
   (removed only in the capstone).

6. **Root `composer.json`** — add the namespace to `autoload.psr-4` (alphabetical):
   `"Mantle\\Pkg\\": "src/mantle/<pkg>/",`

7. **`phpcs.xml`: no change needed.** Lines 34-35 already globally exclude
   `WordPress.Files.FileName.NotHyphenatedLowercase` and `InvalidClassFileName`.

8. **`rector.php` / `phpstan.neon`:** edit only if they reference a renamed path
   (`grep -n "mantle/<pkg>"`). Whole-directory excludes (e.g. `src/mantle/blocks`)
   don't change since the directory name is unchanged in this phase.

9. **CHANGELOG.md** — add the package to the Unreleased PSR-4 bullet.

## Verification (run all)

```bash
git ls-files src/mantle/<pkg>            # confirm PascalCase dirs + no class-*.php left
composer dump-autoload --optimize --strict-psr
composer lint                            # phpcs + phpstan + rector; must exit 0
```
`composer lint` is the CI lint gate (phpcs + phpstan + rector). Run it whole, not
just phpcs.

**CRITICAL — match CI's tool versions before trusting phpstan; do NOT regenerate
the baseline.** The lint CI job installs deps with `composer update`, so it runs
the **latest stable** phpstan/rector (e.g. phpstan 2.2.x), *not* whatever your
`composer.lock` pins (the root lock is gitignored and often stale). A rename is
logic-free, so it cannot add or remove phpstan errors — `phpstan-baseline.neon`
should need **no change**. If `composer phpstan` shows errors locally, your local
phpstan is older than CI's and is producing phantom errors. **Fix your
environment, not the baseline:** run `composer update` first so local phpstan
matches CI, then re-run. Regenerating the baseline against a stale local phpstan
adds ignore-patterns CI's newer phpstan never emits, and CI fails them via
`reportUnmatchedIgnoredErrors` ("Ignored error pattern … was not matched").
Then prove runtime resolution loads from the **new** path:
```bash
php -r 'require "vendor/autoload.php"; $r=new ReflectionClass("Mantle\\Pkg\\Class_Name"); echo $r->getFileName(),"\n";'
```

**Expected benign `--strict-psr` warnings** (not caused by your change — do not chase):
un-migrated packages still using `class-*.php` (e.g. `Mantle\Testkit\*`), test
fixtures under `Mantle\Tests\`, and the intentional global shim
`src/mantle/testing/wp-unittestcase.php`. Your migrated package must produce **zero**
new violations. PHPUnit needs Vagrant/WordPress — defer the full matrix to CI; this is
a rename-only change.

## Finishing up (only after verification passes)

Each batch is a PR **stacked on the previous batch's branch** (not `1.x`), so the
diff stays scoped to this batch. Create the PR and update the issue only once all
checks above are green:

1. **Open the PR against the previous batch's branch** as base (e.g. batch-2's base
   is `feature/psr-packages-batch-1`; the first batch bases off `1.x`):
   ```bash
   git push -u origin <this-branch>
   gh pr create --base <previous-batch-branch> --head <this-branch> \
     --title "Convert <pkgs> to PSR-4" --body "<summary + verification + Part of #812>"
   ```
   The PR body should list the packages, note class/trait names are unchanged (not a
   breaking change), and summarize the verification run.
2. **Update issue #812** — comment on the issue noting which packages this PR
   migrated and link the PR, so the running checklist stays current:
   `gh issue comment 812 --body "..."`. Check/tick the package list in the issue body
   if it has one (`gh issue edit 812`).
3. When the base PR later merges, retarget this PR's base to `1.x` (or let GitHub do
   it automatically) — don't merge out of order.

## Common mistakes

| Mistake | Fix |
|---|---|
| Only deleting the package's `wordpress-autoloader` block | Also **add** `autoload.psr-4` to that package's `composer.json` |
| Registering PSR-4 in root only (or package only) | Both are required — root resolves the monorepo, package resolves the split |
| Leaving a nested dir lowercase (`events/`) | PSR-4 needs `Events/`; `--strict-psr` will flag it and the class won't load |
| Editing `phpcs.xml` to add an exclude | Unnecessary — FileName rules are globally excluded (lines 34-35) |
| Editing class/namespace declarations or call sites | Names don't change; renaming is files/dirs only, no consumer edits |
| Panicking at `--strict-psr` warnings | Only *new* violations from your package matter; testkit/Tests/wp-unittestcase are expected |
| `git mv events Events` in one step on macOS | Two-step via a temp name, then re-audit with `git ls-files` |
