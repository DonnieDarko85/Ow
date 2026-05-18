#!/bin/bash

# Script di build per generare index.html e assets compilati
# Uso: ./build.sh

set -e

echo "🔨 Avvio build del progetto..."
echo ""

# Verifica che npm sia disponibile
if ! command -v npm &> /dev/null; then
    echo "❌ npm non trovato. Installa Node.js e npm."
    exit 1
fi

# Installa dipendenze se non presenti
if [ ! -d "node_modules" ]; then
    echo "📦 Installazione dipendenze..."
    npm install
    echo ""
fi

# Esegui il build
echo "⚙️  Compilazione in corso..."
npm run build

echo ""
echo "✅ Build completato!"
echo "📁 I file compilati si trovano in: dist/"
echo ""
echo "📄 File generati:"
ls -lh dist/ | tail -n +2 | awk '{print "   " $9 " (" $5 ")"}'
