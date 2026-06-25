<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\BulkMail\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Emails\Models\EmailModel;
use Nenad\Autosav\Modules\Emails\Services\EmailService;

class BulkMailService implements ServiceInterface{
    private EmailModel $model;
    private EmailService $email;

    public function __construct(?EmailModel $model = null, ?EmailService $email = null)
    {
        $this->model = $model ?? new EmailModel();
        $this->email = $email ?? new EmailService($this->model);
    }

    public function listerCampagnesDepuisJournaux(int $limit = 100): array
    {
        $rows = $this->model->listerJournaux([
            'type_evenement' => 'bulk_mail',
            'societe_id' => $this->activeCompanyId(),
        ], $limit * 10);
        $groupes = [];
        foreach ($rows as $row) {
            $key = (string) ($row['jme_type_evenement'] ?? 'bulk_mail');
            if (!isset($groupes[$key])) {
                $groupes[$key] = [
                    'code' => $key,
                    'sujet' => (string) ($row['jme_sujet'] ?? ''),
                    'cree_le' => $row['jme_cree_le'] ?? null,
                    'total' => 0,
                    'envoyes' => 0,
                    'echoues' => 0,
                    'modele_code' => $row['mel_code'] ?? null,
                ];
            }
            $groupes[$key]['total']++;
            if (($row['statut_code'] ?? '') === 'envoyee') {
                $groupes[$key]['envoyes']++;
            } elseif (($row['statut_code'] ?? '') === 'echouee') {
                $groupes[$key]['echoues']++;
            }
        }
        return array_slice(array_values($groupes), 0, $limit);
    }

    public function preparerFormulaire(): array
    {
        $companyId = $this->activeCompanyId();
        return [
            'modeles' => $this->model->listerModeles(),
            'roles' => $this->model->listerRoles(),
            'companies' => $this->model->listerSocietes($this->isSuperAdmin() ? null : $companyId),
        ];
    }

    public function listerJournauxCampagne(string $code, int $limit = 500): array
    {
        return $this->model->listerJournaux([
            'type_evenement' => $code,
            'societe_id' => $this->activeCompanyId(),
        ], $limit);
    }

    public function compterDestinataires(array $data): int
    {
        return count($this->resoudreDestinataires($data));
    }

    public function envoyerGroupe(array $data, int $operateurId = 0): array
    {
        $destinataires = $this->resoudreDestinataires($data);
        $modeleId = !empty($data['modele_id']) ? (int) $data['modele_id'] : null;
        $subject = trim((string) ($data['subject'] ?? $data['mel_sujet'] ?? ''));
        $body = (string) ($data['body_html'] ?? $data['body'] ?? $data['mel_corps'] ?? '');
        $batchCode = 'bulk_mail.' . date('YmdHis') . '.u' . max(0, $operateurId);
        $activeCompanyId = isset($_SESSION['active_company_id']) ? (int) $_SESSION['active_company_id'] : null;

        if ($modeleId) {
            $modele = $this->model->trouverModele($modeleId);
            if (!$modele) {
                return ['success' => false, 'message' => 'Modèle email introuvable.', 'sent' => 0, 'failed' => 0, 'total' => 0];
            }
            $subject = (string) $modele['mel_sujet'];
            $body = (string) $modele['mel_corps'];
        }

        if ($subject === '' || trim(strip_tags($body)) === '') {
            return ['success' => false, 'message' => 'Sujet et contenu obligatoires.', 'sent' => 0, 'failed' => 0, 'total' => count($destinataires)];
        }

        $max = max(1, min(500, (int) ($data['max_recipients'] ?? 200)));
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach (array_slice($destinataires, 0, $max) as $recipient) {
            $userId = (int) ($recipient['uti_id'] ?? 0);
            if ($userId > 0 && !$this->model->emailAutorisePourUtilisateur($userId, 'bulk_mail')) {
                $skipped++;
                continue;
            }

            $variables = [
                'firstname' => (string) ($recipient['pui_prenom'] ?? ''),
                'lastname' => (string) ($recipient['pui_nom'] ?? ''),
                'fullname' => trim((string) (($recipient['pui_prenom'] ?? '') . ' ' . ($recipient['pui_nom'] ?? ''))),
                'email' => (string) ($recipient['uti_email'] ?? ''),
                'societe' => (string) ($recipient['societe_nom'] ?? ''),
            ];
            $unsubscribeUrl = $this->unsubscribeUrl($userId, (string) ($recipient['uti_email'] ?? ''));
            $variables['unsubscribe_url'] = $unsubscribeUrl;

            $result = $this->email->envoyer([
                'to' => $recipient['uti_email'] ?? '',
                'to_name' => $variables['fullname'],
                'subject' => $this->email->rendre($subject, $variables),
                'body' => $this->email->rendre($body, $variables),
                'modele_id' => $modeleId,
                'societe_expediteur_id' => $activeCompanyId,
                'societe_destinataire_id' => $recipient['societe_id'] ?? null,
                'utilisateur_destinataire_id' => $userId ?: null,
                'type_evenement' => $batchCode,
                'unsubscribe_url' => $unsubscribeUrl,
            ]);

            $result['success'] ? $sent++ : $failed++;
        }

        return [
            'success' => true,
            'code' => $batchCode,
            'total' => count($destinataires),
            'limite' => $max,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'message' => sprintf('%d email(s) envoyé(s), %d échec(s), %d ignoré(s).', $sent, $failed, $skipped),
        ];
    }

    public function desabonner(string $token): bool
    {
        $payload = $this->email->lireTokenDesabonnement($token);
        if (!$payload) {
            return false;
        }
        $this->model->desactiverPreferenceEmail((int) $payload['uid'], 'bulk_mail');
        return true;
    }

    private function resoudreDestinataires(array $data): array
    {
        $target = (string) ($data['target'] ?? 'all');
        $activeCompanyId = $this->activeCompanyId();
        $requestedCompanyId = !empty($data['filter_company_id']) ? (int) $data['filter_company_id'] : 0;
        if (!$this->isSuperAdmin() && $requestedCompanyId > 0 && $requestedCompanyId !== $activeCompanyId) {
            return [];
        }

        $companyId = $this->isSuperAdmin() && $requestedCompanyId > 0
            ? $requestedCompanyId
            : $activeCompanyId;
        if ($companyId <= 0) {
            return [];
        }

        $filters = ['company_id' => $companyId];
        if (in_array($target, ['by_role', 'by_role_and_company'], true) && !empty($data['filter_role_id'])) {
            $filters['role_id'] = (int) $data['filter_role_id'];
        }
        return $this->model->destinatairesUtilisateurs($filters);
    }

    private function activeCompanyId(): int
    {
        return (int) ($_SESSION['active_company_id'] ?? $_SESSION['user']['active_company_id'] ?? 0);
    }

    private function isSuperAdmin(): bool
    {
        return function_exists('has_role')
            && has_role(defined('ROLE_SUPERADMIN') ? (string) ROLE_SUPERADMIN : 'super_administrateur');
    }

    private function unsubscribeUrl(int $userId, string $email): string
    {
        if ($userId <= 0 || $email === '') {
            return '';
        }
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
        return $base . '/email/unsubscribe?token=' . urlencode($this->email->creerTokenDesabonnement($userId, $email));
    }
}
