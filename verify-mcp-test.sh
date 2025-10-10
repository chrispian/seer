#!/bin/bash

echo "=== MCP Integration Test Verification ==="
echo ""

echo "1. Checking if sprint was created..."
php artisan orchestration:sprint:detail SPRINT-TEST-MCP
echo ""

echo "2. Checking if task was created..."
php artisan orchestration:task:detail T-MCP-TEST-01
echo ""

echo "3. Checking sprint tasks..."
php artisan orchestration:tasks --sprint=SPRINT-TEST-MCP
echo ""

echo "=== Verification Complete ==="
