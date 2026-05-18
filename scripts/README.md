# Script utili

## Build frontend

Per rigenerare manualmente la cartella `dist/` su Ubuntu:

```bash
./scripts/build-dist.sh
```

Lo script:

- verifica che `npm` sia disponibile
- installa le dipendenze se `node_modules` non esiste
- esegue la build di produzione
- mostra i file finali pronti per il deploy
