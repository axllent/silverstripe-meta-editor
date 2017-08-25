<?php

namespace Axllent\MetaEditor\Forms;

use Axllent\MetaEditor\Forms\MetaEditorDescriptionColumn;
use Axllent\MetaEditor\Lib\MetaEditorPermissions;
use Axllent\MetaEditor\MetaEditor;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBHTMLText;

class MetaEditorTitleColumn extends GridFieldDataColumns implements
    GridField_ColumnProvider,
    GridField_HTMLProvider,
    GridField_URLHandler
{
    public function augmentColumns($gridField, &$columns)
    {
    }

    public function getColumnsHandled($gridField)
    {
        return ['MetaEditorTitleColumn'];
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

    public function getColumnMetaData($gridField, $columnName)
    {
        return [
            'title' => 'Meta Title'
        ];
    }

    public static function getErrors($record)
    {
        $title_field = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_title_field');
        $title_min = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_title_min_length');
        $title_max = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_title_max_length');

        if (!MetaEditorPermissions::canEdit($record)) {
            return [];
        }

        $errors = [];

        if (strlen($record->$title_field) < $title_min) {
            $errors[] = 'meta-editor-error-too-short';
        } elseif (strlen($record->$title_field) > $title_max) {
            $errors[] = 'meta-editor-error-too-long';
        } elseif (
            $record->$title_field &&
            self::getAllEditableRecords()->filter($title_field, $record->$title_field)->count() > 1
        ) {
            $errors[] = 'meta-editor-error-duplicate';
        }

        return $errors;
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($columnName == 'MetaEditorTitleColumn') {
            $value = $gridField->getDataFieldValue(
                $record,
                Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_title_field')
            );
            if (MetaEditorPermissions::canEdit($record)) {
                $title_field = TextField::create('MetaTitle');
                $title_field->setName($this->getFieldName($title_field->getName(), $gridField, $record));
                $title_field->setValue($value);
                $title_field->addExtraClass('form-control');
                return $title_field->Field() . $this->getErrorMessages();
            } else {
                return '<span class="non-editable">Non-editable</span>';
            }
        }
    }

    protected function getFieldName($name, \SilverStripe\Forms\GridField\GridField $gridField, $record)
    {
        return sprintf(
            '%s[%s][%s]',
            $gridField->getName(),
            $record->ID,
            $name
        );
    }

    public function getHTMLFragments($gridField)
    {
        $gridField->addExtraClass('meta-editor');
    }

    public function getURLHandlers($gridField)
    {
        return [
            'update/$ID' => 'handleAction',
        ];
    }

    public function handleAction($gridField, $request)
    {
        $data = $request->postVar($gridField->getName());

        $title_field = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_title_field');
        $description_field = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_description_field');

        foreach ($data as $id => $params) {
            $page = self::getAllEditableRecords()->byID((int)$id);

            $errors = [];

            foreach ($params as $fieldName => $val) {
                $val = trim(preg_replace('/\s+/', ' ', $val));
                if ($val) {
                    $sqlValue = "'" . Convert::raw2sql($val) . "'";
                } else {
                    $sqlValue = 'NULL';
                }

                /* Make sure the MenuTitle remains unchanged if NULL! */
                if ($fieldName == 'MetaTitle') {
                    if (!$val) {
                        throw new HTTPResponse_Exception($title_field . ' cannot be blank', 500);
                        return $this->ajaxResponse(
                            $title_field . ' cannot be blank',
                            [
                                'errors' => ['meta-editor-error-too-short']
                            ]
                        );
                    }
                    $query = DB::query('SELECT MenuTitle, ' . $title_field . " FROM SiteTree WHERE ID = {$page->ID}");
                    foreach ($query as $row) {
                        $menuTitle = '\'' . Convert::raw2sql($row['MenuTitle']) . '\'';
                        if (is_null($row['MenuTitle'])) {
                            $menuTitle = '\'' . Convert::raw2sql($row[$title_field]) . '\'';
                        }
                        if ($menuTitle == $sqlValue) { // set back to NULL
                            $menuTitle = 'NULL';
                        }
                        DB::query('UPDATE SiteTree SET MenuTitle = ' . $menuTitle . " WHERE ID = {$page->ID}");
                        if ($page->isPublished()) {
                            DB::query('UPDATE SiteTree_Live SET MenuTitle = ' . $menuTitle . " WHERE ID = {$page->ID}");
                        }
                    }

                    /* Update MetaTitle */
                    DB::query("UPDATE SiteTree SET {$title_field} = {$sqlValue} WHERE ID = {$page->ID}");
                    if ($page->isPublished()) {
                        DB::query("UPDATE SiteTree_Live SET {$title_field} = {$sqlValue} WHERE ID = {$page->ID}");
                    }

                    $record = self::getAllEditableRecords()->byID($page->ID);
                    $errors = self::getErrors($record);

                    return $this->ajaxResponse(
                        $title_field . ' saved (' . strlen($val) . ' chars)',
                        ['errors' => $errors]
                    );
                } elseif ($fieldName == 'MetaDescription') {
                    /* Update MetaDescription */
                    DB::query("UPDATE SiteTree SET {$description_field} = {$sqlValue} WHERE ID = {$page->ID}");
                    if ($page->isPublished()) {
                        DB::query("UPDATE SiteTree_Live SET {$description_field} = {$sqlValue} WHERE ID = {$page->ID}");
                    }

                    $record = self::getAllEditableRecords()->byID($page->ID);
                    $errors = MetaEditorDescriptionColumn::getErrors($record);

                    return $this->ajaxResponse(
                        $description_field . ' saved (' . strlen($val) . ' chars)',
                        ['errors' => $errors]
                    );
                }
            }
        }

        throw new HTTPResponse_Exception('An error occurred while saving', 500);
    }

    public function ajaxResponse($message, $data = [])
    {
        $response = new HTTPResponse();
        $response->addHeader(
            'X-Status',
            rawurlencode($message)
        );

        $response->setBody(
            json_encode($data)
        );

        return $response;
    }


    /**
     * Return all editable records
     * Ignored hide_page_types & non_editable_page_types
     * @param null
     * @return DataList
     */
    public static function getAllEditableRecords()
    {
        $hide_page_types = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'hide_page_types');
        $non_editable_page_types = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'non_editable_page_types');
        $ignore = [];
        if (!empty($hide_page_types) && is_array($hide_page_types)) {
            foreach ($hide_page_types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);
                $ignore = array_merge(array_keys($subclasses), $ignore);
            }
        }
        if (!empty($non_editable_page_types) && is_array($non_editable_page_types)) {
            foreach ($non_editable_page_types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);
                $ignore = array_merge(array_keys($subclasses), $ignore);
            }
        }

        $list = SiteTree::get();

        if (!empty($ignore)) {
            $list = $list->exclude('ClassName', $ignore); // remove error pages etc
        }

        return $list->sort('Sort');
    }

    /**
     * Return all the error messages
     *
     * @return string
     */
    public function getErrorMessages()
    {
        $title_min = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_title_min_length');
        $title_max = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'meta_title_max_length');

        return '<div class="meta-editor-errors">' .
            '<span class="meta-editor-message meta-editor-message-too-short">Too short: should be between ' . $title_min . ' &amp; ' . $title_max . ' characters.</span>' .
            '<span class="meta-editor-message meta-editor-message-too-long">Too long: should be between ' . $title_min . ' &amp; ' . $title_max . ' characters long.</span>' .
            '<span class="meta-editor-message meta-editor-message-duplicate">This title is a duplicate of another page.</span>' .
        '</div>';
    }
}
