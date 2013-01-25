<?php

namespace Sandstorm\NeosAdvancedRoutingWorkaround\Domain\Repository;

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class UriMappingRepository extends \TYPO3\Flow\Persistence\Repository {

	protected $defaultOrderings = array(
		'counter' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
	);

	protected $addedObjects = array();

	public function add($uriMapping) {
		parent::add($uriMapping);
		$this->addedObjects[] = $uriMapping;
	}

	public function findOneByContextNodePath($contextNodePath) {
		foreach ($this->addedObjects as $object) {
			if ($object->getContextNodePath() === $contextNodePath) {
				return $object;
			}
		}

		return parent::findOneByContextNodePath($contextNodePath);
	}
}
?>