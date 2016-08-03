<?php

namespace Thunbolt\Bar\DI;

use Nette;
use Nette\DI\CompilerExtension;

class BarExtension extends CompilerExtension {

	/** @var array */
	private $defaults = [
		'enable' => [
			'temp' => TRUE,
			'doctrine' => TRUE
		]
	];

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 */
	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		if ($config['enable']['temp']) {
			$builder->addDefinition($this->prefix('temp'))
				->setClass('Thunbolt\Bar\Temp', [$builder->parameters['tempDir']]);
		}
		if ($config['enable']['doctrine']) {
			$builder->addDefinition($this->prefix('doctrine'))
				->setClass('Thunbolt\Bar\Doctrine');
		}
	}

	/**
	 * Adjusts DI container compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @param Nette\PhpGenerator\ClassType $class
	 */
	public function afterCompile(Nette\PhpGenerator\ClassType $class) {
		$config = $this->validateConfig($this->defaults, $this->getConfig());
		$init = $class->methods['initialize'];

		if ($config['enable']['temp']) {
			$init->addBody('if ($this->parameters["debugMode"]) $this->getService(?)->addPanel($this->getService(?));', [
					'tracy.bar', $this->prefix('temp')
				]);
		}
		if ($config['enable']['doctrine']) {
			$init->addBody('if ($this->parameters["debugMode"]) $this->getService(?)->addPanel($this->getService(?));', [
					'tracy.bar', $this->prefix('doctrine')
				]);
		}
	}

}
