# Repository Admin Setup: Branch Protection for `main`

This document provides instructions for configuring branch protection on the `main` branch of the `waaseyaa` repository.

---

## 1. Required Status Checks

The following CI jobs must pass before any pull request can be merged into `main`:

| Check Name | Source |
|---|---|
| `Lint` | CI workflow |
| `CS Fixer` | CI workflow |
| `PHPStan` | CI workflow |
| `PHPUnit` | CI workflow |
| `Manifest conformance` | CI workflow |
| `Ingestion defaults` | CI workflow |
| `Security defaults` | CI workflow |
| `Frontend build` | CI workflow (new) |
| `Playwright smoke` | CI workflow (new) |

## 2. Required Reviews

- At least **1 approving review** is required before merging.
- Any reviewer or code owner may provide the approval.

## 3. Branch Protection Rules

- **No force pushes** to `main`.
- **No branch deletion** of `main`.
- **Require branches to be up to date** before merging (linear history enforcement).
- **Include administrators** in all restrictions (no bypasses).

---

## Configuration via GitHub CLI

Run the following command from the repository root. Replace `OWNER/REPO` with your GitHub org and repository name (e.g., `jonesrussell/waaseyaa`).

```bash
gh api -X PUT repos/OWNER/REPO/branches/main/protection \
  --input - <<'EOF'
{
  "required_status_checks": {
    "strict": true,
    "contexts": [
      "Lint",
      "CS Fixer",
      "PHPStan",
      "PHPUnit",
      "Manifest conformance",
      "Ingestion defaults",
      "Security defaults",
      "Frontend build",
      "Playwright smoke"
    ]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": {
    "required_approving_review_count": 1,
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": false
  },
  "restrictions": null,
  "allow_force_pushes": false,
  "allow_deletions": false,
  "required_linear_history": false,
  "required_conversation_resolution": false
}
EOF
```

### Verify the configuration

```bash
gh api repos/OWNER/REPO/branches/main/protection
```

---

## Configuration via GitHub Web UI (Alternative)

1. Go to **Settings > Branches** in the repository.
2. Click **Add branch protection rule** (or edit the existing rule for `main`).
3. Set **Branch name pattern** to `main`.
4. Enable **Require a pull request before merging**:
   - Set **Required approvals** to `1`.
   - Enable **Dismiss stale pull request approvals when new commits are pushed**.
5. Enable **Require status checks to pass before merging**:
   - Enable **Require branches to be up to date before merging**.
   - Search for and add each status check listed in section 1 above.
6. Enable **Do not allow force pushes**.
7. Enable **Do not allow deletions**.
8. Enable **Include administrators**.
9. Click **Save changes**.

---

## CODEOWNERS File

If a `CODEOWNERS` file does not already exist, create one at `.github/CODEOWNERS` to define default reviewers. Example:

```
# Default owner for all files
* @jonesrussell

# Package-specific owners (adjust as needed)
# packages/admin/   @frontend-team
# packages/ai-*/    @ai-team
```

After creating the file, you can optionally enable **Require review from Code Owners** in the branch protection settings to enforce that code owners must approve changes to files they own.

To check whether a CODEOWNERS file exists:

```bash
gh api repos/OWNER/REPO/contents/.github/CODEOWNERS 2>/dev/null && echo "CODEOWNERS exists" || echo "CODEOWNERS not found"
```

---

## Notes

- The `Frontend build` and `Playwright smoke` checks are being added to the CI workflow. Ensure the workflow is merged and has run at least once before enabling these as required checks, otherwise pull requests will be blocked waiting for a status that never reports.
- If you need to temporarily bypass protection (e.g., for an emergency hotfix), use the GitHub UI to briefly disable "Include administrators", make the push, and re-enable it immediately.
