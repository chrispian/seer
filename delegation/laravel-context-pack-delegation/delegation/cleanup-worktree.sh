#!/bin/bash

# Laravel Context Pack - Git Worktree Cleanup Script
# Removes worktrees and cleans up branches after sprint completion

set -e

PROJECT_NAME="laravel-context-pack"
BASE_DIR="/Users/chrispian/Projects"
PROJECT_DIR="${BASE_DIR}/${PROJECT_NAME}"

# Check if we're in a git repository
if [ ! -d "${PROJECT_DIR}/.git" ]; then
    echo "❌ Error: Not in a git repository. Please run this from the project root."
    exit 1
fi

# Function to cleanup worktree
cleanup_worktree() {
    local SPRINT_NUM=$1
    local WORKTREE_TYPE=$2
    local BRANCH_NAME="sprint-${SPRINT_NUM}/${WORKTREE_TYPE}"
    local WORKTREE_PATH="${BASE_DIR}/${PROJECT_NAME}-${WORKTREE_TYPE}-sprint${SPRINT_NUM}"
    
    echo "🗑️  Cleaning up ${WORKTREE_TYPE} worktree..."
    
    cd "${PROJECT_DIR}"
    
    # Remove worktree if it exists
    if [ -d "${WORKTREE_PATH}" ]; then
        git worktree remove "${WORKTREE_PATH}" --force
        echo "   ✅ Removed worktree: ${WORKTREE_PATH}"
    else
        echo "   ⚠️  Worktree not found: ${WORKTREE_PATH}"
    fi
    
    # Optionally delete branch (commented out for safety)
    # git branch -D "${BRANCH_NAME}" 2>/dev/null && echo "   ✅ Deleted branch: ${BRANCH_NAME}" || echo "   ⚠️  Branch not found: ${BRANCH_NAME}"
}

# Main execution
SPRINT_NUM=${1}

if [ -z "$1" ]; then
    echo "❌ Error: Sprint number required"
    echo "   Usage: $0 <sprint-number>"
    echo "   Example: $0 001"
    exit 1
fi

echo "🧹 Cleaning up Git Worktrees for Laravel Context Pack"
echo "   Sprint: ${SPRINT_NUM}"
echo "   Base Directory: ${BASE_DIR}"
echo ""

# Confirmation prompt
read -p "⚠️  This will remove all worktrees for Sprint ${SPRINT_NUM}. Continue? (y/N): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Cleanup cancelled."
    exit 1
fi

# Cleanup worktrees
cleanup_worktree "${SPRINT_NUM}" "backend"
cleanup_worktree "${SPRINT_NUM}" "frontend"
cleanup_worktree "${SPRINT_NUM}" "integration"

echo ""
echo "🎉 Cleanup complete!"
echo ""
echo "📋 Remaining worktrees:"
git worktree list
echo ""
echo "💡 Note: Branches are preserved for historical reference"
echo "   To delete branches manually: git branch -D sprint-${SPRINT_NUM}/[backend|frontend|integration]"