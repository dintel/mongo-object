<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
        ->exclude('example')
        ->in(__DIR__);

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(['-psr0', 'short_array_syntax'])
    ->finder($finder);
