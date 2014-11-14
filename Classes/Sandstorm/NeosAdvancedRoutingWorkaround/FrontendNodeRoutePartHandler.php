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
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

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
	protected function resolveValue($node) {
		if (!$node instanceof NodeInterface && !is_string($node)) {
			return FALSE;
		}

		if (is_string($node)) {
			$nodeContextPath = $node;
			$contentContext = $this->buildContextFromContextPath($nodeContextPath);
			if ($contentContext->getWorkspace(FALSE) === NULL) {
				return FALSE;
			}
			$nodePath = $this->removeContextFromPath($nodeContextPath);
			$node = $contentContext->getNode($nodePath);

			if ($node === NULL) {
				return FALSE;
			}
		} else {
			$contentContext = $node->getContext();
		}

		if (!$node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			return FALSE;
		}

		$siteNode = $contentContext->getCurrentSiteNode();
		if ($this->onlyMatchSiteNodes() && $node !== $siteNode) {
			return FALSE;
		}

		// TODO: UNTIL HERE; THIS IS THE ORIGINAL CODE. Modifications follow below.
		if ($node->getContextPath() === $siteNode->getContextPath()) {
			$this->value = '';
		} elseif ($node->getNodeType()->hasUriPattern()) {
			$uriPattern = $node->getNodeType()->getUriPattern();
			$context = array('node' => $node);
			$context['convert_from_date_if_needed'] = function($date) {
				if (is_object($date) && $date instanceof \DateTime) {
					return $date->format('Y-m-d');
				}
				return $date;
			};
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
				// Hacky, but works
				$this->persistenceManager->persistAll();
			} elseif ($uriMapping->getUri() !== $this->value) {
				$uriMapping->setUri($this->value);
				$this->uriMappingRepository->update($uriMapping);
				// Hacky, but works
				$this->persistenceManager->persistAll();
			}

		} else {
			$routePath = $this->resolveRoutePathForNode($siteNode, $node);
			$this->value = $routePath;
		}
		return TRUE;
	}
}
?>