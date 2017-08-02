<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\ElementInterface;

/**
 * JDocumentApiJsonapi class, provides an easy interface to parse output in JSON-API format.
 *
 * @link   http://www.jsonapi.org/
 * @since  __DEPLOY VERSION__
 */
class JDocumentApiJsonapi extends JDocumentJson implements JsonSerializable
{
	/**
	 * The included array.
	 *
	 * @var array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $included = [];

	/**
	 * The errors array.
	 *
	 * @var array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $errors;

	/**
	 * The JSON-API array.
	 *
	 * @var array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $jsonapi;

	/**
	 * The data object.
	 *
	 * @var ElementInterface
	 * @since  __DEPLOY_VERSION__
	 */
	protected $data;

	/**
	 * Class constructor.
	 *
	 * @param   array  $options  Associative array of options
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		// Set mime type to JSON-API
		$this->_mime = 'application/vnd.api+json';
	}

	/**
	 * Get included resources.
	 *
	 * @param   ElementInterface  $element        Element interface.
	 * @param   bool              $includeParent  Option to include the parent resource.
	 *
	 * @return Resource[]
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getIncluded(ElementInterface $element, $includeParent = false)
	{
		$included = [];
		foreach ($element->getResources() as $resource)
		{
			if ($resource->isIdentifier())
			{
				continue;
			}
			if ($includeParent)
			{
				$included = $this->mergeResource($included, $resource);
			}
			else
			{
				$type = $resource->getType();
				$id   = $resource->getId();
			}
			foreach ($resource->getUnfilteredRelationships() as $relationship)
			{
				$includedElement = $relationship->getData();
				if (!$includedElement instanceof ElementInterface)
				{
					continue;
				}
				foreach ($this->getIncluded($includedElement, true) as $child)
				{
					/** If this resource is the same as the top-level "data"
					* resource, then we don't want it to show up again in the
					* "included" array.
					*/
					if (!$includeParent && $child->getType() === $type && $child->getId() === $id)
					{
						continue;
					}
					$included = $this->mergeResource($included, $child);
				}
			}
		}
		$flattened = [];
		array_walk_recursive(
			$included, function ($a) use (&$flattened)
			{
				$flattened[] = $a;
			}
		);

		return $flattened;
	}

	/**
	 * Merges the new resource into existing resource(s).
	 *
	 * @param   Resource[]  $resources    Resource array.
	 * @param   Resource    $newResource  New resource object.
	 *
	 * @return  Resource[]
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function mergeResource(array $resources, Resource $newResource)
	{
		$type = $newResource->getType();
		$id   = $newResource->getId();
		if (isset($resources[$type][$id]))
		{
			$resources[$type][$id]->merge($newResource);
		}
		else
		{
			$resources[$type][$id] = $newResource;
		}

		return $resources;
	}

	/**
	 * Set the data object.
	 *
	 * @param   ElementInterface  $element  Element interface.
	 *
	 * @return $this
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setData(ElementInterface $element)
	{
		$this->data = $element;

		return $this;
	}

	/**
	 * Set the errors array.
	 *
	 * @param   array  $errors  Error array.
	 *
	 * @return   $this
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setErrors($errors)
	{
		$this->errors = $errors;

		return $this;
	}

	/**
	 * Set the JSON-API array.
	 *
	 * @param   array  $jsonapi  JSON-API array.
	 *
	 * @return   $this
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setJsonapi($jsonapi)
	{
		$this->jsonapi = $jsonapi;

		return $this;
	}

	/**
	 * Map everything to arrays.
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function toArray()
	{
		$document = [];
		if (!empty($this->links))
		{
			$document['links'] = $this->links;
		}
		if (!empty($this->data))
		{
			$document['data'] = $this->data->toArray();
			$resources        = $this->getIncluded($this->data);
			if (count($resources))
			{
				$document['included'] = array_map(
					function (Resource $resource) {
						return $resource->toArray();
					}, $resources
				);
			}
		}
		if (!empty($this->meta))
		{
			$document['meta'] = $this->meta;
		}
		if (!empty($this->errors))
		{
			$document['errors'] = $this->errors;
		}
		if (!empty($this->jsonapi))
		{
			$document['jsonapi'] = $this->jsonapi;
		}

		return $document;
	}

	/**
	 * Map to string.
	 *
	 * @return string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __toString()
	{
		return json_encode($this->toArray());
	}


	/**
	 * Outputs the document.
	 *
	 * @param   boolean  $cache   If true, cache the output.
	 * @param   array    $params  Associative array of attributes.
	 *
	 * @return  The rendered data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function render($cache = false, $params = array())
	{
		$app = JFactory::getApplication();
		if ($mdate = $this->getModifiedDate())
		{
			$app->modifiedDate = $mdate;
		}
		$app->mimeType = $this->_mime;
		$app->charSet  = $this->_charset;
	}

	/**
	 * Serialize for JSON usage.
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}
