# Universal Agent Specification & Workspace Rules (AGENTS.md)

> **Scope:** Universal multi-agent orchestration for **Antigravity IDE**, **Zed IDE**, **Cursor Desktop**, **Claude Code**, and **OpenAI Codex**.  
> **Repository:** `dazestack-bulk-plugin-manager` (High-efficiency WordPress Plugin Provisioning Fleet)  
> **Author:** Ashish Dungdung (DazeStack Series)  
> **Environment:** macOS Apple Silicon (`darwin-arm64`)  
> **Standard:** Cross-IDE & Cross-Project Portable (Zero vendor lock-in)

---

## 1. Core Principles & 4-Tier Compute Architecture

This workspace is designed to be completely **IDE-independent and mesh-native**. Regardless of whether work is executed in **Google Antigravity**, **Zed**, **Cursor**, or the **Terminal CLI**, all agents operate within the unified 4-Tier Antigravity AI Mesh:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                      TIER 0: LOCAL APPLE SILICON COMPUTE GRID                          │
│                                (LM Studio / Ollama / MLX)                              │
│  • Endpoint: http://127.0.0.1:1234/v1 | Model: google/gemma-4-12b-qat                   │
│  • Token Cost: ZERO ($0.00) | Air-gapped, zero external network overhead              │
│  • Roles: AST verification, PHP syntax checks, git commit hygiene, offline analysis    │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        TIER 1: NVIDIA NIM ENTERPRISE CLOUD                             │
│                           (integrate.api.nvidia.com/v1)                                │
│  • Key: NVIDIA_API_KEY | Models: moonshotai/kimi-k3, deepseek-ai/deepseek-r1           │
│  • Context: 1 MILLION TOKENS (Kimi-K3) | Vision: meta/llama-3.2-90b-vision-instruct    │
│  • Roles: Whole-codebase audits, monolithic log parsing, WP Admin VLM visual audit     │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                  TIER 2: EXPERIENTIAL LABS GATEWAY & UNIVERSAL BRIDGE                  │
│                     (http://127.0.0.1:4000 -> api.experientiallabs.ai)                 │
│  • Persistent Proxy: launchd com.anumac.claude-bridge on port 4000                    │
│  • Workhorse: claude-haiku-5.5 (541 tok/s, 1M context, $0.10/M in, $0.010/M cached)  │
│  • Parallel Diffusion: mercury-3 by Inception (Diffusion parallel token generation)    │
│  • Frontier Arbiter: gpt-6-luna (Reasoning effort high, distributed architecture)     │
│  • High-Throughput: deepseek-v4.1-flash | Math: qwen3.8-flash | Free: mimo-v2.6-pro   │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                TIER 3: CROSS-PROJECT DOMAIN ENGINES (KV CACHE FLOOR)                   │
│                    (Pre-compiled engineering context >= 1,024 tokens)                  │
│  • AshWebsite: ai_mesh_consult_ash_website (WordPress 6.8+, PHP 8.5, CWV LCP/INP)     │
│  • SolarQnA: ai_mesh_consult_solar (PV sizing, hybrid inverters, TOPCon, PM Surya)    │
│  • AndroidEmulator: ai_mesh_consult_android (Android API 21-35, ABI translation, ANR) │
│  • Helmetsan: ai_mesh_consult_helmetsan (ECE 22.06 motorcycle safety, EPS/MIPS)       │
│  • EcoSport: ai_mesh_consult_ecosport (WinCE 6.0 ARM, FlyAudio, Ti-VCT powertrain)    │
│  • ArchiveMaster: ai_mesh_consult_archive (POSIX 7z/zstd, APFS, ZipSlip defense)       │
│  • SiliconCompute: ai_mesh_consult_silicon (Apple Silicon UMA, MLX, Metal performance)│
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Supported Multi-Mesh Models & Capabilities

Synchronized with `SolarQna`, `ash-website`, `AndroidEmulator`, and `Helmetsan`:

| Model Name | Canonical API ID | Provider Tier | Speed / Context | Primary Role in DSBPM | Cost & Reasoning Controls |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Claude Haiku 5.5** | `claude-haiku-5.5` | Tier 2 (Experiential) | **541 tok/s** / 1M | **Primary Agent Workhorse:** PHP refactoring, JS queue logic, unit tests | $0.10/M in, $0.010/M cached (90% discount) |
| **Mercury 3** | `mercury-3` | Tier 2 (Inception) | Multi-token / 1M | **Diffusion Parallel Generation:** High-throughput parallel synthesis of recipes & modules | $0.20/M in, $0.020/M cached, 131k output |
| **ChatGPT 6 Luna** | `gpt-6-luna` | Tier 2 (Experiential) | High / 1.05M | **Principal Architectural Arbiter:** Security audits, SSRF verification, rollback integrity | `model_reasoning_effort="high"` |
| **Kimi-K3** | `moonshotai/kimi-k3` | Tier 1 (NVIDIA NIM) | Streaming / **1M** | **Whole-Codebase Audits:** Full plugin audit, cross-file AJAX routing check | $0.00 NIM credits / 1M token window |
| **DeepSeek v4.1 Flash**| `deepseek-v4.1-flash`| Tier 2 (Experiential) | Instant / 131k | **High-Throughput Syntax Check:** Fast diff validation, linting checks | High-speed streaming |
| **Qwen 3.8 Flash** | `qwen3.8-flash` | Tier 2 (Experiential) | Instant / 131k | **Mathematical & Memory Bounds:** Batch size allocation, PHP memory limit checks | Deterministic payload calculations |
| **MiMo v2.6 Flash** | `mimo-v2.6-flash` | Tier 2 (Experiential) | High / 1M | **Document Synthesis:** Changelog generation, readme.txt sync, technical guides | High-capacity structured synthesis |
| **Llama 3.2 90B Vision**| `meta/llama-3.2-90b-vision-instruct`| Tier 1 (NVIDIA NIM)| Multi-modal | **Visual UI Inspection:** Mac-style glassmorphic UI, responsive layout audits | VLM multimodal visual assertions |
| **Gemma 4 12B QAT** | `google/gemma-4-12b-qat`| Tier 0 (Local Silicon)| Local UMA | **Offline Edge Workloads:** AST parsing, git pre-commit verification | **$0.00 (Zero Token Burn)** |
| **MiMo v2.6 Pro** | `mimo-v2.6-pro` | Tier 2 (Experiential) | Medium / 1.05M | **Promotional Drafting:** Zero-cost scratchpads, initial ideas | $0 Promo (Hourly ceiling: 5.14M tokens) |
| **DeepSeek-R1** | `deepseek-ai/deepseek-r1`| Tier 1 (NVIDIA NIM) | CoT / 128k | **Deep Algorithmic Reasoning:** Dependency resolution, topological sort | Native Chain-of-Thought reasoning |

---

## 3. Configured API Vaults & Profiles

All credentials reside in the universal central vault at **`~/.config/antigravity/ai_mesh.env`**. Project repositories never store hardcoded secrets.

1. **Experiential Labs Gateway Profiles:**
   - Active Profile: `EXPLABS_API_KEY` (Root Gateway)
   - Sister Project Keys: `EXPLABS_API_KEY_SOLARQNA`, `EXPLABS_API_KEY_AE`, `EXPLABS_API_KEY_PERSONAL`, `EXPLABS_API_KEY_BROTHER`
   - Base URL: `https://api.experientiallabs.ai/v1` (routed via `http://127.0.0.1:4000`)
2. **NVIDIA NIM Gateway:**
   - Key: `NVIDIA_API_KEY`
   - Base URL: `https://integrate.api.nvidia.com/v1`
   - Powers Kimi-K3 (1M tokens), DeepSeek-R1, and Llama 3.2 90B Vision.
3. **Local Apple Silicon Grid:**
   - Endpoint: `http://127.0.0.1:1234/v1` (or Ollama `http://127.0.0.1:11434`)
   - Powered by Apple Unified Memory Architecture (UMA) on Metal Performance Shaders.

---

## 4. Cross-Project Domain Engine Consultations

DazeStack Bulk Plugin Manager has programmatic access to the 7 pre-compiled Domain Engines. Each engine carries an intentional **>= 1,024-token prompt caching floor** (`ai-mesh.json`) for automatic KV cache affinity and >85% cost savings:

| Domain Engine | CLI Command | MCP Tool Call | Core Specialty for DSBPM |
| :--- | :--- | :--- | :--- |
| **AshWebsite** | `ai-mesh ash-website "<query>"`| `ai_mesh_consult_ash_website` | WordPress 6.8+, PHP 8.5 strict types, CWV LCP/INP, transient mutex |
| **SolarQnA** | `ai-mesh solar "<query>"` | `ai_mesh_consult_solar` | Large dataset streaming, Cloudflare R2/D1 edge architecture |
| **AndroidEmulator** | `ai-mesh android "<query>"` | `ai_mesh_consult_android` | ADB, cross-platform bridge communication, IPC protocols |
| **Helmetsan** | `ai-mesh helmetsan "<query>"` | `ai_mesh_consult_helmetsan` | Rigorous standards compliance, safety verification, auditing |
| **EcoSport** | `ai-mesh ecosport "<query>"` | `ai_mesh_consult_ecosport` | Embedded hardware resource constraints, tight memory budgets |
| **ArchiveMaster** | `ai-mesh archive "<query>"` | `ai_mesh_consult_archive` | ZipSlip defense, archive unpacking safety, APFS filesystem speed |
| **SiliconCompute** | `ai-mesh silicon "<query>"` | `ai_mesh_consult_silicon` | Apple Silicon UMA budget, Metal shaders, GPU glassmorphism render |

---

## 5. Cross-IDE Compatibility Matrix

### A. Working in Google Antigravity
- Uses native AI Mesh MCP tools (`ai_mesh_consult_haiku`, `ai_mesh_consult_mercury`, `ai_mesh_consult_luna`, `ai_mesh_consult_ash_website`, `ai_mesh_health`).
- Programmatic SDK access via `scripts/ai/ai_mesh_client.mjs`.

### B. Working in Zed IDE
- **Native Assistant:** Uses models defined in `~/.config/zed/settings.json`.
- **Claude Agent (`claude-acp`):** Driven by `@agentclientprotocol/claude-agent-acp` through `http://127.0.0.1:4000`.
- **OpenAI Codex (`codex-acp`):** Driven by `@openai/codex` Responses API through `http://127.0.0.1:4000/v1`.

### C. Working in Cursor Desktop
- **Settings -> Models:** Set OpenAI API Key to `xpl_...` and Base URL to `http://127.0.0.1:4000/v1`.
- **Composer / Agent Mode (`Cmd+I`):** Select any of the supported models.

### D. Working in Terminal / CLI
- **Mesh Health Diagnostic:** `npm run mesh:health` (or `ai-mesh health`)
- **Direct Terminal Query:** `npm run mesh:consult -- haiku "Analyze PHP transient cache"`
- **Domain Consultation:** `npm run mesh:consult -- ash-website "Review WP 6.8 AJAX nonce validation"`

---

## 6. Operational Safety, PHP Linting & Git Rules

1. **Commit Hygiene:** Use conventional commit messages (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`).
2. **Issue Linking:** Reference tracking tickets (`[DSBPM-xxx]`) in commit titles; validated by commit-msg hook.
3. **Syntax Assurance:** Never commit PHP code without running `npm run lint:php` (`php -l`).
4. **Zero Frontend Footprint:** Verify that frontend public scripts are never enqueued; all hooks must remain scoped to `allowed_pages` in `admin.php`.
5. **SSRF & Security:** Always enforce IP filtering via `gethostbyname()` and validate nonces and capabilities on all AJAX handlers.

---

## 7. Rate Limits, Quota Policies & Reset Cycles

### A. The Hourly Free Allowance (HTTP 429)
When running high-throughput autonomous agents, Experiential Labs enforces an hourly ceiling on the `$0 Free Promo` tier (`mimo-v2.6-pro`):
- **Hourly Token Allowance:** `5,142,857 tokens / hour` (~85,714 tokens/minute).
- **Reset Schedule:** Resets automatically at the top of every hour (**`XX:00 UTC`**).

### B. Unblocking Options When Rate-Limited
1. **Zero-Cost Strategy:** Wait for the reset at `XX:00 UTC`.
2. **Switch to Claude Haiku 5.5 ($0.010/M Cached):** Extremely fast (541 tok/s) and virtually free with prompt caching.
3. **Switch to NVIDIA NIM Kimi-K3 (1M Tokens):** Whole-codebase audits on NVIDIA NIM credits.
4. **Platform Credit Overflow:** Enable Credit Overflow in Experiential Labs portal.

---

## 8. 4-Tier Project Management & Task Execution Protocol (Zero-Token Rule)

All AI agents must adhere to the 4-Tier Project Management Mesh adapted from `SolarQna` to eliminate token waste and prevent context fragmentation:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                 TIER 1: OBSIDIAN CANVAS (ZERO-TOKEN VISUAL GRAPH)                      │
│      • docs/canvas/roadmap-timeline.canvas & docs/canvas/system-architecture.canvas     │
│      • Direct filesystem inspection (0 cloud tokens)                                   │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                   TIER 2: NOTION CLOUD MIRROR (IDEA & STRATEGY CACHE)                  │
│      • docs/roadmap/notion-mirror.md (Local sanitized cache)                           │
│      • Stack recipes, partner integrations, UX inspiration                             │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                 TIER 3: PLANE.SO WORKSPACE MIRROR (SPRINT & TASK ENGINE)               │
│      • docs/roadmap/plane-mirror.md (Local sanitized cache)                           │
│      • Active cycles: Wave 1 Foundation, Wave 2 Expansion, Wave 3 Enterprise           │
│      • Ticket format: DSBPM-xxx                                                        │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                 TIER 4: GITHUB PROJECTS V2 & COMMIT TRACEABILITY                       │
│      • docs/roadmap/github-mirror.md & git commit hooks                                │
│      • Automated commit validation linking [DSBPM-xxx] to releases                     │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

### Pre-Execution Phase (Orient & Read)
1. **Never ask the user "What should I work on next?"** or scan 50 files blindly.
2. Read the local project mirrors directly (0-token filesystem cost):
   - **Active Tasks & Cycles:** `docs/roadmap/plane-mirror.md` (or run `npm run task:list`)
   - **Strategy & Partner Notes:** `docs/roadmap/notion-mirror.md`
   - **GitHub Milestone Board:** `docs/roadmap/github-mirror.md`
   - **Visual Roadmap:** `docs/canvas/roadmap-timeline.canvas`

### Execution Phase (Code & Commit)
1. **Branch & Commit Hygiene:** When working on task `DSBPM-xxx`, name commits conventionally:
   ```bash
   git commit -m "feat(recipes): add multisite network provisioning playbook [DSBPM-108]"
   ```
2. The installed commit-msg hook (`npm run hook:install`) validates commit formatting.

### Post-Execution Phase (Close Loop)
1. **Update Task Status:**
   ```bash
   npm run task:done DSBPM-xxx "Completed implementation and verified via php -l"
   ```
2. **Synchronize & Regenerate Ledger:**
   ```bash
   npm run bridge:sync
   ```
