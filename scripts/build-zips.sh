#!/usr/bin/env bash
# Baut installierbare ZIP-Dateien für Theme und Plugin nach dist/.
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/bernstein-theme.zip dist/bernstein-core.zip
(cd themes && zip -qr ../dist/bernstein-theme.zip bernstein -x '*.DS_Store')
(cd plugins && zip -qr ../dist/bernstein-core.zip bernstein-core -x '*.DS_Store')
ls -lh dist/*.zip
