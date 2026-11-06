#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
kb_lint.py - static consistency checks for an AI knowledge base
(AGENTS.md + docs/ + agent skills).  Standard library only, Python >= 3.8.

The checks are the automated form of the review findings: broken links and
anchors, forbidden/non-existent symbols, numeric drift between documents,
stale file:line citations, skill validity, oversized reading sets, etc.
It only READS files; it never modifies anything.

Examples
  python kb_lint.py --root .                       # run every check
  python kb_lint.py --root . --only links,counts   # run some checks
  python kb_lint.py --root . --json > kb_lint.json # machine readable
  python kb_lint.py --root . --strict              # warnings fail too (CI)
  python kb_lint.py --list-checks

Run it from the repository root (mode "repo": links/citations that point to
source files are verified too) or on an extracted bundle that only contains
AGENTS.md + docs/ + agents/ (mode "bundle": links to files outside the bundle
are counted but not verified).

Exit codes: 0 = no errors, 1 = errors (or warnings with --strict), 2 = usage.
"""
from __future__ import annotations

import argparse
import collections
import datetime as dt
import json
import posixpath
import re
import sys
from pathlib import Path
from urllib.parse import unquote

ERROR, WARN, INFO = "ERROR", "WARN", "INFO"
SEV_ORDER = {ERROR: 0, WARN: 1, INFO: 2}

REQUIRED_FM = ("status", "source_of_truth", "last_verified", "scope", "confidence")
SKILL_ROOTS = (".agents/skills", "agents/skills", ".claude/skills")
KB_PREFIXES = ("docs/", ".agents/", "agents/", ".claude/", ".gemini/")
AGENT_FACING = ("AGENTS.md", "CLAUDE.md", "GEMINI.md", "docs/ai/")   # + every SKILL.md
HISTORICAL_FILES = {"docs/CHANGELOG.md"}                 # historical by nature
ROOT_MD = {"README.md", "CHANGELOG.md", "CONTRIBUTING.md", "SECURITY.md", "LICENSE.md"}

LINK_RE = re.compile(r"!?\[[^\]\n]*\]\(\s*<?([^)\s>]+)>?(?:\s+\"[^\"]*\")?\s*\)")
SCHEME_RE = re.compile(r"^[a-zA-Z][a-zA-Z0-9+.\-]*:")
PHASE_RE = re.compile(r"\bPhases?\s+\d+(?:\s*[\u2013\-]\s*\d+)?\b")
STAGE_RE = re.compile(r"\bO(?:10|[0-9])\b")
PHASE_ALLOW_RE = re.compile(r"<!--\s*lint:\s*allow-phase-ref\b[^>]*-->")
SEARCH_ONLY_RE = re.compile(r"<!--\s*lint:\s*search-only\b[^>]*-->")

CHECKS = {}   # name -> (description, function)


def check(name, desc):
    def deco(fn):
        CHECKS[name] = (desc, fn)
        return fn
    return deco


Finding = collections.namedtuple("Finding", "check sev path line msg")


# --------------------------------------------------------------------------- helpers
def gh_slug(text):
    """GitHub-style heading slug."""
    t = text.strip().lower()
    t = re.sub(r"<[^>]+>", "", t)
    t = re.sub(r"!?\[([^\]]*)\]\([^)]*\)", r"\1", t)
    t = t.replace("`", "").replace("*", "")
    t = re.sub(r"[^\w\- ]", "", t, flags=re.UNICODE)
    return t.replace(" ", "-")


def fence_mask(lines):
    """True for lines inside a fenced code block (including the fences)."""
    mask, fence = [], None
    for line in lines:
        s = line.lstrip()
        if fence is None and (s.startswith("```") or s.startswith("~~~")):
            fence = s[:3]
            mask.append(True)
            continue
        if fence is not None:
            mask.append(True)
            if s.startswith(fence):
                fence = None
            continue
        mask.append(False)
    return mask


def parse_frontmatter(lines):
    """Return (dict|None, first_body_line_index). Minimal YAML: key: value (+ folded > / |)."""
    if not lines or lines[0].strip() != "---":
        return None, 0
    end = None
    for i in range(1, len(lines)):
        if lines[i].strip() == "---":
            end = i
            break
    if end is None:
        return None, 0
    fm, i = {}, 1
    while i < end:
        m = re.match(r"^([A-Za-z0-9_\-]+)\s*:\s*(.*)$", lines[i])
        if m:
            key, val = m.group(1), m.group(2).strip()
            if val in (">", "|", ">-", "|-"):
                buf = []
                while i + 1 < end and (lines[i + 1].startswith(" ") or not lines[i + 1].strip()):
                    i += 1
                    buf.append(lines[i].strip())
                val = " ".join(x for x in buf if x)
            fm[key] = val.strip().strip("\"'")
        i += 1
    return fm, end + 1


def in_kb(rel):
    return rel in ("AGENTS.md", "CLAUDE.md", ".github/copilot-instructions.md") or rel.startswith(KB_PREFIXES)


def is_agent_facing(rel):
    return rel.startswith(AGENT_FACING) or rel == ".github/copilot-instructions.md" or rel.endswith("/SKILL.md")


class KB:
    def __init__(self, root):
        self.root = root
        self.files = {}          # repo-relative posix path -> list of lines
        self.skill_dirs = []
        self._anchors = {}
        self._disk_lines = {}
        self.alias_used = 0
        self.repo_mode = (root / "composer.json").exists() or (root / "plugins").is_dir()
        self._load()

    @staticmethod
    def _read(p):
        return p.read_text(encoding="utf-8-sig", errors="replace").splitlines()

    def _load(self):
        for name in ("AGENTS.md", "CLAUDE.md", "GEMINI.md"):
            p = self.root / name
            if p.is_file():
                self.files[name] = self._read(p)
        copilot_instructions = self.root / ".github/copilot-instructions.md"
        if copilot_instructions.is_file():
            self.files[".github/copilot-instructions.md"] = self._read(copilot_instructions)
        docs = self.root / "docs"
        if docs.is_dir():
            for p in sorted(docs.rglob("*.md")):
                self.files[p.relative_to(self.root).as_posix()] = self._read(p)
        seen_skill_real = set()
        for sr in SKILL_ROOTS:
            d = self.root / sr
            if not d.is_dir():
                continue
            # If .gemini/skills is a symlink to ../.agents/skills, both roots resolve
            # to the same physical directory; scanning both would register every
            # SKILL.md twice and produce duplicate findings. Scan the first, skip the rest.
            try:
                real = d.resolve()
            except OSError:
                real = d
            if real in seen_skill_real:
                continue
            seen_skill_real.add(real)
            for p in sorted(d.rglob("*.md")):
                rel = p.relative_to(self.root).as_posix()
                self.files[rel] = self._read(p)
                if p.name == "SKILL.md":
                    self.skill_dirs.append(p.parent.relative_to(self.root).as_posix())

    def resolve(self, rel):
        """Existing repo-relative path for rel (honours the '.agents' -> 'agents' alias) or None."""
        rel = rel.strip("/")
        if rel in ("", "."):
            return "."
        if (self.root / rel).exists():
            return rel
        if rel.startswith(".agents/") and (self.root / "agents").is_dir():
            alt = rel[1:]
            if (self.root / alt).exists():
                self.alias_used += 1
                return alt
        return None

    def size(self, rel):
        try:
            return (self.root / rel).stat().st_size
        except OSError:
            return 0

    def lines_of(self, rel):
        if rel in self.files:
            return self.files[rel]
        if rel not in self._disk_lines:
            p = self.root / rel
            self._disk_lines[rel] = self._read(p) if p.is_file() else []
        return self._disk_lines[rel]

    def anchors_of(self, rel):
        if rel in self._anchors:
            return self._anchors[rel]
        lines = self.lines_of(rel)
        mask = fence_mask(lines)
        seen, out = collections.Counter(), set()
        for line, in_fence in zip(lines, mask):
            if in_fence:
                continue
            h = re.match(r"^ {0,3}#{1,6}\s+(.*?)\s*#*\s*$", line)
            if h:
                s = gh_slug(h.group(1))
                n = seen[s]
                seen[s] += 1
                out.add(s if n == 0 else "%s-%d" % (s, n))
        for mt in re.finditer(r'\b(?:id|name)="([^"]+)"', "\n".join(lines)):
            out.add(mt.group(1).lower())
        self._anchors[rel] = out
        return out


# --------------------------------------------------------------------------- checks
@check("links", "relative Markdown links and #anchors resolve")
def check_links(kb, o, add):
    outside = 0
    for rel, lines in kb.files.items():
        mask = fence_mask(lines)
        base = posixpath.dirname(rel)
        for i, (line, in_fence) in enumerate(zip(lines, mask), 1):
            if in_fence:
                continue
            for mt in LINK_RE.finditer(line):
                t = mt.group(1)
                if SCHEME_RE.match(t):
                    continue
                path, _, frag = t.partition("#")
                path = unquote(path)
                if path.startswith("/"):
                    tgt = path.lstrip("/")
                elif path:
                    tgt = posixpath.normpath(posixpath.join(base, path))
                else:
                    tgt = rel
                if tgt == ".." or tgt.startswith("../"):
                    outside += 1
                    continue
                real = kb.resolve(tgt)
                if real is None:
                    if in_kb(tgt) or kb.repo_mode:
                        add(ERROR, rel, i, "broken link: %s" % t)
                    else:
                        outside += 1
                    continue
                if frag and real.endswith(".md") and not re.match(r"^L\d+", frag):
                    if frag.lower() not in kb.anchors_of(real):
                        add(WARN, rel, i, "anchor not found in %s: #%s" % (real, frag))
    if outside:
        add(INFO, "", 0, "%d link(s) point to repository files outside this bundle (not verifiable here)" % outside)
    if kb.alias_used:
        add(INFO, "", 0, "%d link(s) use '.agents/' but this bundle names the folder 'agents/' (alias applied)" % kb.alias_used)


@check("frontmatter", "docs/**/*.md front matter: required keys, date validity, confidence signal")
def check_frontmatter(kb, o, add):
    conf, dates = collections.Counter(), []
    n_docs = 0
    for rel, lines in sorted(kb.files.items()):
        if not rel.startswith("docs/"):
            continue
        n_docs += 1
        fm, _ = parse_frontmatter(lines)
        if fm is None:
            add(ERROR, rel, 1, "missing YAML front matter")
            continue
        for key in REQUIRED_FM:
            if not fm.get(key):
                add(ERROR, rel, 1, "front matter is missing '%s'" % key)
        lv = fm.get("last_verified", "")
        if lv:
            try:
                d = dt.date.fromisoformat(lv)
                dates.append(d)
                if d > o.today:
                    add(WARN, rel, 1, "last_verified %s is in the future" % lv)
                elif (o.today - d).days > o.max_age_days:
                    add(WARN, rel, 1, "last_verified %s is %d days old (limit %d)" % (lv, (o.today - d).days, o.max_age_days))
            except ValueError:
                add(WARN, rel, 1, "last_verified '%s' is not an ISO date (YYYY-MM-DD)" % lv)
        c = fm.get("confidence", "")
        if c:
            conf[c] += 1
            if c not in ("high", "medium", "low"):
                add(WARN, rel, 1, "confidence '%s' is not one of high/medium/low" % c)
    if n_docs >= 10 and conf and len(conf) == 1:
        only = next(iter(conf))
        add(INFO, "docs/", 0, "confidence is uniformly '%s' across %d docs; this is the expected state when every doc has been verified to the same bar" % (only, n_docs))
    if dates:
        add(INFO, "docs/", 0, "last_verified range: %s .. %s over %d docs" % (min(dates), max(dates), len(dates)))


NAME_RE = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


@check("skills", "SKILL.md validity, index coverage, undefined jargon, tool-compat hints")
def check_skills(kb, o, add):
    if not kb.skill_dirs:
        add(INFO, "", 0, "no skills found under .agents/skills, agents/skills or .claude/skills")
        return
    index_rel = "docs/development/ai-skills.md"
    index_txt = "\n".join(kb.files.get(index_rel, []))
    for d in kb.skill_dirs:
        rel = d + "/SKILL.md"
        lines = kb.files[rel]
        fm, _ = parse_frontmatter(lines)
        if fm is None:
            add(ERROR, rel, 1, "missing YAML front matter")
            continue
        name, desc = fm.get("name", ""), fm.get("description", "")
        folder = posixpath.basename(d)
        if not name:
            add(ERROR, rel, 1, "front matter has no 'name'")
        else:
            if name != folder:
                add(ERROR, rel, 1, "name '%s' differs from folder '%s'" % (name, folder))
            if len(name) > 64 or not NAME_RE.match(name):
                add(ERROR, rel, 1, "name must be <=64 chars of lowercase letters, digits, hyphens")
            if re.search(r"anthropic|claude", name):
                add(ERROR, rel, 1, "name contains a reserved word")
        if not desc:
            add(ERROR, rel, 1, "front matter has no 'description'")
        else:
            if len(desc) > 1024:
                add(ERROR, rel, 1, "description is %d chars (limit 1024)" % len(desc))
            if re.search(r"<[^>]+>", desc):
                add(ERROR, rel, 1, "description contains XML/HTML tags")
            if not re.search(r"(?i)\buse (this|when|for|only)\b|\bdo not use\b", desc):
                add(INFO, rel, 1, "description has no explicit 'use when' / 'do not use' wording")
            if STAGE_RE.search(desc):
                add(WARN, rel, 1, "internal stage label in description; descriptions drive activation, use plain words")
        body = "\n".join(lines)
        if len(lines) > 500:
            add(WARN, rel, 0, "%d lines (recommended <=500): split into references" % len(lines))
        if STAGE_RE.search(body) and "operational stage" not in body.lower():
            add(WARN, rel, 0, "uses stage labels (O5/O6...) without defining them inside the skill")
        if index_txt and name and name not in index_txt:
            add(WARN, rel, 1, "skill is not listed in %s" % index_rel)
    for tool_file, tool_name in (("CLAUDE.md", "Claude Code"), ("GEMINI.md", "Gemini CLI")):
        if "AGENTS.md" in kb.files and tool_file not in kb.files:
            add(INFO, "", 0, "no %s: %s reads its own entry file, not AGENTS.md; a pointer file importing "
                             "AGENTS.md bridges them (verify syntax against the tool's current docs)"
                % (tool_file, tool_name))
    if any(s.startswith("agents/") for s in kb.skill_dirs) and not (kb.root / ".agents").is_dir():
        add(INFO, "", 0, "skills folder is 'agents/' (no dot) while the docs reference '.agents/'")

@check("knowledge-ownership", "canonical ownership map has unique topics and existing owner paths")
def check_knowledge_ownership(kb, o, add):
    rel = "docs/ai/knowledge-ownership.md"
    lines = kb.files.get(rel)
    if not lines:
        add(ERROR, rel, 1, "missing canonical knowledge ownership map")
        return

    header = False
    topics = {}
    for i, line in enumerate(lines, 1):
        if not line.startswith("|"):
            continue
        cells = [cell.strip() for cell in line.strip().strip("|").split("|")]
        if len(cells) >= 3 and cells[0].lower() == "topic" and cells[1].lower() == "canonical owner":
            header = True
            continue
        if not header or len(cells) < 3 or set(cells[0]) <= set("-: "):
            continue
        topic = cells[0].lower()
        owners = re.findall(r"`([^`]+)`", cells[1])
        if topic in topics:
            add(ERROR, rel, i, "duplicate ownership topic '%s' (first at line %d)" % (cells[0], topics[topic]))
        else:
            topics[topic] = i
        if len(owners) != 1:
            add(ERROR, rel, i, "topic '%s' must declare exactly one canonical owner path" % cells[0])
            continue
        owner = owners[0]
        exists = any(kb.root.glob(owner)) if "*" in owner else (kb.root / owner).exists()
        if not exists:
            add(ERROR, rel, i, "canonical owner path '%s' does not exist" % owner)

    if not header:
        add(ERROR, rel, 1, "missing ownership table with Topic and Canonical owner columns")
    elif not topics:
        add(ERROR, rel, 1, "ownership table has no topics")


@check("assistant-entrypoints", "selected assistant entrypoints route to the canonical AGENTS.md")
def check_assistant_entrypoints(kb, o, add):
    if not kb.repo_mode:
        return

    required = {
        "AGENTS.md": ("AGENTS.md", None),
        "GEMINI.md": ("GEMINI.md", "@AGENTS.md"),
        ".github/copilot-instructions.md": (".github/copilot-instructions.md", "AGENTS.md"),
    }
    for rel, (name, marker) in required.items():
        lines = kb.files.get(name)
        if not lines:
            add(ERROR, rel, 1, "missing supported assistant entrypoint")
        elif marker and marker not in "\n".join(lines):
            add(ERROR, rel, 1, "entrypoint must route to the repository-root AGENTS.md")

    index = "\n".join(kb.files.get("docs/development/ai-skills.md", []))
    for assistant in ("GitHub Copilot", "Gemini CLI", "Codex"):
        if assistant not in index:
            add(ERROR, "docs/development/ai-skills.md", 1, "assistant entrypoint index is missing '%s'" % assistant)


@check("source-hierarchy-owner", "the source-of-truth ordering is not copied outside AGENTS.md")
def check_source_hierarchy_owner(kb, o, add):
    ordered_hierarchy = re.compile(
        r"Source\s*Code\s*(?:>|\u2192)\s*Automated\s+Tests\s*(?:>|\u2192)\s*(?:Migrations|Database Schema)",
        re.I,
    )
    canonical = "\n".join(kb.files.get("AGENTS.md", []))
    if "## Source-of-Truth Hierarchy" not in canonical:
        add(ERROR, "AGENTS.md", 1, "missing canonical Source-of-Truth Hierarchy section")
    for rel, lines in kb.files.items():
        if rel in ("AGENTS.md", "docs/CHANGELOG.md"):
            continue
        if ordered_hierarchy.search(" ".join(lines)):
            add(ERROR, rel, 1, "duplicates the source-of-truth ordering; link to AGENTS.md instead")


@check("workflow-index", "CI governance inventory matches the workflow files on disk")
def check_workflow_index(kb, o, add):
    workflow_root = kb.root / ".github/workflows"
    if not workflow_root.is_dir():
        return

    workflows = sorted(p.name for p in workflow_root.iterdir() if p.suffix in (".yml", ".yaml"))
    rel = "docs/development/ci-testing-governance.md"
    text = "\n".join(kb.files.get(rel, []))
    match = re.search(r"pipeline consists of\s+(\d+)\s+distinct workflow files", text, re.I)
    if not match:
        add(ERROR, rel, 1, "missing declared workflow count")
    elif int(match.group(1)) != len(workflows):
        add(ERROR, rel, 1, "declares %s workflows but %d workflow files exist" % (match.group(1), len(workflows)))
    for name in workflows:
        if name not in text:
            add(ERROR, rel, 1, "workflow '%s' is missing from the CI governance inventory" % name)


BANNED = [
    (r"\bHasOwner\b", "non-existent trait: the canonical name is HasOwnershipScope (terminology.md section 7)"),
    (r"\bHasCompanyScope\b", "non-existent trait: use BelongsToCompany / BelongsToCompanies"),
    (r"silber/bouncer", "Aureus Bouncer is a local class, not the silber/bouncer package"),
    (r"PermissionType::SELF\b", "no such enum case: the canonical case is INDIVIDUAL"),
    (r"\bLivewire\s+(?:v)?3\b", "stale framework version: the project is on Livewire v4"),
]
# A line is an ANTI-example (defining/correcting the wrong term, not using it) if either:
#  (a) it contains a stand-alone, case-sensitive "NOT" - this KB's own convention for
#      flagging a correction ("**NOT** the ...", "is NOT third-party ..."), or
#  (b) once markdown emphasis/backticks are stripped, it matches a broader set of
#      correction/history cues (checked case-insensitively).
ANTI_EXAMPLE_CASE_SENSITIVE_RE = re.compile(r"\bNOT\b")
ANTI_EXAMPLE_RE = re.compile(
    r"(?i:misconception|does not exist|does not use|do not (?:invent|reference|import|assume|use|treat|document|require)"
    r"|must not|must be rejected|forbidden symbol|non-existent|\bnever\b"
    r"|zero occurrences|zero references|absence of|not the |\bis not\b|isn't"
    r"|\bno\b\W{0,3}hascompanyscope|historical(?:ly)?|tempting|guideline claimed"
    r"|conflated|\bcorrects?\b|corrected|pre-upstream|corr-\d|outdated)")
ANTI_EXAMPLE_FILES = {"docs/ai/terminology.md", "docs/ai/forbidden-patterns.md"}


BOX_DRAWING_RE = re.compile(r"[\u2500-\u257F]")


def _is_anti_example(text):
    # Box-drawing ASCII tables wrap one sentence across several bordered cell-lines;
    # collapse the borders and whitespace/newlines so a phrase split across lines
    # ("...zero\n...occurrences of X...") still reads as one contiguous phrase.
    flat = re.sub(r"\s+", " ", BOX_DRAWING_RE.sub(" ", text)).strip()
    if ANTI_EXAMPLE_CASE_SENSITIVE_RE.search(flat):
        return True
    stripped = flat.replace("*", "").replace("`", "")
    return bool(ANTI_EXAMPLE_RE.search(stripped))


@check("banned-terms", "non-existent symbols / stale versions used as if they were real")
def check_banned(kb, o, add):
    for rel, lines in kb.files.items():
        if rel in ANTI_EXAMPLE_FILES or rel in HISTORICAL_FILES:
            continue
        for i, line in enumerate(lines, 1):
            # Box-drawing tables in this KB wrap one sentence across several bordered
            # cell-lines, so the anti-example cue ("...zero\n...occurrences of X...")
            # can land on the line just above the one with the banned term.
            window = "\n".join(lines[max(0, i - 3):i])
            if _is_anti_example(window):
                continue
            for pat, msg in BANNED:
                if re.search(pat, line):
                    add(ERROR, rel, i, msg)


ABS_PATTERNS = [
    r"file://",
    r"(?<![\w/.])/(?:Users|home)/[A-Za-z0-9._\-]{2,}/",
    r"\b[A-Za-z]:\\[A-Za-z]",
    r"~/(?:projects|Documents|Downloads|Desktop|dev|code)/",
]


@check("abs-paths", "machine-local absolute paths / file:// URIs in canonical docs")
def check_abs_paths(kb, o, add):
    for rel, lines in kb.files.items():
        if rel == "docs/ai/forbidden-patterns.md":       # the file that defines the rule
            continue
        for i, line in enumerate(lines, 1):
            for pat in ABS_PATTERNS:
                if re.search(pat, line):
                    add(ERROR, rel, i, "machine-local absolute path or file:// URI")
                    break


DANGLING = [
    (r"Master Execution Prompt", "refers to a prompt/spec file that is not part of the repository"),
]


@check("dangling-refs", "references to artifacts or terms that do not exist in the KB")
def check_dangling(kb, o, add):
    everything = kb.files
    for pat, msg in DANGLING:
        defined = any(re.search(r"^#{1,6}\s+.*" + pat, l) or re.search(r"\*\*" + pat + r"\*\*\s*[:\u2014\-]", l)
                      for ls in everything.values() for l in ls)
        if defined:
            continue
        for rel, lines in everything.items():
            for i, line in enumerate(lines, 1):
                if re.search(pat, line):
                    add(WARN, rel, i, msg)


@check("phase-refs", "project-history labels (Phase N / O-stage) inside agent-facing files")
def check_phase_refs(kb, o, add):
    other = collections.Counter()
    for rel, lines in kb.files.items():
        if rel in HISTORICAL_FILES or rel == "docs/README.md" or rel.startswith("docs/development/"):
            continue
        hits = []
        for i, line in enumerate(lines, 1):
            if PHASE_ALLOW_RE.search(line):
                continue
            n = len(PHASE_RE.findall(line)) + (len(STAGE_RE.findall(line)) if is_agent_facing(rel) else 0)
            if n:
                hits.append((i, n))
        if not hits:
            continue
        total = sum(n for _, n in hits)
        if is_agent_facing(rel):
            add(WARN, rel, hits[0][0], "%d phase/stage label(s) in an agent-facing file (first at line %d): "
                                       "agents should not depend on project history" % (total, hits[0][0]))
        else:
            other[rel.split("/")[1] if rel.count("/") else rel] += total
    if other:
        add(INFO, "docs/", 0, "phase labels elsewhere: " + ", ".join("%s=%d" % kv for kv in sorted(other.items())))


SPAN_RE = re.compile(r"`([^`\n]+)`")
CITE_RE = re.compile(
    r"^(?P<path>.+?\.(?:md|php|yml|yaml|json|ts|js|xml|lock|css)):"
    r"(?P<rng>\d+(?:\s*[\u2013\-]\s*\d+)?(?:\s*,\s*\d+(?:\s*[\u2013\-]\s*\d+)?)*)$")


BASENAME_HINT_RE = re.compile(
    r"`((?:[\w.\-]+/)+[\w.\-]+\.(?:md|php|yml|yaml|json|ts|js|xml|lock|css))`"
)


@check("line-citations", "`path:line` evidence pointers resolve and are not blank / out of range")
def check_line_citations(kb, o, add):
    total = kb_md = unverifiable = 0
    for rel, lines in kb.files.items():
        base = posixpath.dirname(rel)
        for i, line in enumerate(lines, 1):
            for sp in SPAN_RE.finditer(line):
                m = CITE_RE.match(sp.group(1).strip())
                if not m:
                    continue
                path = m.group("path").strip()
                rngs = re.findall(r"(\d+)(?:\s*[\u2013\-]\s*(\d+))?", m.group("rng"))
                total += 1
                real = None
                for cand in (posixpath.normpath(path), posixpath.normpath(posixpath.join(base, path))):
                    r = kb.resolve(cand)
                    if r and r != "." and (kb.root / r).is_file():
                        real = r
                        break
                if real is None and "/" not in path:
                    # Look for the full path in a nearby line: either the same line,
                    # or the nearest preceding section heading (up to 20 lines back).
                    context_lines = [line] + list(reversed(lines[max(0, i - 21):i - 1]))
                    for ctx in context_lines:
                        for hint in BASENAME_HINT_RE.findall(ctx):
                            if posixpath.basename(hint) == path:
                                r = kb.resolve(hint)
                                if r and (kb.root / r).is_file():
                                    real = r
                                break
                        if real:
                            break
                if real is None:
                    if path.endswith(".md"):
                        if "/" not in path and path in ROOT_MD and not kb.repo_mode:
                            unverifiable += 1
                        elif kb.repo_mode or path.startswith(("docs/", "AGENTS.md")) or "/" not in path:
                            add(ERROR, rel, i, "cites '%s' which does not exist" % path)
                        else:
                            unverifiable += 1
                    elif kb.repo_mode:
                        add(ERROR, rel, i, "cites '%s' which does not exist" % path)
                    else:
                        unverifiable += 1
                    continue
                target = kb.lines_of(real)
                if real in kb.files and real.endswith(".md"):
                    kb_md += 1
                for a, b in rngs:
                    a, b = int(a), int(b or a)
                    # wc -l (used when authoring these docs) and Python's
                    # splitlines() disagree by 1 when the last line of the
                    # target has no trailing newline. Treat an upper bound
                    # that is at most 1 past the file length as file-end.
                    effective_len = len(target)
                    if b > effective_len and b <= effective_len + 1:
                        continue   # off-by-one at file end: not a real error
                    if b > effective_len:
                        add(ERROR, rel, i, "cites %s:%d-%d but the file has %d lines" % (real, a, b, effective_len))
                    elif a >= 1 and not target[a - 1].strip():
                        add(WARN, rel, i, "cited line %d of %s is blank (stale citation?)" % (a, real))
    if total:
        add(INFO, "", 0, "%d file:line citations (%d into KB markdown - these break on every edit, prefer "
                         "heading/symbol anchors; %d not verifiable in bundle mode)" % (total, kb_md, unverifiable))


def _scope_for_counts(rel):
    return (rel in ("AGENTS.md", "docs/README.md", "docs/verification-matrix.md",
                    "docs/database/overview.md", "docs/database/schema-conventions.md")
            or rel.startswith(("docs/ai/", "docs/architecture/", "docs/development/")))


HIST_RE = re.compile(r"(?i)historical|previously|earlier|reported|corrected|CORR-|revealed|discover|formerly|"
                     r"legacy|used to|\bwas\b|\bwere\b|"
                     r"following\s+the\s+upstream|after\s+the\s+upstream|updated\s+total\s+table\s+count\s+to")
# Numbers scoped to a sub-domain or a sub-pattern are not drift; they are
# different metrics that happen to share the word "tables". Skip lines where
# the count is explicitly qualified.
SCOPED_COUNT_RE = re.compile(
    r"(?i)operations[- ]domain|\bid\(\)\s*(?:PK|primary)|\bstandard\s+id\(\)|"
    r"\bsubtotal\b|\bsub-pattern\b|\bwithin\s+the\s+\w+\s+domain\b|"
    r"\$table->id\(\)|standard\s+domain\s+entity|standard\s+ID\s+tables"
)
APPROX_RE = re.compile(r"(?i)(?:over|more than|about|approximately|around|~|>)\s*$")

# name, mention-patterns (first capture group = number, used to scan the whole corpus),
# minimum plausible value, verification-matrix row key, optional dedicated canon-only
# pattern(s) (tried first, against the claim cell alone, before falling back to the
# mention patterns - needed where a metric's own unit words also appear in an unrelated
# COUNT row, e.g. "0 database tables" inside the Zero-Table-Extension-Layers claim).
METRICS = [
    ("observers", [r"(\d+)\s+(?:Eloquent\s+)?(?:[Mm]odel\s+)?[Oo]bserver(?:s|\s+[Cc]lasses)\b",
                   r"[Oo]bserver classes[^|\n]{0,60}?exactly\s+(\d+)"], 5, "observer classes", None),
    ("services", [r"(\d+)\s+(?:concrete\s+|domain\s+)?[Ss]ervices?(?:\s+[Cc]lasses)?\b",
                  r"[Ss]ervice classes[^|\n]{0,60}?exactly\s+(\d+)"], 30, "service classes", None),
    ("events", [r"(\d+)\s+(?:[Dd]omain\s+)?[Ee]vents?(?:\s+[Cc]lasses)?\b",
                r"[Ee]vent classes[^|\n]{0,60}?exactly\s+(\d+)"], 10, "event classes", None),
    ("listeners", [r"(\d+)\s+(?:[Ee]vent\s+)?[Ll]isteners?(?:\s+[Cc]lasses)?\b",
                   r"[Ll]istener classes[^|\n]{0,60}?exactly\s+(\d+)"], 4, "listener classes", None),
    ("tables", [r"(\d[\d,]*)\s+(?:physical\s+|verified\s+|database\s+)*tables\b"
                r"(?!\s+(?:with|without|using|use|that|which|having)\b)",
                r"total\s+table\s+count\s+to\s+(\d+)"],
     40, "database tables",
     [r"[Tt]otal\s+database\s+tables\s+created[^|\n]{0,60}?exactly\s+(\d[\d,]*)\s+tables"]),
    ("foreign_keys", [r"(\d[\d,]*)\s+(?:physical\s+)?foreign\s+keys"], 500, "foreign keys", None),
    ("plugins", [r"(\d+)\s+modular\s+plugins", r"(?:all|across(?:\s+all)?|total\s+of)\s+(\d+)\s+plugins",
                 r"plugins count[^|\n]{0,40}?exactly\s+(\d+)"], 20, "plugins count", None),
    ("filament_resources", [r"(\d+)\s+(?:Filament\s+)?[Rr]esources\b"], 100, "filament resources", None),
    ("filament_pages", [r"(\d+)\s+(?:Filament\s+)?[Pp]ages\b"], 100, "filament resources", None),
    ("filament_clusters", [r"(\d+)\s+(?:Filament\s+)?[Cc]lusters\b"], 10, "filament resources", None),
    ("filament_widgets", [r"(\d+)\s+(?:Filament\s+)?[Ww]idget(?:s|\s+[Cc]lasses)\b"], 10, "filament resources", None),
]


def _to_int(s):
    return int(s.replace(",", ""))


def _claim_cell(row):
    """The claim text of a '| **COUNT-XXX** | claim | status | evidence | ... |' row,
    isolated from its evidence/path/notes cells so those cannot contaminate a metric's
    canonical value (e.g. a corrective note mentioning the old, superseded number)."""
    parts = row.split("|")
    return parts[2] if len(parts) > 2 else row


@check("numeric-drift", "the same repository-wide metric quoted with different values")
def check_numeric_drift(kb, o, add):
    matrix = kb.files.get("docs/verification-matrix.md", [])
    count_rows = [l for l in matrix if l.startswith("| **COUNT-")]
    for name, pats, minimum, key, canon_pats in METRICS:
        canonical = None
        for row in count_rows:
            claim = _claim_cell(row)
            if key in claim.lower():
                for p in (canon_pats or pats):
                    m = re.search(p, claim)
                    if m:
                        canonical = _to_int(m.group(1))
                        break
            if canonical is not None:
                break
        seen = []   # (value, rel, line)
        for rel, lines in kb.files.items():
            if not _scope_for_counts(rel) or rel in HISTORICAL_FILES:
                continue
            for i, line in enumerate(lines, 1):
                if HIST_RE.search(line):
                    continue
                if SCOPED_COUNT_RE.search(line):
                    continue
                for p in pats:
                    for m in re.finditer(p, line):
                        if APPROX_RE.search(line[max(0, m.start(1) - 16):m.start(1)]):
                            continue
                        v = _to_int(m.group(1))
                        if v >= minimum or "table count" in p:
                            seen.append((v, rel, i))
        if not seen:
            continue
        values = collections.Counter(v for v, _, _ in seen)
        ref = canonical if canonical is not None else values.most_common(1)[0][0]
        src = "verification-matrix" if canonical is not None else "most common value"
        done = set()
        for v, rel, i in seen:
            if v != ref and (rel, i, v) not in done:
                done.add((rel, i, v))
                add(WARN, rel, i, "%s: says %d, expected %d (%s)" % (name, v, ref, src))
    # per-plugin test-file counts quoted in several places
    per = collections.defaultdict(list)
    for rel, lines in kb.files.items():
        if rel in HISTORICAL_FILES or not rel.startswith(("docs/ai/", "docs/architecture/", "docs/development/")):
            continue
        for i, line in enumerate(lines, 1):
            for m in re.finditer(r"`([a-z][a-z0-9\-]+)`\s*\((\d+)\s+test files", line):
                per[m.group(1)].append((int(m.group(2)), rel, i))
    for plugin, occ in sorted(per.items()):
        if len({v for v, _, _ in occ}) > 1:
            add(WARN, occ[0][1], occ[0][2], "test files for '%s' quoted as %s" % (
                plugin, ", ".join("%d (%s:%d)" % (v, r, n) for v, r, n in occ)))


@check("delegation", "AGENTS.md rules that say 'See docs/...' are really covered by that doc")
def check_delegation(kb, o, add):
    src = kb.files.get("AGENTS.md")
    if not src:
        return
    for i, line in enumerate(src, 1):
        m = re.search(r"See\s+`(docs/[^`]+\.md)`", line)
        if not m:
            continue
        target = m.group(1)
        tl = kb.files.get(target)
        if tl is None:
            add(ERROR, "AGENTS.md", i, "delegates to %s which is not in the KB" % target)
            continue
        text = "\n".join(tl)
        terms = [t for t in re.findall(r"`([^`]+)`", line)
                 if "/" not in t and not t.endswith((".md", ".php")) and len(t) >= 3]
        if not terms:
            continue
        missing = [t for t in terms if t not in text]
        if len(missing) == len(terms):
            add(ERROR, "AGENTS.md", i, "delegates to %s, but none of %s appears there" % (
                target, ", ".join("`%s`" % t for t in terms)))
        elif missing:
            add(WARN, "AGENTS.md", i, "delegates to %s, but %s is not covered there" % (
                target, ", ".join("`%s`" % t for t in missing)))


@check("unknowns", "empty 'Unknowns' sections (Zero Unknowns is not complete understanding)")
def check_unknowns(kb, o, add):
    for rel, lines in kb.files.items():
        if rel in ANTI_EXAMPLE_FILES or rel in HISTORICAL_FILES:
            continue
        for i, line in enumerate(lines, 1):
            if re.match(r"^\s*None identified\.?\s*$", line, re.I):
                window = " ".join(lines[max(0, i - 6):i - 1])
                if re.search(r"(?i)unknown", window):
                    add(WARN, rel, i, "'None identified' under an Unknowns heading (see Forbidden Pattern #8)")


@check("sizes", "oversized documents an agent might read whole")
def check_sizes(kb, o, add):
    total = 0
    for rel in sorted(kb.files, key=lambda r: -kb.size(r)):
        s = kb.size(rel)
        total += s
        if s > o.max_kb * 1024:
            head = "\n".join(kb.lines_of(rel)[:20])
            if SEARCH_ONLY_RE.search(head):
                add(INFO, rel, 0, "%d KB but declared search-only in its header (see docs/ai/reading-order.md)" % (s // 1024))
            else:
                add(WARN, rel, 0, "%d KB (~%dk tokens): search it (rg) or split it, do not read it whole" % (s // 1024, s // 4000))
    add(INFO, "", 0, "whole KB: %.2f MB (~%.0fk tokens by the bytes/4 estimate)" % (total / 1048576.0, total / 4000.0))

def _effective_size(kb, rel, factor=0.15):
    """Size an agent is actually expected to load. Search-only files are
    discounted: the agent runs rg and reads a section, not the whole file."""
    head = "\n".join(kb.lines_of(rel)[:20])
    if SEARCH_ONLY_RE.search(head):
        return int(kb.size(rel) * factor)
    return kb.size(rel)

@check("reading-sets", "token cost of each task row in docs/ai/reading-order.md")
def check_reading_sets(kb, o, add):
    rel = "docs/ai/reading-order.md"
    lines = kb.files.get(rel)
    if not lines:
        return
    base = ["AGENTS.md", "docs/ai/context.md", rel]
    for i, line in enumerate(lines, 1):
        if not line.startswith("|"):
            continue
        cells = [c.strip() for c in line.strip().strip("|").split("|")]
        if len(cells) < 3 or set(cells[0]) <= set("-: ") or cells[0].lower() == "task":
            continue
        paths = re.findall(r"`(docs/[^`]+?\.md)`", cells[1])
        if not paths:
            continue
        files = base + [p for p in paths if p not in base]
        total = sum(_effective_size(kb, f) for f in files)
        tokens = total // 4
        sev = WARN if tokens > o.max_read_tokens else INFO
        add(sev, rel, i, "'%s': %d docs, %d KB (~%dk tokens)" % (cells[0][:55], len(files), total // 1024, tokens // 1000))


@check("plugin-index", "docs/plugins/README.md table vs plugin docs vs 'core' wording elsewhere")
def check_plugin_index(kb, o, add):
    rel = "docs/plugins/README.md"
    lines = kb.files.get(rel)
    if not lines:
        return
    klass = {}
    for i, line in enumerate(lines, 1):
        m = re.match(r"^\|\s*`([a-z][a-z0-9\-]*)`\s*\|\s*[^|]*\|\s*(Core|Optional)\s*\|", line)
        if m:
            klass[m.group(1)] = m.group(2)
            if "docs/plugins/%s.md" % m.group(1) not in kb.files:
                add(ERROR, rel, i, "plugin '%s' is listed but docs/plugins/%s.md does not exist" % (m.group(1), m.group(1)))
    for f in kb.files:
        if f.startswith("docs/plugins/") and f != rel:
            name = posixpath.basename(f)[:-3]
            if name not in klass:
                add(WARN, f, 0, "plugin doc is not listed in the docs/plugins/README.md table")
    if klass:
        add(INFO, rel, 0, "table lists %d plugins (%d Core, %d Optional)" % (
            len(klass), sum(1 for v in klass.values() if v == "Core"), sum(1 for v in klass.values() if v == "Optional")))
    for f, ls in kb.files.items():
        if not f.startswith(("docs/ai/", "docs/architecture/", "docs/README.md")):
            continue
        for i, line in enumerate(ls, 1):
            for m in re.finditer(r"(?i)\bcore\b[^.\n|]{0,60}?plugins?\s*\((?:such as|e\.g\.,?)\s*([^)]*)\)", line):
                for name in re.findall(r"`([a-z][a-z0-9\-]*)`", m.group(1)):
                    if klass.get(name) == "Optional":
                        add(WARN, f, i, "'%s' is called core here but is Optional in docs/plugins/README.md" % name)


@check("orphans", "documents that no other KB file links to or mentions by path")
def check_orphans(kb, o, add):
    inbound = collections.Counter()
    path_re = re.compile(r"(docs/[A-Za-z0-9_./\-]+\.md)")
    for rel, lines in kb.files.items():
        text = "\n".join(lines)
        base = posixpath.dirname(rel)
        targets = set()
        for mt in LINK_RE.finditer(text):
            t = mt.group(1)
            if SCHEME_RE.match(t):
                continue
            p = unquote(t.partition("#")[0])
            if p:
                targets.add(posixpath.normpath(posixpath.join(base, p)))
        targets.update(path_re.findall(text))
        for t in targets:
            if t != rel:
                inbound[t] += 1
    entry = {"AGENTS.md", "CLAUDE.md", "docs/README.md"}
    for rel in sorted(kb.files):
        if rel in entry or rel.endswith("/SKILL.md"):
            continue
        if inbound[rel] == 0:
            add(WARN, rel, 0, "no inbound link or path mention from any other KB file")


@check("evidence-stats", "distribution of [VERIFIED]/[PARTIALLY VERIFIED]/[INFERRED]/[UNKNOWN] labels")
def check_evidence_stats(kb, o, add):
    tags = ["[VERIFIED]", "[PARTIALLY VERIFIED]", "[INFERRED]", "[UNKNOWN]"]
    counts, unk_files, n = collections.Counter(), 0, 0
    for rel, lines in kb.files.items():
        if not rel.startswith("docs/"):
            continue
        n += 1
        text = "\n".join(lines)
        for t in tags:
            counts[t] += text.count(t)
        if "[UNKNOWN]" in text:
            unk_files += 1
    if n:
        add(INFO, "docs/", 0, "labels: %s; files containing [UNKNOWN]: %d of %d" % (
            ", ".join("%s=%d" % (t, counts[t]) for t in tags), unk_files, n))

FORBIDDEN_HEADING_ALLOW_RE = re.compile(r"<!--\s*lint:\s*allow-forbidden-heading\b[^>]*-->")
FORBIDDEN_HEADING_RE = re.compile(
    r"^ {0,3}#{2,6}\s+(Enabled\s*/\s*Disabled|Runtime\s+(?:Plugin\s+)?Dependencies)\s*#*\s*$",
    re.I,
)


def _plugin_facts(kb):
    if hasattr(kb, "_cached_plugin_facts"):
        return kb._cached_plugin_facts
    readme_rel = "docs/plugins/README.md"
    readme_lines = kb.files.get(readme_rel, [])
    readme_state = {}
    readme_install = {}
    readme_dependents = {}
    readme_row_line = {}
    header_ok = False
    for i, line in enumerate(readme_lines, 1):
        if line.startswith("| Plugin Slug |") and "Install Plugins Dependency" in line and "Dependents" in line:
            header_ok = True
        m = re.match(r"^\|\s*`([a-z][a-z0-9\-]*)`\s*\|", line)
        if not m:
            continue
        cells = [c.strip() for c in line.strip().strip("|").split("|")]
        if len(cells) < 8:
            continue
        slug = m.group(1)
        state = cells[2]
        inst = sorted(re.findall(r"`([a-z][a-z0-9\-]*)`", cells[5]))
        deps = sorted(re.findall(r"`([a-z][a-z0-9\-]*)`", cells[6]))
        readme_state[slug] = state
        readme_install[slug] = inst
        readme_dependents[slug] = deps
        readme_row_line[slug] = i

    src_is_core = {}
    src_install = {}
    src_provider = {}
    composer_webkul = {}
    ns_to_slug = {}
    plugins_dir = kb.root / "plugins" / "webkul"
    if kb.repo_mode and plugins_dir.is_dir():
        for d in sorted(plugins_dir.iterdir()):
            if not d.is_dir():
                continue
            slug = d.name
            cj_path = d / "composer.json"
            if cj_path.is_file():
                try:
                    cj = json.loads(cj_path.read_text(encoding="utf-8"))
                except Exception:
                    cj = {}
                composer_webkul[slug] = sorted(
                    k for k in cj.get("require", {}) if k.startswith("webkul/")
                )
                for ns in cj.get("autoload", {}).get("psr-4", {}):
                    if ns.endswith("\\") and ns.count("\\") == 2:
                        ns_to_slug[ns] = slug
            is_core = False
            inst = []
            prov_rel = "plugins/webkul/%s/src" % slug
            src_dir = d / "src"
            if src_dir.is_dir():
                for pf in sorted(src_dir.glob("*ServiceProvider.php")):
                    prov_rel = pf.relative_to(kb.root).as_posix()
                    ptxt = pf.read_text(encoding="utf-8", errors="replace")
                    if re.search(r"->isCore\(\s*(?:true\s*)?\)", ptxt):
                        is_core = True
                    for bm in re.finditer(r"hasDependencies\(\s*\[(.*?)\]\s*\)", ptxt, re.S):
                        inst.extend(re.findall(r"['\"]([a-z][a-z0-9\-]*)['\"]", bm.group(1)))
                    for sm in re.finditer(r"hasDependency\(\s*['\"]([a-z][a-z0-9\-]*)['\"]\s*\)", ptxt):
                        inst.append(sm.group(1))
            src_is_core[slug] = is_core
            src_install[slug] = sorted(set(inst))
            src_provider[slug] = prov_rel

    kb._cached_plugin_facts = {
        "header_ok": header_ok,
        "readme_state": readme_state,
        "readme_install": readme_install,
        "readme_dependents": readme_dependents,
        "readme_row_line": readme_row_line,
        "src_is_core": src_is_core,
        "src_install": src_install,
        "src_provider": src_provider,
        "composer_webkul": composer_webkul,
        "ns_to_slug": ns_to_slug,
    }
    return kb._cached_plugin_facts


@check("forbidden-headings", "prohibited headings (## Enabled/Disabled, ## Runtime (Plugin) Dependencies)")
def check_forbidden_headings(kb, o, add):
    for rel, lines in kb.files.items():
        if rel in HISTORICAL_FILES:
            continue
        if FORBIDDEN_HEADING_ALLOW_RE.search("\n".join(lines)):
            continue
        mask = fence_mask(lines)
        for i, (line, in_fence) in enumerate(zip(lines, mask), 1):
            if in_fence:
                continue
            m = FORBIDDEN_HEADING_RE.match(line)
            if m:
                add(ERROR, rel, i, "forbidden heading '%s' (use '## Status' or '## Install Plugins Dependency')" % m.group(1))


@check("install-deps-mirror", "docs/plugins/README.md Install Plugins Dependency mirrors hasDependencies()")
def check_install_deps_mirror(kb, o, add):
    facts = _plugin_facts(kb)
    rel = "docs/plugins/README.md"
    if rel not in kb.files:
        return
    if not facts["header_ok"]:
        add(ERROR, rel, 1, "missing 'Install Plugins Dependency' or 'Dependents' column header in plugin table")
        return
    if not facts["src_install"]:
        return
    for slug, doc_deps in sorted(facts["readme_install"].items()):
        if slug not in facts["src_install"]:
            continue
        src_deps = facts["src_install"][slug]
        if doc_deps != src_deps:
            add(
                ERROR,
                rel,
                facts["readme_row_line"].get(slug, 0),
                "plugin '%s': README Install Plugins Dependency is %s but source hasDependencies() declares %s"
                % (slug, doc_deps, src_deps),
            )


@check("core-install-empty", "Core plugins must not declare any Install Plugins Dependency")
def check_core_install_empty(kb, o, add):
    facts = _plugin_facts(kb)
    rel = "docs/plugins/README.md"
    for slug, state in sorted(facts["readme_state"].items()):
        if state == "Core" and facts["readme_install"].get(slug):
            add(
                ERROR,
                rel,
                facts["readme_row_line"].get(slug, 0),
                "Core plugin '%s' must not declare Install Plugins Dependency in README (got %s)"
                % (slug, facts["readme_install"][slug]),
            )
    for slug, is_core in sorted(facts["src_is_core"].items()):
        if is_core and facts["src_install"].get(slug):
            add(
                ERROR,
                facts["src_provider"].get(slug, rel),
                0,
                "Core plugin '%s' must have empty hasDependencies() in source (got %s)"
                % (slug, facts["src_install"][slug]),
            )


@check("optional-no-core", "Optional plugins must not list any Core plugin in Install Plugins Dependency")
def check_optional_no_core(kb, o, add):
    facts = _plugin_facts(kb)
    rel = "docs/plugins/README.md"
    core_doc = {s for s, st in facts["readme_state"].items() if st == "Core"}
    core_src = {s for s, ic in facts["src_is_core"].items() if ic}
    core_all = core_doc | core_src
    for slug, state in sorted(facts["readme_state"].items()):
        if state == "Optional":
            leaked = sorted(set(facts["readme_install"].get(slug, [])) & core_all)
            if leaked:
                add(
                    ERROR,
                    rel,
                    facts["readme_row_line"].get(slug, 0),
                    "Optional plugin '%s' lists Core plugin(s) %s in Install Plugins Dependency"
                    % (slug, leaked),
                )
    for slug, is_core in sorted(facts["src_is_core"].items()):
        if not is_core:
            leaked = sorted(set(facts["src_install"].get(slug, [])) & core_all)
            if leaked:
                add(
                    ERROR,
                    facts["src_provider"].get(slug, rel),
                    0,
                    "Optional plugin '%s' declares Core plugin(s) %s in hasDependencies()"
                    % (slug, leaked),
                )


@check("core-set-mirror", "docs/plugins/README.md Core/Optional state mirrors Package::isCore()")
def check_core_set_mirror(kb, o, add):
    facts = _plugin_facts(kb)
    rel = "docs/plugins/README.md"
    if not facts["src_is_core"]:
        return
    for slug, state in sorted(facts["readme_state"].items()):
        if slug not in facts["src_is_core"]:
            continue
        expected = "Core" if facts["src_is_core"][slug] else "Optional"
        if state != expected:
            add(
                ERROR,
                rel,
                facts["readme_row_line"].get(slug, 0),
                "plugin '%s' is '%s' in README but '%s' in source provider"
                % (slug, state, expected),
            )


@check("dependents-mirror", "docs/plugins/README.md Dependents column equals reverse of Install Plugins Dependency")
def check_dependents_mirror(kb, o, add):
    facts = _plugin_facts(kb)
    rel = "docs/plugins/README.md"
    install_map = facts["readme_install"]
    for slug, doc_dependents in sorted(facts["readme_dependents"].items()):
        expected = sorted(p for p, deps in install_map.items() if slug in deps)
        if doc_dependents != expected:
            add(
                ERROR,
                rel,
                facts["readme_row_line"].get(slug, 0),
                "plugin '%s': Dependents column is %s but reverse of Install Plugins Dependency is %s"
                % (slug, doc_dependents, expected),
            )


@check("plugin-doc-status", "every plugin spec has ## Status and no duplicate ## headings")
def check_plugin_doc_status(kb, o, add):
    for rel, lines in sorted(kb.files.items()):
        if not rel.startswith("docs/plugins/") or rel == "docs/plugins/README.md":
            continue
        mask = fence_mask(lines)
        seen_h2 = {}
        has_status = False
        for i, (line, in_fence) in enumerate(zip(lines, mask), 1):
            if in_fence:
                continue
            m = re.match(r"^ {0,3}##\s+(.*?)\s*#*\s*$", line)
            if not m:
                continue
            title = m.group(1).strip()
            key = title.lower()
            if key == "status":
                has_status = True
            if key in seen_h2:
                add(ERROR, rel, i, "duplicate H2 heading '## %s' (first at line %d)" % (title, seen_h2[key]))
            else:
                seen_h2[key] = i
        if not has_status:
            add(ERROR, rel, 1, "missing required '## Status' section")


@check("composer-webkul-empty", "webkul/* entries in plugin composer.json require must be documented")
def check_composer_webkul(kb, o, add):
    facts = _plugin_facts(kb)
    for slug, reqs in sorted(facts["composer_webkul"].items()):
        if not reqs:
            continue
        doc_rel = "docs/plugins/%s.md" % slug
        lines = kb.files.get(doc_rel, [])
        sec_lines, in_sec = [], False
        for line in lines:
            if re.match(r"^ {0,3}##\s+Composer\s+Dependencies\b", line, re.I):
                in_sec = True
                continue
            if in_sec and re.match(r"^ {0,3}##\s+", line):
                break
            if in_sec:
                sec_lines.append(line)
        sec_txt = "\n".join(sec_lines)
        missing = [r for r in reqs if not re.search(r"(?<!/)\b" + re.escape(r) + r"\b", sec_txt)]
        if missing:
            add(
                WARN,
                "plugins/webkul/%s/composer.json" % slug,
                0,
                "webkul/* composer require %s not documented in %s" % (missing, doc_rel),
            )


@check("consumption-stats", "code-level consumption (use Webkul\\...) across plugins: declared vs guarded vs unguarded")
def check_consumption_stats(kb, o, add):
    facts = _plugin_facts(kb)
    ns_to_slug = facts["ns_to_slug"]
    if not kb.repo_mode or not ns_to_slug:
        return
    install_map = facts["src_install"]
    core_set = {s for s, ic in facts["src_is_core"].items() if ic}

    def closure(slug, seen=None):
        seen = seen or set()
        for dep in install_map.get(slug, []):
            if dep not in seen:
                seen.add(dep)
                closure(dep, seen)
        return seen

    closures = {s: closure(s) for s in install_map}
    use_re = re.compile(r"^\s*use\s+(Webkul\\[A-Za-z]+\\)([^\n;]+);", re.M)
    guard_re = re.compile(r"isPluginInstalled|class_exists|Schema::hasTable|Schema::hasColumn")

    core_imp = decl_imp = guard_imp = unguard_imp = 0
    plugins_dir = kb.root / "plugins" / "webkul"
    for slug in sorted(install_map):
        src_dir = plugins_dir / slug / "src"
        if not src_dir.is_dir():
            continue
        cl = closures[slug]
        for php in sorted(src_dir.rglob("*.php")):
            ptxt = php.read_text(encoding="utf-8", errors="replace")
            has_guard = bool(guard_re.search(ptxt))
            for m in use_re.finditer(ptxt):
                target = ns_to_slug.get(m.group(1))
                if not target or target == slug:
                    continue
                if target in core_set:
                    core_imp += 1
                elif target in cl:
                    decl_imp += 1
                elif has_guard:
                    guard_imp += 1
                else:
                    unguard_imp += 1

    add(
        INFO,
        "plugins/webkul/",
        0,
        "cross-plugin PHP imports: core=%d, declared-optional=%d, file-guarded-optional=%d, other-undeclared-optional=%d"
        % (core_imp, decl_imp, guard_imp, unguard_imp),
    )


# --------------------------------------------------------------------------- driver
def build_parser():
    ap = argparse.ArgumentParser(description="Static consistency checks for an AI knowledge base.")
    ap.add_argument("--root", default=".", help="repository root (or extracted bundle root)")
    ap.add_argument("--only", default="", help="comma-separated checks to run")
    ap.add_argument("--skip", default="", help="comma-separated checks to skip")
    ap.add_argument("--json", action="store_true", help="print JSON instead of text")
    ap.add_argument("--strict", action="store_true", help="exit 1 on warnings as well as errors")
    ap.add_argument("--all", action="store_true", help="do not truncate findings per check")
    ap.add_argument("--max-per-check", type=int, default=15)
    ap.add_argument("--today", default="", help="override today's date (YYYY-MM-DD)")
    ap.add_argument("--max-age-days", type=int, default=90, help="last_verified staleness limit")
    ap.add_argument("--max-kb", type=int, default=100, help="size warning threshold per document")
    ap.add_argument("--max-read-tokens", type=int, default=40000, help="warning threshold per reading set")
    ap.add_argument("--list-checks", action="store_true")
    return ap


def main(argv=None):
    args = build_parser().parse_args(argv)
    for stream in (sys.stdout, sys.stderr):
        try:
            stream.reconfigure(encoding="utf-8", errors="replace")
        except Exception:
            pass
    if args.list_checks:
        for name, (desc, _) in CHECKS.items():
            print("%-16s %s" % (name, desc))
        return 0
    root = Path(args.root).resolve()
    if not root.is_dir():
        print("root not found: %s" % root, file=sys.stderr)
        return 2
    try:
        args.today = dt.date.fromisoformat(args.today) if args.today else dt.date.today()
    except ValueError:
        print("--today must be YYYY-MM-DD", file=sys.stderr)
        return 2
    only = [x.strip() for x in args.only.split(",") if x.strip()]
    skip = {x.strip() for x in args.skip.split(",") if x.strip()}
    unknown = [x for x in only + sorted(skip) if x not in CHECKS]
    if unknown:
        print("unknown check(s): %s" % ", ".join(unknown), file=sys.stderr)
        return 2
    kb = KB(root)
    if not kb.files:
        print("no AGENTS.md / docs/ / skills found under %s" % root, file=sys.stderr)
        return 2

    findings = []
    for name in (only or list(CHECKS)):
        if name in skip:
            continue

        def add(sev, path, line, msg, _n=name):
            findings.append(Finding(_n, sev, path, line, msg))
        CHECKS[name][1](kb, args, add)

    findings.sort(key=lambda f: (list(CHECKS).index(f.check), SEV_ORDER[f.sev], f.path, f.line))
    n = collections.Counter(f.sev for f in findings)
    mode = "repo" if kb.repo_mode else "bundle"

    if args.json:
        print(json.dumps({
            "root": str(root), "mode": mode, "files": len(kb.files), "today": str(args.today),
            "summary": {"errors": n[ERROR], "warnings": n[WARN], "info": n[INFO]},
            "findings": [f._asdict() for f in findings]}, indent=2, ensure_ascii=False))
    else:
        print("kb_lint  root=%s  mode=%s  files=%d  today=%s" % (root, mode, len(kb.files), args.today))
        print("-" * 78)
        per = collections.defaultdict(collections.Counter)
        for f in findings:
            per[f.check][f.sev] += 1
        print("%-16s %6s %6s %6s" % ("check", "ERROR", "WARN", "INFO"))
        for name in CHECKS:
            if name in per:
                c = per[name]
                print("%-16s %6d %6d %6d" % (name, c[ERROR], c[WARN], c[INFO]))
        print("-" * 78)
        shown = collections.Counter()
        for f in findings:
            shown[f.check] += 1
            if not args.all and shown[f.check] > args.max_per_check:
                if shown[f.check] == args.max_per_check + 1:
                    rest = sum(per[f.check].values()) - args.max_per_check
                    print("   ... %d more in '%s' (use --all)" % (rest, f.check))
                continue
            loc = f.path + (":%d" % f.line if f.line else "")
            print("[%s] %-14s %s%s" % (f.sev, f.check, loc + "  " if loc else "", f.msg))
        print("-" * 78)
        print("Summary: %d error(s), %d warning(s), %d info" % (n[ERROR], n[WARN], n[INFO]))

    if n[ERROR] or (args.strict and n[WARN]):
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
