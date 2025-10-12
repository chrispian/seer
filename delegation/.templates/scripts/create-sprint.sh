#!/bin/bash
# Create a new sprint from template

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATES_DIR="$(dirname "$SCRIPT_DIR")"
SPRINTS_DIR="$(dirname "$TEMPLATES_DIR")/sprints"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

usage() {
    echo "Usage: $0 <sprint-id> <sprint-name>"
    echo ""
    echo "Example: $0 auth-system 'Authentication System Implementation'"
    echo ""
    exit 1
}

# Check arguments
if [ $# -lt 2 ]; then
    usage
fi

SPRINT_ID=$1
SPRINT_NAME=$2
SPRINT_DIR="$SPRINTS_DIR/$SPRINT_ID"

# Check if sprint already exists
if [ -d "$SPRINT_DIR" ]; then
    echo -e "${RED}Error: Sprint directory already exists: $SPRINT_DIR${NC}"
    exit 1
fi

echo -e "${GREEN}Creating sprint: $SPRINT_NAME${NC}"
echo -e "Sprint ID: $SPRINT_ID"
echo -e "Directory: $SPRINT_DIR"
echo ""

# Create sprint directory
mkdir -p "$SPRINT_DIR"

# Generate sprint hash
SPRINT_HASH=$(echo -n "${SPRINT_ID}-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1)
echo -e "Sprint hash: ${YELLOW}$SPRINT_HASH${NC}"

# Copy templates
echo "Copying templates..."
cp "$TEMPLATES_DIR/sprint-template/SPRINT_TEMPLATE.md" "$SPRINT_DIR/SPRINT.md"
cp "$TEMPLATES_DIR/sprint-template/README_TEMPLATE.md" "$SPRINT_DIR/README.md"
cp "$TEMPLATES_DIR/task-template/TASK_TEMPLATE.md" "$SPRINT_DIR/TASK_TEMPLATE.md"
cp "$TEMPLATES_DIR/agent-base/AGENT_TASK.yml" "$SPRINT_DIR/AGENT_TEMPLATE.yml"

# Replace placeholders in SPRINT.md
sed -i.bak "s/<sprint-slug>/$SPRINT_ID/g" "$SPRINT_DIR/SPRINT.md"
sed -i.bak "s/\[Sprint Name\]/$SPRINT_NAME/g" "$SPRINT_DIR/SPRINT.md"
sed -i.bak "s/YYYY-MM-DD/$(date +%Y-%m-%d)/g" "$SPRINT_DIR/SPRINT.md"
rm "$SPRINT_DIR/SPRINT.md.bak"

# Replace placeholders in README.md
sed -i.bak "s/\[Sprint Name\]/$SPRINT_NAME/g" "$SPRINT_DIR/README.md"
sed -i.bak "s/YYYY-MM-DD/$(date +%Y-%m-%d)/g" "$SPRINT_DIR/README.md"
sed -i.bak "s/<sprint-hash>/$SPRINT_HASH/g" "$SPRINT_DIR/README.md"
rm "$SPRINT_DIR/README.md.bak"

echo ""
echo -e "${GREEN}✅ Sprint created successfully!${NC}"
echo ""
echo "Next steps:"
echo "1. Edit $SPRINT_DIR/SPRINT.md to define your sprint"
echo "2. Run ./create-task.sh $SPRINT_ID <task-id> '<task-name>' to create tasks"
echo "3. Update README.md with task index"
echo ""
