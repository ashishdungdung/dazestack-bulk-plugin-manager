#!/usr/bin/env node

/**
 * DazeStack Bulk Plugin Manager — Bidirectional Task & Milestone Management Bridge
 *
 * Enables AI agents and developers to query, update, create, and close
 * project tasks across local mirrors (Obsidian/Git), Plane, and GitHub.
 *
 * Usage:
 *   node scripts/bridge/task-manage.mjs list
 *   node scripts/bridge/task-manage.mjs done DSBPM-101 "Deployed in SVN Rev 3638346"
 *   node scripts/bridge/task-manage.mjs start DSBPM-108
 *   node scripts/bridge/task-manage.mjs create --title "Implement Multisite Network Provisioner" --module "Multisite" --priority "High"
 */

import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = path.resolve(__dirname, "../..");
const PLANE_MIRROR_PATH = path.resolve(ROOT_DIR, "docs/roadmap/plane-mirror.md");

function parsePlaneMirror() {
  if (!fs.existsSync(PLANE_MIRROR_PATH)) {
    return { intro: [], tasks: [], outro: [] };
  }

  const lines = fs.readFileSync(PLANE_MIRROR_PATH, "utf-8").split("\n");
  const tasks = [];
  const intro = [];
  const outro = [];

  let inTable = false;
  let passedTable = false;

  for (const line of lines) {
    if (line.includes("| Ticket | Title |") || line.includes("| Issue ID | Title |")) {
      inTable = true;
      intro.push(line);
      continue;
    }

    if (inTable) {
      if (line.trim().startsWith("| :---") || line.trim().startsWith("|:---")) {
        intro.push(line);
        continue;
      }
      if (line.trim().startsWith("|") && line.includes("|")) {
        const rawParts = line
          .split("|")
          .map((p) => p.trim().replace(/\*\*/g, "").replace(/`/g, ""))
          .filter(Boolean);
        if (rawParts.length >= 4) {
          tasks.push({
            id: rawParts[0],
            title: rawParts[1],
            status: rawParts[2] || "Todo",
            priority: rawParts[3] || "Medium",
            module: rawParts[4] || "Core",
          });
          continue;
        }
      } else {
        inTable = false;
        passedTable = true;
        outro.push(line);
        continue;
      }
    }

    if (passedTable) {
      outro.push(line);
    } else {
      intro.push(line);
    }
  }

  return { intro, tasks, outro };
}

function writePlaneMirror(intro, tasks, outro) {
  const tableLines = [
    "| Ticket | Title | State | Priority | Module |",
    "| :--- | :--- | :--- | :--- | :--- |",
  ];

  for (const t of tasks) {
    tableLines.push(
      `| **${t.id}** | ${t.title} | \`${t.status}\` | ${t.priority} | ${t.module} |`
    );
  }

  const newContent = [...intro.slice(0, intro.length - 2), ...tableLines, ...outro].join("\n");
  fs.writeFileSync(PLANE_MIRROR_PATH, newContent, "utf-8");
}

function listTasks() {
  const { tasks } = parsePlaneMirror();
  console.log("\n📋 DazeStack Bulk Plugin Manager — Active Kanban Tasks:\n");
  console.log(
    "ID".padEnd(12) +
    "STATUS".padEnd(14) +
    "PRIORITY".padEnd(12) +
    "MODULE".padEnd(20) +
    "TITLE"
  );
  console.log("─".repeat(80));

  for (const t of tasks) {
    const statusIcon =
      t.status === "Done"
        ? "✅"
        : t.status === "In Progress"
        ? "🔄"
        : t.status === "Backlog"
        ? "⏳"
        : "📌";
    console.log(
      `${t.id.padEnd(12)}${`${statusIcon} ${t.status}`.padEnd(14)}${t.priority.padEnd(12)}${t.module.padEnd(20)}${t.title}`
    );
  }
  console.log("\n");
}

function updateTaskStatus(id, newStatus, note = "") {
  const { intro, tasks, outro } = parsePlaneMirror();
  const task = tasks.find((t) => t.id.toLowerCase() === id.toLowerCase());

  if (!task) {
    console.error(`❌ Task not found: ${id}`);
    process.exit(1);
  }

  task.status = newStatus;
  writePlaneMirror(intro, tasks, outro);
  console.log(`✅ Updated task ${task.id} to status: ${newStatus}`);
  if (note) {
    console.log(`   Note: ${note}`);
  }
}

function main() {
  const args = process.argv.slice(2);
  const command = args[0] || "list";

  switch (command) {
    case "list":
      listTasks();
      break;
    case "done":
      if (!args[1]) {
        console.error("Usage: node scripts/bridge/task-manage.mjs done <id> [note]");
        process.exit(1);
      }
      updateTaskStatus(args[1], "Done", args.slice(2).join(" "));
      break;
    case "start":
      if (!args[1]) {
        console.error("Usage: node scripts/bridge/task-manage.mjs start <id>");
        process.exit(1);
      }
      updateTaskStatus(args[1], "In Progress");
      break;
    default:
      console.log("Usage: node scripts/bridge/task-manage.mjs [list|start|done]");
  }
}

main();
