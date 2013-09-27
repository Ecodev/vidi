<?php
namespace TYPO3\CMS\Vidi\GridRenderer;
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
use TYPO3\CMS\Vidi\Tca\TcaServiceFactory;

/**
 * Class rendering relation
 */
class RelationCreate extends GridRendererAbstract {

	/**
	 * Render a representation of the relation on the GUI.
	 *
	 * @return string
	 */
	public function render() {
		$template = '<div style="text-align: right" class="pull-right invisible"><a href="#" data-uid="%s" class="btn-create-relation btn-%s">%s</a></div>';
		$result = sprintf($template,
			$this->object->getUid(),
			$this->object->getDataType(),
			\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new')
		);

		return $result;
	}
}
?>