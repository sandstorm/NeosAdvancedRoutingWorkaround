<?php

namespace Sandstorm\NeosAdvancedRoutingWorkaround;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class FrontendNodeRoutePartHandler extends \TYPO3\Neos\Routing\FrontendNodeRoutePartHandler {

	/**
	 * @FLOW\Inject
	 * @var \TYPO3\Eel\EelEvaluatorInterface
	 */
	protected $eelEvaluator;

	/**
	 * @Flow\Inject
	 * @var \Sandstorm\NeosAdvancedRoutingWorkaround\Domain\Repository\UriMappingRepository
	 */
	protected $uriMappingRepository;

	/**

	 */
	protected function matchValue($requestPath) {
		if (strpos($requestPath, 'neos') === 0) {
			return FALSE;
		}
		$uriMapping = $this->uriMappingRepository->findOneByUri($requestPath);
		if ($uriMapping !== NULL) {
			$this->value = $uriMapping->getContextNodePath();
			return TRUE;
		}
		return parent::matchValue($requestPath);
	}

		/**
	 * Checks, whether given value is a Node object and if so, sets $this->value to the respective node context path.
	 *
	 * In order to render a suitable frontend URI, this function strips off the path to the site node and only keeps
	 * the actual node path relative to that site node. In practice this function would set $this->value as follows:
	 *
	 * absolute node path: /sites/neostypo3org/homepage/about
	 * $this->value:       homepage/about
	 *
	 * absolute node path: /sites/neostypo3org/homepage/about@user-admin
	 * $this->value:       homepage/about@user-admin

	 *
	 * @param mixed $value Either a Node object or an absolute context node path
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 */
	protected function resolveValue($value) {
		if (!$value instanceof NodeInterface && !is_string($value)) {
			return FALSE;
		}

		if (is_string($value)) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $value, $matches);
			if (!isset($matches['NodePath'])) {
				return FALSE;
			}

			$contextProperties = array(
				'workspaceName' => (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live'),
			);

			$currentDomain = $this->domainRepository->findOneByActiveRequest();
			if ($currentDomain !== NULL) {
				$contextProperties['currentSite'] = $currentDomain->getSite();
				$contextProperties['currentDomain'] = $currentDomain;
			} else {
				$contextProperties['currentSite'] = $this->siteRepository->findFirst();
			}
			$contentContext = $this->contextFactory->create($contextProperties);

			if ($contentContext->getWorkspace(FALSE) === NULL) {
				return FALSE;
			}

			$node = $contentContext->getNode($matches['NodePath']);
		} elseif ($value instanceof \TYPO3\TYPO3CR\Domain\Model\Node) {
			$node = $value;
			$contentContext = $node->getContext();
		} else {
			throw new \InvalidArgumentException('The provided value was neither a string nor a node.', 1371673910);
		}

		if ($node instanceof NodeInterface) {
			$nodeContextPath = $node->getContextPath();
			$siteNodePath = $contentContext->getCurrentSiteNode()->getPath();
		} else {
			return FALSE;
		}

		if (substr($nodeContextPath, 0, strlen($siteNodePath)) !== $siteNodePath) {
			return FALSE;
		}

		// TODO: UNTIL HERE; THIS IS THE ORIGINAL CODE. Modifications follow below.

		if ($node->getNodeType()->hasUriPattern()) {
			$uriPattern = $node->getNodeType()->getUriPattern();
			$context = array('node' => $node);
			$context['str_replace'] = function($k, $v, $s) {
				return str_replace($k, $v, $s);
			};
			$this->value = $this->eelEvaluator->evaluate($uriPattern, new \TYPO3\Eel\Context($context));
			$workspaceName = $node->getContext()->getWorkspace()->getName();
			if ($workspaceName !== 'live') {
				$this->value .= '@' . $workspaceName;
			}

			$uriMapping = $this->uriMappingRepository->findOneByContextNodePath($node->getContextPath());
			if ($uriMapping === NULL) {
				$uriMapping = new Domain\Model\UriMapping();
				$uriMapping->setUri($this->value);
				$uriMapping->setContextNodePath($node->getContextPath());
				$this->uriMappingRepository->add($uriMapping);
			} elseif ($uriMapping->getUri() !== $this->value) {
				$uriMapping->setUri($this->value);
				$this->uriMappingRepository->update($uriMapping);
			}
		} else {
			$this->value = substr($nodeContextPath, strlen($siteNodePath) + 1);
		}
		return TRUE;
	}
}
?>