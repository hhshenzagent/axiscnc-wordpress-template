#!/usr/bin/env bash
set -euo pipefail
export LC_ALL=C
cd "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
node_ready() { command -v node >/dev/null && command -v npm >/dev/null && node -e 'const [a,b]=process.versions.node.split(".").map(Number);process.exit(a>22||(a===22&&b>=13)?0:1)' >/dev/null 2>&1; }
if [[ "${AXIS_USE_PORTABLE_NODE:-0}" == 1 ]] || ! node_ready; then
  case "$(uname -s)-$(uname -m)" in
    Darwin-arm64) target=darwin-arm64; expected=cc04a76a09f79290194c0646f48fec40354d88969bec467789a5d55dd097f949 ;;
    Darwin-x86_64) target=darwin-x64; expected=00df9c5df3e4ec6848c26b70fb47bf96492f342f4bed6b17f12d99b3a45eeecc ;;
    Linux-x86_64) target=linux-x64; expected=eeaccb0378b79406f2208e8b37a62479c70595e20be6b659125eb77dd1ab2a29 ;;
    Linux-aarch64|Linux-arm64) target=linux-arm64; expected=4181609e03dcb9880e7e5bf956061ecc0503c77a480c6631d868cb1f65a2c7dd ;;
    *) echo 'Install Node.js 22.13+ for this platform, then rerun.' >&2; exit 1 ;;
  esac
  directory=".tools/node-v22.20.0-$target"
  if [[ ! -x "$directory/bin/node" ]]; then
    mkdir -p .tools
    archive=".tools/node-v22.20.0-$target.tar.gz"
    curl --fail --location --retry 2 --output "$archive" "https://nodejs.org/dist/v22.20.0/node-v22.20.0-$target.tar.gz"
    if command -v shasum >/dev/null; then actual="$(shasum -a 256 "$archive" | cut -d ' ' -f 1)"; else actual="$(sha256sum "$archive" | cut -d ' ' -f 1)"; fi
    [[ "$actual" == "$expected" ]] || { echo 'Node.js checksum mismatch.' >&2; exit 1; }
    tar -xzf "$archive" -C .tools
    rm -- "$archive"
  fi
  export PATH="$PWD/$directory/bin:$PATH"
fi
mkdir -p .tools
current="$(node -e 'process.stdout.write(require("node:crypto").createHash("sha256").update(require("node:fs").readFileSync("package-lock.json")).digest("hex"))')"
saved="$(cat .tools/installed-lock.sha256 2>/dev/null || true)"
if [[ ! -d node_modules/@wp-playground/wordpress ]] || [[ "$current" != "$saved" ]]; then
  npm ci --ignore-scripts --no-audit --no-fund
  printf '%s' "$current" > .tools/installed-lock.sha256
fi
exec node scripts/start.mjs "$@"
