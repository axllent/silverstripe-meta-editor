<?php

namespace Axllent\MetaEditor\Forms;

use Axllent\MetaEditor\Forms\MetaEditorTitleColumn;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\ORM\FieldType\DBVarchar;

class MetaEditorPageColumn implements GridField_ColumnProvider
{
    public function augmentColumns($gridField, &$columns)
    {
    }

    public function getColumnsHandled($gridField)
    {
        return ['MetaEditorPageColumn'];
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return [];
    }

    public function getColumnMetaData($gridField, $columnName)
    {
        switch ($columnName) {
            case 'MetaEditorPageColumn':
                return array('title' => 'Page');
            default:
                break;
        }
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($columnName == 'MetaEditorPageColumn') {
            $children = MetaEditorTitleColumn::getAllEditableRecords()->filter('ParentID', $record->ID);
            if ($children->Count()) {
                $output = '<p><a href="admin/meta-editor/?ParentID=' . $record->ID .'" class="btn btn-secondary font-icon-right-dir">
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
