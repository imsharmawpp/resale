#!/usr/bin/env bash
# Build a clean WordPress-installable rims-pro.zip at the repo root.
# Excludes dev files (tests, node_modules, .git, .kiro, src sources, build artifacts).
set -euo pipefail

ROOT="$(cd -- "$(dirname -- "$0")" && pwd)"
SRC="${ROOT}/rims-pro"
OUT_NAME="rims-pro.zip"
OUT="${ROOT}/${OUT_NAME}"
STAGE="$(mktemp -d)"
DEST="${STAGE}/rims-pro"

mkdir -p "$DEST"

# Copy plugin contents using cp + manual exclusions (rsync not always available).
cp -a "${SRC}/." "${DEST}/"

# Strip dev/test/build artifacts from the staged copy.
rm -rf \
    "${DEST}/tests" \
    "${DEST}/.phpunit.cache" \
    "${DEST}/phpunit.xml.dist" \
    "${DEST}/composer.lock" \
    "${DEST}/assets/src" \
    "${DEST}/.git" \
    "${DEST}/.kiro" \
    "${DEST}/node_modules" \
    "${DEST}/.idea" || true

# Remove vendor/ entirely — the plugin's PSR-4 fallback autoloader handles all
# RimsPro\\ classes, and vendor/ only adds dev-only packages (PHPUnit, Eris)
# that can cause fatal errors in production.
rm -rf "${DEST}/vendor" || true

# Remove any stray zip artifacts.
find "${DEST}" -name '*.zip' -type f -delete 2>/dev/null || true
find "${DEST}" -name '.DS_Store' -type f -delete 2>/dev/null || true

# Build the zip.
rm -f "$OUT"
( cd "$STAGE" && zip -rq "$OUT" rims-pro )

# Cleanup.
rm -rf "$STAGE"

echo "Built ${OUT}"
ls -lh "$OUT"
echo "Top-level entries inside zip:"
unzip -Z1 "$OUT" | head -20
