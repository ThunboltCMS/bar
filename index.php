<?php

require __DIR__ . '/vendor/autoload.php';

\Tracy\Debugger::enable();

$compiler = new \Nette\DI\Compiler();

$compiler->addExtension('bar', new \Thunbolt\Bar\DI\BarExtension());
$compiler->addExtension('http', new \Nette\Bridges\HttpDI\HttpExtension());

eval($compiler->compile());

