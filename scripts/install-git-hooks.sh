#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOOKS_DIR="$REPO_ROOT/.githooks"

echo "Installing git hooks from $HOOKS_DIR..."

# Configure git to use our hooks directory
git config core.hooksPath "$HOOKS_DIR"

# Ensure hooks are executable
chmod +x "$HOOKS_DIR"/*

echo "✅ Git hooks installed. Using $HOOKS_DIR as hooks directory."
echo ""
echo "Installed hooks:"
ls -1 "$HOOKS_DIR"
