#!/usr/bin/env bash
set -euo pipefail

BINARY_URL="${BINARY_URL:-}"
SERVER_URL="${1:-}"
CONFIG_PATH="/etc/monitoring-agent/config.json"
KEY_PATH="/etc/monitoring-agent/agent.key"
BINARY_PATH="/usr/local/bin/monitoring-agent"
SERVICE_FILE="/etc/systemd/system/monitoring-agent.service"
AGENT_USER="monitoring-agent"
GO_VERSION="1.22.3"
GO_INSTALL_DIR="/usr/local"
REPO_URL="https://github.com/jdc1898/perry-agent.git"

# ── helpers ───────────────────────────────────────────────────────────────────

err()  { echo "ERROR: $*" >&2; exit 1; }
info() { echo "  >> $*"; }

require_root() {
  [[ $EUID -eq 0 ]] || err "this script must be run as root"
}

require_arg() {
  [[ -n "$SERVER_URL" ]] || err "usage: install.sh <config-server-url>"
}

go_arch() {
  local machine
  machine="$(uname -m)"
  case "$machine" in
    x86_64)  echo "amd64" ;;
    aarch64) echo "arm64" ;;
    armv7*)  echo "armv6l" ;;
    *)       err "unsupported architecture: $machine" ;;
  esac
}

install_go() {
  local arch tarball url tmp
  arch="$(go_arch)"
  tarball="go${GO_VERSION}.linux-${arch}.tar.gz"
  url="https://go.dev/dl/${tarball}"
  tmp="$(mktemp -d)"

  info "downloading Go ${GO_VERSION} (${arch})"
  curl -fsSL "$url" -o "${tmp}/${tarball}"
  rm -rf "${GO_INSTALL_DIR}/go"
  tar -C "$GO_INSTALL_DIR" -xzf "${tmp}/${tarball}"
  rm -rf "$tmp"
  export PATH="${GO_INSTALL_DIR}/go/bin:${PATH}"
  info "Go $(go version) installed"
}

ensure_go() {
  if command -v go &>/dev/null; then
    info "Go already installed: $(go version)"
    return
  fi
  if [[ -x "${GO_INSTALL_DIR}/go/bin/go" ]]; then
    export PATH="${GO_INSTALL_DIR}/go/bin:${PATH}"
    return
  fi
  info "Go not found — installing Go ${GO_VERSION}"
  install_go
}

# When run via "curl | bash" BASH_SOURCE[0] is not a real file path.
# Detect this and clone the repo into a temp directory instead.
fetch_source() {
  local tmp
  tmp="$(mktemp -d)"
  info "cloning perry-agent source from GitHub" >&2
  command -v git &>/dev/null || err "git is required to build from source"
  git clone --depth=1 "$REPO_URL" "$tmp/perry-agent" >/dev/null 2>&1
  echo "$tmp/perry-agent"
}

build_from_source() {
  local src_dir

  # Detect if we have a real on-disk source tree next to this script
  if [[ -f "${BASH_SOURCE[0]}" ]] && [[ -f "$(dirname "${BASH_SOURCE[0]}")/go.mod" ]]; then
    src_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    info "building from local source at ${src_dir}"
  else
    src_dir="$(fetch_source)"
    info "building from cloned source at ${src_dir}"
  fi

  ensure_go

  info "building monitoring-agent"
  pushd "$src_dir" >/dev/null
  go mod tidy
  go build -o "$BINARY_PATH" ./cmd/agent/
  popd >/dev/null
  info "build complete"
}

# ── pre-flight ────────────────────────────────────────────────────────────────

require_root
require_arg

command -v systemctl >/dev/null 2>&1 || err "systemd is required"
command -v curl     >/dev/null 2>&1 || err "curl is required"

echo ""
echo "Perry Monitoring Agent — Installer"
echo "  Management server: $SERVER_URL"
echo ""

# ── create system user ────────────────────────────────────────────────────────

if ! id "$AGENT_USER" &>/dev/null; then
  info "creating system user: $AGENT_USER"
  useradd --system --no-create-home --shell /usr/sbin/nologin "$AGENT_USER"
fi

# ── install binary ────────────────────────────────────────────────────────────

if [[ -n "$BINARY_URL" ]]; then
  info "downloading binary from $BINARY_URL"
  curl -fsSL "$BINARY_URL" -o "$BINARY_PATH"
elif [[ -f "${BASH_SOURCE[0]:-}" ]] && [[ -f "$(dirname "${BASH_SOURCE[0]}")/monitoring-agent" ]]; then
  info "installing local binary"
  cp "$(dirname "${BASH_SOURCE[0]}")/monitoring-agent" "$BINARY_PATH"
else
  info "no pre-built binary found — building from source"
  build_from_source
fi

chmod 755 "$BINARY_PATH"
info "binary installed at $BINARY_PATH"

# ── initialise agent (generate keys) ─────────────────────────────────────────

if [[ -f "$CONFIG_PATH" ]]; then
  info "config already exists — skipping init"
else
  info "generating agent identity and keys"
  "$BINARY_PATH" init "$SERVER_URL" "$CONFIG_PATH" "$KEY_PATH"
fi

chown -R "$AGENT_USER:$AGENT_USER" /etc/monitoring-agent
chmod 700 /etc/monitoring-agent
chmod 600 "$KEY_PATH" "$CONFIG_PATH"

# ── install systemd service ───────────────────────────────────────────────────

cat > "$SERVICE_FILE" <<'EOF'
[Unit]
Description=Perry Monitoring Agent
After=network-online.target
Wants=network-online.target
StartLimitIntervalSec=60
StartLimitBurst=5

[Service]
Type=simple
User=monitoring-agent
Group=monitoring-agent
ExecStart=/usr/local/bin/monitoring-agent run /etc/monitoring-agent/config.json
Restart=on-failure
RestartSec=10s
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
PrivateTmp=true
ReadWritePaths=/etc/monitoring-agent
StandardOutput=journal
StandardError=journal
SyslogIdentifier=monitoring-agent

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable monitoring-agent
systemctl start monitoring-agent

echo ""
echo "════════════════════════════════════════════════════════════"
echo " Perry Monitoring Agent installed and running"
echo ""
echo " NEXT STEP: copy these credentials into Perry:"
echo ""
"$BINARY_PATH" fingerprint
echo ""
echo "════════════════════════════════════════════════════════════"
echo " Check status : systemctl status monitoring-agent"
echo " View logs    : journalctl -u monitoring-agent -f"
echo ""
