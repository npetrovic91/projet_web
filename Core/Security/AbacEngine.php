<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security;

use Nenad\Autosav\Core\Database\Database;

/**
 * AUTOSAV — Moteur ABAC (Attribute-Based Access Control)
 * Fichier : Core/Security/AbacEngine.php
 *
 * Évalue les politiques d'accès contextuelles définies dans :
 *   - sav_politiques_acces           (politique : code, permission liée, effet, priorité)
 *   - sav_conditions_politiques_acces (conditions : attribut, opérateur, valeur JSON)
 *
 * INTÉGRATION RBAC + ABAC (PROJET.md §4.1) :
 *   Le RBAC couvre les droits structurels (rôle → permission).
 *   L'ABAC couvre les droits contextuels (attributs dynamiques : société active,
 *   marque active, module actif, certifications, horaires, etc.).
 *
 *   Règle « deny > allow » (identique au RBAC) :
 *     1. Si RBAC refuse          → accès refusé (ABAC non consulté)
 *     2. Si ABAC dit DENY        → accès refusé (même si RBAC autorise)
 *     3. Si ABAC dit ALLOW       → accès accordé (sous réserve RBAC)
 *     4. Aucune politique ABAC   → pas de restriction contextuelle
 *
 * USAGE :
 *   // Vérification RBAC seule (comportement actuel inchangé)
 *   has_permission('repair_order.create');
 *
 *   // Vérification RBAC + ABAC
 *   AbacEngine::getInstance()->hasAccess('repair_order.create', AbacContext::fromSession());
 *
 * PERFORMANCE :
 *   Les politiques sont mises en cache statique par code de permission.
 *   Un appel à AbacEngine::clearCache() invalide ce cache
 *   (appelé automatiquement lors du changement de contexte via ContextController).
 *
 * @see PROJET.md §4.1 « règles ABAC »
 * @see sav_politiques_acces, sav_conditions_politiques_acces
 */
final class AbacEngine
{
    private static ?self $instance = null;

    /** @var array<string, array<int, array<string, mixed>>> Cache des politiques par permission */
    private static array $policyCache = [];

    private Database $db;

    private function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ----------------------------------------------------------------
    // POINT D'ENTRÉE PRINCIPAL
    // ----------------------------------------------------------------

    /**
     * Vérification RBAC + ABAC combinée.
     *
     * @param string      $permissionCode  Code de permission (ex: 'repair_order.create')
     * @param AbacContext $context         Contexte utilisateur courant
     */
    public function hasAccess(string $permissionCode, AbacContext $context): bool
    {
        // 1. RBAC structurel (rapide, en session)
        if (!function_exists('has_permission') || !has_permission($permissionCode)) {
            return false;
        }

        // 2. ABAC contextuel
        $decision = $this->evaluate($permissionCode, $context);
        if ($decision->isDenied()) {
            $this->logDenial($permissionCode, $context, $decision);
            return false;
        }

        return true;
    }

    /**
     * Évaluation ABAC pure (sans RBAC).
     * Retourne une décision avec son motif.
     */
    public function evaluate(string $permissionCode, AbacContext $context): AbacDecision
    {
        $policies = $this->loadPolicies($permissionCode);

        if ($policies === []) {
            return AbacDecision::noPolicy($permissionCode);
        }

        // Trier par priorité décroissante (100 = normale, 200 = haute, 50 = basse)
        usort($policies, static fn($a, $b) => (int) $b['pac_priorite'] <=> (int) $a['pac_priorite']);

        $denyReasons  = [];
        $allowMatched = false;

        foreach ($policies as $policy) {
            $conditions = $this->loadConditions((int) $policy['pac_id']);

            // Toutes les conditions doivent être vraies (AND implicite)
            if (!$this->evaluateConditions($conditions, $context)) {
                continue; // politique non applicable au contexte actuel
            }

            $effect = mb_strtolower(trim((string) ($policy['pac_effet'] ?? '')));

            if ($effect === 'refuser' || $effect === 'deny') {
                $denyReasons[] = (string) ($policy['pac_nom'] ?? $policy['pac_code'] ?? '?');
                // On continue pour collecter TOUS les deny actifs
            } elseif ($effect === 'autoriser' || $effect === 'allow') {
                $allowMatched = true;
            }
        }

        // deny > allow — règle absolue (PROJET.md §15.8)
        if ($denyReasons !== []) {
            return AbacDecision::denied($permissionCode, $denyReasons);
        }

        return $allowMatched
            ? AbacDecision::allowed($permissionCode)
            : AbacDecision::noPolicy($permissionCode);
    }

    // ----------------------------------------------------------------
    // ÉVALUATION DES CONDITIONS
    // ----------------------------------------------------------------

    /**
     * Évalue toutes les conditions d'une politique (AND implicite).
     * Pour un OR logique : déclarer plusieurs politiques ALLOW distinctes.
     */
    private function evaluateConditions(array $conditions, AbacContext $context): bool
    {
        foreach ($conditions as $condition) {
            $attribute = (string) ($condition['cpa_attribut']   ?? '');
            $operator  = (string) ($condition['cpa_operateur']  ?? '');
            $valueJson = (string) ($condition['cpa_valeur_json'] ?? 'null');

            $expectedValue = json_decode($valueJson, true, flags: JSON_THROW_ON_ERROR);
            $contextValue  = $context->getAttribute($attribute);

            if (!$this->applyOperator($contextValue, $operator, $expectedValue)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Applique un opérateur de comparaison.
     *
     * Opérateurs supportés :
     *   equals, not_equals, in, not_in, greater_than, less_than,
     *   greater_or_equal, less_or_equal, is_null, is_not_null,
     *   contains, starts_with, ends_with, is_true, is_false
     */
    private function applyOperator(mixed $contextValue, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            'equals',        '==' => $contextValue == $expected,
            'not_equals',    '!=' => $contextValue != $expected,
            'strict_equals', '===' => $contextValue === $expected,

            'in'     => is_array($expected) && in_array($contextValue, $expected, true),
            'not_in' => is_array($expected) && !in_array($contextValue, $expected, true),

            'greater_than',     '>'  => is_numeric($contextValue) && is_numeric($expected)
                                        && (float) $contextValue > (float) $expected,
            'less_than',        '<'  => is_numeric($contextValue) && is_numeric($expected)
                                        && (float) $contextValue < (float) $expected,
            'greater_or_equal', '>=' => is_numeric($contextValue) && is_numeric($expected)
                                        && (float) $contextValue >= (float) $expected,
            'less_or_equal',    '<=' => is_numeric($contextValue) && is_numeric($expected)
                                        && (float) $contextValue <= (float) $expected,

            'is_null'     => $contextValue === null,
            'is_not_null' => $contextValue !== null,
            'is_true'     => $contextValue === true || $contextValue === 1 || $contextValue === '1',
            'is_false'    => $contextValue === false || $contextValue === 0 || $contextValue === '0',

            'contains'    => is_string($contextValue) && is_string($expected)
                             && str_contains($contextValue, $expected),
            'starts_with' => is_string($contextValue) && is_string($expected)
                             && str_starts_with($contextValue, $expected),
            'ends_with'   => is_string($contextValue) && is_string($expected)
                             && str_ends_with($contextValue, $expected),

            default => false, // opérateur inconnu → refus par précaution
        };
    }

    // ----------------------------------------------------------------
    // CHARGEMENT DEPUIS LA BASE
    // ----------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function loadPolicies(string $permissionCode): array
    {
        if (array_key_exists($permissionCode, self::$policyCache)) {
            return self::$policyCache[$permissionCode];
        }

        $policies = $this->db->fetchAll(
            "SELECT
                 p.pac_id,
                 p.pac_code,
                 p.pac_nom,
                 p.pac_effet,
                 p.pac_priorite
               FROM sav_politiques_acces p
               INNER JOIN sav_permissions per ON per.per_id = p.pac_permission_id
               LEFT JOIN sav_statuts st ON st.sta_id = p.pac_statut_id
              WHERE per.per_code      = :perm_code
                AND p.pac_supprime_le IS NULL
                AND p.pac_archive_le  IS NULL
                AND (st.sta_id IS NULL OR (st.sta_domaine = 'general' AND st.sta_code = 'actif'))
              ORDER BY p.pac_priorite DESC, p.pac_effet DESC",
            ['perm_code' => $permissionCode]
        );

        self::$policyCache[$permissionCode] = $policies;
        return $policies;
    }

    /** @return array<int, array<string, mixed>> */
    private function loadConditions(int $policyId): array
    {
        return $this->db->fetchAll(
            "SELECT cpa_attribut, cpa_operateur, cpa_valeur_json
               FROM sav_conditions_politiques_acces
              WHERE cpa_politique_acces_id = :policy_id
                AND cpa_supprime_le IS NULL
              ORDER BY cpa_id ASC",
            ['policy_id' => $policyId]
        );
    }

    // ----------------------------------------------------------------
    // JOURNALISATION
    // ----------------------------------------------------------------

    private function logDenial(string $permCode, AbacContext $context, AbacDecision $decision): void
    {
        try {
            $this->db->execute(
                "INSERT INTO sav_journaux_audit
                 (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible,
                  jau_raison, jau_adresse_ip, jau_user_agent, jau_metadata_json, jau_cree_le)
                 VALUES (:uid, :cid, 'abac_deny', 'permission', :motif, :ip, :ua, :meta, NOW())",
                [
                    'uid'   => $context->userId,
                    'cid'   => $context->companyId,
                    'motif' => 'ABAC_DENY:' . $permCode,
                    'ip'    => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? ''),
                    'ua'    => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'meta'  => json_encode([
                        'permission' => $permCode,
                        'reasons'    => $decision->reasons,
                        'context'    => $context->toArray(),
                    ], JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (\Throwable) {
            // Journalisation non bloquante — ne jamais faire échouer une requête pour un log
        }
    }

    /** Vide le cache des politiques (à appeler après modification en back-office) */
    public static function clearCache(): void
    {
        self::$policyCache = [];
    }
}

// ============================================================
// AbacContext — attributs dynamiques du contexte utilisateur
// ============================================================

/**
 * Contexte ABAC construit depuis la session PHP.
 *
 * Attributs disponibles (correspondance PROJET.md §4.1) :
 *   company_id, concession_id, brand_id, service_id, team_id,
 *   user_level, is_super_admin, subscription_active,
 *   has_certification_{code}, module_active
 */
final class AbacContext
{
    /**
     * @param array<string, mixed> $attributes Attributs contextuels dynamiques
     */
    public function __construct(
        public readonly int   $userId    = 0,
        public readonly ?int  $companyId = null,
        public readonly ?int  $brandId   = null,
        public readonly ?int  $serviceId = null,
        public readonly ?int  $teamId    = null,
        public readonly int   $userLevel = 0,
        private readonly array $attributes = [],
    ) {}

    /**
     * Construit le contexte depuis la session PHP courante.
     * Enrichit avec les attributs calculés (certifications, etc.).
     */
    public static function fromSession(): self
    {
        $userId    = (int) ($_SESSION['user']['id']                 ?? $_SESSION['user_id']                   ?? 0);
        $companyId = (int) ($_SESSION['active_company_id']          ?? $_SESSION['user']['actual_society_id'] ?? 0) ?: null;
        $brandId   = (int) ($_SESSION['active_brand_id']            ?? $_SESSION['user']['actual_brand']      ?? 0) ?: null;
        $serviceId = (int) ($_SESSION['user']['actual_service_id']  ?? 0) ?: null;
        $teamId    = (int) ($_SESSION['user']['actual_team_id']     ?? 0) ?: null;
        $level     = (int) ($_SESSION['user']['level']              ?? $_SESSION['user_level']                ?? 0);

        $attributes = [
            'user_id'              => $userId,
            'company_id'           => $companyId,
            'concession_id'        => $companyId, // alias
            'brand_id'             => $brandId,
            'service_id'           => $serviceId,
            'team_id'              => $teamId,
            'user_level'           => $level,
            'is_super_admin'       => function_exists('has_role')
                                      && has_role(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur'),
            'subscription_active'  => true,       // validé à la connexion par TenantEntitlementMiddleware
        ];

        // Enrichissement depuis la session (certifications chargées à la connexion)
        foreach ((array) ($_SESSION['user']['certifications'] ?? []) as $certCode) {
            $key = 'has_certification_' . mb_strtolower((string) $certCode);
            $attributes[$key] = true;
        }

        return new self(
            userId:     $userId,
            companyId:  $companyId,
            brandId:    $brandId,
            serviceId:  $serviceId,
            teamId:     $teamId,
            userLevel:  $level,
            attributes: $attributes,
        );
    }

    /** Retourne la valeur d'un attribut contextuel, ou null si absent */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Retourne le contexte sous forme de tableau (pour la journalisation).
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->attributes, [
            'user_id'    => $this->userId,
            'company_id' => $this->companyId,
            'brand_id'   => $this->brandId,
        ]);
    }
}

// ============================================================
// AbacDecision — résultat d'une évaluation ABAC
// ============================================================

final class AbacDecision
{
    private function __construct(
        public readonly bool   $denied,
        public readonly bool   $allowed,
        public readonly bool   $hasPolicy,
        public readonly string $permission,
        /** @var string[] */
        public readonly array  $reasons = [],
    ) {}

    /** Accès refusé par au moins une politique DENY */
    public static function denied(string $perm, array $reasons): self
    {
        return new self(denied: true, allowed: false, hasPolicy: true, permission: $perm, reasons: $reasons);
    }

    /** Accès accordé par au moins une politique ALLOW (et aucun DENY) */
    public static function allowed(string $perm): self
    {
        return new self(denied: false, allowed: true, hasPolicy: true, permission: $perm);
    }

    /** Aucune politique ABAC pour cette permission → pas de restriction contextuelle */
    public static function noPolicy(string $perm): self
    {
        return new self(denied: false, allowed: false, hasPolicy: false, permission: $perm);
    }

    public function isDenied(): bool  { return $this->denied; }
    public function isAllowed(): bool { return $this->allowed; }
    public function hasPolicy(): bool { return $this->hasPolicy; }
}