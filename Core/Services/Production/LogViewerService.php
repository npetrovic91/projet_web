<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Production;

/**
 * AUTOSAV — Lecture des journaux applicatifs (storage/logs/)
 *
 * Lecture seule : ce service ne modifie jamais les fichiers de logs. Les
 * 5 canaux connus correspondent exactement à Core/Logger/LogManager.php
 * (application, security, database, audit, error). Le canal "error"
 * mélange deux formats de ligne : le nôtre ([date] [canal niveau]
 * message {contexte}, via Core/Logger/Class/Handler/FileHandler.php) et
 * le format natif PHP ([date fuseau] PHP Warning: ...), car
 * config/environment.php pointe ini_set('error_log', ...) sur le même
 * fichier. parseLine() gère les deux, avec repli sur la ligne brute si
 * aucun des deux motifs ne correspond.
 */
final class LogViewerService
{
    /** Canaux connus, alignés sur Core/Logger/LogManager.php. */
    private const CANAUX = ['application', 'security', 'database', 'audit', 'error'];

    /** Taille max lue en mémoire par fichier, pour ne jamais charger un log de plusieurs Go entièrement. */
    private const TAILLE_MAX_LUE = 8 * 1024 * 1024; // 8 Mo

    public function __construct(private string $dossier = '')
    {
        $this->dossier = $this->dossier !== '' ? $this->dossier : (defined('LOGS_PATH') ? LOGS_PATH : '');
    }

    /**
     * Liste les canaux avec leurs métadonnes (taille, dernière écriture).
     * Inclut aussi tout fichier *.log présent mais non déclaré dans
     * LogManager, pour ne jamais cacher un journal existant.
     */
    public function canaux(): array
    {
        $resultat = [];
        $vus = [];

        foreach (self::CANAUX as $nom) {
            $chemin = $this->cheminCanal($nom);
            $resultat[] = $this->metaCanal($nom, $chemin);
            $vus[$nom] = true;
        }

        foreach (glob(rtrim($this->dossier, '/\\') . '/*.log') ?: [] as $fichier) {
            $nom = basename($fichier, '.log');
            if (isset($vus[$nom])) {
                continue;
            }
            $resultat[] = $this->metaCanal($nom, $fichier);
        }

        return $resultat;
    }

    /**
     * Dernières lignes d'un canal, plus récentes en premier.
     *
     * @return array{lignes: array<int, array{brut: string, date: ?string, niveau: ?string, message: ?string}>, total_lignes: int, fichier_existe: bool, taille_octets: int, tronque: bool}
     */
    public function lire(string $canal, int $limite = 200, string $recherche = '', string $niveau = ''): array
    {
        $canal = $this->normaliserNomCanal($canal);
        $chemin = $this->cheminCanal($canal);
        $limite = max(1, min(2000, $limite));

        if (!is_file($chemin)) {
            return ['lignes' => [], 'total_lignes' => 0, 'fichier_existe' => false, 'taille_octets' => 0, 'tronque' => false];
        }

        $tailleOctets = (int) (filesize($chemin) ?: 0);
        $tronque = $tailleOctets > self::TAILLE_MAX_LUE;

        $contenu = $tronque
            ? $this->lireFinDeFichier($chemin, self::TAILLE_MAX_LUE)
            : (file_get_contents($chemin) ?: '');

        $toutesLignes = preg_split('/\r\n|\r|\n/', rtrim($contenu, "\r\n")) ?: [];
        if ($toutesLignes === ['']) {
            $toutesLignes = [];
        }

        $recherche = trim($recherche);
        $niveau = trim(mb_strtolower($niveau));

        $parsees = [];
        foreach ($toutesLignes as $ligneBrute) {
            if ($ligneBrute === '') {
                continue;
            }
            $analyse = $this->parserLigne($ligneBrute);

            if ($recherche !== '' && stripos($ligneBrute, $recherche) === false) {
                continue;
            }
            if ($niveau !== '' && mb_strtolower((string) ($analyse['niveau'] ?? '')) !== $niveau) {
                continue;
            }
            $parsees[] = $analyse;
        }

        $totalApresFiltre = count($parsees);
        $parsees = array_slice($parsees, -$limite);
        $parsees = array_reverse($parsees);

        return [
            'lignes' => $parsees,
            'total_lignes' => $totalApresFiltre,
            'fichier_existe' => true,
            'taille_octets' => $tailleOctets,
            'tronque' => $tronque,
        ];
    }

    private function metaCanal(string $nom, string $chemin): array
    {
        $existe = is_file($chemin);
        return [
            'nom' => $nom,
            'chemin' => $chemin,
            'existe' => $existe,
            'taille_octets' => $existe ? (int) (filesize($chemin) ?: 0) : 0,
            'modifie_le' => $existe ? (int) (filemtime($chemin) ?: 0) : null,
        ];
    }

    /**
     * Lit uniquement les derniers $octets d'un fichier, sans jamais le
     * charger entièrement — nécessaire sur un log de plusieurs Mo/Go.
     */
    private function lireFinDeFichier(string $chemin, int $octets): string
    {
        $taille = (int) (filesize($chemin) ?: 0);
        $depart = max(0, $taille - $octets);

        $poignee = @fopen($chemin, 'rb');
        if ($poignee === false) {
            return '';
        }
        try {
            fseek($poignee, $depart);
            $contenu = stream_get_contents($poignee) ?: '';
        } finally {
            fclose($poignee);
        }

        // Repart au premier saut de ligne complet pour ne pas garder une ligne tronquée en tête.
        $premierSaut = strpos($contenu, "\n");
        return $premierSaut !== false && $depart > 0 ? substr($contenu, $premierSaut + 1) : $contenu;
    }

    /**
     * @return array{brut: string, date: ?string, niveau: ?string, message: ?string}
     */
    private function parserLigne(string $ligne): array
    {
        // Format maison : [2026-06-28 10:13:00] [canal niveau] message {"contexte":...}
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*\[([^\]]+)\]\s*(.*)$/s', $ligne, $m) === 1) {
            return ['brut' => $ligne, 'date' => $m[1], 'niveau' => $this->extraireNiveau($m[2]), 'message' => $m[3]];
        }

        // Format natif PHP : [28-Jun-2026 23:11:10 Europe/Paris] PHP Warning:  message
        if (preg_match('/^\[([0-9]{1,2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}[^\]]*)\]\s*PHP\s+(\w+):\s*(.*)$/s', $ligne, $m) === 1) {
            return ['brut' => $ligne, 'date' => $m[1], 'niveau' => mb_strtolower($m[2]), 'message' => $m[3]];
        }

        // Format inconnu : on garde la ligne brute sans la masquer.
        return ['brut' => $ligne, 'date' => null, 'niveau' => null, 'message' => null];
    }

    private function extraireNiveau(string $etiquette): ?string
    {
        // "canal niveau" (ex. "error ERROR", "audit INFO") -> dernier mot.
        $parties = preg_split('/\s+/', trim($etiquette)) ?: [];
        $dernier = end($parties);
        return $dernier !== false && $dernier !== '' ? mb_strtolower($dernier) : null;
    }

    private function normaliserNomCanal(string $canal): string
    {
        $canal = preg_replace('/[^a-zA-Z0-9_-]/', '', $canal) ?? '';
        return $canal !== '' ? $canal : 'application';
    }

    private function cheminCanal(string $canal): string
    {
        return rtrim($this->dossier, '/\\') . '/' . $canal . '.log';
    }
}
