#!/usr/bin/env bash

# Install commit-msg hook for DazeStack Bulk Plugin Manager
HOOK_DIR=".git/hooks"
HOOK_FILE="$HOOK_DIR/commit-msg"

if [ ! -d "$HOOK_DIR" ]; then
  echo "❌ Error: .git directory not found. Must run from repository root."
  exit 1
fi

cat << 'EOF' > "$HOOK_FILE"
#!/usr/bin/env bash
node scripts/bridge/verify-commit.mjs "$1"
EOF

chmod +x "$HOOK_FILE"
echo "✅ Installed commit-msg hook at $HOOK_FILE"
