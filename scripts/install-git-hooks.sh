#!/usr/bin/env bash
set -e

HOOK_DEST=".git/hooks/pre-commit"

if [ ! -d ".git" ]; then
    echo " [ERROR] .git directory not found. Must run from repository root."
    exit 1
fi

cat << 'EOF' > "$HOOK_DEST"
#!/usr/bin/env bash
# Aureus ERP Pre-Commit Architecture Guard
echo "Verifying architecture specification alignment..."
php scripts/sync-architecture.php --check

if [ $? -ne 0 ]; then
    echo " [ERROR] Architecture drift detected. Commit blocked."
    echo "         Run 'php scripts/sync-architecture.php --sync' to update ARCHITECTURE.md."
    exit 1
fi
EOF

chmod +x "$HOOK_DEST"
echo " [OK] Pre-commit hook installed successfully in $HOOK_DEST."

