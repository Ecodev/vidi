<?php
namespace TYPO3\CMS\Vidi\Grid;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Fabien Udriot <fabien.udriot@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for editing mm relation between objects
 */
class RelationEditRenderer extends GridRendererAbstract {

	/**
	 * Render a representation of the relation on the GUI.
	 *
	 * @return string
	 */
	public function render() {

		$template = '<div style="text-align: right" class="pull-right invisible"><a href="%s" data-uid="%s" class="btn-edit-relation">%s</a></div>';

		// Initialize url parameters array.
		$urlParameters = array(
			$this->getModuleLoader()->getParameterPrefix() => array(
				'controller' => 'Content',
				'action' => 'edit',
				'contentIdentifier' => $this->object->getUid(),
				'fieldName' => $this->getFieldName(),
			),
		);

		$result = sprintf($template,
			$this->getModuleLoader()->getModuleUrl($urlParameters),
			$this->object->getUid(),
			IconUtility::getSpriteIcon('actions-edit-add')
		);

		return $result;
	}

	/**
	 * Get the Vidi Module Loader.
	 *
	 * @return \TYPO3\CMS\Vidi\ModuleLoader
	 */
	protected function getModuleLoader() {
		return GeneralUtility::makeInstance('TYPO3\CMS\Vidi\ModuleLoader');
	}
}
