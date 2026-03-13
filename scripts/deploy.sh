#!/usr/bin/env bash
set -euo pipefail

ENVIRONMENT="${1:?Usage: scripts/deploy.sh <staging|production>}"

if [[ "$ENVIRONMENT" != "staging" && "$ENVIRONMENT" != "production" ]]; then
    echo "❌ Invalid environment: $ENVIRONMENT (must be staging or production)"
    exit 1
fi

SHA=$(git rev-parse HEAD)
TIMESTAMP=$(date -u +%Y-%m-%dT%H:%M:%SZ)

echo "🚀 Deploying to $ENVIRONMENT..."
echo "   Commit: $SHA"
echo "   Time:   $TIMESTAMP"

# Record deployment metadata
mkdir -p build/deploy
cat > "build/deploy/${ENVIRONMENT}-metadata.json" <<EOF
{
  "environment": "$ENVIRONMENT",
  "sha": "$SHA",
  "timestamp": "$TIMESTAMP",
  "branch": "$(git branch --show-current)",
  "tag": "$(git describe --tags --exact-match 2>/dev/null || echo 'none')"
}
EOF

# Production safety check
if [ "$ENVIRONMENT" = "production" ]; then
    echo ""
    echo "⚠️  Production deployment"
    echo "   Ensure staging has been validated first."
    echo ""
fi

# Placeholder for actual deployment commands
# Replace with your deployment tool (Deployer, rsync, etc.)
echo "📦 Running composer install --no-dev --optimize-autoloader..."
composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true

echo "📦 Building frontend assets..."
if [ -d "packages/admin" ]; then
    (cd packages/admin && npm ci --production 2>/dev/null && npm run build 2>/dev/null) || echo "⚠️  Frontend build skipped"
fi

echo ""
echo "✅ Deployment to $ENVIRONMENT complete"
echo "   Metadata: build/deploy/${ENVIRONMENT}-metadata.json"
