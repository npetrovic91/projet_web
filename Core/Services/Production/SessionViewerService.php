<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Production;

/**
 * AUTOSAV — Lecture des sessions PHP fichier (storage/sessions/)
 *
 * Lecture seule. Le moteur de session du projet (Core/Security/Class/
 * SessionHandler.php) utilise session.save_handler=files au format
 * standard PHP ("cle|valeur_serialisee;cle2|valeur2;..."), jamais
 * session_serializer=php_serialize. decoderSession() s'appuie sur ce
 * format sans jamais appeler session_decode()/session_start() sur ces
 * données — cela modifierait la session active de la requête en cours.
 * unserialize() s'arrête naturellement à la fin d'une valeur et ignore
 * le reste de la chaîne ; on retrouve l'octet de reprise exact en
 * resérialisant la valeur obtenue (strlen(serialize($valeur)) ===
 * nombre d'octets consommés, par construction du format).
 */
final class SessionViewerService
{
    public function __construct(private string $dossier = '')
    {
        $this->dossier = $this->dossier !== '' ? $this->dossier : (defined('SESSION_SAVE_PATH') ? SESSION_SAVE_PATH : '');
    }

    /**
     * Liste les sessions actives, triées par dernière activité (plus
     * récente en premier), avec un résumé sûr (jamais le contenu brut).
     */
    public function lister(int $limite = 200): array
    {
        $fichiers = glob(rtrim($this->dossier, '/\\') . '/sess_*') ?: [];
        $resultat = [];

        foreach ($fichiers as $chemin) {
            if (!is_file($chemin)) {
                continue;
            }
            $id = $this->idDepuisChemin($chemin);
            if ($id === null) {
                continue;
            }
            $tailleOctets = (int) (filesize($chemin) ?: 0);
            $modifieLe = (int) (filemtime($chemin) ?: 0);
            $donnees = $tailleOctets > 0 ? $this->decoder((string) (file_get_contents($chemin) ?: '')) : [];

            $resultat[] = [
                'id' => $id,
                'modifie_le' => $modifieLe,
                'taille_octets' => $tailleOctets,
                'resume' => $this->resumer($donnees),
            ];
        }

        usort($resultat, static fn(array $a, array $b): int => $b['modifie_le'] <=> $a['modifie_le']);

        return array_slice($resultat, 0, max(1, min(2000, $limite)));
    }

    /**
     * Contenu décodé d'une session précise. $id est strictement validé
     * (alphanumérique, format PHP standard) avant toute construction de
     * chemin — pas d'accès fichier arbitraire possible via ce paramètre.
     */
    public function afficher(string $id): ?array
    {
        if (!$this->idValide($id)) {
            return null;
        }
        $chemin = rtrim($this->dossier, '/\\') . '/sess_' . $id;
        if (!is_file($chemin)) {
            return null;
        }

        $tailleOctets = (int) (filesize($chemin) ?: 0);
        $modifieLe = (int) (filemtime($chemin) ?: 0);
        $brut = (string) (file_get_contents($chemin) ?: '');

        return [
            'id' => $id,
            'modifie_le' => $modifieLe,
            'taille_octets' => $tailleOctets,
            'donnees' => $this->decoder($brut),
        ];
    }

    /**
     * Décode le format "cle|valeur_serialisee;..." des sessions PHP en
     * tableau associatif, sans jamais utiliser session_decode().
     */
    private function decoder(string $brut): array
    {
        $resultat = [];
        $longueur = strlen($brut);
        $position = 0;
        $iterations = 0;

        while ($position < $longueur && $iterations < 500) {
            $iterations++;
            $posPipe = strpos($brut, '|', $position);
            if ($posPipe === false) {
                break;
            }
            $cle = substr($brut, $position, $posPipe - $position);
            $reste = substr($brut, $posPipe + 1);

            $valeur = @unserialize($reste, ['allowed_classes' => false]);
            if ($valeur === false && !str_starts_with($reste, 'b:0;')) {
                // Valeur non décodable (format inattendu) : on s'arrête plutôt que de boucler.
                break;
            }

            $consomme = strlen(serialize($valeur));
            $resultat[$cle] = $valeur;
            $position = $posPipe + 1 + $consomme;
        }

        return $resultat;
    }

    /**
     * Résumé sûr pour la liste : jamais de jeton CSRF, mot de passe ou
     * autre secret, seulement ce qui aide à identifier la session.
     */
    private function resumer(array $donnees): array
    {
        $user = $donnees['user'] ?? null;
        return [
            'utilisateur_id' => is_array($user) ? ($user['id'] ?? null) : ($donnees['user_id'] ?? null),
            'utilisateur_nom' => is_array($user) ? ($user['name'] ?? null) : null,
            'role_codes' => is_array($user) ? ($user['role_codes'] ?? []) : ($donnees['user_roles'] ?? []),
            'societe_active_id' => $donnees['active_company_id'] ?? null,
        ];
    }

    private function idDepuisChemin(string $chemin): ?string
    {
        $nom = basename($chemin);
        if (!str_starts_with($nom, 'sess_')) {
            return null;
        }
        $id = substr($nom, 5);
        return $this->idValide($id) ? $id : null;
    }

    /**
     * Format des identifiants de session PHP (session.sid_bits_per_character
     * par défaut) : alphanumérique + "," et "-". Rejette tout le reste pour
     * empêcher une traversée de chemin via ce paramètre.
     */
    private function idValide(string $id): bool
    {
        return $id !== '' && preg_match('/^[A-Za-z0-9,-]+$/', $id) === 1;
    }
}
