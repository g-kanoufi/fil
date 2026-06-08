#!/usr/bin/env bash
# npm/npx wrapper — clears Cursor/sandbox-injected npm_config_devdir (node-gyp legacy).
# npm 11+ warns: "Unknown env config devdir". Use this in shell scripts; IDE terminals
# also get npm_config_devdir="" via .vscode/settings.json.
set -euo pipefail

unset npm_config_devdir 2>/dev/null || true
export npm_config_devdir=

if [[ $# -eq 0 ]]; then
  exec npm
fi

if [[ "$1" == "npx" ]]; then
  shift
  exec npx "$@"
fi

exec npm "$@"
