# Release Process

This document describes how releases are made for the mantle-framework project.

## Overview

Releases are driven by changes to `CHANGELOG.md` on maintenance branches (e.g. `1.x`). Pushing a new version entry to the changelog automatically creates a draft GitHub release. A team member then reviews and publishes the draft, which triggers the monorepo split workflow.

## Step-by-Step

### 1. Update the Changelog

Add a new version section to `CHANGELOG.md` following the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format. Version headers must match `## vX.Y.Z` exactly, as this is what the automation uses to detect new versions:

```markdown
## v1.20.0

### Added

- Added a new feature.

### Fixed

- Fixed a bug.
```

The changelog follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html):

- **Patch** (`vX.Y.Z` → `vX.Y.Z+1`): Bug fixes and other non-breaking changes.
- **Minor** (`vX.Y.Z` → `vX.Y+1.0`): New backward-compatible features.
- **Major** (`vX.Y.Z` → `vX+1.0.0`): Breaking changes.

### 2. Push to a Maintenance Branch

Merge (or push) the changelog update to the appropriate maintenance branch, for example `1.x`. The branch name must match the glob pattern `*.x` (e.g. `1.x`, `2.x`).

### 3. Automated Draft Release Creation

Pushing to a `*.x` branch with a change to `CHANGELOG.md` (or the workflow file itself) triggers the [`release-from-changelog`](.github/workflows/release-from-changelog.yml) workflow. The workflow:

1. Extracts the most recent version number from `CHANGELOG.md` (the first `## vX.Y.Z` heading).
2. Compares it against the latest published GitHub release to determine whether a new release is needed.
3. Parses the release notes for that version from the changelog.
4. Creates a **draft** GitHub release tagged `vX.Y.Z` with the extracted notes (using `gh release create --draft`). If a release with that tag already exists the step is skipped.
5. Posts a Slack notification with a direct link to the draft release for review.

> If the workflow fails (e.g. due to an invalid version or other error), a failure notification is also sent to Slack.

### 4. Review and Publish the Draft Release

A team member opens the draft release linked in the Slack notification, reviews the notes, and clicks **Publish release** on GitHub when satisfied.

### 5. Post-Publication Automation

Publishing the release triggers two additional workflows:

#### Update Changelog ([`update-changelog.yml`](.github/workflows/update-changelog.yml))

Uses [`stefanzweifel/changelog-updater-action`](https://github.com/stefanzweifel/changelog-updater-action) to update `CHANGELOG.md` with the final release name and notes (in case the release body was edited in the GitHub UI), then commits the result back to the `1.x` branch.

#### Split Monorepo ([`split_monorepo.yml`](.github/workflows/split_monorepo.yml))

Splits each package under `src/mantle/` into its own repository under the [`mantle-framework`](https://github.com/mantle-framework) GitHub organization and applies the release tag (`vX.Y.Z`) to each split repository.

## Workflow Trigger Summary

| Workflow | Trigger | Purpose |
|---|---|---|
| `release-from-changelog.yml` | Push to `*.x` changing `CHANGELOG.md` | Create draft GitHub release |
| `update-changelog.yml` | GitHub release published | Commit final release notes back to `CHANGELOG.md` |
| `split_monorepo.yml` | Push to `main`/`*.x`/`*.*.x`, or tag `v*.*.*` | Sync packages to their split repositories |

## Changelog Format

The automation expects version headers in this exact format:

```
## vX.Y.Z
```

Any heading that does not match this pattern (e.g. `## Unreleased`) is ignored. Release notes are extracted from everything between the first version heading and the start of the next heading.

## Required Secrets

| Secret | Purpose |
|---|---|
| `ALLEY_CI_KEY` | GitHub token with write access used for checkout and monorepo splits |
| `SLACK_WEBHOOK_URL` | Incoming webhook URL for Slack release and failure notifications |
