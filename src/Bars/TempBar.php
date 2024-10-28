<?php

declare(strict_types=1);

namespace Thunbolt\Bar\Bars;

use Nette\Application\Application;
use Nette\Caching\IStorage;
use Nette\Caching\Storages\NewMemcachedStorage;
use Nette\Http\IRequest;
use Nette\Utils\Finder;
use Thunbolt\Bar\BarException;
use Tracy\IBarPanel;

class TempBar extends Bar implements IBarPanel {

	/** @var string */
	private $tempDir;

	/** @var IStorage */
	private $storage;

	public function __construct(string $tempDir, IRequest $request, IStorage $storage, ?Application $application = NULL) {
		parent::__construct($request, $application);
		$this->tempDir = $tempDir;
		$this->storage = $storage;
		if (!class_exists(Finder::class)) {
			throw new BarException('Temp panel needs ' . Finder::class);
		}

		$this->callFunc('cache', function (string $val) {
			$this->removeRecursiveIn($dir = $this->tempDir . '/cache/' . $val);
			rmdir($dir);

			$this->redirectBack();
		});
		$this->callFunc('cacheAll', function (string $val) {
			if ($val === 'yes') {
				$this->removeRecursiveIn($this->tempDir . '/cache');

				$this->redirectBack();
			}
		});
		$this->callFunc('cacheFile', function (string $val) {
			if (is_string($val) && strpos($val, '/') === FALSE) {
				@unlink($this->tempDir . '/cache/' . $val); // @ - may not exists
				$this->redirectBack();
			}
		});
		$this->callFunc('proxiesDir', function () {
			$this->removeRecursiveIn($this->tempDir . '/proxies');

			$this->redirectBack();
		});
		$this->callFunc('flushMemcached', function () {
			$this->storage->getConnection()->flush();

			$this->redirectBack();
		});
	}

	private function removeRecursiveIn(string $dir): void {
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			$todo($fileinfo->getRealPath());
		}
	}

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab(): string {
		ob_start();
		require __DIR__ . '/templates/temp.tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel(): string {
		ob_start();
		$cache = Finder::findDirectories('*')->in($this->tempDir . '/cache');
		$files = Finder::findFiles('*')->in($this->tempDir . '/cache');
		$hasProxies = is_dir($this->tempDir . '/proxies');
		$hasMemcached = $this->storage instanceof NewMemcachedStorage;

		require __DIR__ . '/templates/temp.panel.phtml';
		return ob_get_clean();
	}

}
