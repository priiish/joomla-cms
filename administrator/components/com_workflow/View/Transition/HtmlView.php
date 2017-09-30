<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Workflow\Administrator\View\Transition;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Workflow\Administrator\Helper\WorkflowHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class to add or edit Workflow
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * From object to generate fields
	 *
	 * @var     \JForm
	 * @since  __DEPLOY_VERSION__
	 */
	protected $form;

	/**
	 * Items array
	 *
	 * @var     object
	 * @since  __DEPLOY_VERSION__
	 */
	protected $item;

	/**
	 * That is object of Application
	 *
	 * @var     CMSApplication
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * The application input object.
	 *
	 * @var    Input
	 * @since  __DEPLOY_VERSION__
	 */
	protected $input;

	/**
	 * The ID of current workflow
	 *
	 * @var     integer
	 * @since  __DEPLOY_VERSION__
	 */
	protected $workflowID;

	/**
	 * Display item view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \JViewGenericdataexception(implode("\n", $errors), 500);
		}

		$this->app = \JFactory::getApplication();
		$this->input = $this->app->input;

		// Get the Data
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');

		// Get the ID of workflow
		$this->workflowID = $this->input->getCmd("workflow_id");

		// Set the form selects sql
		$sqlStatesFrom = WorkflowHelper::getStatesSQL('from_state_id', $this->workflowID);
		$sqlStatesTo = WorkflowHelper::getStatesSQL('to_state_id', $this->workflowID);
		$this->form->setFieldAttribute('from_state_id', 'query', $sqlStatesFrom);
		$this->form->setFieldAttribute('to_state_id', 'query', $sqlStatesTo);

		// Set the toolbar
		$this->addToolBar();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(empty($this->item->id) ? \JText::_('COM_WORKFLOW_TRANSITION_ADD') : \JText::_('COM_WORKFLOW_TRANSITION_EDIT'), 'address');
		\JFactory::getApplication()->input->set('hidemainmenu', true);
		ToolbarHelper::saveGroup(
			[
				['apply', 'transition.apply'],
				['save', 'transition.save'],
				['save2new', 'transition.save2new']
			],
			'btn-success'
		);
		ToolbarHelper::cancel('transition.cancel');
		ToolbarHelper::divider();
	}
}
