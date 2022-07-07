<?php

$finder = PhpCsFixer\Finder::create()
            ->in(__DIR__.'/src')
            ->exclude(__DIR__.'/src/Kernel.php')
;
$config = new PhpCsFixer\Config();

return $config->setRules([
    '@Symfony' => true,
    'yoda_style' => false,
])
     ->setFinder($finder)
;
