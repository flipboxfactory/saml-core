# saml-core

Composer library (not a Craft plugin) shared by `flipboxfactory/saml-sp` and `flipboxfactory/saml-idp`. Changes reach those plugins only after a release and a constraint bump there, so treat public classes and method signatures as API: breaking them needs a major version.

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
- `simplesamlphp/saml2` is the only SAML dependency. Check its advisories (`composer audit` and GitHub security advisories) when touching it. v6 is deferred, see CHANGELOG 5.0.11.

## Workflow

- Default branch is `master`. Work on a branch and open a PR; CI (`.github/workflows/ci.yml`) must be green.
- SemVer. Version lives in `composer.json` `version`; git tags are unprefixed (`5.0.11`) and match it.
- Add a `CHANGELOG.md` entry (newest first, `# X.Y.Z YYYY-MM-DD` with `## Security|Fixed|Added|Changed|Removed`) for any user-facing change.
- Docs go in `README.md`. Keep them concise.
