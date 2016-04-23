<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Search.monitor
 *
 * @copyright   Copyright (C) 2016 Constantin Romankiewicz.
 * @license     Apache License 2.0; see LICENSE
 */
defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php';

/**
 * Smart Search adapter for issues for the com_monitor extension.
 *
 * @author  Constantin Romankiewicz <constantin@zweiiconkram.de>
 * @since   1.0
 */
class PlgFinderIssues extends FinderIndexerAdapter
{
	/**
	 * The plugin identifier.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $context = 'Issues';

	/**
	 * The extension name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $extension = 'com_monitor';

	/**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  2.5
	 */
	//protected $layout = 'issues';  // TODO

	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $type_title = 'Issue';

	/**
	 * The table name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $table = '#__monitor_issues';

	/**
	 * The field the published state is stored in.
	 *
	 * @var    string
	 * @since  2.5
	 */
	//protected $state_field = 'published';  //TODO

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to index an item.
	 *
	 * @param   FinderIndexerResult  $item  The item to index as a FinderIndexerResult object.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	protected function index(FinderIndexerResult $item)
	{
		// Check if the extension is enabled.
		if (JComponentHelper::isEnabled($this->extension) == false)
		{
			return;
		}

		$router = new MonitorRouter;

		$item->summary     = FinderIndexerHelper::prepareContent($item->getElement('text'), $item->params);
		$item->start_date  = $item->getElement('created');
		$item->state       = 1;
		$item->published   = 1;

		$url = array(
			'option' => $this->extension,
			'view'   => 'issue',
			'id'     => $item->getElement('id'),
		);

		$item->url = 'index.php?' . JUri::buildQuery($url);
		$item->route = 'index.php?' . JUri::buildQuery($router->preprocess($url));

		$item->addTaxonomy('Type', 'Issue');
		$item->addTaxonomy('Project', $item->getElement('project_name'));
		$item->addTaxonomy('Author', $item->getElement('author'));
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'status_name');

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to get the SQL query used to retrieve the list of content items.
	 *
	 * @param   mixed  $query  A JDatabaseQuery object. [optional]
	 *
	 * @return  JDatabaseQuery  A database object.
	 *
	 * @since   2.5
	 */
	protected function getListQuery($query = null)
	{
		$query = parent::getListQuery($query);

		$query->select('a.id, a.title, a.text, a.project_id, a.version, a.created, a.author_id, a.status, a.classification')
			->from($this->table . ' AS a')
			->select('p.name AS project_name')
			->leftJoin('#__monitor_projects AS p ON p.id = a.project_id')
			->select('cl.access AS access')
			->leftJoin('#__monitor_issue_classifications AS cl ON cl.id = a.classification')
			->select('u.name AS author')
			->leftJoin('#__users AS u ON u.id = a.author_id')
			->select('s.name AS status_name')
			->leftJoin('#__monitor_status AS s ON s.id = a.status');

		return $query;
	}

	/**
	 * Method to setup the adapter before indexing.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	protected function setup()
	{
		include_once JPATH_SITE . '/components/com_monitor/router.php';

		return true;
	}
}
