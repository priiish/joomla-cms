<?php
/**
 * Item Model for a Prove Component.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_prove
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       4.0
 */

namespace Joomla\Component\Workflow\Administrator\Model;

defined('_JEXEC') or die;

use JError;
use Joomla\CMS\Factory;
use Joomla\CMS\Model\Admin;

/**
 * The first example class, this is in the same
 * package as declared at the start of file but
 * this example has a defined subpackage
 *
 * @since  4.0
 */
class Workflow extends Admin
{

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean True on success.
	 *
	 * @since 4.0
	 */
	public function save($data)
	{
		$user					= \JFactory::getUser();
		$app					 = \JFactory::getApplication();
		$context				= $this->option . '.' . $this->name;
		$extension				= $app->getUserStateFromRequest($context . '.filter.extension', 'extension', 'com_content', 'cmd');
		$data['extension']		= $extension;
		$data['asset_id']		= 0;
		$data['modified_by']	= $user->get('id');

		if (!empty($data['id']))
		{
			$data['modified'] = date("Y-m-d H:i:s");
		}
		else
		{
			$data['created_by'] = $user->get('id');
		}

		if ($data['default'] == '1')
		{
			if ($data['published'] !== '1')
			{
				$this->setError(\JText::_("COM_WORKFLOW_ITEM_MUST_PUBLISHED"));

				return false;
			}

			$table = $this->getTable();

			if ($table->load(array('default' => '1')))
			{
				$table->default = 0;
				$table->store();
			}
		}
		else
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true);

			$query->select("id")
				->from($db->qn("#__workflows"))
				->where($db->qn("default") . '= 1');
			$db->setQuery($query);
			$workflows = $db->loadObject();

			if (empty($workflows) || $workflows->id === $data['id'])
			{
				$data['default'] = '1';
				$this->setError(\JText::_("COM_WORKFLOW_DISABLE_DEFAULT"));

				return false;
			}
		}

		$result = parent::save($data);

		// Create a default state
		if ($result && $this->getState($this->getName() . '.new'))
		{
			$state = $this->getTable('State');

			$newstate = new \stdClass;

			$newstate->workflow_id = (int) $this->getState($this->getName() . '.id');
			$newstate->title = \JText::_('COM_WORKFLOW_PUBLISHED');
			$newstate->description = '';
			$newstate->published = 1;
			$newstate->condition = 1;
			$newstate->default = 1;

			$state->save($newstate);
		}

		return $result;
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return \JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since 4.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_workflow.workflow',
			'workflow',
			array(
				'control'   => 'jform',
				'load_data' => $loadData
			)
		);

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed  The data for the form.
	 *
	 * @since 4.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = \JFactory::getApplication()->getUserState(
			'com_workflow.edit.workflow.data',
			array()
		);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to change the home state of one item.
	 *
	 * @param   array    $pk     A list of the primary keys to change.
	 * @param   integer  $value  The value of the home state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   4.0
	 */
	public function setHome($pk, $value = 1)
	{
		$table = $this->getTable();

		if ($table->load(array('id' => $pk)))
		{
			if ($table->published !== 1)
			{
				$this->setError(\JText::_("COM_WORKFLOW_ITEM_MUST_PUBLISHED"));

				return false;
			}
		}

		if ($value)
		{
			// Unset other default item
			if ($table->load(array('default' => '1')))
			{
				$table->default = 0;
				$table->modified = date("Y-m-d H:i:s");
				$table->store();
			}
		}

		if ($table->load(array('id' => $pk)))
		{
			$table->modified = date("Y-m-d H:i:s");
			$table->default  = $value;
			$table->store();
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   4.0
	 */
	protected function canDelete($record)
	{
		// @TODO check here if the record can be deleted (no item is assigned to a status etc...)
		return parent::canDelete($record);
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
		$table = $this->getTable();
		$pks   = (array) $pks;

		// Default menu item existence checks.
		foreach ($pks as $i => $pk)
		{
			if ($value != 1 && $table->default)
			{
				$this->setError(\JText::_('COM_WORKFLOW_ITEM_MUST_PUBLISHED'));
				unset($pks[$i]);
				break;
			}

			$table->load($pk);
			$table->modified = date("Y-m-d H:i:s");
			$table->store();
		}

		return parent::publish($pks, $value);
	}
}
