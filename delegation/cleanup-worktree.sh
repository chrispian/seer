#!/bin/bash

# Fragments Engine - Git Worktree Cleanup
# Usage: ./cleanup-worktree.sh <sprint-number>

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

echo "🧹 Cleaning up Git Worktrees for Sprint $SPRINT_NUMBER"
echo "Project: $PROJECT_NAME"
echo ""

# Define worktree types
WORKTREE_TYPES=("backend" "frontend" "integration")

# Check for uncommitted changes
echo "🔍 Checking for uncommitted changes..."
UNCOMMITTED_CHANGES=false

for WORKTREE_TYPE in "${WORKTREE_TYPES[@]}"; do
    WORKTREE_PATH="$BASE_DIR/$PROJECT_NAME-$WORKTREE_TYPE-sprint$SPRINT_NUMBER"
    
    if [[ -d "$WORKTREE_PATH" ]]; then
        echo "   Checking $WORKTREE_TYPE worktree..."
        cd "$WORKTREE_PATH"
        
        if ! git diff --quiet || ! git diff --cached --quiet; then
            echo "   ⚠️  Uncommitted changes found in $WORKTREE_TYPE worktree"
            echo "      Path: $WORKTREE_PATH"
            git status --short
            UNCOMMITTED_CHANGES=true
        fi
        
        cd "$PROJECT_DIR"
    fi
done

if [[ "$UNCOMMITTED_CHANGES" == "true" ]]; then
    echo ""
    echo "❌ Cannot proceed - uncommitted changes found!"
    echo ""
    echo "Please commit or stash changes in all worktrees before cleanup."
    echo "Use 'git status' in each worktree to see uncommitted changes."
    exit 1
fi

echo "   ✅ No uncommitted changes found"

# Confirm cleanup
echo ""
read -p "🗑️  Proceed with worktree cleanup for Sprint $SPRINT_NUMBER? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cleanup cancelled."
    exit 0
fi

echo ""
echo "🗑️  Removing worktrees..."

# Remove each worktree
for WORKTREE_TYPE in "${WORKTREE_TYPES[@]}"; do
    WORKTREE_PATH="$BASE_DIR/$PROJECT_NAME-$WORKTREE_TYPE-sprint$SPRINT_NUMBER"
    BRANCH_NAME="sprint-$SPRINT_NUMBER/$WORKTREE_TYPE"
    
    if [[ -d "$WORKTREE_PATH" ]]; then
        echo "   Removing $WORKTREE_TYPE worktree..."
        git worktree remove "$WORKTREE_PATH" --force
        echo "   ✅ Removed worktree at $WORKTREE_PATH"
        
        # Optionally delete branch (with confirmation)
        read -p "      Delete branch $BRANCH_NAME? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git branch -D "$BRANCH_NAME" 2>/dev/null || echo "      Branch $BRANCH_NAME already deleted"
            echo "      ✅ Deleted branch $BRANCH_NAME"
        else
            echo "      ⏭️  Keeping branch $BRANCH_NAME"
        fi
    else
        echo "   ℹ️  Worktree $WORKTREE_TYPE not found (already cleaned up)"
    fi
done

# Clean up main sprint branch if requested
MAIN_BRANCH="sprint-$SPRINT_NUMBER/main"
echo ""
read -p "🗑️  Also delete main sprint branch $MAIN_BRANCH? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    git checkout main 2>/dev/null || git checkout master 2>/dev/null || git checkout $(git branch | head -1 | sed 's/^..//')
    git branch -D "$MAIN_BRANCH" 2>/dev/null || echo "Branch $MAIN_BRANCH already deleted"
    echo "✅ Deleted main sprint branch $MAIN_BRANCH"
else
    echo "⏭️  Keeping main sprint branch $MAIN_BRANCH"
fi

echo ""
echo "🎉 Worktree cleanup complete for Sprint $SPRINT_NUMBER!"
echo ""
echo "📊 Remaining worktrees:"
git worktree list

echo ""
echo "📋 Remaining branches:"
git branch | grep "sprint-$SPRINT_NUMBER" || echo "   No sprint-$SPRINT_NUMBER branches remaining"