<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services;

use Nenad\Autosav\Core\Database\Database;

/**
 * Service noyau pour gérer la concurrence applicative sur une entité sensible.
 * Il s'appuie uniquement sur sav_verrous_entites et peut être utilisé par tous les modules.
 */
class VerrouEntiteService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function verifier(string $cibleType, int $cibleId, ?int $utilisateurId = null): ?array
    {
        $params = ['type' => $cibleType, 'id' => $cibleId];
        $sql = "SELECT v.*, u.uti_email_normalise
                FROM sav_verrous_entites v
                INNER JOIN sav_utilisateurs u ON u.uti_id = v.ven_utilisateur_id
                WHERE v.ven_cible_type = :type
                  AND v.ven_cible_id = :id
                  AND v.ven_libere_le IS NULL
                  AND v.ven_expire_le > NOW()";
        if ($utilisateurId !== null) {
            $sql .= " AND v.ven_utilisateur_id <> :user_id";
            $params['user_id'] = $utilisateurId;
        }
        $sql .= " ORDER BY v.ven_expire_le ASC LIMIT 1";
        return $this->db->fetch($sql, $params);
    }

    public function verrouiller(string $cibleType, int $cibleId, int $utilisateurId, ?int $sessionId = null, string $raison = '', int $dureeMinutes = 15): int
    {
        $existant = $this->verifier($cibleType, $cibleId, $utilisateurId);
        if ($existant) {
            throw new \RuntimeException('Entité déjà verrouillée par un autre utilisateur jusqu’au ' . ($existant['ven_expire_le'] ?? 'date inconnue'));
        }

        $this->db->execute(
            "INSERT INTO sav_verrous_entites (ven_cible_type, ven_cible_id, ven_utilisateur_id, ven_session_utilisateur_id, ven_raison, ven_expire_le, ven_cree_le)
             VALUES (:type, :id, :user_id, :session_id, :raison, DATE_ADD(NOW(), INTERVAL :duree MINUTE), NOW())",
            [
                'type' => $cibleType,
                'id' => $cibleId,
                'user_id' => $utilisateurId,
                'session_id' => $sessionId,
                'raison' => $raison ?: 'Verrou automatique',
                'duree' => max(1, min(1440, $dureeMinutes)),
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function prolonger(int $verrouId, int $utilisateurId, int $dureeMinutes = 15): bool
    {
        return $this->db->execute(
            "UPDATE sav_verrous_entites
             SET ven_expire_le = DATE_ADD(NOW(), INTERVAL :duree MINUTE), ven_modifie_le = NOW()
             WHERE ven_id = :id AND ven_utilisateur_id = :user_id AND ven_libere_le IS NULL",
            ['id' => $verrouId, 'user_id' => $utilisateurId, 'duree' => max(1, min(1440, $dureeMinutes))]
        );
    }

    public function liberer(int $verrouId, int $utilisateurId): bool
    {
        return $this->db->execute(
            "UPDATE sav_verrous_entites
             SET ven_libere_le = NOW(), ven_modifie_le = NOW()
             WHERE ven_id = :id AND ven_utilisateur_id = :user_id AND ven_libere_le IS NULL",
            ['id' => $verrouId, 'user_id' => $utilisateurId]
        );
    }

    public function libererPourEntite(string $cibleType, int $cibleId, int $utilisateurId): bool
    {
        return $this->db->execute(
            "UPDATE sav_verrous_entites
             SET ven_libere_le = NOW(), ven_modifie_le = NOW()
             WHERE ven_cible_type = :type AND ven_cible_id = :id AND ven_utilisateur_id = :user_id AND ven_libere_le IS NULL",
            ['type' => $cibleType, 'id' => $cibleId, 'user_id' => $utilisateurId]
        );
    }
}
