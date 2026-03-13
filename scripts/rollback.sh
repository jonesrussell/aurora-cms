#!/usr/bin/env bash
set -euo pipefail

TAG="${1:?Usage: scripts/rollback.sh <tag> (e.g., v1.0.0)}"

echo "⏪ Rolling back to $TAG..."

# Verify tag exists
if ! git rev-parse "$TAG" > /dev/null 2>&1; then
    echo "❌ Tag $TAG does not exist"
    echo "Available tags:"
    git tag --sort=-v:refname | head -10
    exit 1
fi

TAG_SHA=$(git rev-parse "$TAG")
echo "   Target commit: $TAG_SHA"

# Record rollback metadata
TIMESTAMP=$(date -u +%Y-%m-%dT%H:%M:%SZ)
mkdir -p build/deploy
cat > "build/deploy/rollback-metadata.json" <<EOF
{
  "action": "rollback",
  "target_tag": "$TAG",
  "target_sha": "$TAG_SHA",
  "rolled_back_from": "$(git rev-parse HEAD)",
  "timestamp": "$TIMESTAMP"
}
EOF

# Checkout the tag
git checkout "$TAG"

# Re-run deployment
echo "Re-deploying $TAG..."
if [ -f scripts/deploy.sh ]; then
    bash scripts/deploy.sh production
else
    echo "⚠️  scripts/deploy.sh not found — manual deployment required"
fi

echo ""
echo "✅ Rollback to $TAG complete"
echo "   Metadata: build/deploy/rollback-metadata.json"
echo ""
echo "⚠️  Remember to investigate the issue and create a fix branch."
