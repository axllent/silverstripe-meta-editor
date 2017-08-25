<?php

namespace Axllent\MetaEditor\Forms;

use Axllent\MetaEditor\Forms\MetaEditorTitleColumn;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;

class MetaEditorPageLinkColumn implements GridField_ColumnProvider
{
    public function augmentColumns($gridField, &$columns)
    {
    }

    public function getColumnsHandled($gridField)
    {
        return ['MetaEditorPageLinkColumn'];
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return [];
    }

    public function getColumnMetaData($gridField, $columnName)
    {
        switch ($columnName) {
            case 'MetaEditorPageLinkColumn':
                return array('title' => '');
            default:
                break;
        }
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($columnName == 'MetaEditorPageLinkColumn') {
            $link = $record->Link();
            $edit_link = $record->CMSEditLink();
            return '<a href="' . $link . '?stage=Stage" target="_blank" class="btn btn-secondary no-text font-icon-eye" title="View page"></a><br />' .
                '<a href="' . $edit_link . '" class="btn btn-secondary no-text font-icon-edit" title="Edit page"></a>';
        }
    }
}
