#!/usr/bin/env bash
# iptables egress filter (Linux) for user fe-agent
set -euo pipefail

USER_NAME="fe-agent"
ALLOWED_HOSTS=("api.github.com")
ALLOWED_PORTS=(80 443)

UID=$(id -u "$USER_NAME")

# Flush old rules (careful: demo only)
iptables -F
iptables -A OUTPUT -m owner --uid-owner $UID -j DROP
for host in "${ALLOWED_HOSTS[@]}"; do
  for port in "${ALLOWED_PORTS[@]}"; do
    iptables -A OUTPUT -m owner --uid-owner $UID -p tcp -d "$host" --dport "$port" -j ACCEPT
  done
done
echo "Applied demo egress rules for $USER_NAME"
