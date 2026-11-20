#!/usr/bin/env node

/**
 * DazeStack Bulk Plugin Manager — Git Commit & Task Traceability Validator
 *
 * Verifies that commit messages follow conventional formats and reference
 * active Plane/GitHub issues (e.g. `[DSBPM-101]`, `[Wave 2]`, or `[closes #12]`).
 *
 * Usage:
 *   node scripts/bridge/verify-commit.mjs <commit_msg_file_or_string>
 */

import fs from "node:fs";

const CONVENTIONAL_PATTERN = /^(feat|fix|docs|style|refactor|perf|test|build|ci|chore|revert|sec)(\([a-z0-9_-]+\))?:\s.+/;
const ISSUE_OR_WAVE_PATTERN = /(\[DSBPM-\d+\]|\[Wave\s*\d+\]|#\d+|\[closes\s*#\d+\])/i;

function main() {
  const arg = process.argv[2];
  if (!arg) {
    console.log("Usage: node scripts/bridge/verify-commit.mjs <commit_msg_file_or_text>");
    process.exit(0);
  }

  let commitMessage = arg;
  if (fs.existsSync(arg)) {
    commitMessage = fs.readFileSync(arg, "utf-8").trim();
  }

  // Strip comments and empty lines
  const firstLine = commitMessage
    .split("\n")
    .map((l) => l.trim())
    .filter((l) => l.length > 0 && !l.startsWith("#"))[0];

  if (!firstLine) {
    console.error("❌ Commit message is empty.");
    process.exit(1);
  }

  // Check conventional format
  if (!CONVENTIONAL_PATTERN.test(firstLine)) {
    console.error("\n❌ Commit message does not follow Conventional Commits specification!");
    console.error(`   Received: "${firstLine}"`);
    console.error("   Expected format: <type>(<scope>): <subject> [DSBPM-xxx]");
    console.error("   Valid types: feat, fix, docs, refactor, perf, test, chore, sec\n");
    process.exit(1);
  }

  // Check issue/wave traceability advisory
  if (!ISSUE_OR_WAVE_PATTERN.test(firstLine) && !ISSUE_OR_WAVE_PATTERN.test(commitMessage)) {
    console.warn("⚠️  Advisory: Commit does not link to an active task (e.g. `[DSBPM-101]` or `[Wave 2]`).");
    console.warn("   Tip: Include `[DSBPM-xxx]` to auto-synchronize with Plane & GitHub Projects.\n");
  } else {
    console.log("✅ Commit message format and task traceability verified.");
  }
}

main();
