---
status: policy
source_of_truth: repository-governance
last_verified: 2026-11-06
scope: knowledge-ownership
confidence: high
---

# Knowledge Ownership Map

This map assigns one canonical documentation owner to each cross-cutting topic. Supporting documents may summarize or route readers to the owner, but must not maintain competing copies of its authoritative content. Executable behavior remains governed by the source hierarchy in [`AGENTS.md`](../../AGENTS.md#source-of-truth-hierarchy).

| Topic | Canonical owner | Supporting references (not owners) |
| --- | --- | --- |
| Repository-wide AI instructions and source-of-truth hierarchy | `AGENTS.md` | `.github/copilot-instructions.md`, `GEMINI.md`, task skills |
| System context and architecture baseline | `docs/ai/context.md` | `docs/README.md` |
| Task-specific reading routes | `docs/ai/reading-order.md` | `AGENTS.md` |
| Canonical terminology | `docs/ai/terminology.md` | Domain documents |
| Plugin catalog, classification, migration and per-plugin test counts | `docs/plugins/README.md` | `docs/ai/testing-rules.md`, `docs/development/ci-testing-governance.md`, individual plugin specifications |
| Test conventions and minimum standards | `docs/ai/testing-rules.md` | `docs/development/testing-strategy.md` |
| CI workflows, job execution, and emitted status contexts | `docs/development/ci-testing-governance.md` | `.github/workflows/` |
| GitHub server-side rulesets and required-check bindings | `docs/development/github-governance.md` | GitHub repository settings |
| Repository skill catalog and assistant entrypoints | `docs/development/ai-skills.md` | `.agents/skills/*/SKILL.md`, assistant adapter files |
| Individual skill procedures | `.agents/skills/*/SKILL.md` | `docs/development/ai-skills.md` |
| Domain workflow specifications | `docs/workflows/*.md` | `docs/plugins/*.md` |
| Business calculation rules | `docs/business-rules/*.md` | `docs/plugins/*.md` |
| Knowledge-base change history | `docs/CHANGELOG.md` | None; historical entries are not rewritten |

## Maintenance Rule

Before adding or correcting cross-cutting guidance, update its canonical owner first, then adjust only the directly affected indexes, summaries, and links. Keep factual claims traceable to code, tests, schema, configuration, or repository governance as appropriate. Do not turn this ownership map into a second copy of the owned rules.