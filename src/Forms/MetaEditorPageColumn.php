<?php

namespace Axllent\MetaEditor\Forms;

use SilverStripe\Forms\GridField\GridField_ColumnProvider;

class MetaEditorPageColumn implements GridField_ColumnProvider
{
    /**
     * Augment Columns
     *
     * @param GridField $gridField Gridfield
     * @param array     $columns   Columns
     *
     * @return null
     */
    public function augmentColumns($gridField, &$columns)
    {
    }

    /**
     * GetColumnsHandled
     *
     * @param GridField $gridField Gridfield
     *
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ['MetaEditorPageColumn'];
    }

    /**
     * GetColumnMetaData
     *
     * @param GridField $gridField  Gridfield
     * @param string    $columnName Column name
     *
     * @return array
     */
    public function getColumnMetaData($gridField, $columnName)
    {
        switch ($columnName) {
            case 'MetaEditorPageColumn':
                return ['title' => 'Page'];

            default:
                break;
        }
    }

    /**
     * Get column attributes
     *
     * @param GridField  $gridField  Gridfield
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
     * @param GridField  $gridField  Gridfield
     * @param DataObject $record     Record
     * @param string     $columnName Column name
     *
     * @return string
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if ('MetaEditorPageColumn' == $columnName) {
            $children = MetaEditorTitleColumn::getAllEditableRecords()
                ->filter('ParentID', $record->ID);

            if ($children->Count()) {
                $output = '<p><a href="admin/meta-editor/?ParentID=' . $record->ID . '" class="btn btn-secondary font-icon-right-dir">
                    <b>' . htmlspecialchars($record->MenuTitle) . ' <span class="font-icon-right-dirs"></span></b></a></p>';
            } else {
                $output = '<p><b>' . htmlspecialchars($record->MenuTitle) . '</b></p>';
            }
            $output .= '<p><a href="' . $record->Link() . '?stage=Stage" class="btn btn-secondary" target="_blank">/' .
            ltrim($record->RelativeLink(), '/') . '</a></p>';

            return $output;
        }
    }
}
