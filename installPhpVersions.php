<?php

use Drarok\PHPTools\Config;

require_once __DIR__ . '/lib/Config.php';

$config = Config::getInstance();
$versions = $config->getVersions();
$packages = $config->getPackages();
sort($versions);
sort($packages);

// Build an array of all packages for all versions.
$expectedFormulae = array();
foreach ($versions as $version) {
    $expectedFormulae[] = $version;
    foreach ($packages as $package) {
        // mcrypt was finally removed in PHP 7.2
        if ($version >= 'php72' && $package === 'mcrypt') {
            continue;
        }

        $expectedFormulae[] = $version . '-' . $package;
    }
}

// Get all currently installed relevant formula.
$installedFormulae = array_filter(explode(PHP_EOL, shell_exec('brew list | grep \'php[57]\'')));

// Work out which (if any) are missing.
$missingFormulae = array_diff($expectedFormulae, $installedFormulae);

// Ensure we don't unlink/link unnecessarily by sorting (so we go all php55 in one go, then php56).
sort($missingFormulae);

if (! $missingFormulae) {
    echo 'Nothing to do!', PHP_EOL;
    return;
}

if (array_search('--dry-run', $argv) !== false) {
    echo 'Would install:', PHP_EOL;

    foreach ($missingFormulae as $formula) {
        echo $formula, PHP_EOL;
    }

    return;
}

// Unlink all.
if (count($installedFormulae) > 0) {
    $unlinkFormula = $installedFormulae;
    array_unshift($unlinkFormula, 'unlink');
    execCmd('brew', $unlinkFormula);
}

$activeVersion = '';
foreach ($missingFormulae as $formula) {
    if (strpos($formula, '-') !== false) {
        $formulaVersion = substr($formula, 0, 5);
        $link = true;
    } else {
        $formulaVersion = $formula;
        $link = false;
    }

    // Check we have the right version installed/linked.
    if ($formulaVersion != $activeVersion) {
        if ($activeVersion) {
            execCmd('brew', 'unlink', $activeVersion);
        }

        if ($link) {
            echo 'Switching to ', $formulaVersion, PHP_EOL;
            execCmd('brew', 'link', $formulaVersion);
        }

        $activeVersion = $formulaVersion;
    }

    echo 'Installing ', $formula, PHP_EOL;
    execCmd('brew', 'install', $formula);
}

// Switch back to latest version once we're finished.
$latestVersion = end($versions);
if ($activeVersion !== $latestVersion) {
    echo 'Switching back to latest version ', $latestVersion, '.', PHP_EOL;
    execCmd('brew', 'unlink', $activeVersion);
    execCmd('brew', 'link', $latestVersion);
}

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
