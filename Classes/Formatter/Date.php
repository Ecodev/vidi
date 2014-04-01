<?php
namespace TYPO3\CMS\Vidi\Formatter;
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
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Format a date that will be displayed in the Grid
 */
class Date implements FormatterInterface, SingletonInterface {

	/**
	 * Format a date
	 *
	 * @param int $value
	 * @return string
	 */
	public function format($value) {
		$result = '';
		if ($value > 0) {
			/** @var $viewHelper \TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper */
			$viewHelper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper');
			$result = $viewHelper->render('@' . $value, $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
		}
		return $result;
	}

}
