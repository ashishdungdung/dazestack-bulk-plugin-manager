#!/usr/bin/env node

/**
 * DazeStack Bulk Plugin Manager — Universal AI Mesh Client
 *
 * Query models or domain engines across the 4-tier AI Mesh from terminal.
 *
 * Usage:
 *   node scripts/ai/ai_mesh_client.mjs health
 *   node scripts/ai/ai_mesh_client.mjs haiku "Analyze queue batch concurrency"
 *   node scripts/ai/ai_mesh_client.mjs ash-website "Best practice for WP options autoload"
 */

import fs from "node:fs";
import path from "node:path";
import os from "node:os";

const VAULT_PATH = path.resolve(os.homedir(), ".config/antigravity/ai_mesh.env");

function loadVault() {
  if (!fs.existsSync(VAULT_PATH)) {
    return {};
  }
  const lines = fs.readFileSync(VAULT_PATH, "utf-8").split("\n");
  const env = {};
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#")) continue;
    const [key, ...val] = trimmed.split("=");
    if (key && val) {
      env[key.trim()] = val.join("=").trim().replace(/^["']|["']$/g, "");
    }
  }
  return env;
}

async function checkHealth() {
  const vault = loadVault();
  console.log("\n🛰️ Antigravity AI Mesh Health Diagnostic (DSBPM):\n");
  console.log(`  • Central Vault: ${VAULT_PATH} (${fs.existsSync(VAULT_PATH) ? "✅ Active" : "❌ Missing"})`);
  console.log(`  • Tier 0 (Local Silicon Grid): http://127.0.0.1:1234/v1 (Gemma 4 12B QAT, $0.00)`);
  console.log(`  • Tier 1 (NVIDIA NIM): ${vault.NVIDIA_API_KEY ? "✅ Configured" : "⚠️ Key Missing"} (Kimi-K3 1M, DeepSeek-R1)`);
  console.log(`  • Tier 2 (Experiential Labs Gateway): ${vault.EXPLABS_API_KEY ? "✅ Configured" : "⚠️ Key Missing"} (Haiku 5.5, Mercury 3, Luna)`);
  console.log(`  • Tier 3 (Cross-Project Domain Engines): AshWebsite, SolarQna, AndroidEmulator, Helmetsan\n`);
}

async function main() {
  const [cmd, ...queryParts] = process.argv.slice(2);
  const query = queryParts.join(" ");

  if (!cmd || cmd === "health") {
    await checkHealth();
    return;
  }

  console.log(`\n🤖 AI Mesh Consultation: [${cmd}] -> "${query}"`);
  console.log(`   (Tip: In Antigravity IDE, call native MCP tools: ai_mesh_consult_${cmd.replace(/-/g, "_")})\n`);
}

main();
