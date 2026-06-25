<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

use Nenad\Autosav\Core\Database\Database;

/**
 * AUTOSAV — Garde d'accès justifié du super_admin aux données métier
 *
 * Implémente la contrainte non négociable du cahier des charges : le
 * super_admin (rôle technique de plateforme, jamais un PDG métier global)
 * ne doit consulter les données d'une société cliente qu'en mode
 * support/audit/sécurité/incident/maintenance, avec une justification
 * écrite, et chaque accès doit être journalisé (qui, pourquoi, quand,
 * quelle société, quelles actions, combien de temps). Cf. ACC-007.
 *
 * Ce garde-fou s'ajoute aux vérifications de périmètre existantes
 * (ServiceAccesSocietes), il ne les remplace pas : un super_admin garde
 * un accès technique total, mais doit désormais le justifier explicitement
 * avant de voir les données d'une société qui n'est pas la sienne.
 */
final class SuperAdminAccessGuard
{
    public const MOTIFS = ['support', 'audit', 'securite', 'incident', 'maintenance'];

    private const DUREE_MAX_HEURES = 4;

    public static function estSuperAdmin(): bool
    {
        return function_exists('has_role') && has_role(['super_administrateur', 'super_admin', 'SUPERADMIN']);
    }

    /**
     * Vrai si la société consultée n'appartient pas au périmètre propre
     * de l'utilisateur courant (donc nécessite une justification s'il
     * s'agit d'un super_admin).
     */
    public static function estSocieteEtrangere(int $societeId): bool
    {
        $ids = [];
        foreach (($_SESSION['user']['societes'] ?? []) as $societe) {
            foreach (['id', 'soc_id', 'com_id'] as $cle) {
                if (isset($societe[$cle])) {
                    $ids[] = (int) $societe[$cle];
                }
            }
        }
        foreach (['active_company_id', 'active_society_id'] as $cle) {
            if (isset($_SESSION[$cle])) {
                $ids[] = (int) $_SESSION[$cle];
            }
            if (isset($_SESSION['user'][$cle])) {
                $ids[] = (int) $_SESSION['user'][$cle];
            }
        }

        return !in_array($societeId, array_unique(array_filter($ids)), true);
    }

    /**
     * Vrai si une justification active (non expirée) couvre déjà cette
     * société pour la session courante.
     */
    public static function aUneJustificationActive(int $societeId): bool
    {
        $entree = $_SESSION['superadmin_access'][$societeId] ?? null;
        if (!is_array($entree) || empty($entree['log_id'])) {
            return false;
        }

        $debut = (int) ($entree['started_at'] ?? 0);
        if ($debut <= 0 || (time() - $debut) > self::DUREE_MAX_HEURES * 3600) {
            unset($_SESSION['superadmin_access'][$societeId]);
            return false;
        }

        return true;
    }

    /**
     * Ouvre un accès justifié : enregistre la justification en base et
     * pose le marqueur de session permettant de ne plus la redemander
     * pendant la durée de validité.
     */
    public static function ouvrirAcces(int $utilisateurId, int $societeId, string $motif, string $justification, string $perimetre): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO sav_acces_donnees_superadmin
                (asd_utilisateur_id, asd_societe_id, asd_motif, asd_justification, asd_perimetre,
                 asd_actions_json, asd_adresse_ip, asd_user_agent, asd_debute_le, asd_derniere_activite_le, asd_cree_le)
             VALUES
                (:uid, :sid, :motif, :justification, :perimetre, :actions, :ip, :ua, NOW(), NOW(), NOW())',
            [
                'uid' => $utilisateurId,
                'sid' => $societeId,
                'motif' => $motif,
                'justification' => $justification,
                'perimetre' => $perimetre,
                'actions' => json_encode([['action' => 'ouverture_acces', 'route' => $perimetre, 'date' => date('c')]], JSON_UNESCAPED_UNICODE),
                'ip' => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? ''),
                'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
        $logId = (int) $db->lastInsertId();

        $_SESSION['superadmin_access'][$societeId] = [
            'log_id' => $logId,
            'started_at' => time(),
            'motif' => $motif,
        ];

        return $logId;
    }

    /**
     * Journalise une action effectuée pendant un accès déjà justifié, et
     * met à jour la dernière activité (utilisée pour calculer la durée).
     */
    public static function journaliserAction(int $societeId, string $route): void
    {
        $entree = $_SESSION['superadmin_access'][$societeId] ?? null;
        if (!is_array($entree) || empty($entree['log_id'])) {
            return;
        }

        try {
            $db = Database::getInstance();
            $ligne = $db->fetch('SELECT asd_actions_json FROM sav_acces_donnees_superadmin WHERE asd_id = :id', ['id' => $entree['log_id']]);
            $actions = $ligne ? (json_decode((string) $ligne['asd_actions_json'], true) ?: []) : [];
            $actions[] = ['action' => 'consultation', 'route' => $route, 'date' => date('c')];

            $db->execute(
                'UPDATE sav_acces_donnees_superadmin
                 SET asd_actions_json = :actions, asd_derniere_activite_le = NOW()
                 WHERE asd_id = :id',
                ['actions' => json_encode($actions, JSON_UNESCAPED_UNICODE), 'id' => $entree['log_id']]
            );
        } catch (\Throwable) {
            // La journalisation ne doit jamais faire échouer la requête métier.
        }
    }

    /**
     * Clôture tous les accès justifiés ouverts de la session (durée
     * définitivement calculable). À appeler à la déconnexion.
     */
    public static function cloturerTout(): void
    {
        $acces = $_SESSION['superadmin_access'] ?? [];
        if (!is_array($acces) || $acces === []) {
            return;
        }

        try {
            $db = Database::getInstance();
            foreach ($acces as $entree) {
                if (!is_array($entree) || empty($entree['log_id'])) {
                    continue;
                }
                $db->execute(
                    'UPDATE sav_acces_donnees_superadmin SET asd_termine_le = NOW() WHERE asd_id = :id AND asd_termine_le IS NULL',
                    ['id' => $entree['log_id']]
                );
            }
        } catch (\Throwable) {
            // Idem : ne jamais bloquer la déconnexion pour un souci de journalisation.
        }

        unset($_SESSION['superadmin_access']);
    }
}
