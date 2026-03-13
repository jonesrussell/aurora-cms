#!/usr/bin/env bash
set -euo pipefail

VERSION="${1:?Usage: scripts/release.sh <version> (e.g., v1.0.1)}"

# Validate version format
if [[ ! "$VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "❌ Invalid version format: $VERSION (expected vX.Y.Z)"
    exit 1
fi

echo "📦 Creating release $VERSION..."

# Ensure we're on main and up to date
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo "❌ Must be on main branch (currently on $CURRENT_BRANCH)"
    exit 1
fi

git fetch origin main
LOCAL_SHA=$(git rev-parse HEAD)
REMOTE_SHA=$(git rev-parse origin/main)
if [ "$LOCAL_SHA" != "$REMOTE_SHA" ]; then
    echo "❌ Local main is not up to date with origin/main"
    echo "   Local:  $LOCAL_SHA"
    echo "   Remote: $REMOTE_SHA"
    exit 1
fi

# Check if tag already exists
if git rev-parse "$VERSION" > /dev/null 2>&1; then
    echo "❌ Tag $VERSION already exists"
    exit 1
fi

# Generate changelog from merged PRs since last tag
PREV_TAG=$(git tag --sort=-v:refname | head -1)
echo "Generating changelog since $PREV_TAG..."

CHANGELOG=""
if command -v gh &> /dev/null; then
    CHANGELOG=$(gh pr list --state merged --base main --search "merged:>=$(git log -1 --format=%ci "$PREV_TAG" 2>/dev/null | cut -d' ' -f1)" --json number,title --jq '.[] | "- #\(.number): \(.title)"' 2>/dev/null || echo "")
fi

if [ -z "$CHANGELOG" ]; then
    CHANGELOG=$(git log "$PREV_TAG"..HEAD --oneline --no-merges | sed 's/^/- /')
fi

echo ""
echo "📋 Changelog:"
echo "$CHANGELOG"
echo ""

# Create annotated tag
git tag -a "$VERSION" -m "$(cat <<EOF
Release $VERSION

$CHANGELOG
EOF
)"

echo "✅ Created tag $VERSION"

# Push tag
git push origin "$VERSION"
echo "✅ Pushed tag $VERSION"

# Create GitHub release if gh is available
if command -v gh &> /dev/null; then
    gh release create "$VERSION" \
        --title "Release $VERSION" \
        --notes "$CHANGELOG" \
        --verify-tag
    echo "✅ Created GitHub release $VERSION"
else
    echo "⚠️  gh CLI not available — create GitHub release manually"
fi

echo ""
echo "🎉 Release $VERSION complete!"
echo "   Tag: $VERSION"
echo "   Commit: $LOCAL_SHA"
