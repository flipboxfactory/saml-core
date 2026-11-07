# saml-core

Composer library (not a Craft plugin) shared by `flipboxfactory/saml-sp` and `flipboxfactory/saml-idp`. Changes reach those plugins only after a release and a constraint bump there, so treat public classes and method signatures as API: breaking them needs a major version.

## Security first

These are commercial SSO/identity plugins that customers buy to protect their Craft sites. A bug here can mean an authentication bypass or a site-wide outage. Security and code quality take priority over speed, convenience and scope.

- **Fail closed.** On any parse, signature, decryption or validation error, reject the message. Never fall back to accepting unsigned, unencrypted or unvalidated data.
- **Never weaken a check to make something work**: signature/destination/audience/time validation, algorithm blacklists, size limits, or tests. If a check gets in the way, stop and raise it.
- **Treat all SAML input as hostile**: query/POST parameters, XML, metadata and RelayState. Bound sizes, keep XXE protections, and don't redirect to unvalidated URLs.
- **Never log or expose secrets**: private keys, decrypted assertions, session or security keys. That includes exception messages and debug output.
- **Dependencies**: run `composer audit` and check GitHub advisories before adding or bumping one. Prefer the smallest patched version and justify any new dependency.
- **Security fixes** ship as a patch release with a regression test (one that fails without the fix) and a CHANGELOG `## Security` entry naming the advisory (GHSA/CVE).
- When unsure how the SAML spec, saml2 or xmlseclibs behaves, verify it against the source or the spec rather than guessing, and say what you couldn't verify.

## Before every commit

```bash
composer check   # ECS + PHPStan + Codeception; must pass
composer fix-cs  # auto-fix style, then re-run check
```

Tests need MySQL and `tests/.env` (see README "Development").

## Rules

- Add or update tests in `tests/unit/` for every behaviour change or bug fix. Bug fixes get a test that fails without the fix.
- Don't lower the PHPStan level or add a baseline. Use an inline `@phpstan-ignore <identifier>` with a reason only for genuine false positives.
- Keep style-only changes (`fix-cs`) in their own commit, separate from logic changes.
- Don't commit generated or local files: `tests/.env`, `tests/_output`, `tests/_support/_generated`, `vendor/`, `composer.lock`.
- `simplesamlphp/saml2` is the only SAML dependency. Stay on `~4.19.x` for 5.x: 4.20+ and v6 need a Composer plugin (Composer 2.9+) that would break site updates; see CHANGELOG 5.0.11.

## Workflow

- Default branch is `master`. Work on a branch and open a PR; CI (`.github/workflows/ci.yml`) must be green.
- **PR titles and commit messages use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)**: `<type>(<scope>): <description>`, e.g. `fix(sso): ...` (see `CONTRIBUTING.md`). Always title PRs this way: the title is the squash-merge commit and decides the release. Pick the type by what users get: `fix:` for a bug or security fix, `feat:` for new behaviour, `!` for a breaking change to public API or config; otherwise a non-releasing type. If unsure whether a change should release, ask.
- SemVer from the PR title: `fix:`/`perf:` → patch, `feat:` → minor, `<type>!:` or a `BREAKING CHANGE:` line in the PR body → major. `docs`, `test`, `ci`, `chore`, `build`, `refactor`, `style` and `revert` don't release. A releasing PR adds its notes at the top of `CHANGELOG.md` under `# Unreleased` (with `## Security|Fixed|Added|Changed|Removed`; `# Unreleased [CRITICAL]` for a critical release). Don't touch `composer.json` `version` or write version headings. The "Release check" CI job enforces this.
- Merging to `master` releases once checks and tests pass: CI renames `Unreleased` to `# X.Y.Z YYYY-MM-DD`, sets the `composer.json` version, pushes a `chore(release): X.Y.Z` commit and the unprefixed tag (`5.0.11`) as the release GitHub App, and creates the GitHub release from that section (`.github/scripts/version.sh`). Never bump, tag or release manually.
- Docs go in `README.md`. Keep them concise.
