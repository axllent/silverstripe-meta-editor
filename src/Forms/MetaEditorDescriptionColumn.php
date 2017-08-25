<?php

namespace Axllent\MetaEditor\Forms;

use Axllent\MetaEditor\Lib\MetaEditorPermissions;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\TextareaField;

class MetaEditorDescriptionColumn extends MetaEditorTitleColumn
{
    public function augmentColumns($gridField, &$columns)
    {
    }

    public function getColumnsHandled($gridField)
    {
        return [
            'MetaEditorDescriptionColumn'
        ];
    }

    public function getColumnMetaData($gridField, $columnName)
    {
        return [
            'title' => 'Meta Description'
        ];
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        if (!MetaEditorPermissions::canEdit($record)) {
            return [];
        }

        $errors = self::getErrors($record);

        return [
            'class' => count($errors)
                ? 'has-warning meta-editor-error ' . implode(' ', $errors)
                : 'has-success'
        ];
    }

    public static function getErrors($record)
    {
        $description_field = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_description_field');
        $description_min = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_description_min_length');
        $description_max = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_description_max_length');

        if (!MetaEditorPermissions::canEdit($record)) {
            return [];
        }

        $errors = [];

        if (strlen($record->$description_field) < $description_min) {
            $errors[] = 'meta-editor-error-too-short';
        } elseif (strlen($record->$description_field) > $description_max) {
            $errors[] = 'meta-editor-error-too-long';
        } elseif (
            $record->$description_field &&
            self::getAllEditableRecords()->filter($description_field, $record->$description_field)->count() > 1
        ) {
            $errors[] = 'meta-editor-error-duplicate';
        }

        return $errors;
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($columnName == 'MetaEditorDescriptionColumn') {
            $value = $gridField->getDataFieldValue(
                $record,
                Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_description_field')
            );
            if (MetaEditorPermissions::canEdit($record)) {
                $description_field = TextareaField::create('MetaDescription');
                $description_field->setName($this->getFieldName($description_field->getName(), $gridField, $record));
                $description_field->setValue($value);
                return $description_field->Field() . $this->getErrorMessages();
            } else {
                return ''; // blank
            }
        }
    }

    /**
     * Return all the error messages
     *
     * @return string
     */
    public function getErrorMessages()
    {
        $description_min = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_description_min_length');
        $description_max = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_description_max_length');

        return '<div class="meta-editor-errors">' .
            '<span class="meta-editor-message meta-editor-message-too-short">Too short: should be between ' . $description_min . ' &amp; ' . $description_max . ' characters.</span>' .
            '<span class="meta-editor-message meta-editor-message-too-long">Too long: should be between ' . $description_min . ' &amp; ' . $description_max . ' characters.</span>' .
            '<span class="meta-editor-message meta-editor-message-duplicate">This description is a duplicate of another page.</span>' .
        '</div>';
    }
}
