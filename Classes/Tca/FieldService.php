<?php

namespace Fab\Vidi\Tca;

/*
 * This file is part of the Fab/Vidi project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Fab\Vidi\Tool\AbstractTool;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * A class to handle TCA field configuration.
 */
class FieldService extends AbstractTca
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $compositeField;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var array
     */
    protected $tca;

    /**
     * @param string $fieldName
     * @param array $tca
     * @param string $tableName
     * @param string $compositeField
     * @return \Fab\Vidi\Tca\FieldService
     */
    public function __construct($fieldName, array $tca, $tableName, $compositeField = '')
    {
        $this->fieldName = $fieldName;
        $this->tca = $tca;
        $this->tableName = $tableName;
        $this->compositeField = $compositeField;
    }

    /**
     * Tells whether the field is considered as system field, e.g. uid, crdate, tstamp, etc...
     *
     * @return bool
     */
    public function isSystem()
    {
        return in_array($this->fieldName, Tca::getSystemFields());
    }

    /**
     * Tells the opposition of isSystem()
     *
     * @return bool
     */
    public function isNotSystem()
    {
        return !$this->isSystem();
    }

    /**
     * Returns the configuration for a $field
     *
     * @throws \Exception
     * @return array
     */
    public function getConfiguration()
    {
        return empty($this->tca['config']) ? [] : $this->tca['config'];
    }

    /**
     * Returns a key of the configuration.
     * If the key can not to be found, returns null.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $configuration = $this->getConfiguration();
        return empty($configuration[$key]) ? null : $configuration[$key];
    }

    /**
     * Returns the foreign field of a given field (opposite relational field).
     * If no relation exists, returns null.
     *
     * @return string|null
     */
    public function getForeignField()
    {
        $result = null;
        $configuration = $this->getConfiguration();

        if (!empty($configuration['foreign_field'])) {
            $result = $configuration['foreign_field'];
        } elseif ($this->hasRelationManyToMany()) {
            $foreignTable = $this->getForeignTable();
            $manyToManyTable = $this->getManyToManyTable();

            // Load TCA service of foreign field.
            $tcaForeignTableService = Tca::table($foreignTable);

            // Look into the MM relations checking for the opposite field
            foreach ($tcaForeignTableService->getFields() as $fieldName) {
                if ($manyToManyTable == $tcaForeignTableService->field($fieldName)->getManyToManyTable()) {
                    $result = $fieldName;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Returns the foreign table of a given field (opposite relational table).
     * If no relation exists, returns null.
     *
     * @return string|null
     */
    public function getForeignTable()
    {
        $result = null;
        $configuration = $this->getConfiguration();

        if (!empty($configuration['foreign_table'])) {
            $result = $configuration['foreign_table'];
        } elseif ($this->isGroup()) {
            $fieldParts = explode('.', $this->compositeField, 2);
            $result = $fieldParts[1];
        }
        return $result;
    }

    /**
     * Returns the foreign clause.
     * If no foreign order exists, returns empty string.
     *
     * @return string
     */
    public function getForeignClause()
    {
        $result = '';
        $configuration = $this->getConfiguration();

        if (!empty($configuration['foreign_table_where'])) {
            $parts = explode('ORDER BY', $configuration['foreign_table_where']);
            if (!empty($parts[0])) {
                $result = $parts[0];
            }
        }

        // Substitute some variables
        return $this->substituteKnownMarkers($result);
    }

    /**
     * Substitute some known markers from the where clause in the Frontend Context.
     *
     * @param string $clause
     * @return string
     */
    protected function substituteKnownMarkers($clause)
    {
        if ($clause && AbstractTool::isFrontend()) {
            $searches = array(
                '###CURRENT_PID###',
                '###REC_FIELD_sys_language_uid###'
            );

            $replaces = array(
                $this->getFrontendObject()->id,
                GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id'),
            );

            $clause = str_replace($searches, $replaces, $clause);
        }
        return $clause;
    }

    /**
     * Returns the foreign order of the current field.
     * If no foreign order exists, returns empty string.
     *
     * @return string
     */
    public function getForeignOrder()
    {
        $result = '';
        $configuration = $this->getConfiguration();

        if (!empty($configuration['foreign_table_where'])) {
            $parts = explode('ORDER BY', $configuration['foreign_table_where']);
            if (!empty($parts[1])) {
                $result = $parts[1];
            }
        }
        return $result;
    }

    /**
     * Returns the MM table of a field.
     * If no relation exists, returns null.
     *
     * @return string|null
     */
    public function getManyToManyTable()
    {
        $configuration = $this->getConfiguration();
        return empty($configuration['MM']) ? null : $configuration['MM'];
    }

    /**
     * Returns a possible additional table name used in MM relations.
     * If no table name exists, returns null.
     *
     * @return string|null
     */
    public function getAdditionalTableNameCondition()
    {
        $result = null;
        $configuration = $this->getConfiguration();

        if (!empty($configuration['MM_match_fields']['tablenames'])) {
            $result = $configuration['MM_match_fields']['tablenames'];
        } elseif ($this->isGroup()) {
            // @todo check if $this->fieldName could be simply used as $result
            $fieldParts = explode('.', $this->compositeField, 2);
            $result = $fieldParts[1];
        }

        return $result;
    }

    /**
     * Returns a possible additional conditions for MM tables such as "tablenames", "fieldname", etc...
     *
     * @return array
     */
    public function getAdditionalMMCondition()
    {
        $additionalMMConditions = [];
        $configuration = $this->getConfiguration();

        if (!empty($configuration['MM_match_fields'])) {
            $additionalMMConditions = $configuration['MM_match_fields'];
        }

        // Add in any case a table name for "group"
        if ($this->isGroup()) {
            // @todo check if $this->fieldName could be simply used as $result
            $fieldParts = explode('.', $this->compositeField, 2);
            $additionalMMConditions = array(
                'tablenames' => $fieldParts[1],
            );
        }
        return $additionalMMConditions;
    }

    /**
     * Returns whether the field name is the opposite in MM relation.
     *
     * @return bool
     */
    public function isOppositeRelation()
    {
        $configuration = $this->getConfiguration();
        return isset($configuration['MM_opposite_field']);
    }

    /**
     * Returns the configuration for a $field.
     *
     * @throws \Exception
     * @return string
     */
    public function getType()
    {
        if ($this->isSystem()) {
            $fieldType = FieldType::NUMBER;
        } else {
            $configuration = $this->getConfiguration();

            if (empty($configuration['type'])) {
                throw new \Exception(sprintf('No field type found for "%s" in table "%s"', $this->fieldName, $this->tableName), 1385556627);
            }

            $fieldType = $configuration['type'];

            if ($configuration['type'] === FieldType::SELECT && !empty($configuration['size']) && $configuration['size'] > 1) {
                $fieldType = FieldType::MULTISELECT;
            } elseif (!empty($configuration['foreign_table'])
                && ($configuration['foreign_table'] == 'sys_file_reference' || $configuration['foreign_table'] == 'sys_file')
            ) {
                $fieldType = FieldType::FILE;
            } elseif (!empty($configuration['eval'])) {
                $parts = GeneralUtility::trimExplode(',', $configuration['eval']);
                if (in_array('datetime', $parts)) {
                    $fieldType = FieldType::DATETIME;
                } elseif (in_array('date', $parts)) {
                    $fieldType = FieldType::DATE;
                } elseif (in_array('email', $parts)) {
                    $fieldType = FieldType::EMAIL;
                } elseif (in_array('int', $parts) || in_array('double2', $parts)) {
                    $fieldType = FieldType::NUMBER;
                }
            }

            // Do some legacy conversion
            if ($fieldType === 'input') {
                $fieldType = FieldType::TEXT;
            } elseif ($fieldType === 'text') {
                $fieldType = FieldType::TEXTAREA;
            }
        }
        return $fieldType;
    }

    /**
     * Return the default value.
     *
     * @return bool
     */
    public function getDefaultValue()
    {
        $configuration = $this->getConfiguration();
        return isset($configuration['default']) ? $configuration['default'] : null;
    }

    /**
     * Get the translation of a label given a column.
     *
     * @return string
     */
    public function getLabel()
    {
        $label = '';
        if ($this->hasLabel()) {
            try {
                $label = LocalizationUtility::translate($this->tca['label'], '');
            } catch (\InvalidArgumentException $e) {
            }
            if (empty($label)) {
                $label = $this->tca['label'];
            }
        }
        return $label;
    }

    /**
     * Get the translation of a label given a column.
     *
     * @param string $itemValue the item value to search for.
     * @return string
     */
    public function getLabelForItem($itemValue)
    {
        // Early return whether there is nothing to be translated as label.
        if (is_null($itemValue)) {
            return '';
        } elseif (is_string($itemValue) && $itemValue === '') {
            return $itemValue;
        }

        $configuration = $this->getConfiguration();
        if (!empty($configuration['items']) && is_array($configuration['items'])) {
            foreach ($configuration['items'] as $item) {
                if ($item[1] == $itemValue) {
                    try {
                        $label = LocalizationUtility::translate($item[0], '');
                    } catch (\InvalidArgumentException $e) {
                    }
                    if (empty($label)) {
                        $label = $item[0];
                    }
                    break;
                }
            }
        }

        // Try fetching a label from a possible itemsProcFunc
        if (!isset($label) && is_scalar($itemValue)) {
            $items = $this->fetchItemsFromUserFunction();
            if (!empty($items[$itemValue])) {
                $label = $items[$itemValue];
            }
        }

        // Returns a label if it has been found, otherwise returns the item value as fallback.
        return isset($label) ? $label : $itemValue;
    }

    /**
     * Retrieve items from User Function.
     *
     * @return array
     */
    protected function fetchItemsFromUserFunction()
    {
        $values = [];

        $configuration = $this->getConfiguration();
        if (!empty($configuration['itemsProcFunc'])) {
            $parts = explode('php:', $configuration['itemsProcFunc']);
            if (!empty($parts[1])) {
                [$class, $method] = explode('->', $parts[1]);

                $parameters['items'] = [];
                $object = GeneralUtility::makeInstance($class);
                $object->$method($parameters);

                foreach ($parameters['items'] as $items) {
                    $values[$items[1]] = $items[0];
                }
            }
        }
        return $values;
    }

    /**
     * Get a possible icon given a field name an an item.
     *
     * @param string $itemValue the item value to search for.
     * @return string
     */
    public function getIconForItem($itemValue)
    {
        $result = '';
        $configuration = $this->getConfiguration();
        if (!empty($configuration['items']) && is_array($configuration['items'])) {
            foreach ($configuration['items'] as $item) {
                if ($item[1] == $itemValue) {
                    $result = empty($item[2]) ? '' : $item[2];
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Returns whether the field has a label.
     *
     * @return bool
     */
    public function hasLabel()
    {
        return empty($this->tca['label']) ? false : true;
    }

    /**
     * Tell whether the current BE User has access to this field.
     *
     * @return bool
     */
    public function hasAccess()
    {
        $hasAccess = true;
        if (AbstractTool::isBackend()
            && Tca::table($this->tableName)->hasAccess()
            && isset($this->tca['exclude'])
            && $this->tca['exclude']
        ) {
            $hasAccess = $this->getBackendUser()->check('non_exclude_fields', $this->tableName . ':' . $this->fieldName);
        }
        return $hasAccess;
    }

    /**
     * Returns whether the field is numerical.
     *
     * @return bool
     */
    public function isNumerical()
    {
        $result = $this->isSystem();
        if ($result === false) {
            $configuration = $this->getConfiguration();
            $parts = [];
            if (!empty($configuration['eval'])) {
                $parts = GeneralUtility::trimExplode(',', $configuration['eval']);
            }
            $result = in_array('int', $parts) || in_array('float', $parts);
        }
        return $result;
    }

    /**
     * Returns whether the field is of type text area.
     *
     * @return bool
     */
    public function isTextArea()
    {
        return $this->getType() === FieldType::TEXTAREA;
    }
    /**
     * Returns whether the field is of type text area.
     *
     * @return bool
     */
    public function isText()
    {
        return $this->getType() === FieldType::TEXT;
    }

    /**
     * Returns whether the field is displayed as a tree.
     *
     * @return bool
     */
    public function isRenderModeTree()
    {
        $configuration = $this->getConfiguration();
        return isset($configuration['renderMode']) && $configuration['renderMode'] == FieldType::TREE;
    }

    /**
     * Returns whether the field is of type select.
     *
     * @return bool
     */
    public function isSelect()
    {
        return $this->getType() === FieldType::SELECT;
    }

    /**
     * Returns whether the field is of type select.
     *
     * @return bool
     */
    public function isMultipleSelect()
    {
        return $this->getType() === FieldType::MULTISELECT;
    }

    /**
     * Returns whether the field is of type select.
     *
     * @return bool
     */
    public function isCheckBox()
    {
        return $this->getType() === FieldType::CHECKBOX;
    }

    /**
     * Returns whether the field is of type db.
     *
     * @return bool
     */
    public function isGroup()
    {
        return $this->getType() === 'group';
    }

    /**
     * Returns whether the field is language aware.
     *
     * @return bool
     */
    public function isLocalized()
    {
        $isLocalized = false;
        if (isset($this->tca['l10n_mode'])) {
            if ($this->tca['l10n_mode'] == 'prefixLangTitle' || $this->tca['l10n_mode'] == 'mergeIfNotBlank') {
                $isLocalized = true;
            }
        }
        return $isLocalized;
    }

    /**
     * Returns whether the field is required.
     *
     * @return bool
     */
    public function isRequired()
    {
        $configuration = $this->getConfiguration();

        $isRequired = false;
        if (isset($configuration['minitems'])) {
            // is required of a select?
            $isRequired = $configuration['minitems'] == 1 ? true : false;
        } elseif (isset($configuration['eval'])) {
            $parts = GeneralUtility::trimExplode(',', $configuration['eval'], true);
            $isRequired = in_array('required', $parts);
        }
        return $isRequired;
    }

    /**
     * Returns an array containing the configuration of a column.
     *
     * @return array
     */
    public function getField()
    {
        return $this->tca;
    }

    /**
     * Returns the relation type
     *
     * @return string
     */
    public function relationDataType()
    {
        $configuration = $this->getConfiguration();
        return empty($configuration['foreign_table']) ? '' : $configuration['foreign_table'];
    }

    /**
     * Returns whether the field has relation (one to many, many to many)
     *
     * @return bool
     */
    public function hasRelation()
    {
        return null !== $this->getForeignTable();
    }

    /**
     * Returns whether the field has no relation (one to many, many to many)
     *
     * @return bool
     */
    public function hasNoRelation()
    {
        return !$this->hasRelation();
    }

    /**
     * Returns whether the field has a "many" objects connected including "many-to-many" or "one-to-many".
     *
     * @return bool
     */
    public function hasMany()
    {
        $configuration = $this->getConfiguration();
        return $this->hasRelation() && ($configuration['maxitems'] > 1 || isset($configuration['foreign_table_field']));
    }

    /**
     * Returns whether the field has relation "one" object connected including of "one-to-one" or "many-to-one".
     *
     * @return bool
     */
    public function hasOne()
    {
        $configuration = $this->getConfiguration();
        return !isset($configuration['MM']) && $this->hasRelation() && ($configuration['maxitems'] == 1 || !isset($configuration['maxitems']));
    }

    /**
     * Returns whether the field has many-to-one relation.
     *
     * @return bool
     */
    public function hasRelationManyToOne()
    {
        $result = false;

        $foreignField = $this->getForeignField();
        if (!empty($foreignField)) {
            // Load TCA service of the foreign field.
            $foreignTable = $this->getForeignTable();
            $result = $this->hasOne() && Tca::table($foreignTable)->field($foreignField)->hasMany();
        }
        return $result;
    }

    /**
     * Returns whether the field has one-to-many relation.
     *
     * @return bool
     */
    public function hasRelationOneToMany()
    {
        $result = false;

        $foreignField = $this->getForeignField();
        if (!empty($foreignField)) {
            // Load TCA service of the foreign field.
            $foreignTable = $this->getForeignTable();
            $result = $this->hasMany() && Tca::table($foreignTable)->field($foreignField)->hasOne();
        }
        return $result;
    }

    /**
     * Returns whether the field has one-to-one relation.
     *
     * @return bool
     */
    public function hasRelationOneToOne()
    {
        $result = false;

        $foreignField = $this->getForeignField();
        if (!empty($foreignField)) {
            // Load TCA service of foreign field.
            $foreignTable = $this->getForeignTable();
            $result = $this->hasOne() && Tca::table($foreignTable)->field($foreignField)->hasOne();
        }
        return $result;
    }

    /**
     * Returns whether the field has many to many relation.
     *
     * @return bool
     */
    public function hasRelationManyToMany()
    {
        $configuration = $this->getConfiguration();
        return $this->hasRelation() && (isset($configuration['MM']) || isset($configuration['foreign_table_field']));
    }

    /**
     * Returns whether the field has many to many relation using comma separated values (legacy).
     *
     * @return bool
     */
    public function hasRelationWithCommaSeparatedValues()
    {
        $configuration = $this->getConfiguration();
        return $this->hasRelation() && !isset($configuration['MM']) && !isset($configuration['foreign_field']) && $configuration['maxitems'] > 1;
    }

    /**
     * @return array
     */
    public function getTca()
    {
        return $this->tca['columns'];
    }

    /**
     * @return string
     */
    public function getCompositeField()
    {
        return $this->compositeField;
    }

    /**
     * @param string $compositeField
     */
    public function setCompositeField($compositeField)
    {
        $this->compositeField = $compositeField;
    }

    /**
     * Returns an instance of the Frontend object.
     *
     * @return TypoScriptFrontendController
     */
    protected function getFrontendObject()
    {
        return $GLOBALS['TSFE'];
    }
}
