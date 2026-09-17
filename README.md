# Nook

A cozy digital workspace for everything you work on.

**Everything has a place.**

Nook is a personal work, documentation and knowledge platform. It organizes context — workspaces for companies, products, books, podcasts and personal projects — rather than tasks alone.

## Stack

- PHP
- MySQL
- HTML / CSS / JavaScript

No Docker, Node, Redis or WebSockets required. Runs on classic shared hosting.

## Local / homelab

1. Copy the project to the web root (this homelab uses `W:\nook`).
2. Copy `config/secrets.example.php` to `config/secrets.php` and set `DB_*` plus `APP_URL`.
3. Open `install.php` once to create the database.
4. Visit the app and create an account.

Default URL on this network: `http://192.168.100.50/nook`
