<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Tobscure\JsonApi\Document;
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
	 * The JsonApi Document object.
	 *
	 * @var    Document
	 * @since  __DEPLOY_VERSION__
	 */
	protected $document;

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

		if (array_key_exists('api_document', $options) && $options['api_document'] instanceof Document)
		{
			$this->document = $options['api_document'];
		}
		else
		{
			$this->document = new Document;
		}
	}

	/**
	 * Set the data object.
	 *
	 * @param   ElementInterface  $element  Element interface.
	 *
	 * @return  $this
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setData(ElementInterface $element)
	{
		$this->document->setData($element);

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
		$this->document->setErrors($errors);

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
		$this->setJsonapi($jsonapi);

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
		return $this->document->toArray();
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
	 * @return  string  The rendered data.
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

		return (string) $this;
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
