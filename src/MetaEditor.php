<?php

namespace Axllent\MetaEditor;

use Axllent\MetaEditor\Forms\MetaEditorPageColumn;
use Axllent\MetaEditor\Forms\MetaEditorPageLinkColumn;
use Axllent\MetaEditor\Forms\MetaEditorTitleColumn;
use Axllent\MetaEditor\Forms\MetaEditorDescriptionColumn;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use Axllent\MetaEditor\Forms\GridField\GridFieldLevelup;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Filters\PartialMatchFilter;

class MetaEditor extends ModelAdmin
{

    /**
     * @config string
     */
    private static $meta_title_field = 'Title';
    private static $meta_title_min_length = 20;
    private static $meta_title_max_length = 70;

    /**
     * @config string
     */
    private static $meta_description_field = 'MetaDescription';
    private static $meta_description_min_length = 100;
    private static $meta_description_max_length = 200;

    /**
     * Non-editable pages (includes extended classes)
     * @config array
     */
    private static $non_editable_page_types = [
        'SilverStripe\\CMS\\Model\\RedirectorPage',
        'SilverStripe\\CMS\\Model\\VirtualPage'
    ];

    /**
     * Hidden pages (includes extended classes)
     * Note that these (or their children) are not displayed
     * @config array
     */
    private static $hide_page_types = [
     'SilverStripe\\ErrorPage\\ErrorPage'
    ];

    /**
     * @var string
     */
    private static $menu_title = 'Meta Editor';

    /**
     * @var string
     */
    private static $url_segment = 'meta-editor';

    /**
     * @var string
     */
    private static $menu_icon = 'silverstripe-meta-editor/images/MetaEditor.svg';

    /**
     * @var array
     */
    private static $managed_models = [
        'SilverStripe\\CMS\\Model\\SiteTree'
    ];

    public function init()
    {
        parent::init();
        Requirements::css(METAEDITOR_DIR . '/css/meta-editor.css');
        Requirements::javascript(METAEDITOR_DIR . '/javascript/meta-editor.js');
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridFieldName = $this->sanitiseClassName($this->modelClass);

        $grid = $form->Fields()->dataFieldByName($gridFieldName);
        if ($grid) {
            $config = $grid->getConfig();
            $config->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldAddNewButton');
            $config->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldPrintButton');
            $config->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldEditButton');
            $config->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldExportButton');
            $config->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldImportButton');
            $config->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldDeleteAction');

            $parent_id = $this->request->requestVar('ParentID') ? : 0;

            if ($parent_id) {
                $parent = SiteTree::get()->byID($parent_id);

                if ($parent) {
                    if ($parent->Parent()->exists()) {
                        $up_title = $parent->Parent()->MenuTitle;
                    } else {
                        $up_title = 'top level';
                    }
                    $uplink = new GridFieldLevelup();
                    $uplink->setContent('Back to ' . htmlspecialchars($up_title));
                    if ($parent->ParentID) {
                        $uplink->setLinkSpec('admin/meta-editor/?ParentID=' . $parent->ParentID);
                    } else {
                        $uplink->setLinkSpec('admin/meta-editor/');
                    }
                    $config->addComponent($uplink);
                }
            } elseif ($this->request->requestVar('action_search')) {
                $uplink = new GridFieldLevelup();
                $uplink->setContent('Back to page listing');
                $uplink->setLinkSpec('admin/meta-editor/');
                $config->addComponent($uplink);
            }

            $config->getComponentByType('SilverStripe\\Forms\\GridField\GridFieldDataColumns')->setDisplayFields(
                [
                    'MetaEditorPageColumn' => 'Page',
                    'MetaEditorTitleColumn' => 'Meta Title',
                    'MetaEditorDescriptionColumn' => 'Meta Description',
                    'MetaEditorPageLinkColumn' => ''
                ]
            );

            $config->addComponent(new MetaEditorPageColumn());
            $config->addComponent(new MetaEditorTitleColumn());
            $config->addComponent(new MetaEditorDescriptionColumn());
            $config->addComponent(new MetaEditorPageLinkColumn());
        }

        return $form;
    }

    public function getSearchContext()
    {
        $context = parent::getSearchContext();

        $fields = FieldList::create(
            TextField::create('Search', 'Search page, meta title & description'),
            CheckboxField::create('PagesWithWarnings', 'Pages with warnings')
        );

        $context->setFields($fields);

        return $context;
    }

    /**
     * Get the list for the GridField
     *
     * @return SS_List
     */
    public function getList()
    {
        $list = parent::getList();

        $parent_id = $this->request->requestVar('ParentID') ? : 0;

        $search_filter = false;

        if (!empty($this->request->requestVar('Search'))) {
            $search = $this->request->requestVar('Search');
            $search_filter = true;
            $list = $list->filterAny([
                'MenuTitle' . ':PartialMatch' => $search,
                $this->config()->meta_title_field . ':PartialMatch' => $search,
                $this->config()->meta_description_field . ':PartialMatch' => $search
            ]);
        }
        if (!empty($this->request->requestVar('EmptyMetaDescriptions'))) {
            $search_filter = true;
            $list = $list->where($this->config()->meta_description_field . ' IS NULL');
        }
        if (!empty($this->request->requestVar('PagesWithWarnings'))) {
            $search_filter = true;
            $list = $list->filterByCallback(function ($item) {
                if (
                    !empty(MetaEditorTitleColumn::getErrors($item)) ||
                    !empty(MetaEditorDescriptionColumn::getErrors($item))
                ) {
                    return true;
                }
                return false;
            });
        }


        if ($this->config()->hide_page_types) {
            $ignore = [];
            foreach ($this->config()->hide_page_types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);
                $ignore = array_merge(array_keys($subclasses), $ignore);
            }
            if (!empty($ignore)) {
                $list = $list->exclude('ClassName', $ignore); // remove error pages etc
            }
        }

        if (!$search_filter) {
            $list = $list->filter('ParentID', $parent_id);
        }

        return $list;
    }
}
