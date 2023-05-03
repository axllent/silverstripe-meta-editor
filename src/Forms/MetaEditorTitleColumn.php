<?php

namespace Axllent\MetaEditor\Forms;

use Axllent\MetaEditor\Lib\MetaEditorPermissions;
use Axllent\MetaEditor\MetaEditor;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use TractorCow\Fluent\Extension\FluentSiteTreeExtension;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

class MetaEditorTitleColumn extends GridFieldDataColumns implements GridField_ColumnProvider, GridField_HTMLProvider, GridField_URLHandler
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
        return ['MetaEditorTitleColumn'];
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
            'title' => 'Meta Title',
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
     * Return all the error messages
     *
     * @param DataObject $record DataObject
     *
     * @return string
     */
    public static function getErrors($record)
    {
        $title_field = Config::inst()
            ->get(MetaEditor::class, 'meta_title_field');
        $title_min = Config::inst()
            ->get(MetaEditor::class, 'meta_title_min_length');
        $title_max = Config::inst()
            ->get(MetaEditor::class, 'meta_title_max_length');

        if (!MetaEditorPermissions::canEdit($record)) {
            return [];
        }

        $errors = [];

        if (strlen($record->{$title_field}) < $title_min) {
            $errors[] = 'meta-editor-error-too-short';
        } elseif (strlen($record->{$title_field}) > $title_max) {
            $errors[] = 'meta-editor-error-too-long';
        } elseif (
            $record->{$title_field}
            && self::getAllEditableRecords()->filter($title_field, $record->{$title_field})->count() > 1
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
        if ('MetaEditorTitleColumn' == $columnName) {
            $value = $gridField->getDataFieldValue(
                $record,
                Config::inst()->get(MetaEditor::class, 'meta_title_field')
            );
            if (MetaEditorPermissions::canEdit($record)) {
                $title_field = TextField::create('MetaTitle');
                $title_field->setName(
                    $this->getFieldName(
                        $title_field->getName(),
                        $gridField,
                        $record
                    )
                );
                $title_field->setValue($value);
                $title_field->addExtraClass('form-control');

                return $title_field->Field() . $this->getErrorMessages();
            }

            return '<span class="non-editable">Meta tags not editable</span>';
        }
    }

    /**
     * Get field name
     *
     * @param string     $name      Name
     * @param GridField  $gridField Gridfield
     * @param DataObject $record    Record
     *
     * @return string
     */
    protected function getFieldName($name, GridField $gridField, $record)
    {
        return sprintf(
            '%s[%s][%s]',
            $gridField->getName(),
            $record->ID,
            $name
        );
    }

    /**
     * Get HTML Fragment
     *
     * @param GridField $gridField Gridfield
     *
     * @return GridField
     */
    public function getHTMLFragments($gridField)
    {
        $gridField->addExtraClass('meta-editor');
    }

    /**
     * Get URL handlers
     *
     * @param GridField $gridField Gridfield
     *
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'update/$ID' => 'handleAction',
        ];
    }

    /**
     * Handle Action
     *
     * @param GridField   $gridField Gridfield
     * @param HTTPRequest $request   HTTP request
     *
     * @return HTTPResponse
     */
    public function handleAction($gridField, $request)
    {
        $data = $request->postVar($gridField->getName());

        $title_field = Config::inst()
            ->get(MetaEditor::class, 'meta_title_field');
        $description_field = Config::inst()
            ->get(MetaEditor::class, 'meta_description_field');

        $sitetree      = 'SiteTree';
        $sitetree_live = 'SiteTree_Live';
        $fluent        = Injector::inst()
            ->get(SiteTree::class)
            ->hasExtension(FluentSiteTreeExtension::class)
        && Locale::get()->count();

        if ($fluent) {
            $sitetree      = 'SiteTree_Localised';
            $sitetree_live = 'SiteTree_Localised_Live';
            $locale        = FluentState::singleton()->getLocale();
        }

        foreach ($data as $id => $params) {
            $page = self::getAllEditableRecords()->byID((int) $id);

            $errors = [];

            $identifier = $fluent ?
            "RecordID = {$page->ID} AND Locale = '{$locale}'" :
            "ID = {$page->ID}";

            foreach ($params as $fieldName => $val) {
                $val = trim(preg_replace('/\s+/', ' ', $val));
                if ($val) {
                    $sqlValue = "'" . Convert::raw2sql($val) . "'";
                } else {
                    $sqlValue = 'NULL';
                }

                // Make sure the MenuTitle remains unchanged if NULL!
                if ('MetaTitle' == $fieldName) {
                    if (!$val) {
                        throw new HTTPResponse_Exception(
                            $title_field . ' cannot be blank',
                            500
                        );

                        return $this->ajaxResponse(
                            $title_field . ' cannot be blank',
                            [
                                'errors' => ['meta-editor-error-too-short'],
                            ]
                        );
                    }
                    // Only change the Title, leaving the MenuTitle as it was
                    $query = DB::query(
                        "SELECT MenuTitle, {$title_field} FROM {$sitetree}
                        WHERE " . $identifier
                    );
                    foreach ($query as $row) {
                        $menuTitle = '\'' . Convert::raw2sql($row['MenuTitle']) . '\'';
                        if (is_null($row['MenuTitle'])) {
                            $menuTitle = '\'' . Convert::raw2sql($row[$title_field]) . '\'';
                        }
                        if ($menuTitle == $sqlValue) { // set back to NULL
                            $menuTitle = 'NULL';
                        }
                        DB::query(
                            "UPDATE {$sitetree} SET MenuTitle = {$menuTitle}
                             WHERE " . $identifier
                        );
                        if ($page->isPublished()) {
                            DB::query(
                                "UPDATE {$sitetree_live} SET MenuTitle = {$menuTitle}
                                WHERE " . $identifier
                            );

                            if ($page->hasMethod('onAfterMetaEditorUpdate')) {
                                $page->onAfterMetaEditorUpdate();
                            }
                        }
                    }

                    // Update MetaTitle
                    DB::query(
                        "UPDATE {$sitetree} SET {$title_field} = {$sqlValue}
                        WHERE " . $identifier
                    );

                    if ($page->isPublished()) {
                        DB::query(
                            "UPDATE {$sitetree_live} SET {$title_field} = {$sqlValue}
                            WHERE " . $identifier
                        );
                    }

                    $record = self::getAllEditableRecords()->byID($page->ID);
                    $errors = self::getErrors($record);

                    return $this->ajaxResponse(
                        $title_field . ' saved (' . strlen($val) . ' chars)',
                        ['errors' => $errors]
                    );
                }
                if ('MetaDescription' == $fieldName) {
                    // Update MetaDescription
                    DB::query(
                        "UPDATE {$sitetree} SET {$description_field} = {$sqlValue}
                        WHERE " . $identifier
                    );

                    if ($page->isPublished()) {
                        DB::query(
                            "UPDATE {$sitetree_live}
                            SET {$description_field} = {$sqlValue}
                            WHERE " . $identifier
                        );

                        if ($page->hasMethod('onAfterMetaEditorUpdate')) {
                            $page->onAfterMetaEditorUpdate();
                        }
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

    /**
     * Ajac response
     *
     * @param string $message Message
     * @param array  $data    Array
     *
     * @return HTTPResponse
     */
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
     * Ignored hidden_page_types & non_editable_page_types
     *
     * @return DataList
     */
    public static function getAllEditableRecords()
    {
        $hidden_page_types = Config::inst()
            ->get(MetaEditor::class, 'hidden_page_types');
        $non_editable_page_types = Config::inst()
            ->get(MetaEditor::class, 'non_editable_page_types');
        $ignore = [];
        if (!empty($hidden_page_types) && is_array($hidden_page_types)) {
            foreach ($hidden_page_types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);
                $ignore     = array_merge(array_keys($subclasses), $ignore);
            }
        }
        if (!empty($non_editable_page_types) && is_array($non_editable_page_types)) {
            foreach ($non_editable_page_types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);
                $ignore     = array_merge(array_keys($subclasses), $ignore);
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
        $title_min = Config::inst()->get(MetaEditor::class, 'meta_title_min_length');
        $title_max = Config::inst()->get(MetaEditor::class, 'meta_title_max_length');

        return '<div class="meta-editor-errors">' .
            '<span class="meta-editor-message meta-editor-message-too-short">' .
            _t(
                self::class . '.TITLE_TOO_SHORT',
                'Too short: should be between {title_min} &amp; {title_max} characters.',
                [
                    'title_min' => $title_min,
                    'title_max' => $title_max,
                ]
            ) . '</span>' .
            '<span class="meta-editor-message meta-editor-message-too-long">' .
            _t(
                self::class . '.TITLE_TOO_LONG',
                'Too long: should be between {title_min} &amp; {title_max} characters.',
                [
                    'title_min' => $title_min,
                    'title_max' => $title_max,
                ]
            ) . '</span>' .
            '<span class="meta-editor-message meta-editor-message-duplicate">' .
            _t(
                self::class . '.TITLE_DUPLICATE',
                'This title is a duplicate of another page.'
            ) . '</span>' .
            '</div>';
    }
}
