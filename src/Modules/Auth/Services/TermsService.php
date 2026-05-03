<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Services;

use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Modules\Auth\Models\TermsVersionModel;
use Nenad\Autosav\Modules\Auth\Models\TermsAcceptanceModel;

/**
 * Service de gestion des Conditions GÃ©nÃ©rales d'Utilisation.
 *
 * Logique de dÃ©clenchement du modal :
 *   - Si l'utilisateur n'a jamais acceptÃ© les CGU â†’ modal
 *   - Si la version acceptÃ©e â‰  version courante â†’ modal
 *   - Si TERMS_FORCE_EACH_LOGIN = true â†’ toujours modal
 */
class TermsService
{
    private TermsVersionModel    $versionModel;
    private TermsAcceptanceModel $acceptanceModel;
    private LogManager           $logger;

    public function __construct(
        TermsVersionModel    $versionModel,
        TermsAcceptanceModel $acceptanceModel,
        LogManager           $logger
    ) {
        $this->versionModel    = $versionModel;
        $this->acceptanceModel = $acceptanceModel;
        $this->logger          = $logger;
    }

    /**
     * VÃ©rifie si l'utilisateur doit voir le modal des CGU.
     * AppelÃ© aprÃ¨s chaque connexion rÃ©ussie (R31).
     *
     * @param int $userId ID de l'utilisateur
     * @return bool       true = modal Ã  afficher, false = accÃ¨s autorisÃ©
     */
    public function isTermsPendingForUser(int $userId): bool
    {
        // Si forÃ§age Ã  chaque connexion
        if (TERMS_FORCE_EACH_LOGIN) {
            return true;
        }

        $currentVersion = $this->versionModel->findCurrent();

        if ($currentVersion === null) {
            // Aucune CGU publiÃ©e â†’ pas de modal
            return false;
        }

        $lastAccepted = $this->acceptanceModel->findLastAcceptedByUser($userId);

        if ($lastAccepted === null) {
            // Jamais acceptÃ© â†’ modal obligatoire
            return true;
        }

        // Comparer la version acceptÃ©e avec la version courante
        return $lastAccepted['trv_version'] !== $currentVersion['trv_version'];
    }

    /**
     * RÃ©cupÃ¨re la version courante des CGU.
     *
     * @return array|null
     */
    public function getCurrentVersion(): ?array
    {
        return $this->versionModel->findCurrent();
    }

    /**
     * Traite l'acceptation des CGU par l'utilisateur (R33).
     *
     * @param int    $userId    ID de l'utilisateur
     * @param int    $versionId ID de la version acceptÃ©e
     * @param string $ip        Adresse IP
     * @param string $userAgent User-Agent
     * @return bool
     */
    public function recordAcceptance(int $userId, int $versionId, string $ip, string $userAgent): bool
    {
        $version = $this->versionModel->findById($versionId);

        if ($version === null) {
            throw new \InvalidArgumentException("Version CGU #{$versionId} introuvable.");
        }

        // Enregistrer l'acceptation en base
        $this->acceptanceModel->record($userId, $versionId, 'accepted', $ip, $userAgent);

        // Mettre Ã  jour la colonne de synthÃ¨se sur l'utilisateur
        $db = \Nenad\Autosav\Core\Database\Database::getInstance();
        $db->execute(
            "UPDATE sav_users
             SET use_terms_accepted_version = :version,
                 use_terms_accepted_at      = NOW(),
                 use_updated_at             = NOW()
             WHERE use_id = :id",
            [':version' => $version['trv_version'], ':id' => $userId]
        );

        $this->logger->channel('audit')->info('terms_accepted', [
            'user_id'    => $userId,
            'version'    => $version['trv_version'],
            'version_id' => $versionId,
            'ip'         => $ip,
        ]);

        return true;
    }

    /**
     * Traite le refus des CGU par l'utilisateur (R34).
     * Le refus est tracÃ© si l'utilisateur Ã©tait authentifiÃ© (R35).
     *
     * @param int    $userId    ID de l'utilisateur
     * @param int    $versionId ID de la version refusÃ©e
     * @param string $ip        Adresse IP
     * @param string $userAgent User-Agent
     * @return void
     */
    public function recordRefusal(int $userId, int $versionId, string $ip, string $userAgent): void
    {
        $version = $this->versionModel->findById($versionId);
        $versionStr = $version ? $version['trv_version'] : 'unknown';

        // Tracer le refus (R35 â€” utilisateur Ã©tait authentifiÃ© au moment du refus)
        $this->acceptanceModel->record($userId, $versionId, 'refused', $ip, $userAgent);

        $this->logger->channel('audit')->info('terms_refused', [
            'user_id'    => $userId,
            'version'    => $versionStr,
            'version_id' => $versionId,
            'ip'         => $ip,
        ]);

        $this->logger->channel('security')->warning('terms_refused_session_destroyed', [
            'user_id' => $userId,
            'ip'      => $ip,
        ]);
    }
}
