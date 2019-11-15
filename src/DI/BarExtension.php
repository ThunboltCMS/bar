<?php

declare(strict_types=1);

namespace Thunbolt\Bar\DI;

use Nette;
use Nette\DI\CompilerExtension;
use Doctrine\ORM\EntityManager;
use Thunbolt\Bar\BarException;
use Thunbolt\Bar\Bars\DoctrineBar;
use Thunbolt\Bar\Bars\LogBar;
use Thunbolt\Bar\Bars\TempBar;
use Tracy\Bar;
use Tracy\Debugger;

class BarExtension extends CompilerExtension {

	/** @var array */
	public $defaults = [
		'temp' => [
			'enable' => TRUE,
			'tempDir' => NULL,
		],
		'doctrine' => [
			'enable' => NULL,
		],
		'log' => [
			'enable' => NULL,
			'logDir' => NULL,
		]
	];

	/** @var string */
	private $tracyBarService;

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 */
	public function loadConfiguration(): void {
		$this->processDefaults();
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		$this->tracyBarService = $this->getContainerBuilder()->getByType(Bar::class);
		if (!$this->tracyBarService) {
			throw new BarException('Tracy bar not found in container.');
		}

		if ($config['temp']['enable']) {
			if (!$config['temp']['tempDir']) {
				throw new BarException('Temp dir has not been set.');
			}
			$builder->addDefinition($this->prefix('temp'))
				->setFactory(TempBar::class, [$config['temp']['tempDir']]);
		}
		if ($config['doctrine']['enable']) {
			$builder->addDefinition($this->prefix('doctrine'))
				->setType(DoctrineBar::class);
		}
		if ($config['log']['enable']) {
			$builder->addDefinition($this->prefix('log'))
				->setFactory(LogBar::class, [$config['log']['logDir']]);
		}
	}

	public function processDefaults(): void {
		$this->defaults['doctrine']['enable'] = class_exists(EntityManager::class);
		$this->defaults['log']['enable'] = Debugger::$logDirectory && is_dir(Debugger::$logDirectory);
		$this->defaults['log']['logDir'] = Debugger::$logDirectory;
		if (isset($this->getContainerBuilder()->parameters['tempDir'])) {
			$this->defaults['temp']['tempDir'] = $this->getContainerBuilder()->parameters['tempDir'];
		}
	}

	/**
	 * Adjusts DI container compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @param Nette\PhpGenerator\ClassType $class
	 */
	public function afterCompile(Nette\PhpGenerator\ClassType $class): void {
		$builder = $this->getContainerBuilder();
		$init = $class->getMethods()['initialize'];

		if ($builder->hasDefinition($this->prefix('temp'))) {
			$init->addBody('if ($this->parameters["debugMode"]) $this->getService(?)->addPanel($this->getService(?));', [
				$this->tracyBarService, $this->prefix('temp')
			]);
		}
		if ($builder->hasDefinition($this->prefix('doctrine'))) {
			$init->addBody('if ($this->parameters["debugMode"]) $this->getService(?)->addPanel($this->getService(?));', [
				$this->tracyBarService, $this->prefix('doctrine')
			]);
		}
		if ($builder->hasDefinition($this->prefix('log'))) {
			$init->addBody('if ($this->parameters["debugMode"]) $this->getService(?)->addPanel($this->getService(?));', [
				$this->tracyBarService, $this->prefix('log')
			]);
		}
	}

}
