# CLAUDE.md — Sylius Plugin (générique)

## Architecture du plugin

## Installation & setup

Voir `.claude/rules/docker.md` pour les commandes complètes.

```bash
cp compose.override.dist.yml compose.override.yml
# Adapter les ports dans compose.override.yml si nécessaire
ENV=dev make init
ENV=dev make database-init
ENV=dev make load-fixtures
```

## Lancer l'application de test

## Base de données

## Tests

## Conventions de code

## Conventions de commits