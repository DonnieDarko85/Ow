#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"

echo "==> Root progetto: ${ROOT_DIR}"
cd "${ROOT_DIR}"

if ! command -v npm >/dev/null 2>&1; then
  echo "Errore: npm non trovato. Installa Node.js e npm prima di continuare."
  exit 1
fi

if [ ! -f "package.json" ]; then
  echo "Errore: package.json non trovato nella root del progetto."
  exit 1
fi

if [ ! -d "node_modules" ]; then
  echo "==> node_modules non presente, installo le dipendenze..."
  npm install
fi

echo "==> Genero la nuova build di produzione..."
npm run build

if [ ! -f "${DIST_DIR}/index.html" ]; then
  echo "Errore: build completata ma dist/index.html non esiste."
  exit 1
fi

echo "==> Preparo la struttura completa di deploy dentro dist/..."

mkdir -p "${DIST_DIR}/api"
mkdir -p "${DIST_DIR}/backend/src"
mkdir -p "${DIST_DIR}/backend/config"

cp backend/public/api/index.php "${DIST_DIR}/api/index.php"
cp backend/src/Database.php "${DIST_DIR}/backend/src/Database.php"
cp backend/config/config.php "${DIST_DIR}/backend/config/config.php"

chmod 644 "${DIST_DIR}/api/index.php"
chmod 644 "${DIST_DIR}/backend/src/Database.php"
chmod 600 "${DIST_DIR}/backend/config/config.php"

echo
echo "Build completata con successo."
echo "Contenuto pronto da deployare con drag & drop FTP:"
find "${DIST_DIR}" -maxdepth 4 -type f | sort
echo
echo "Carica sul server tutto il contenuto della cartella dist/:"
echo "- index.html"
echo "- assets/"
echo "- api/index.php"
echo "- backend/src/Database.php"
echo "- backend/config/config.php"
