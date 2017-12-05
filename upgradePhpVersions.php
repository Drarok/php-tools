<?php

use Drarok\PHPTools\Config;

require_once __DIR__ . '/lib/Config.php';

$config = Config::getInstance();
$versions = $config->getVersions();
sort($versions);

// Get outdated formulae from brew.
$outdatedFormulae = array_filter(explode(PHP_EOL, shell_exec('brew outdated | grep php[57] | awk \'{ print $1 }\'')));

if (! $outdatedFormulae) {
    echo 'Nothing to do!', PHP_EOL;
    return;
}

$outdatedFormulae = array_map(
    function ($formula) {
        $parts = explode('/', $formula);
        return end($parts);
    },
    $outdatedFormulae
);

// Ensure we don't unlink/link unnecessarily by sorting (so we do all php55 in one go, then php56).
sort($outdatedFormulae);

echo 'Will upgrade:', PHP_EOL;
foreach ($outdatedFormulae as $formula) {
    echo '    ', $formula, PHP_EOL;
}

if (array_search('--dry-run', $argv, true)) {
    return;
}

// Unlink all.
$unlinkFormula = array_keys($versions);
array_unshift($unlinkFormula, 'unlink');
execCmd('brew', $unlinkFormula);

$activeVersion = '';
foreach ($outdatedFormulae as $formula) {
    // If this is a phpxy-something formula, check we have the right version linked.
    if (strpos($formula, '-') !== false) {
        $formulaVersion = substr($formula, 0, 5);
    } else {
        $formulaVersion = $formula;
    }

    if ($formulaVersion !== $activeVersion) {
        echo 'Switching to ', $formulaVersion, PHP_EOL;
        if ($activeVersion) {
            execCmd('brew', 'unlink', $activeVersion);
        }
        execCmd('brew', 'link', $formulaVersion);
        $activeVersion = $formulaVersion;
    }

    echo 'Upgrading ', $formula, PHP_EOL;
    execCmd('brew', 'upgrade', $formula);
}

$latestVersion = end($versions);
if ($activeVersion !== $latestVersion) {
    echo 'Switching back to stable version ', $latestVersion, '.', PHP_EOL;
    execCmd('brew', 'unlink', $activeVersion);
    execCmd('brew', 'link', $latestVersion);
}

echo 'Cleaning up...', PHP_EOL;
execCmd('brew', 'cleanup');

/**
 * Execute a command safely.
 *
 * @param string $cmd Name of the command.
 * @param string ... Extra arguments, or an array of arguments.
 *
 * @return void
 */
function execCmd($cmd)
{
    $cmd = escapeshellcmd($cmd);
    if (func_num_args() > 1) {
        $args = array_slice(func_get_args(), 1);
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }
        $cmd .= ' ' . implode(' ', array_map('escapeshellarg', $args));
    }

    echo $cmd, PHP_EOL;
    passthru($cmd, $exitCode);

    if ($exitCode) {
        throw new Exception('Unexpected exit code: ' . var_export($exitCode, true));
    }
}
