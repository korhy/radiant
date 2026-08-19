<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('migrations')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    // declare_strict_types est classée « risky » : sans cette ligne la règle est
    // ignorée en silence, y compris en CI.
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        // Règle globale n°5 : le ruleset @Symfony ne l'inclut pas, donc sans cette
        // ligne la CI ne verra jamais un fichier qui l'oublie.
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;