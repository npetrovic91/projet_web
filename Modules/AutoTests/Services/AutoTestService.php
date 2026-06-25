<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\AutoTests\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

/**
 * Inventorie les modules metier et le noyau Core sans executer d'action.
 */
final class AutoTestService implements ServiceInterface
{
    private string $racine;
    private string $modulesPath;
    private string $corePath;

    public function __construct(?string $racine = null)
    {
        $this->racine = $racine ?? (defined('SRC_PATH') ? SRC_PATH : dirname(__DIR__, 3));
        $this->modulesPath = $this->racine . '/Modules';
        $this->corePath = $this->racine . '/Core';
    }

    public function modules(): array
    {
        $modules = [
            'Core' => $this->resumeComposant('Core', 'core'),
        ];
        foreach ($this->coreComposants() as $composant) {
            $nom = 'Core_' . $composant;
            $modules[$nom] = $this->resumeComposant($nom, 'core');
        }
        foreach (glob($this->modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $module = basename($dir);
            $modules[$module] = $this->resumeComposant($module, 'module');
        }
        ksort($modules);
        return array_values($modules);
    }

    public function resumeGlobal(): array
    {
        $modules = $this->modules();
        $totalClasses = 0;
        $totalMethodes = 0;
        $notok = 0;
        foreach ($modules as $module) {
            $totalClasses += count($module['classes']);
            foreach ($module['classes'] as $classe) {
                $totalMethodes += count($classe['methodes_publiques']);
                if ($classe['statut'] !== 'SUCCESS') {
                    $notok++;
                }
                foreach ($classe['methodes_publiques'] as $methode) {
                    if ($methode['statut'] !== 'SUCCESS') {
                        $notok++;
                    }
                }
            }
            if (!$module['page_autotest']) {
                $notok++;
            }
        }
        return [
            'composants' => count($modules),
            'modules' => count(array_filter($modules, static fn(array $module): bool => $module['type'] === 'module')),
            'core' => count(array_filter($modules, static fn(array $module): bool => $module['type'] === 'core')),
            'classes' => $totalClasses,
            'methodes_publiques' => $totalMethodes,
            'notok' => $notok,
            'success' => $notok === 0,
        ];
    }

    public function testerModule(string $module): array
    {
        $module = $this->normaliserModule($module);
        $dir = $this->cheminDuModule($module);
        if (!is_dir($dir)) {
            return [
                'nom' => $module,
                'type' => $this->estCore($module) ? 'core' : 'module',
                'statut' => 'NOTOK',
                'message' => 'Composant introuvable.',
                'page_autotest' => false,
                'fichiers_php' => 0,
                'classes' => [],
            ];
        }
        $classes = $this->classesDuModule($module);
        $notok = [];
        foreach ($classes as $classe) {
            if ($classe['statut'] !== 'SUCCESS') {
                $notok[] = $classe['classe'];
            }
            foreach ($classe['methodes_publiques'] as $methode) {
                if ($methode['statut'] !== 'SUCCESS') {
                    $notok[] = $classe['classe'] . '::' . $methode['nom'];
                }
            }
        }
        $page = $this->pageAutotestExiste($module);
        if (!$page) {
            $notok[] = 'Page autotest manquante';
        }
        return [
            'nom' => $module,
            'type' => $this->estCore($module) ? 'core' : 'module',
            'statut' => $notok === [] ? 'SUCCESS' : 'NOTOK',
            'message' => $notok === [] ? 'Autotest structurel reussi.' : 'Elements a deboguer : ' . implode(', ', $notok),
            'page_autotest' => $page,
            'fichiers_php' => count($this->fichiersPhp($module)),
            'classes' => $classes,
        ];
    }

    public function export(): array
    {
        $modules = [];
        foreach ($this->modules() as $module) {
            $modules[] = $this->testerModule($module['nom']);
        }
        return [
            'resume' => $this->resumeGlobal(),
            'modules' => $modules,
        ];
    }

    private function resumeComposant(string $module, string $type): array
    {
        return [
            'nom' => $module,
            'type' => $type,
            'chemin' => $this->cheminDuModule($module),
            'page_autotest' => $this->pageAutotestExiste($module),
            'fichiers_php' => count($this->fichiersPhp($module)),
            'classes' => $this->classesDuModule($module),
        ];
    }

    private function classesDuModule(string $module): array
    {
        $classes = [];
        foreach ($this->fichiersPhp($module) as $file) {
            $analyse = $this->analyserFichier($file, $module);
            if ($analyse !== null) {
                $classes[] = $analyse;
            }
        }
        usort($classes, static fn(array $a, array $b): int => strcmp($a['classe'], $b['classe']));
        return $classes;
    }

    private function analyserFichier(string $file, string $module): ?array
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return null;
        }
        $tokens = token_get_all($code);
        $namespace = '';
        $classe = '';
        $methodes = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    $t = $tokens[$j];
                    if ($t === ';' || $t === '{') {
                        break;
                    }
                    if (is_array($t) && in_array($t[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $namespace .= $t[1];
                    }
                }
            }
            if (
                $classe === ''
                && is_array($token)
                && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)
                && !$this->estClasseAnonyme($tokens, $i)
            ) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $t = $tokens[$j];
                    if (is_array($t) && $t[0] === T_STRING) {
                        $classe = $t[1];
                        break;
                    }
                }
            }
            if (is_array($token) && $token[0] === T_FUNCTION) {
                $visibility = $this->visibiliteAvant($tokens, $i);
                $nom = null;
                for ($j = $i + 1; $j < $count; $j++) {
                    $t = $tokens[$j];
                    if ($t === '(') {
                        break;
                    }
                    if (is_array($t) && $t[0] === T_STRING) {
                        $nom = $t[1];
                        break;
                    }
                }
                if ($nom !== null && $visibility === 'public') {
                    $methodes[] = [
                        'nom' => $nom,
                        'statut' => 'SUCCESS',
                        'message' => 'Methode publique detectee. Execution destructive non lancee.',
                        'debug_url' => '/autotests/debogage?module=' . rawurlencode($module) . '&classe=' . rawurlencode($classe) . '&fonction=' . rawurlencode($nom),
                    ];
                }
            }
        }
        if ($classe === '') {
            return null;
        }
        $fqn = trim($namespace . '\\' . $classe, '\\');
        $statut = $this->namespaceConforme($namespace, $module) ? 'SUCCESS' : 'NOTOK';
        return [
            'fichier' => ltrim(str_replace('\\', '/', substr($file, strlen($this->racine))), '/'),
            'classe' => $fqn,
            'statut' => $statut,
            'message' => $statut === 'SUCCESS' ? 'Classe conforme au composant.' : 'Namespace ou classe incoherent avec le composant.',
            'methodes_publiques' => $methodes,
            'debug_url' => '/autotests/debogage?module=' . rawurlencode($module) . '&classe=' . rawurlencode($fqn),
        ];
    }

    private function visibiliteAvant(array $tokens, int $index): string
    {
        for ($i = $index - 1; $i >= 0 && $i >= $index - 12; $i--) {
            $token = $tokens[$i];
            if (is_array($token)) {
                if ($token[0] === T_PUBLIC) {
                    return 'public';
                }
                if ($token[0] === T_PROTECTED || $token[0] === T_PRIVATE) {
                    return 'non_public';
                }
            }
        }
        return 'public';
    }

    /**
     * @return array<string>
     */
    private function fichiersPhp(string $module): array
    {
        $dir = $this->cheminDuModule($module);
        if (!is_dir($dir)) {
            return [];
        }
        if (!$this->estCore($module)) {
            return glob($dir . '/{Controllers,Models,Services}/*.php', GLOB_BRACE) ?: [];
        }

        $fichiers = [];
        $iterateur = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterateur as $fichier) {
            if ($fichier->isFile() && strtolower($fichier->getExtension()) === 'php') {
                $fichiers[] = $fichier->getPathname();
            }
        }
        sort($fichiers);
        return $fichiers;
    }

    private function cheminDuModule(string $module): string
    {
        if ($module === 'Core') {
            return $this->corePath;
        }
        if (str_starts_with($module, 'Core_')) {
            return $this->corePath . '/' . substr($module, 5);
        }
        return $this->modulesPath . '/' . $module;
    }

    private function pageAutotestExiste(string $module): bool
    {
        if ($this->estCore($module)) {
            return is_file($this->cheminDuModule($module) . '/autotest.php');
        }
        return is_file($this->cheminDuModule($module) . '/Views/autotest.php');
    }

    private function namespaceConforme(string $namespace, string $module): bool
    {
        if ($module === 'Core') {
            return $namespace === 'Nenad\\Autosav\\Core' || str_starts_with($namespace, 'Nenad\\Autosav\\Core\\');
        }
        if (str_starts_with($module, 'Core_')) {
            $composant = substr($module, 5);
            $attendu = 'Nenad\\Autosav\\Core\\' . $composant;
            return $namespace === $attendu || str_starts_with($namespace, $attendu . '\\');
        }
        return str_contains($namespace, '\\Modules\\' . $module . '\\');
    }

    private function estClasseAnonyme(array $tokens, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return is_array($token) && $token[0] === T_NEW;
        }
        return false;
    }

    private function normaliserModule(string $module): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', $module) ?: '';
    }

    /**
     * @return array<string>
     */
    private function coreComposants(): array
    {
        $composants = [];
        foreach (glob($this->corePath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $composants[] = basename($dir);
        }
        sort($composants);
        return $composants;
    }

    private function estCore(string $module): bool
    {
        return $module === 'Core' || str_starts_with($module, 'Core_');
    }
}
