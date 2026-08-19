<?php

declare(strict_types=1);

// Ruleset standard de twig-cs-fixer. Les gabarits du projet sont indentés à
// 4 espaces — c'est déjà le défaut de la règle Indent, rien à surcharger ici.
$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->getFinder()->in(__DIR__.'/templates');

return $config;
