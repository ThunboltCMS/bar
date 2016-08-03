<?php

namespace Thunbolt\Bar;

use Nette\Http\Request;
use Nette\Utils\Finder;
use Tracy\Debugger;
use Tracy\IBarPanel;

class Temp extends AbstractPanel implements IBarPanel {

	const NOT_CONTAIN = '[?/\%*:|"><\s]+';

	/** @var \Iterator */
	private $finder;

	/** @var string */
	private $tempDir;

	public function __construct($tempDir, Request $request) {
		parent::__construct($request);
		$this->tempDir = $tempDir;
		if (isset($_GET['wch-log-show'])) {
			$this->showLog($_GET['wch-log-show']);
		}
		if (isset($_GET['wch-log']) && !preg_match('#' . self::NOT_CONTAIN . '#', $_GET['wch-log'])) {
			@unlink(Debugger::$logDirectory . '/' . $_GET['wch-log']);
			$this->redirect('wch-log');
		}
		if (isset($_GET['wch-cache']) && !preg_match('#' . self::NOT_CONTAIN . '#', $_GET['wch-cache'])) {
			foreach (Finder::findFiles('*')->in($tempDir . '/cache/' . $_GET['wch-cache']) as $file) {
				unlink((string) $file);
			}
			$this->redirect('wch-cache');
		}
		if (isset($_GET['wch-cache-all']) && $_GET['wch-cache-all'] === 'yes') {
			foreach (Finder::findFiles('*')->in($tempDir . '/cache')->limitDepth(1) as $file) {
				unlink((string) $file);
			}
			$this->redirect('wch-cache-all');
		}
		if (isset($_GET['wch-log-all']) && $_GET['wch-log-all'] === 'yes') {
			foreach (Finder::findFiles('*.html')->in(Debugger::$logDirectory) as $file) {
				unlink((string) $file);
			}
			$this->redirect('wch-log-all');
		}
		if (isset($_GET['wch-cache-file']) && is_string($_GET['wch-cache-file']) && strpos($_GET['wch-cache-file'], '/') === FALSE) {
			@unlink($tempDir . '/cache/' . $_GET['wch-cache-file']); // @ - may not exists
		}
	}

	private function showLog($name) {
		if (!file_exists(Debugger::$logDirectory . '/' . $name)) {
			return;
		}

		readfile(Debugger::$logDirectory . '/' . $name);
		die();
	}

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab() {
		$this->finder = Finder::findFiles('*.html')->in(Debugger::$logDirectory)->getIterator();
		ob_start();
		$count = iterator_count($this->finder);
		require __DIR__ . '/templates/temp.tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel() {
		ob_start();
		$logs = $this->finder;
		$cache = Finder::findDirectories('*')->in($this->tempDir . '/cache');
		$files = Finder::findFiles('*')->in($this->tempDir . '/cache');
		require __DIR__ . '/templates/temp.panel.phtml';
		return ob_get_clean();
	}

}
