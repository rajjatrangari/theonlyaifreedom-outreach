#!/usr/bin/env bash
# Deploy Outreach AI Freedom to Hostinger public_html/outreach
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DRY_RUN=false
PACKAGE_ONLY=false

for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=true ;;
    --package-only) PACKAGE_ONLY=true ;;
    *) echo "Unknown option: $arg"; exit 1 ;;
  esac
done

echo "=== [1/3] Pre-flight Package Preparation ==="
ZIP_NAME="outreach-hostinger-upload.zip"

if [ "$PACKAGE_ONLY" = true ]; then
  echo "=== Creating deployable zip package ==="
  cd "$SCRIPT_DIR"
  zip -r -q "$SCRIPT_DIR/$ZIP_NAME" . -x "core/config.local.php" "data/*" "backups/*" "*.git*" "*.DS_Store*"
  echo "Package created at: $SCRIPT_DIR/$ZIP_NAME"
  exit 0
fi

# Hostinger SSH details
HOSTINGER_USER="${HOSTINGER_USER:-}"
HOSTINGER_HOST="${HOSTINGER_HOST:-}"
HOSTINGER_PORT="${HOSTINGER_PORT:-65002}"
REMOTE_PATH="${REMOTE_PATH:-domains/theonlyaifreedom.in/public_html/outreach/}"

if [ -z "$HOSTINGER_USER" ] || [ -z "$HOSTINGER_HOST" ]; then
  echo ""
  echo "NOTE: HOSTINGER_USER and HOSTINGER_HOST not set for SSH rsync."
  echo "Creating offline deploy package instead..."
  cd "$SCRIPT_DIR"
  zip -r -q "$SCRIPT_DIR/$ZIP_NAME" . -x "core/config.local.php" "data/*" "backups/*" "*.git*" "*.DS_Store*"
  echo "✓ Created $ZIP_NAME for upload to Hostinger (domains/theonlyaifreedom.in/public_html/outreach/)."
  exit 0
fi

echo "=== [2/3] Syncing files to Hostinger via rsync ==="
RSYNC_OPTS=(-avz --delete)
[ "$DRY_RUN" = true ] && RSYNC_OPTS+=(--dry-run)

EXCLUDES=(
  --exclude="core/config.local.php"
  --exclude="data/"
  --exclude="backups"
  --exclude=".git*"
  --exclude=".DS_Store"
)

rsync "${RSYNC_OPTS[@]}" "${EXCLUDES[@]}" -e "ssh -p $HOSTINGER_PORT" "$SCRIPT_DIR/" "$HOSTINGER_USER@$HOSTINGER_HOST:$REMOTE_PATH"

echo "=== [3/3] Deployment complete ==="
echo "Live URL: https://outreach.theonlyaifreedom.in"
