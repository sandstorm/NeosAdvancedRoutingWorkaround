<?php

namespace Sandstorm\NeosAdvancedRoutingWorkaround\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * URL Mapper
 *
 * @Flow\Entity
 */
class UriMapping {

	/**
	 * @var string
	 */
	protected $contextNodePath;

	/**
	 * @var integer
	 * @ORM\Column(type="integer", columnDefinition="INT(11) NOT NULL AUTO_INCREMENT UNIQUE")
	 */
	protected $counter;

	/**
	 * @var string
	 */
	protected $uri;

	public function getContextNodePath() {
		return $this->contextNodePath;
	}

	public function setContextNodePath($contextNodePath) {
		$this->contextNodePath = $contextNodePath;
	}

	public function getUri() {
		return $this->uri;
	}

	public function setUri($uri) {
		$this->uri = $uri;
	}
}
?>