# Old World Campaign Portal

Base implementativa iniziale per un portale campagna responsive, mobile-first e a tema fantasy-bellico.

## Stack scelto

- Frontend: Vue 3 + Vite + TypeScript + Pinia + Vue Router
- Backend placeholder: PHP
- Database: MySQL

## Struttura

- `src/`: frontend SPA con layout, pagine e mock data
- `sql/001_schema_portale_wargame_mysql.sql`: schema MySQL separato con seed iniziale
- `backend/public/api/index.php`: bootstrap API PHP minimale
- `backend/config/config.example.php`: esempio configurazione DB/app

## Avvio frontend

1. Installare dipendenze:

```bash
npm install
```

2. Avviare il dev server:

```bash
npm run dev
```

3. Se vuoi usare API reali, crea un file `.env` con:

```bash
VITE_API_BASE_URL=http://localhost/percorso/api
```

Se `VITE_API_BASE_URL` non e impostato, il frontend usa i dati mock per permettere sviluppo UI immediato.

## Configurazione backend PHP

1. Copiare:

```bash
backend/config/config.example.php
```

in:

```bash
backend/config/config.php
```

2. Inserire i parametri reali MySQL.

3. Importare lo schema:

```bash
sql/001_schema_portale_wargame_mysql.sql
```

## Stato attuale

- UI navigabile e responsive
- Placeholder per logo app, federazione e banner
- Footer con versione, note legali e contatti
- Layer API pronto per aggancio backend reale
- SQL separato pronto per import

I test end-to-end e il collegamento al database reale restano il prossimo step, appena mi dai i parametri di configurazione.
