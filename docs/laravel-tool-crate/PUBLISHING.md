# Publishing Guide — hollis-labs/laravel-tool-crate

This package is ready to share publicly in the **hollis-labs** GitHub organization, but remain marked as a preview. Follow the steps below to publish it.

## 1. Prepare a clean working tree
```bash
git status --short
```
Commit any outstanding changes in the main project repository before exporting the package contents.

## 2. Export package contents
From the project root:
```bash
PACKAGE_EXPORT="/tmp/laravel-tool-crate"
rm -rf "$PACKAGE_EXPORT"
mkdir -p "$PACKAGE_EXPORT"
rsync -a --delete docs/laravel-tool-crate/ "$PACKAGE_EXPORT"/
```
This creates a standalone copy without the surrounding monorepo assets.

## 3. Initialize the standalone repository
```bash
cd "$PACKAGE_EXPORT"
git init
```
(If you prefer to preserve history, use `git subtree split --prefix=docs/laravel-tool-crate main` in the monorepo and push that instead.)

## 4. Create the GitHub repository
```bash
# Requires GitHub CLI authenticated for the hollis-labs org
gh repo create hollis-labs/laravel-tool-crate \
  --public \
  --description "Opinionated MCP tool crate for Laravel (help-first toolbox)." \
  --homepage "https://github.com/hollis-labs/laravel-tool-crate" \
  --license MIT
```

## 5. Push the code
```bash
git add .
git commit -m "chore: bootstrap laravel tool crate package"
git branch -M main
git remote add origin git@github.com:hollis-labs/laravel-tool-crate.git
git push -u origin main
```

## 6. Tag the preview release
```bash
git tag v0.2.0
git push origin v0.2.0
```

## 7. Configure repository metadata
- Enable discussions/issues if desired.
- Add topics such as `laravel`, `mcp`, `ai-tools`, `developer-tools`.

## 8. Draft a GitHub Release (optional)
Create a release titled `v0.2.0 Preview` with notes reminding consumers that the package is **not ready for production**.

## 9. Publish to Packagist (optional, when stable)
Skip for now while the package is in preview. When ready:
1. Log into https://packagist.org/packages/submit.
2. Submit the repository URL `https://github.com/hollis-labs/laravel-tool-crate`.

---

**Reminder**: The README already includes a “Not Ready for Use” warning. Leave this in place until the CLI + MCP integration stabilizes and tests cover real project scenarios.
