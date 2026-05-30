@AGENTS.md

## Claude Code

FIL uses the same conventions as Cursor (see `.cursor/rules/`). Additional Claude-specific rules live in `.claude/rules/`:

| Scope | File |
|-------|------|
| Always (project-wide) | `.claude/rules/core.md`, `.claude/rules/security.md`, `.claude/rules/git-workflow.md` |
| `backend/**` | `.claude/rules/backend.md` |
| `frontend/**` | `.claude/rules/frontend.md` |

**Key docs:** `docs/README.md` · `docs/AUTH.md` · `docs/METADATA.md` · `docs/SECURITY_AUDIT.md` · `docs/MVP_DEPLOY.md`

**Workflow**

- Plan before large changes; keep diffs minimal and focused.
- TDD: failing test first (`php artisan test --compact`, `npm run test:run`).
- OpenAPI-first for API changes: update `docs/api.openapi.yaml`.
- Do **not** commit unless the user explicitly asks.
- **Git:** new features on `feature/*` from `dev`; merge to `dev` when done; EOD PR `dev` → `staging` if GitHub is set and `dev` is ahead (see `.claude/rules/git-workflow.md`).
- Personal preferences: `CLAUDE.local.md` at repo root (gitignored).

**Monorepo note:** Start Claude from repo root so all rules load. Backend-only work: `cd backend` is fine — parent `CLAUDE.md` still applies.
