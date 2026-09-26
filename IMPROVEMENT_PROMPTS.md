# Improvement Prompts — Aureus ERP Knowledge Base

كل برومبت أدناه مستقل بذاته، ومكتوب بالإنجليزية عمداً لأنه سيُنفَّذ بواسطة وكيل ذكاء اصطناعي (Gemini CLI في حالتك) داخل مستودع كل توثيقه بالإنجليزية — الصق البرومبت كما هو في الوكيل مباشرة، وليس في هذه المحادثة. الصياغة عامة وتصلح لأي وكيل بصلاحية وصول للكود؛ البرومبت 8 وحده مخصص لـ Gemini CLI تحديداً.

**سير عمل مناسب لعمل فردي (بدل فرع منفصل لكل برومبت):**
1. فرع واحد (مثلاً `docs/kb-audit-fixes`)، وكوميت مستقل لكل برومبت بدل فرع منفصل لكل واحد — تحصل على نفس فائدة العزل والقدرة على التراجع دون تكلفة إدارة فروع متعددة لا يفيدها إلا مراجعة جماعية. بادئة موحدة للرسائل مثل `docs(kb): ...` تسهّل البحث والتراجع لاحقاً.
2. ابدأ بالبرومبت 1 (فحص محلي عبر git hook بدل CI كامل — لست بحاجة له الآن كمطوّر منفرد) حتى تكتشف أي انحدار (regression) تُحدثه بقية البرومبتات فوراً بدل اكتشافه لاحقاً.
3. بعد كل كوميت (لا تنتظر نهاية القائمة)، شغّل: `python3 docs/development/kb_lint.py --root . --strict` — هذا يحدد بدقة أي كوميت أحدث المشكلة إن ظهرت.
4. كل برومبت يطلب من الوكيل التحقق من المصدر الحي أولاً قبل أي تعديل — لا تسمح له بالثقة بهذا التقرير وحده.
5. بعد إنجاز كل البرومبتات، ادمج الفرع في الفرع الرئيسي بكوميتاته كاملة (أو نفّذ `squash` إن رغبت بسجل أنظف — القرار لك).

---

## Prompt 1 — Add a local safety net (CI can wait)

```
Context: this is a solo project today, so a GitHub Actions gate is more
ceremony than value right now - a fast local check you actually run beats a
CI job nobody but you would ever look at. kb_lint.py (attached) is a
stdlib-only Python 3 script already validated against this repo's docs/ tree.

Task:
1. Add kb_lint.py to the repository at docs/development/kb_lint.py.
2. Add a versioned git hook the repo ships (raw .git/hooks/* is not tracked
   by git itself): create scripts/hooks/pre-commit that runs
     python3 docs/development/kb_lint.py --root . --strict
   only when the commit touches docs/**, AGENTS.md, or agents/** (check
   `git diff --cached --name-only` first), and blocks the commit on nonzero
   exit. Document the one-time setup in
   docs/development/ci-testing-governance.md: `git config core.hooksPath
   scripts/hooks` right after cloning.
3. Do not silence or filter any current finding to make the hook pass - if
   kb_lint.py reports an error on the current tree, that is a real fix to make
   (see the other prompts in this file), not something to exempt.

Optional, later - skip for now: if this project ever gets an external
contributor, promote the same command into an actual GitHub Actions workflow
under .github/workflows/, matching whatever style already exists there. A
hook only protects commits made on your own machine; it does nothing for a
stranger's pull request, which is the point CI actually starts to matter.

Verification: stage a change that reintroduces one of the errors this audit
found (e.g. re-add `HasOwner` somewhere), confirm the hook blocks the commit,
then unstage it.
```

---

## Prompt 2 — Fix the two smallest, highest-confidence bugs

```
Context: a documentation audit found two specific, narrow bugs. Fix only these
two; do not touch anything else in this pass.

Bug A — non-existent trait referenced as if real:
  File: docs/ai/plugin-rules.md, line 142
  Current: "Record ownership resolution via `HasOwner` and `Webkul\Security\Bouncer`."
  docs/ai/terminology.md documents `HasOwner` as a common misconception and
  states the real trait is `HasOwnershipScope`.
  Action: grep the actual source tree (plugins/webkul/*/src) to confirm which
  trait name really exists. If it's HasOwnershipScope, fix plugin-rules.md:142
  to use it. If source disagrees with terminology.md instead, fix
  terminology.md and open a separate note about the discrepancy - do not just
  trust either doc without checking source.

Bug B — broken internal cross-reference for an absolute constraint:
  File: AGENTS.md, Absolute Constraint #10 (forbids raw `DB::` usage), which
  currently points the reader to docs/ai/coding-rules.md for details.
  docs/ai/coding-rules.md never mentions `DB::` at all. The actual policy,
  including its documented exceptions, lives in docs/ai/security-rules.md
  section 2.
  Action: fix the cross-reference in AGENTS.md to point at
  docs/ai/security-rules.md (or wherever you confirm the policy actually
  lives after checking). Then check every other "See `docs/...`" delegation
  line in AGENTS.md the same way - confirm the target file actually contains
  the term(s) AGENTS.md attributes to it - and fix any other you find broken
  the same way, listing what you changed.

Verification: after the fix, re-run kb_lint.py's `banned-terms` and
`delegation` checks specifically:
  python3 docs/development/kb_lint.py --root . --only banned-terms,delegation
Both should report zero errors.
```

---

## Prompt 3 — Reconcile the repository-wide COUNT numbers

```
Context: docs/verification-matrix.md declares itself the canonical ledger for
repository-wide counts (COUNT-001 through COUNT-013). Several other documents
quote older or different values for the same metrics. Treat
verification-matrix.md's current numbers as provisional, not gospel - your
first job is to recount from the live source tree, then propagate whichever
number is actually correct.

Step 1 - recount from source (do this before editing any prose):
  - Model observer classes: count files under plugins/webkul/*/src/Observers/.
    Docs currently disagree between 7 and 8 (the extra one being
    accounts' CompanyObserver).
  - Domain service classes: count files under plugins/webkul/*/src/Services/.
    Docs disagree between 53 and 54.
  - Physical foreign keys: count foreignId()/foreign() declarations across
    database/migrations/ and plugins/webkul/*/database/migrations/. Docs
    disagree between 1,000 and 1,016.
  - Total database tables: count CREATE TABLE-equivalent migrations across the
    same paths. Docs disagree between 87, 194, and 262 in different places -
    262 appears to be the true repo-wide total, 87 the Operations-domain
    subtotal (already correctly scoped in docs/database/erds/operations.md),
    and 194 a sub-pattern count (tables using the standard id() PK) that is
    fine on its own but reads as a drift when scanned out of context. Confirm
    all three meanings and make sure each is labeled with what it actually
    counts, not left as a bare unlabeled number.
  - Per-plugin automated test file counts: recount files under
    plugins/webkul/<plugin>/tests/ for partners, support, accounts, employees,
    products, purchases, and sales. docs/ai/plugin-rules.md and
    docs/ai/testing-rules.md currently disagree on every one of these seven
    numbers.

Step 2 - update the source of truth:
  Update the relevant COUNT- rows in docs/verification-matrix.md with the
  recounted values, evidence pointer, and today's date. If a value changed
  from what verification-matrix.md previously said, add a one-line note
  in docs/CHANGELOG.md per this repo's own change-management.md rules
  (Evidence and Claim Discipline).

Step 3 - propagate:
  Update every other location that quotes these numbers so it matches the
  corrected verification-matrix.md value. Known locations to check (there may
  be more - grep for each number):
    docs/README.md, docs/ai/coding-rules.md, docs/architecture/change-impact.md,
    docs/architecture/events-catalog.md, docs/database/schema-conventions.md,
    docs/development/knowledge-base-readiness-audit.md,
    docs/ai/plugin-rules.md, docs/ai/testing-rules.md.

Verification:
  python3 docs/development/kb_lint.py --root . --only numeric-drift --all
  should report zero warnings once every location matches the recounted,
  corrected value.
```

---

## Prompt 4 — Resolve the "core" vs "Optional" plugin classification conflict

```
Context: docs/plugins/README.md has an authoritative classification table
where `accounts` and `products` are both marked "Optional". But three other
files call one or both of them "core"/"foundational" plugins:
  - docs/ai/architecture-rules.md:169
  - docs/ai/terminology.md:442
  - docs/architecture/dynamic-schema.md:13

Task:
1. Check Package::isCore() (or wherever plugin core/optional status is
   actually determined in source) for both `accounts` and `products` and
   confirm the ground truth.
2. If they are genuinely Optional per source: reword the three flagged
   passages. If what the author meant was "widely depended upon by other
   plugins" rather than formally core, say that explicitly instead of using
   the word "core" - these are different claims and conflating them is what
   caused the drift.
3. If source actually shows them as core and docs/plugins/README.md's table
   is the one that's wrong, fix the table instead, and re-check every plugin
   row in that table against Package::isCore(), not just these two, since a
   wrong table entry undermines the one place the rest of the KB is supposed
   to treat as authoritative.

Verification:
  python3 docs/development/kb_lint.py --root . --only plugin-index
  should report zero warnings.
```

---

## Prompt 5 — Retire the dangling "Final Master Execution Prompt" / "Exit Report" references

```
Context: four citations point at a file that does not exist anywhere in this
repository:
  docs/ai/forbidden-patterns.md:126
  docs/ai/forbidden-patterns.md:140
  docs/ai/terminology.md:447
  docs/ai/terminology.md:477
All four cite: "Aureus ERP — Phase 10_Remaining AI Rules — Final Master
Execution Prompt.md"
Separately, docs/ai/forbidden-patterns.md:143 requires producing an "Exit
Report" as part of some process, but no document anywhere defines what an
Exit Report is, what triggers it, or what it must contain.

Task:
1. Find out whether "Final Master Execution Prompt.md" ever existed in this
   repo's history (check git log/git log --all --full-history for the
   filename) or was always an external authoring document that was never
   meant to be tracked.
   - If it should be tracked: locate and commit it (ask a maintainer if you
     cannot find it), then leave the citations as they are.
   - If it was always external / is intentionally not tracked: rewrite each
     of the four citations to point at real, currently-tracked evidence for
     the same claim (the surrounding text in each case makes clear what fact
     is being supported - find the actual proof for that fact in source,
     tests, or another doc, and cite that instead).
2. For "Exit Report": either write a short definition (trigger condition,
   required contents, where it is filed) in the same section of
   forbidden-patterns.md that requires it, or - if this was leftover language
   from an earlier authoring phase that no longer applies - remove the
   requirement and say so in docs/CHANGELOG.md.

Verification:
  python3 docs/development/kb_lint.py --root . --only dangling-refs,line-citations
  should report zero findings for these specific files/lines (other,
  unrelated line-citation findings about stale line numbers are a separate
  prompt - see Prompt 6 note below - do not try to fix those here).
```

---

## Prompt 6 — Remove project-history leakage from agent-facing rule files

```
Context: docs/README.md defines two separate timelines: historical
"Documentation Phases 0-13" and an active "Operational Stages O0-O10". Both
are meant to stay out of instructional, agent-facing prose per this repo's
own docs/development/change-management.md ("do not edit locked historical
phase narratives to make a new policy appear historical" / keep the
source-of-truth hierarchy visible). In practice, Phase-N or O-stage labels
appear, unexplained, inside files an agent reads without ever passing through
docs/README.md first:
  docs/ai/terminology.md, docs/ai/forbidden-patterns.md,
  docs/ai/reading-order.md, docs/ai/security-rules.md, docs/ai/testing-rules.md,
  agents/skills/aureus-test-and-ci-change/SKILL.md,
  agents/skills/aureus-upstream-sync/SKILL.md

Task, for each file above:
  Find every "Phase N" / "Phases N-M" / "O0".."O10" mention. For each one,
  decide:
  (a) it's load-bearing context the reader needs right there (e.g. "this
      check was introduced in the O7 upstream-sync procedure, see
      docs/development/upstream-sync.md") - if so, keep it but add a
      three-to-six word inline gloss so a reader who never opened
      docs/README.md still understands it without a lookup, or
  (b) it's incidental (a stray "(Phase 11)" evidence-column tag, a leftover
      authoring-order note) - if so, delete it; it adds token cost without
      adding meaning for an agent executing a current task.
  Do not touch docs/README.md's own "Documentation History" / "Operational
  Remediation Roadmap" sections, docs/CHANGELOG.md, or docs/verification-
  matrix.md's evidence-column phase tags - those are the correct, intended
  home for this history per change-management.md.

Verification:
  python3 docs/development/kb_lint.py --root . --only phase-refs --all
  Confirm every remaining WARN is a deliberate (a)-type keep with a gloss you
  added, not an unexplained leftover.
```

---

## Prompt 7 — Split or index `docs/database/models-index.md` (currently ~317 KB)

```
Context: docs/ai/reading-order.md routes any schema-touching task through
docs/database/models-index.md in full, alongside 7 other documents, for a
combined reading set of roughly 99k tokens before any actual work starts.
models-index.md alone is ~317 KB (~81k tokens).

Task - pick one approach and implement it fully (don't half-do both):

Option A (split): Break models-index.md into one file per schema-owning
plugin: docs/database/models-index/<plugin>.md (22 files, matching the 22
schema-owning plugins from COUNT-003). Create docs/database/models-index/
README.md as a short index: plugin name -> file -> one-line scope. Update
every internal link currently pointing at
docs/database/models-index.md (with or without a #anchor) to the correct new
per-plugin file - grep the whole docs/ tree for
"database/models-index.md" first and list every hit before you start editing,
so you can verify afterward that none were missed. Update
docs/ai/reading-order.md and docs/ai/database-rules.md's reading-set
instructions to reference the split structure.

Option B (search-don't-read convention): Keep models-index.md as one file,
but change docs/ai/reading-order.md and docs/ai/database-rules.md to
explicitly instruct: for a specific model/table, use
`rg -n '<ModelName>|<table_name>' docs/database/models-index.md` and read only
the matched section, rather than loading the whole file. Add a short "How to
use this file" note at the top of models-index.md itself saying the same
thing, so a reader who opens it directly (not via reading-order.md) also gets
the instruction.

Either way, re-run the reading-set token estimate afterward and confirm it
dropped meaningfully from the current ~99k baseline.

Verification:
  python3 docs/development/kb_lint.py --root . --only sizes,reading-sets --all
```

---

## Prompt 8 — Gemini CLI bridge (GEMINI.md + .gemini/skills)

```
Context: this KB's agent entry point is AGENTS.md and its skills live under
.agents/skills/<name>/SKILL.md. Gemini CLI's native context file is GEMINI.md,
read hierarchically from the repo root the same way CLAUDE.md works for
Claude Code, and it discovers Agent Skills - the same SKILL.md format this
repo already uses - from .gemini/skills/<name>/SKILL.md in a trusted
workspace (or ~/.gemini/skills/ for skills you want in every project).
Verify current behavior before changing anything, since tooling conventions
move - start at https://geminicli.com/docs/cli/gemini-md and
https://geminicli.com/docs/cli/creating-skills and confirm what's below still
matches.

Task:
1. Add a minimal GEMINI.md at the repo root. Check Gemini's current docs for
   the exact import-directive syntax (there is evidence of an absolute-path
   @-import mechanism, e.g. something like `@AGENTS.md`, but confirm the
   precise syntax yourself rather than trusting this description) and use it
   so GEMINI.md imports AGENTS.md instead of duplicating its content. If no
   import directive exists in the current docs, fall back to a short GEMINI.md
   that plainly tells the model to read AGENTS.md first and follow it as the
   operating protocol before doing anything else.
2. Make the existing skills discoverable to Gemini CLI without duplicating
   them: create .gemini/skills as a relative symlink to ../.agents/skills
   (`ln -s ../.agents/skills .gemini/skills` on macOS/Linux) so the two
   directories can never drift apart. If your environment or packaging strips
   symlinks, copy instead and add a note in docs/development/ai-skills.md that
   the two directories must be kept in sync manually.
3. Gemini CLI only loads workspace-level .gemini/skills/ in a *trusted*
   workspace - add a line to docs/README.md's Quick Start telling a new
   contributor to run `/trust` inside Gemini CLI the first time they open this
   repo, so missing skills aren't a confusing silent no-op.
4. Confirm it actually works: open Gemini CLI in this repo, run `/skills`, and
   check that all six aureus-* skills from .agents/skills/ show up.

Verification:
  python3 docs/development/kb_lint.py --root . --only skills --all
  Some of this check's advisory notes were written with Claude Code in mind
  (a "no CLAUDE.md" note, etc.) - those are fine to leave unaddressed since
  Gemini CLI is your actual target; only act on notes that are actually about
  Gemini or about the skills themselves.
```
