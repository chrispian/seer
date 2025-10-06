#!/bin/bash

# Fragments Engine - Git Worktree Setup for Multi-Agent Development
# Usage: ./setup-worktree.sh <sprint-number>

set -e

SPRINT_NUMBER=$1
PROJECT_DIR=$(pwd)
PROJECT_NAME=$(basename "$PROJECT_DIR")
BASE_DIR=$(dirname "$PROJECT_DIR")

if [[ -z "$SPRINT_NUMBER" ]]; then
    echo "Usage: $0 <sprint-number>"
    echo "Example: $0 46"
    exit 1
fi

echo "🚀 Setting up Git Worktree for Sprint $SPRINT_NUMBER"
echo "Project: $PROJECT_NAME"
echo "Base Directory: $BASE_DIR"
echo ""

# Define worktree configurations
WORKTREE_TYPES=("backend" "frontend" "integration")
WORKTREE_DESCRIPTIONS=(
    "Backend agent workspace - Laravel, database, API work"
    "Frontend agent workspace - React, TypeScript, UI work"  
    "Integration workspace - Testing and merge coordination"
)

# Create main sprint branch if it doesn't exist
MAIN_BRANCH="sprint-$SPRINT_NUMBER/main"
echo "📋 Creating main sprint branch: $MAIN_BRANCH"
git checkout -b "$MAIN_BRANCH" 2>/dev/null || git checkout "$MAIN_BRANCH"

# Setup each worktree
for i in "${!WORKTREE_TYPES[@]}"; do
    WORKTREE_TYPE="${WORKTREE_TYPES[$i]}"
    WORKTREE_DESC="${WORKTREE_DESCRIPTIONS[$i]}"
    BRANCH_NAME="sprint-$SPRINT_NUMBER/$WORKTREE_TYPE"
    WORKTREE_PATH="$BASE_DIR/$PROJECT_NAME-$WORKTREE_TYPE-sprint$SPRINT_NUMBER"
    
    echo ""
    echo "🌳 Setting up $WORKTREE_TYPE worktree..."
    echo "   Branch: $BRANCH_NAME"
    echo "   Path: $WORKTREE_PATH"
    echo "   Purpose: $WORKTREE_DESC"
    
    # Create feature branch from main sprint branch
    git checkout -b "$BRANCH_NAME" "$MAIN_BRANCH" 2>/dev/null || git checkout "$BRANCH_NAME"
    
    # Remove existing worktree if it exists
    if [[ -d "$WORKTREE_PATH" ]]; then
        echo "   ⚠️  Removing existing worktree at $WORKTREE_PATH"
        git worktree remove "$WORKTREE_PATH" --force 2>/dev/null || true
        rm -rf "$WORKTREE_PATH" 2>/dev/null || true
    fi
    
    # Create new worktree
    git worktree add "$WORKTREE_PATH" "$BRANCH_NAME"
    
    # Create worktree-specific README
    cat > "$WORKTREE_PATH/WORKTREE_INFO.md" << EOF
# $WORKTREE_TYPE Worktree - Sprint $SPRINT_NUMBER

**Purpose**: $WORKTREE_DESC
**Branch**: $BRANCH_NAME
**Created**: $(date)

## Agent Assignment
This worktree is reserved for agents working on $WORKTREE_TYPE tasks.

## Development Guidelines
- Keep changes focused on $WORKTREE_TYPE concerns
- Commit frequently with descriptive messages
- Run tests before pushing changes
- Coordinate with integration worktree for merges

## Available Commands
\`\`\`bash
# Run development environment
composer run dev  # (backend only)
npm run dev       # (frontend only)

# Run tests
composer test     # (backend only)
npm test         # (frontend only)

# Code formatting  
./vendor/bin/pint # (backend only)
npm run format   # (frontend only)
\`\`\`

## Integration Process
1. Complete your assigned tasks in this worktree
2. Run full test suite and ensure all tests pass
3. Format code according to project standards
4. Coordinate with project manager for integration
5. Merge will be handled in integration worktree
EOF
    
    echo "   ✅ $WORKTREE_TYPE worktree created successfully"
done

# Return to original branch
git checkout "$MAIN_BRANCH"

echo ""
echo "🎉 Worktree setup complete for Sprint $SPRINT_NUMBER!"
echo ""
echo "📍 Available Worktrees:"
for WORKTREE_TYPE in "${WORKTREE_TYPES[@]}"; do
    WORKTREE_PATH="$BASE_DIR/$PROJECT_NAME-$WORKTREE_TYPE-sprint$SPRINT_NUMBER"
    echo "   $WORKTREE_TYPE: $WORKTREE_PATH"
done

echo ""
echo "🔧 Next Steps:"
echo "1. Assign agents to appropriate worktrees"
echo "2. Agents should cd into their assigned worktree directory"
echo "3. Begin parallel development on assigned tasks"
echo "4. Use integration worktree for final testing and merges"

echo ""
echo "📊 View current worktrees:"
echo "   git worktree list"

echo ""
echo "🧹 Cleanup worktrees when sprint is complete:"
echo "   ./delegation/cleanup-worktree.sh $SPRINT_NUMBER"