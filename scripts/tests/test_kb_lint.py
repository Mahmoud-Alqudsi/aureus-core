import importlib.util
import json
import sys
import tempfile
import unittest
from pathlib import Path
from types import SimpleNamespace


REPO_ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location("kb_lint", REPO_ROOT / "scripts" / "kb_lint.py")
kb_lint = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = kb_lint
SPEC.loader.exec_module(kb_lint)


class KnowledgeBaseLintTest(unittest.TestCase):
    def setUp(self):
        self.temp_dir = tempfile.TemporaryDirectory()
        self.root = Path(self.temp_dir.name)
        (self.root / "docs/ai").mkdir(parents=True)
        (self.root / "docs/development").mkdir(parents=True)
        (self.root / ".github").mkdir()
        (self.root / "composer.json").write_text(json.dumps({"name": "fixture/app"}), encoding="utf-8")

    def tearDown(self):
        self.temp_dir.cleanup()

    def findings(self, check_name):
        kb = kb_lint.KB(self.root)
        results = []

        def add(severity, path, line, message):
            results.append((severity, path, line, message))

        kb_lint.CHECKS[check_name][1](kb, SimpleNamespace(), add)
        return results

    def test_ownership_map_rejects_duplicate_topics_and_missing_owner(self):
        (self.root / "AGENTS.md").write_text("# Instructions\n", encoding="utf-8")
        (self.root / "docs/ai/knowledge-ownership.md").write_text(
            "| Topic | Canonical owner | Supporting references |\n"
            "| --- | --- | --- |\n"
            "| Shared rules | `AGENTS.md` | None |\n"
            "| Shared rules | `docs/missing.md` | None |\n",
            encoding="utf-8",
        )
        findings = self.findings("knowledge-ownership")
        messages = [finding[3] for finding in findings]
        self.assertTrue(any("duplicate ownership topic" in message for message in messages))
        self.assertTrue(any("does not exist" in message for message in messages))

    def test_assistant_entrypoints_must_route_to_agents(self):
        (self.root / "AGENTS.md").write_text("# Agent instructions\n", encoding="utf-8")
        (self.root / "GEMINI.md").write_text("@AGENTS.md\n", encoding="utf-8")
        (self.root / ".github/copilot-instructions.md").write_text("Follow AGENTS.md\n", encoding="utf-8")
        (self.root / "docs/development/ai-skills.md").write_text(
            "GitHub Copilot\nGemini CLI\nCodex\n", encoding="utf-8"
        )
        self.assertEqual([], self.findings("assistant-entrypoints"))

        (self.root / ".github/copilot-instructions.md").write_text("Independent rules\n", encoding="utf-8")
        findings = self.findings("assistant-entrypoints")
        self.assertTrue(any("route to the repository-root AGENTS.md" in item[3] for item in findings))

    def test_source_hierarchy_copy_is_rejected(self):
        (self.root / "AGENTS.md").write_text("## Source-of-Truth Hierarchy\n", encoding="utf-8")
        (self.root / "docs/ai/context.md").write_text(
            "Source Code > Automated Tests > Migrations & DB Schema\n", encoding="utf-8"
        )
        findings = self.findings("source-hierarchy-owner")
        self.assertTrue(any("duplicates the source-of-truth ordering" in item[3] for item in findings))

    def test_workflow_inventory_must_match_files_on_disk(self):
        workflow_dir = self.root / ".github/workflows"
        workflow_dir.mkdir()
        (workflow_dir / "first.yml").write_text("name: First\n", encoding="utf-8")
        (workflow_dir / "second.yml").write_text("name: Second\n", encoding="utf-8")
        (self.root / "docs/development/ci-testing-governance.md").write_text(
            "The pipeline consists of 1 distinct workflow files: `first.yml`.\n", encoding="utf-8"
        )
        findings = self.findings("workflow-index")
        messages = [finding[3] for finding in findings]
        self.assertTrue(any("declares 1 workflows but 2 workflow files exist" in message for message in messages))
        self.assertTrue(any("second.yml" in message for message in messages))


if __name__ == "__main__":
    unittest.main()