<?php

namespace Axllent\MetaEditor\Forms;

use Axllent\MetaEditor\Lib\MetaEditorPermissions;
use Axllent\MetaEditor\MetaEditor;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\TextareaField;

class MetaEditorDescriptionColumn extends MetaEditorTitleColumn
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
        return [
            'MetaEditorDescriptionColumn',
        ];
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
        return [
            'title' => 'Meta Description',
        ];
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
        if (!MetaEditorPermissions::canEdit($record)) {
            return [];
        }

        $errors = self::getErrors($record);

        return [
            'class' => count($errors)
            ? 'has-warning meta-editor-error ' . implode(' ', $errors)
            : 'has-success',
        ];
    }

    /**
     * Get errors
     *
     * @param DataObject $record Record
     *
     * @return array
     */
    public static function getErrors($record)
    {
        $description_field = Config::inst()->get(
            MetaEditor::class,
            'meta_description_field'
        );
        $description_min = Config::inst()->get(
            MetaEditor::class,
            'meta_description_min_length'
        );
        $description_max = Config::inst()->get(
            MetaEditor::class,
            'meta_description_max_length'
        );

        if (!MetaEditorPermissions::canEdit($record)) {
            return [];
        }

        $errors = [];

        if (!$record->{$description_field}
            || strlen($record->{$description_field}) < $description_min
        ) {
            $errors[] = 'meta-editor-error-too-short';
        } elseif ($record->{$description_field}
            && strlen($record->{$description_field}) > $description_max
        ) {
            $errors[] = 'meta-editor-error-too-long';
        } elseif ($record->{$description_field}
            && self::getAllEditableRecords()
                ->filter($description_field, $record->{$description_field})->count() > 1
        ) {
            $errors[] = 'meta-editor-error-duplicate';
        }

        return $errors;
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
        if ('MetaEditorDescriptionColumn' == $columnName) {
            $value = $gridField->getDataFieldValue(
                $record,
                Config::inst()->get(MetaEditor::class, 'meta_description_field')
            );
            if (MetaEditorPermissions::canEdit($record)) {
                $description_field = TextareaField::create('MetaDescription');
                $description_field->setName(
                    $this->getFieldName(
                        $description_field->getName(),
                        $gridField,
                        $record
                    )
                );
                $description_field->setValue($value);

                return $description_field->Field() . $this->getErrorMessages();
            }

            return ''; // blank
        }
    }

    /**
     * Return all the error messages
     *
     * @return string
     */
    public function getErrorMessages()
    {
        $description_min = Config::inst()->get(
            MetaEditor::class,
            'meta_description_min_length'
        );
        $description_max = Config::inst()->get(
            MetaEditor::class,
            'meta_description_max_length'
        );

        return '<div class="meta-editor-errors">' .
            '<span class="meta-editor-message meta-editor-message-too-short">Too short: should be between ' . $description_min . ' &amp; ' . $description_max . ' characters.</span>' .
            '<span class="meta-editor-message meta-editor-message-too-long">Too long: should be between ' . $description_min . ' &amp; ' . $description_max . ' characters.</span>' .
            '<span class="meta-editor-message meta-editor-message-duplicate">This description is a duplicate of another page.</span>' .
            '</div>';
    }
}
