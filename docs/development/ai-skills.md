---
status: policy
source_of_truth: repository-governance-and-codex-skill-format
last_verified: 2026-09-17
scope: repository-scoped-ai-skills
confidence: high
---

# Repository AI Skills & Developer Automation

## Purpose

Repository-scoped AI skills provide concise, repeatable entry paths for high-risk or frequently recurring Aureus work. They improve task routing; they do not create a second source of truth or grant authority to mutate code, infrastructure, GitHub, or protected branches.

The canonical rules remain `AGENTS.md` and the referenced documents under `docs/`. When a skill conflicts with those sources, the source-of-truth hierarchy and explicit user authorization prevail.

## Location and Discovery

The skills are versioned under `.agents/skills/` at the repository root. Codex discovers repository skills from that path, so the skills apply to this repository without becoming global user rules. The root `.gitignore` excludes other `.agents` content while explicitly retaining this reviewed skill subtree. Each skill is intentionally instruction-only: no skill includes scripts, credentials, or automatic remote mutation.

| Skill | Use for | Canonical controls |
| --- | --- | --- |
| `aureus-plugin-change` | Local plugin behavior, registration, panel, or coupling changes | `AGENTS.md`, plugin rules, plugin registry |
| `aureus-api-change` | API routes, actions, validation, authorization, or API contracts | Reading-order API route, application/architecture/security documentation |
| `aureus-schema-change` | Migrations, persistence, models, relationships, and data backfills | Database rules, company isolation, schema conventions, change impact |
| `aureus-test-and-ci-change` | Pest, Playwright, translation, helpers, or GitHub Actions validation work | Testing rules, CI governance, upstream workflow review |
| `aureus-documentation-change` | Material documentation, AI guidance, runbook, or PR-template work | Change-management policy and direct repository evidence |
| `aureus-upstream-sync` | Approved upstream audit, integration, recovery, or promotion work | Git workflow, GitHub governance, upstream runbook |

Use a skill only when its task description matches. It supplements the baseline reading in `AGENTS.md`; it does not replace it. When two skills appear relevant, begin with the one covering the highest-risk boundary and load the other only if the work reaches its scope.

## Automation Boundary

O9 deliberately adds no executable automation. The repository has no established safe wrapper that can decide test scope, resolve an upstream conflict, alter a schema, or mutate GitHub settings without task-specific evidence and authorization. Preserving these human gates is more important than automating their commands.

Reusable scripts may be proposed later only when all of the following are true:

1. the task is deterministic and repeated;
2. inputs, outputs, and failure behavior are verified;
3. the script cannot bypass an approval, security, or protected-branch control;
4. its scope and verification are approved under [`change-management.md`](change-management.md).

## Skill Maintenance

Changes to a repository skill follow [`change-management.md`](change-management.md). Keep each skill focused on one job, link rather than duplicate canonical rules, and update this index when a skill is added, removed, renamed, or materially retargeted.

Validate a changed skill with the Codex Skill Creator validator available in the current environment, inspect its rendered `SKILL.md`, and confirm that its description is specific enough to avoid unrelated implicit activation. Validate all corresponding documentation links and record material changes in [`docs/CHANGELOG.md`](../CHANGELOG.md).

## Completion Boundary

O9 is complete on this branch when the repository contains discoverable, narrow skills for the established high-risk workflows and a maintained index that makes their authority boundaries explicit. It does not prove automation of those workflows or replace the O10 scenario-based readiness audit.
