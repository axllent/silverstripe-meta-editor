<?php

namespace Axllent\MetaEditor\Forms;

use SilverStripe\Forms\GridField\GridField_ColumnProvider;

class MetaEditorPageLinkColumn implements GridField_ColumnProvider
{
    /**
     * Augment Columns
     *
     * @param GridField $gridField GridField
     * @param array     $columns   Columns
     *
     * @return null
     */
    public function augmentColumns($gridField, &$columns) {}

    /**
     * GetColumnsHandled
     *
     * @param GridField $gridField GridField
     *
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ['MetaEditorPageLinkColumn'];
    }

    /**
     * GetColumnMetaData
     *
     * @param GridField $gridField  GridField
     * @param string    $columnName Column name
     *
     * @return array
     */
    public function getColumnMetaData($gridField, $columnName)
    {
        switch ($columnName) {
            case 'MetaEditorPageLinkColumn':
                return ['title' => ''];

            default:
                break;
        }
    }

    /**
     * Get column attributes
     *
     * @param GridField  $gridField  GridField
     * @param DataObject $record     Record
     * @param string     $columnName Column name
     *
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return [];
    }

    /**
     * Get column content
     *
     * @param GridField  $gridField  GridField
     * @param DataObject $record     Record
     * @param string     $columnName Column name
     *
     * @return string
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($columnName == 'MetaEditorPageLinkColumn') {
            $link      = $record->Link();
            $edit_link = $record->CMSEditLink();

            return '<a href="' . $link . '?stage=Stage" target="_blank" class="btn btn-secondary no-text font-icon-eye" title="View page"></a><br />'
                . '<a href="' . $edit_link . '" class="btn btn-secondary no-text font-icon-edit" title="Edit page"></a>';
        }
    }
}
