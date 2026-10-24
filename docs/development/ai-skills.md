---
status: policy
source_of_truth: repository-governance-and-codex-skill-format
last_verified: 2026-10-25
scope: repository-scoped-ai-skills
confidence: high
---

# Repository AI Skills & Developer Automation

## Purpose

Repository-scoped AI skills provide concise, repeatable entry paths for high-risk or frequently recurring Aureus work. They improve task routing; they do not create a second source of truth or grant authority to mutate code, infrastructure, GitHub, or protected branches.

The canonical rules remain `AGENTS.md` and the referenced documents under `docs/`. When a skill conflicts with those sources, the source-of-truth hierarchy and explicit user authorization prevail.

## Location and Discovery

The skills are versioned under `.agents/skills/` at the repository root. AI coding assistants discover repository skills from that path, so the skills apply to this repository without becoming global user rules. The root `.gitignore` excludes other `.agents` content while explicitly retaining this reviewed skill subtree. Each skill is intentionally instruction-only: no skill includes credentials or automatic remote mutation.

| Skill | Use for | Canonical controls |
| --- | --- | --- |
| `aureus-plugin-change` | Local plugin behavior, registration, panel, or coupling changes | `AGENTS.md`, plugin rules, plugin registry |
| `aureus-api-change` | API routes, actions, validation, authorization, or API contracts | Reading-order API route, application/architecture/security documentation |
| `aureus-schema-change` | Migrations, persistence, models, relationships, and data backfills | Database rules, company isolation, schema conventions, change impact |
| `aureus-test-and-ci-change` | Pest, Playwright, translation, helpers, or GitHub Actions validation work | Testing rules, CI governance, upstream workflow review |
| `aureus-documentation-change` | Material documentation, AI guidance, runbook, or PR-template work | Change-management policy, [`plugin-documentation-template.md`](plugin-documentation-template.md), and direct repository evidence |
| `aureus-upstream-sync` | Approved upstream audit, integration, recovery, or promotion work | Git workflow, GitHub governance, upstream runbook |

Use a skill only when its task description matches. It supplements the baseline reading in `AGENTS.md`; it does not replace it. When two skills appear relevant, begin with the one covering the highest-risk boundary and load the other only if the work reaches its scope.

## Automation Boundary

Skills remain instruction-only and do not embed unreviewed mutation scripts. The repository does not delegate test-scope selection, upstream conflict resolution, schema changes, or GitHub settings mutations to autonomous wrappers without task-specific evidence and human authorization. Deterministic validation scripts (such as [`scripts/kb_lint.py`](../../scripts/kb_lint.py) and [`scripts/hooks/pre-commit`](../../scripts/hooks/pre-commit)) operate strictly as read-only verification gates.

Additional reusable scripts may be introduced only when all of the following are true:

1. the task is deterministic and repeated;
2. inputs, outputs, and failure behavior are verified;
3. the script cannot bypass an approval, security, or protected-branch control;
4. its scope and verification are approved under [`change-management.md`](change-management.md).

## Skill Maintenance

Changes to a repository skill follow [`change-management.md`](change-management.md). Keep each skill focused on one job, link rather than duplicate canonical rules, and update this index when a skill is added, removed, renamed, or materially retargeted.

Validate a changed skill with [`scripts/kb_lint.py`](../../scripts/kb_lint.py), inspect its rendered `SKILL.md`, and confirm that its description is specific enough to avoid unrelated implicit activation. Validate all corresponding documentation links and record material changes in [`docs/CHANGELOG.md`](../CHANGELOG.md).
