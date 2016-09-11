<?php

namespace Thunbolt\Bar\Bars;

use Nette\Application\Application;
use Nette\Http\Request;
use Tracy\IBarPanel;

abstract class Bar implements IBarPanel {

	const PREFIX = 'wch-';

	/** @var \Nette\Http\UrlScript */
	private $url;

	/** @var string */
	private $lastParam;

	/** @var Application */
	private $application;

	public function __construct(Request $request, Application $application = NULL) {
		$this->url = $request->getUrl();
		$this->application = $application;
	}

	protected function terminate() {
		if ($this->application) {
			$this->application->onStartup = [function () {
				exit(1);
			}];
		} else {
			exit(1);
		}
	}

	/**
	 * @param string $param
	 * @param string|callable $func
	 */
	protected function callFunc($param, $func) {
		$this->lastParam = self::PREFIX . $param;
		if (($val = $this->url->getQueryParameter($this->lastParam)) !== NULL) {
			$func = is_callable($func) ? $func : [$this, $func];
			call_user_func($func, $val);
		}
	}

	protected function redirectBack() {
		header('Location: ' . $this->url->setQueryParameter($this->lastParam, NULL));
		exit(1);
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	public function link(array $parameters) {
		$url = clone $this->url;
		foreach ($parameters as $name => $value) {
			$url->setQueryParameter($name, $value);
		}

		return (string) $url;
	}

	/**
	 * @param string $param
	 * @param mixed $val
	 * @return string
	 */
	public function fastLink($param, $val) {
		$url = clone $this->url;
		$url->setQueryParameter(self::PREFIX . $param, $val);

		return (string) $url;
	}

}
