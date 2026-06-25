<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Contacts\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Contacts\Models\ContactSocieteModel;

class ContactSocieteService implements ServiceInterface{
    private ContactSocieteModel $model;

    public function __construct(?ContactSocieteModel $model = null)
    {
        $this->model = $model ?? new ContactSocieteModel();
    }

    public function tableauDeBord(?int $societeId = null): array
    {
        return [
            'stats' => $this->model->stats($societeId),
            'contacts' => $this->model->listerContacts(['societe_id' => $societeId]),
            'types' => $this->model->listerTypes(),
            'refs' => $this->references($societeId),
        ];
    }

    public function contacts(array $filters = []): array
    {
        return $this->model->listerContacts($filters);
    }

    public function contact(int $id): ?array
    {
        return $this->model->trouverContact($id);
    }

    public function enregistrerContact(array $data, ?int $id, ?int $userId, ?string $ip): int
    {
        $savedId = $this->model->enregistrerContact($data, $id, $userId);
        $this->model->audit($id ? 'contact_societe.modifier' : 'contact_societe.creer', 'sav_contacts_societes', $savedId, $userId, $ip, [
            'societe_id' => (int)($data['cts_societe_id'] ?? 0),
            'type_contact_id' => !empty($data['cts_type_contact_id']) ? (int)$data['cts_type_contact_id'] : null,
        ]);
        return $savedId;
    }

    public function supprimerContact(int $id, ?int $userId, ?string $ip): void
    {
        $this->model->supprimerContact($id, $userId);
        $this->model->audit('contact_societe.supprimer', 'sav_contacts_societes', $id, $userId, $ip);
    }

    public function types(array $filters = []): array
    {
        return $this->model->listerTypes($filters);
    }

    public function type(int $id): ?array
    {
        return $this->model->trouverType($id);
    }

    public function enregistrerType(array $data, ?int $id, ?int $userId, ?string $ip): int
    {
        $savedId = $this->model->enregistrerType($data, $id);
        $this->model->audit($id ? 'type_contact.modifier' : 'type_contact.creer', 'sav_types_contacts', $savedId, $userId, $ip, [
            'code' => (string)($data['tco_code'] ?? ''),
        ]);
        return $savedId;
    }

    public function supprimerType(int $id, ?int $userId, ?string $ip): void
    {
        $this->model->supprimerType($id);
        $this->model->audit('type_contact.supprimer', 'sav_types_contacts', $id, $userId, $ip);
    }

    public function references(?int $societeId = null): array
    {
        return [
            'societes' => $this->model->societes(),
            'utilisateurs' => $this->model->utilisateurs($societeId),
            'types' => $this->model->listerTypes(),
            'statuts' => $this->model->statuts(),
        ];
    }

    public function export(?int $societeId = null): array
    {
        return [
            'genere_le' => date('c'),
            'societe_id' => $societeId,
            'stats' => $this->model->stats($societeId),
            'contacts' => $this->model->listerContacts(['societe_id' => $societeId]),
            'types_contacts' => $this->model->listerTypes(),
        ];
    }
}
