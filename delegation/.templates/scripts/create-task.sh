#!/bin/bash
# Create a new task from template

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATES_DIR="$(dirname "$SCRIPT_DIR")"
SPRINTS_DIR="$(dirname "$TEMPLATES_DIR")/sprints"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

usage() {
    echo "Usage: $0 <sprint-id> <task-id> <task-name> [last-task-id] [next-task-id]"
    echo ""
    echo "Example: $0 auth-system auth-phase-1-setup 'Phase 1: Setup' null auth-phase-2-core"
    echo ""
    exit 1
}

# Check arguments
if [ $# -lt 3 ]; then
    usage
fi

SPRINT_ID=$1
TASK_ID=$2
TASK_NAME=$3
LAST_TASK=${4:-null}
NEXT_TASK=${5:-null}

SPRINT_DIR="$SPRINTS_DIR/$SPRINT_ID"
TASK_DIR="$SPRINT_DIR/$TASK_ID"

# Check if sprint exists
if [ ! -d "$SPRINT_DIR" ]; then
    echo -e "${RED}Error: Sprint not found: $SPRINT_DIR${NC}"
    echo "Run create-sprint.sh first"
    exit 1
fi

# Check if task already exists
if [ -d "$TASK_DIR" ]; then
    echo -e "${RED}Error: Task directory already exists: $TASK_DIR${NC}"
    exit 1
fi

echo -e "${GREEN}Creating task: $TASK_NAME${NC}"
echo -e "Task ID: $TASK_ID"
echo -e "Sprint: $SPRINT_ID"
echo ""

# Create task directory
mkdir -p "$TASK_DIR"

# Generate task hash
TASK_HASH=$(echo -n "${TASK_ID}-$(date +%Y%m%d)" | sha256sum | cut -d' ' -f1)
echo -e "Task hash: ${YELLOW}$TASK_HASH${NC}"
echo -e "Links: $LAST_TASK → $TASK_ID → $NEXT_TASK"

# Copy templates
echo "Copying templates..."
cp "$TEMPLATES_DIR/task-template/TASK_TEMPLATE.md" "$TASK_DIR/TASK.md"
cp "$TEMPLATES_DIR/agent-base/AGENT_TASK.yml" "$TASK_DIR/AGENT.yml"

# Replace placeholders in TASK.md
sed -i.bak "s/<task-id>/$TASK_ID/g" "$TASK_DIR/TASK.md"
sed -i.bak "s/<sprint-id>/$SPRINT_ID/g" "$TASK_DIR/TASK.md"
sed -i.bak "s/\[Task Name\]/$TASK_NAME/g" "$TASK_DIR/TASK.md"
sed -i.bak "s/<task-hash>/$TASK_HASH/g" "$TASK_DIR/TASK.md"
rm "$TASK_DIR/TASK.md.bak"

# Replace placeholders in AGENT.yml
sed -i.bak "s/<task-id>/$TASK_ID/g" "$TASK_DIR/AGENT.yml"
sed -i.bak "s/<generate: echo -n 'task-id-YYYYMMDD' | sha256sum>/$TASK_HASH/g" "$TASK_DIR/AGENT.yml"
sed -i.bak "s/<sprint-id>/$SPRINT_ID/g" "$TASK_DIR/AGENT.yml"
sed -i.bak "s/<previous-task-id>/$LAST_TASK/g" "$TASK_DIR/AGENT.yml"
sed -i.bak "s/<next-task-id>/$NEXT_TASK/g" "$TASK_DIR/AGENT.yml"
rm "$TASK_DIR/AGENT.yml.bak"

# Store hash for reference
echo "$TASK_HASH" > "$TASK_DIR/.hash"

echo ""
echo -e "${GREEN}✅ Task created successfully!${NC}"
echo ""
echo "Next steps:"
echo "1. Edit $TASK_DIR/TASK.md to define task details"
echo "2. Edit $TASK_DIR/AGENT.yml to configure agent capabilities"
echo "3. If there was a previous task, update its agent_steps.next"
echo "4. Update sprint README.md task index"
echo ""
