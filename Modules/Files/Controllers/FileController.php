<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Files\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Files\Services\FileService;

class FileController extends BaseController
{
    private FileService $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new FileService();
    }

    public function index(): void
    {
        $this->requirePermission('files.read');
        $data = $this->files->dashboard([
            'q' => (string)$this->get('q', ''),
            'target_type' => (string)$this->get('target_type', ''),
            'target_id' => (int)$this->get('target_id', 0),
        ]);
        $this->render('Files/Views/index', $data + [
            'filters' => ['q' => (string)$this->get('q', ''), 'target_type' => (string)$this->get('target_type', ''), 'target_id' => (int)$this->get('target_id', 0)],
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Fichiers',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('files.manage');
        $data = $this->files->dashboard([]);
        $this->render('Files/Views/upload', [
            'statuts' => $data['statuts'],
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Ajouter un fichier',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('files.manage');
        $this->validateCsrf();
        try {
            $id = $this->files->upload($_FILES['file'] ?? [], $_POST, (int)$this->userId());
            $this->flash()->success('Fichier enregistré.');
            $this->redirect('/files/' . $id);
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/files/create');
        }
    }

    public function show(string $id): void
    {
        $this->requirePermission('files.read');
        $file = $this->files->trouver((int)$id);
        if (!$file) {
            $this->flash()->error('Fichier introuvable.');
            $this->redirect('/files');
        }
        $this->render('Files/Views/show', [
            'file' => $file,
            'links' => $this->files->liaisons((int)$id),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Fichier',
        ]);
    }

    public function link(string $id): void
    {
        $this->requirePermission('files.manage');
        $this->validateCsrf();
        $this->files->lier((int)$id, (string)$this->post('lfi_cible_type', ''), (int)$this->post('lfi_cible_id', 0), (string)$this->post('lfi_type_liaison', 'piece_jointe'), (int)$this->userId());
        $this->flash()->success('Liaison ajoutée.');
        $this->redirect('/files/' . (int)$id);
    }

    public function download(string $id): never
    {
        $this->requirePermission('files.read');
        $row = $this->files->contenu((int)$id);
        if (!$row) {
            http_response_code(404);
            echo 'Fichier introuvable.';
            exit;
        }
        header('Content-Type: ' . ($row['fic_mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string)strlen((string)$row['cfi_contenu_blob']));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string)$row['fic_nom_original']) . '"');
        echo $row['cfi_contenu_blob'];
        exit;
    }

    public function delete(string $id): void
    {
        $this->requirePermission('files.manage');
        $this->validateCsrf();
        $this->files->supprimer((int)$id, (int)$this->userId());
        $this->flash()->success('Fichier supprimé logiquement.');
        $this->redirect('/files');
    }
}
