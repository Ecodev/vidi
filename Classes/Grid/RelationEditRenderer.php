<?php

namespace Fab\Vidi\Grid;

/*
 * This file is part of the Fab/Vidi project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Fab\Vidi\Tca\Tca;
use Fab\Vidi\Tool\AbstractTool;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * Class for editing mm relation between objects.
 */
class RelationEditRenderer extends ColumnRendererAbstract
{
    /**
     * @return string
     */
    public function render()
    {
        $output = '';
        if (AbstractTool::isBackend()) {
            $output = $this->renderForBackend();
        }

        return $output;
    }

    /**
     * @return string
     */
    protected function renderForBackend()
    {
        // Initialize url parameters array.
        $urlParameters = array(
            $this->getModuleLoader()->getParameterPrefix() => array(
                'controller' => 'Content',
                'action' => 'edit',
                'matches' => array('uid' => $this->object->getUid()),
                'fieldNameAndPath' => $this->getFieldName(),
            ),
        );

        $fieldLabel = Tca::table()->field($this->getFieldName())->getLabel();
        if ($fieldLabel) {
            $fieldLabel = str_replace(':', '', $fieldLabel); // sanitize label
        }

        return sprintf(
            '<div style="text-align: right" class="pull-right invisible"><a href="%s" class="btn-edit-relation" data-field-label="%s">%s</a></div>',
            $this->getModuleLoader()->getModuleUrl($urlParameters),
            $fieldLabel,
            $this->getIconFactory()->getIcon('actions-add', Icon::SIZE_SMALL)
        );
    }
}
