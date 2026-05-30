# FIL git workflow

Branch flow: **`feature/*` → `dev` → `staging` → production** (when applicable).

## Feature work

1. **Start on a feature branch** — never implement new features directly on `dev`, `staging`, or `main`/`master`.
2. **Branch from `dev`:** `git checkout dev && git pull && git checkout -b feature/<short-name>`.
3. **Naming:** `feature/lead-export`, `feature/ach-idempotency` — lowercase, hyphenated, descriptive.
4. **Finish the feature:** tests green, scope complete, then merge into `dev`:
   - Local merge: `git checkout dev && git pull && git merge feature/<short-name>`
   - Or open a PR **into `dev`** if the team prefers review before merge.
5. **Delete** the feature branch after merge (local + remote when applicable).

## End of day — promote `dev` to `staging`

When wrapping up for the day **and** GitHub remote is configured:

1. Confirm remote: `git remote get-url origin` points at GitHub.
2. Update local `dev`: `git checkout dev && git pull`.
3. Check whether `dev` is ahead of `staging`:
   ```bash
   git fetch origin
   git log origin/staging..dev --oneline
   ```
4. If there are **new commits or merges on `dev`** not yet on `staging`:
   - Push `dev` if needed: `git push -u origin dev`
   - Open a PR **`dev` → `staging`** with `gh`:
     ```bash
     gh pr create --base staging --head dev \
       --title "Promote dev to staging ($(date +%Y-%m-%d))" \
       --body "$(cat <<'EOF'
     ## Summary
     End-of-day promotion of integrated work from `dev` to `staging`.

     ## Test plan
     - [ ] CI green on `dev`
     - [ ] Staging deploy smoke after merge
     EOF
     )"
     ```
5. If `dev` has **no** new changes vs `staging`, do not open a duplicate PR.

## Agent behavior

- **Before starting feature work:** offer to create/switch to `feature/<name>` from `dev` unless the user is explicitly fixing something on another branch.
- **After feature is done:** merge to `dev` (or prepare PR to `dev`) when the user asks to land the work — do not skip straight to `staging`.
- **End of day / “wrap up” / “promote”:** run the `dev` vs `staging` check; create the staging PR only when GitHub is configured and `dev` is ahead.
- **Do not** force-push `dev`, `staging`, or `main`/`master` unless the user explicitly requests it.
- **Do not** commit unless the user explicitly asks (see project commit rules).

## Long-lived branches

| Branch | Purpose |
|--------|---------|
| `feature/*` | One feature or fix; short-lived |
| `dev` | Integration branch for completed features |
| `staging` | Pre-production / staging environment |
| `main` / `master` | Production (protect; no direct feature commits) |
