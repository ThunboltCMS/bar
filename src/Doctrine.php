<?php

namespace Thunbolt\Bar;

use Thunbolt\Console\Insert;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\Request;
use Tracy\IBarPanel;

class Doctrine extends AbstractPanel implements IBarPanel {

	/** @var EntityManager */
	private $em;

	/** @var Insert */
	private $insert;

	public function __construct(Request $request, EntityManager $em, Insert $insert = NULL) {
		parent::__construct($request);
		$this->em = $em;
		$this->insert = $insert;
		if (isset($_GET['wch-doctrine-delete'])) {
			$this->delete($_GET['wch-doctrine-delete']);
			$this->redirect('wch-doctrine-delete');
		}
		if (isset($_GET['wch-doctrine-update'])) {
			$this->update($_GET['wch-doctrine-update']);
			$this->redirect('wch-doctrine-update');
		}
		if (isset($_GET['wch-doctrine-truncate'])) {
			$this->truncate($_GET['wch-doctrine-truncate']);
			$this->redirect('wch-doctrine-truncate');
		}
		if (isset($_GET['wch-doctrine-create'])) {
			$this->create($_GET['wch-doctrine-create']);
			$this->redirect('wch-doctrine-create');
		}
		if (isset($_GET['wch-doctrine-createAll'])) {
			$this->createAll();
			$this->redirect('wch-doctrine-createAll');
		}
		if (isset($_GET['wch-doctrine-insertValues'])) {
			$this->insertValues();
			$this->redirect('wch-doctrine-insertValues');
		}
	}

	protected function insertValues() {
		if ($this->insert) {
			$this->insert->apply();
		}
	}

	protected function createAll() {
		$schemaTool = new SchemaTool($this->em);
		$classes = $this->em->getMetadataFactory()->getAllMetadata();
		$classes = $this->getTablesForCreate($classes);

		if ($classes) {
			$schemaTool->createSchema($classes);
		}
	}

	/**
	 * @param array $classes
	 * @return array
	 */
	private function getTablesForCreate($classes) {
		$currently = $this->em->getConnection()->getSchemaManager()->listTableNames();

		$toInstall = array();

		foreach ($classes as $row) {
			if (!in_array($row->table['name'], $currently) && $row->isMappedSuperclass !== TRUE) {
				$toInstall[] = $row;
			}
		}

		return $toInstall;
	}

	/**
	 * @param string $hash
	 */
	protected function create($hash) {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === TRUE) {
			return;
		}

		$entity = $this->em->getClassMetadata($tables[$hash]['name']);
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->createSchema([$entity]);
	}

	/**
	 * @param string $hash
	 */
	protected function truncate($hash) {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === FALSE) {
			return;
		}

		$cmd = $this->em->getClassMetadata($tables[$hash]['name']);
		$connection = $this->em->getConnection();
		$dbPlatform = $connection->getDatabasePlatform();
		$connection->beginTransaction();

		try {
			$connection->query('SET FOREIGN_KEY_CHECKS=0');
			$q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
			$connection->executeUpdate($q);
			$connection->query('SET FOREIGN_KEY_CHECKS=1');
			$connection->commit();
		} catch (\Exception $e) {
			$connection->rollback();
		}
	}

	/**
	 * @param string $hash
	 */
	protected function update($hash) {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === FALSE) {
			return;
		}

		$entity = $this->em->getClassMetadata($tables[$hash]['name']);
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->updateSchema([$entity], true);
	}

	/**
	 * @param string $hash
	 */
	protected function delete($hash) {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === FALSE) {
			return;
		}

		$entity = $this->em->getClassMetadata($tables[$hash]['name']);
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->dropSchema([$entity]);
	}

	private function getTables() {
		$schemaTool = new SchemaTool($this->em);
		$classes = $this->em->getMetadataFactory()->getAllMetadata();
		$tables = $this->parseTables($classes);

		return $tables;
	}

	private function parseTables($classes) {
		$currently = $this->em->getConnection()->getSchemaManager()->listTableNames();
		$return = [];
		foreach ($classes as $row) {
			if (!in_array($row->table['name'], $currently) && $row->isMappedSuperclass !== TRUE) {
				$return[md5($row->rootEntityName)] = [
					'name' => $row->rootEntityName,
					'table_name' => $row->table['name'],
					'isInstalled' => FALSE
				];
			} else if ($row->isMappedSuperclass !== TRUE) {
				$return[md5($row->rootEntityName)] = [
					'name' => $row->rootEntityName,
					'table_name' => $row->table['name'],
					'isInstalled' => TRUE
				];
			}
		}

		return $return;
	}

	/************************* IBarPanel **************************/

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab() {
		ob_start();
		require __DIR__ . '/templates/doctrine.tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel() {
		ob_start();
		$tables = $this->getTables();
		$insert = (bool) $this->insert;
		require __DIR__ . '/templates/doctrine.panel.phtml';
		return ob_get_clean();
	}

}
