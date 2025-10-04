#!/bin/bash

# Laravel Context Pack - Git Worktree Setup Script
# Creates isolated development environments for parallel agent work

set -e

PROJECT_NAME="laravel-context-pack"
BASE_DIR="/Users/chrispian/Projects"
PROJECT_DIR="${BASE_DIR}/${PROJECT_NAME}"

# Check if we're in a git repository
if [ ! -d "${PROJECT_DIR}/.git" ]; then
    echo "❌ Error: Not in a git repository. Please run this from the project root."
    exit 1
fi

# Function to create worktree
create_worktree() {
    local SPRINT_NUM=$1
    local WORKTREE_TYPE=$2
    local BRANCH_NAME="sprint-${SPRINT_NUM}/${WORKTREE_TYPE}"
    local WORKTREE_PATH="${BASE_DIR}/${PROJECT_NAME}-${WORKTREE_TYPE}-sprint${SPRINT_NUM}"
    
    echo "📁 Creating ${WORKTREE_TYPE} worktree for Sprint ${SPRINT_NUM}..."
    
    # Create branch if it doesn't exist
    cd "${PROJECT_DIR}"
    git checkout -B "${BRANCH_NAME}" 2>/dev/null || git checkout "${BRANCH_NAME}"
    
    # Remove existing worktree if it exists
    if [ -d "${WORKTREE_PATH}" ]; then
        echo "🗑️  Removing existing worktree: ${WORKTREE_PATH}"
        git worktree remove "${WORKTREE_PATH}" --force 2>/dev/null || rm -rf "${WORKTREE_PATH}"
    fi
    
    # Create new worktree
    git worktree add "${WORKTREE_PATH}" "${BRANCH_NAME}"
    
    if [ $? -eq 0 ]; then
        echo "✅ Created ${WORKTREE_TYPE} worktree: ${WORKTREE_PATH}"
        echo "   Branch: ${BRANCH_NAME}"
    else
        echo "❌ Failed to create ${WORKTREE_TYPE} worktree"
        return 1
    fi
}

# Main execution
SPRINT_NUM=${1:-"001"}

if [ -z "$1" ]; then
    echo "ℹ️  No sprint number provided, using default: 001"
    echo "   Usage: $0 <sprint-number>"
    echo "   Example: $0 001"
    echo ""
fi

echo "🚀 Setting up Git Worktrees for Laravel Context Pack"
echo "   Sprint: ${SPRINT_NUM}"
echo "   Base Directory: ${BASE_DIR}"
echo ""

# Create worktrees for different development streams
create_worktree "${SPRINT_NUM}" "backend"
create_worktree "${SPRINT_NUM}" "frontend" 
create_worktree "${SPRINT_NUM}" "integration"

echo ""
echo "🎉 Worktree setup complete!"
echo ""
echo "📋 Available Worktrees:"
echo "   Backend:     ${BASE_DIR}/${PROJECT_NAME}-backend-sprint${SPRINT_NUM}"
echo "   Frontend:    ${BASE_DIR}/${PROJECT_NAME}-frontend-sprint${SPRINT_NUM}"  
echo "   Integration: ${BASE_DIR}/${PROJECT_NAME}-integration-sprint${SPRINT_NUM}"
echo ""
echo "🔧 Next Steps:"
echo "   1. Assign agents to appropriate worktrees"
echo "   2. Begin parallel development work"
echo "   3. Use integration worktree for merging and testing"
echo ""
echo "📚 View all worktrees: git worktree list"
echo "🗑️  Cleanup when done: ./delegation/cleanup-worktree.sh ${SPRINT_NUM}"