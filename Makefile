# ============================================================
# AUTOSAV — Makefile production
# Usage : make <cible>
# ============================================================
# Requis : PHP 8.2+, Composer 2, make, ssh, curl, git
# ============================================================

SHELL := /bin/bash
.ONESHELL:
.DEFAULT_GOAL := help

# ── Configuration (surcharger via env ou make VAR=val) ───────
PHP            ?= php8.2
COMPOSER       ?= composer
APP_DIR        ?= $(shell pwd)
STORAGE_DIR    ?= $(APP_DIR)/storage

# ─────────────────────────────────────────────────────────────
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-28s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Usage: make <cible>"

# ─────────────────────────────────────────────────────────────
# DÉVELOPPEMENT LOCAL
# ─────────────────────────────────────────────────────────────

install: ## Installe les dépendances (dev)
	$(COMPOSER) install --prefer-dist

install-prod: ## Installe les dépendances production (--no-dev)
	$(COMPOSER) install \
		--no-interaction \
		--no-progress \
		--prefer-dist \
		--no-dev \
		--optimize-autoloader \
		--classmap-authoritative

update: ## Met à jour les dépendances et recrée composer.lock
	$(COMPOSER) update --prefer-dist
	@echo "⚠️  Testez AVANT de committer le nouveau composer.lock"

# ─────────────────────────────────────────────────────────────
# QUALITÉ & SÉCURITÉ
# ─────────────────────────────────────────────────────────────

lint: ## Vérifie la syntaxe PHP de tous les fichiers
	@find Core Modules config bin scripts tests -name '*.php' -print0 \
		| xargs -0 -P4 -n1 $(PHP) -l
	@echo "✅ Syntaxe PHP OK"

stan: ## Analyse statique PHPStan (niveau 5)
	$(COMPOSER) run stan

test: ## Lance la suite de tests unitaires
	$(COMPOSER) run test

audit: ## Vérifie les CVE dans les dépendances Composer
	$(COMPOSER) audit --no-interaction

security: lint stan test audit ## Lance toutes les vérifications qualité/sécurité

# ─────────────────────────────────────────────────────────────
# GESTION DES SECRETS
# ─────────────────────────────────────────────────────────────

gen-encryption-key: ## Génère une ENCRYPTION_KEY sécurisée
	@echo ""
	@echo "Copiez cette ligne dans votre .env :"
	@echo ""
	@$(PHP) -r "echo 'ENCRYPTION_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
	@echo ""

gen-healthcheck-token: ## Génère un HEALTHCHECK_TOKEN sécurisé
	@echo ""
	@echo "Copiez cette ligne dans votre .env :"
	@echo ""
	@$(PHP) -r "echo 'HEALTHCHECK_TOKEN='.bin2hex(random_bytes(32)).PHP_EOL;"
	@echo ""

gen-secrets: gen-encryption-key gen-healthcheck-token ## Génère tous les secrets

# ─────────────────────────────────────────────────────────────
# BASE DE DONNÉES
# ─────────────────────────────────────────────────────────────

migrate: ## Applique les migrations SQL en attente
	$(PHP) bin/migrate.php

migrate-dry: ## Simule les migrations sans les exécuter
	$(PHP) bin/migrate.php --dry-run

db-backup: ## Lance une sauvegarde de la base de données
	$(PHP) bin/backup_database.php
	@echo "✅ Backup créé dans storage/backups/"

db-backup-list: ## Liste les sauvegardes disponibles
	@ls -lhS $(STORAGE_DIR)/backups/ 2>/dev/null || echo "Aucune sauvegarde trouvée."

# ─────────────────────────────────────────────────────────────
# DÉPLOIEMENT
# ─────────────────────────────────────────────────────────────

preflight: ## Vérifie que l'environnement est prêt pour la production
	@echo "── Vérification preflight ──────────────────────────"
	@$(PHP) bin/production_preflight.php
	@echo "── check-production ────────────────────────────────"
	@$(PHP) scripts/check-production.php

health: ## Vérifie l'état de l'application
	@$(PHP) bin/health_check.php

storage-init: ## Crée et initialise les dossiers storage/
	@for d in logs sessions cache uploads exports backups; do \
		mkdir -p $(STORAGE_DIR)/$$d; \
		chmod 775 $(STORAGE_DIR)/$$d; \
		echo "✅ storage/$$d"; \
	done

permissions: ## Applique les permissions correctes sur le projet
	@echo "Application des permissions..."
	@find . -type f -not -path './storage/*' -not -path './vendor/*' -exec chmod 644 {} \;
	@find . -type d -not -path './storage/*' -not -path './vendor/*' -exec chmod 755 {} \;
	@chmod 640 .env 2>/dev/null || true
	@chmod 755 bin/*.php scripts/*.php
	@chmod -R 775 storage/
	@echo "✅ Permissions appliquées"

setup: storage-init permissions ## Configuration initiale complète (première installation)
	@cp -n .env.example .env || true
	@echo ""
	@echo "⚠️  Éditez .env avec vos secrets puis lancez : make preflight"

# ─────────────────────────────────────────────────────────────
# MAINTENANCE
# ─────────────────────────────────────────────────────────────

maintenance: ## Tâche de maintenance applicative complète
	$(PHP) bin/run_maintenance.php

rotate-logs: ## Rotation des fichiers de logs
	$(PHP) bin/rotate_logs.php

clean-cache: ## Vide le cache applicatif
	@rm -rf $(STORAGE_DIR)/cache/*
	@echo "✅ Cache vidé"

clean-rate-limit: ## Supprime les buckets rate limiting expirés
	@find $(STORAGE_DIR)/../ -path '*/rate_limit/*.json' \
		-mmin +60 -delete 2>/dev/null || true
	@echo "✅ Buckets rate limit expirés supprimés"

clean-sessions: ## Supprime les sessions expirées (> 2h)
	@find $(STORAGE_DIR)/sessions -name 'sess_*' \
		-mmin +120 -delete 2>/dev/null || true
	@echo "✅ Sessions expirées supprimées"

clean: clean-cache clean-rate-limit ## Nettoyage general

# ─────────────────────────────────────────────────────────────
# MONITORING
# ─────────────────────────────────────────────────────────────

health-url: ## Teste le health check via URL (nécessite curl + HEALTHCHECK_TOKEN dans .env)
	@source .env 2>/dev/null; \
	URL="$${APP_URL}/health.php?token=$${HEALTHCHECK_TOKEN}"; \
	echo "Test : $$URL"; \
	CODE=$$(curl -s -o /dev/null -w "%{http_code}" "$$URL"); \
	if [ "$$CODE" = "200" ]; then \
		echo "✅ Health check OK (HTTP $$CODE)"; \
	else \
		echo "❌ Health check ÉCHOUÉ (HTTP $$CODE)"; \
		exit 1; \
	fi

logs: ## Affiche les logs applicatifs récents
	@tail -50 $(STORAGE_DIR)/logs/application.log 2>/dev/null \
		|| echo "Aucun log applicatif trouvé."

logs-security: ## Affiche les logs de sécurité récents
	@tail -100 $(STORAGE_DIR)/logs/security.log 2>/dev/null \
		|| echo "Aucun log de sécurité trouvé."

logs-error: ## Affiche les logs d'erreurs récents
	@tail -100 $(STORAGE_DIR)/logs/error.log 2>/dev/null \
		|| echo "Aucun log d'erreur trouvé."

# ─────────────────────────────────────────────────────────────
# WORKFLOW COMPLET
# ─────────────────────────────────────────────────────────────

deploy-local: security install-prod storage-init permissions migrate preflight health ## Déploiement complet local
	@echo ""
	@echo "═══════════════════════════════════════════"
	@echo "  ✅ AUTOSAV — Déploiement production OK  "
	@echo "═══════════════════════════════════════════"

.PHONY: help install install-prod update lint stan test audit security \
	gen-encryption-key gen-healthcheck-token gen-secrets \
	migrate migrate-dry db-backup db-backup-list \
	preflight health storage-init permissions setup \
	maintenance rotate-logs clean-cache clean-rate-limit clean-sessions clean \
	health-url logs logs-security logs-error deploy-local
