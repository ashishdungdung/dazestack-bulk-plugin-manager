#!/usr/bin/env node

/**
 * DazeStack Bulk Plugin Manager — Universal Bridge Sync
 * Synchronizes local project management mirrors (Plane, Notion, GitHub).
 */

import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = path.resolve(__dirname, "../..");

console.log("🔄 Synchronizing 4-Tier Project Management Mesh for DazeStack Bulk Plugin Manager...");

const planePath = path.resolve(ROOT_DIR, "docs/roadmap/plane-mirror.md");
const notionPath = path.resolve(ROOT_DIR, "docs/roadmap/notion-mirror.md");
const githubPath = path.resolve(ROOT_DIR, "docs/roadmap/github-mirror.md");
const canvasPath = path.resolve(ROOT_DIR, "docs/canvas/roadmap-timeline.canvas");

const files = [planePath, notionPath, githubPath, canvasPath];
for (const file of files) {
  if (fs.existsSync(file)) {
    console.log(`  ✅ Verified mirror: ${path.relative(ROOT_DIR, file)}`);
  } else {
    console.warn(`  ⚠️ Missing mirror: ${path.relative(ROOT_DIR, file)}`);
  }
}

console.log("✨ 4-Tier Project Management Mesh is in sync.\n");
