<?php

namespace Thunbolt\Bar;

use Nette\Http\Request;

abstract class AbstractPanel {

	/** @var \Nette\Http\UrlScript */
	private $url;

	public function __construct(Request $request) {
		$this->url = $request->getUrl();
	}

	public function redirect($resetQuery) {
		header('Location: ' . $this->url->setQueryParameter($resetQuery, NULL));
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

}
