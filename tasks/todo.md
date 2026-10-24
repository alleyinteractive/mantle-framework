# #812 Capstone — Drop wordpress-autoloader, go fully PSR-4 (`src/Mantle/`)

Branch: `feature/psr-4-drop-wordpress-autoloader` off `1.x`.

## Goal
- Rename `src/mantle/` → `src/Mantle/`, PascalCase every package dir to match its namespace
  (`http-client` → `Http_Client`, `rest-api` → `REST_API`, etc.). Leave `testing-dependencies`
  lowercase (no namespace / no classes).
- Collapse root autoload to a single `"Mantle\\": "src/Mantle/"` (truest PSR-4, Laravel-style).
- Remove `alleyinteractive/composer-wordpress-autoloader` everywhere (root + 29 packages +
  monorepo-builder DATA_TO_APPEND), and the `extra.wordpress-autoloader` blocks.
- Published split repos stay lowercase-hyphenated — `split_monorepo.yml` derives the repo name
  from the PascalCase dir via `tr '[:upper:]' '[:lower:]' | tr '_' '-'`.

## Tasks
- [ ] A. `git mv` rename every package dir to PascalCase (two-step temp, case-safe) + parent → `src/Mantle`.
- [ ] B. Root `composer.json`: single psr-4 map, fix `files`/`exclude-from-classmap` paths, drop autoloader from require + allow-plugins + extra.
- [ ] C. 29 package `composer.json`: drop autoloader from require + allow-plugins; drop `extra.wordpress-autoloader` (framework-views, testing-dependencies).
- [ ] D. Path refs → `src/Mantle/<Pkg>`: phpstan.neon, phpstan-baseline.neon, rector.php, phpcs.xml, monorepo-builder.php, bin/docblock.php, tests/* (2), workflows (2).
- [ ] E. monorepo-builder.php: remove autoloader from DATA_TO_APPEND.
- [ ] F. split_monorepo.yml: add dir→lowercase repo-name transform step.
- [ ] G. CHANGELOG Unreleased entry.
- [ ] H. Verify: `git ls-files`, `composer update`, `composer dump-autoload --optimize --strict-psr`, `composer lint`, runtime reflection, `composer validate --strict` + `monorepo-validate`.

## Review — COMPLETE & VERIFIED
- A ✅ All package dirs PascalCased (`Http_Client`, `REST_API`, `Testing_Dependencies`, …) +
  parent `src/Mantle/`. Done via commit-between method (zsh `while read`, `core.ignorecase=false`)
  after the case-insensitive FS reverted the naive approach. 667 renames, disk == index.
- B ✅ Root composer.json: single `"Mantle\\": "src/Mantle/"` map; `files`/`exclude-from-classmap`
  paths fixed; autoloader removed from `require` + `extra`. KEPT in `allow-plugins` (it's a
  transitive dep of wp-caper/logger/wp-match-blocks/wp-plugin-loader; a blocked plugin breaks install).
  Added `Testing/wp-unittestcase.php` (a require_once shim) to `exclude-from-classmap` → zero
  package-level strict-psr violations.
- C ✅ All 29 package composer.json: autoloader removed from require + allow-plugins + extra.
- D ✅ Path refs updated everywhere (phpstan/baseline, rector, phpcs, monorepo-builder, bin, tests, workflows).
- E ✅ monorepo-builder DATA_TO_APPEND no longer appends the autoloader.
- F ✅ Split workflows now build the matrix from PSR-4 dir basenames (packages-json returns the
  *composer name*, which no longer matches the dir); repo name derived via `tr A-Z a-z | tr _ -`.
  Simulated all 29: each maps to a valid dir AND the correct lowercase published repo.
- G ✅ CHANGELOG: all per-batch PSR-4 bullets squashed into one entry.
- H ✅ phpcs ✓, phpstan ✓ (no baseline change), rector ✓, composer validate ✓, monorepo-validate ✓,
  all 29 package validates ✓, runtime reflection loads from src/Mantle/<Pascal> ✓, workflow YAML valid ✓.
