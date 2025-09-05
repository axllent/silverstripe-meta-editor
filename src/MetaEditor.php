<?php

namespace Axllent\MetaEditor;

use Axllent\MetaEditor\Forms\GridField\GridFieldLevelup;
use Axllent\MetaEditor\Forms\MetaEditorDescriptionColumn;
use Axllent\MetaEditor\Forms\MetaEditorPageColumn;
use Axllent\MetaEditor\Forms\MetaEditorPageLinkColumn;
use Axllent\MetaEditor\Forms\MetaEditorTitleColumn;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\View\Requirements;
use TractorCow\Fluent\Extension\FluentSiteTreeExtension;
use TractorCow\Fluent\Model\Locale;

class MetaEditor extends ModelAdmin
{
    /**
     * Meta title field
     *
     * @config string
     */
    private static $meta_title_field = 'Title';

    /**
     * Meta title minimum length
     *
     * @var int
     */
    private static $meta_title_min_length = 20;

    /**
     * Meta title maximum length
     *
     * @var int
     */
    private static $meta_title_max_length = 70;

    /**
     * Meta description field
     *
     * @config string
     */
    private static $meta_description_field = 'MetaDescription';

    /**
     * Meta description field minimum length
     *
     * @var int
     */
    private static $meta_description_min_length = 100;

    /**
     * Meta description field maximum length
     *
     * @var int
     */
    private static $meta_description_max_length = 200;

    /**
     * Non-editable pages (includes all classes extending these)
     *
     * @config array
     */
    private static $non_editable_page_types = [
        'SilverStripe\CMS\Model\RedirectorPage',
        'SilverStripe\CMS\Model\VirtualPage',
    ];

    /**
     * Hidden pages (includes all classes extending these)
     * Note that these (or their children) are not displayed
     *
     * @config array
     */
    private static $hidden_page_types = [
        'SilverStripe\ErrorPage\ErrorPage',
    ];

    /**
     * CMS menu title
     *
     * @var string
     */
    private static $menu_title = 'Meta Editor';

    /**
     * CMS url segment
     *
     * @var string
     */
    private static $url_segment = 'meta-editor';

    /**
     * CMS menu icon
     *
     * @var string
     */
    private static $menu_icon = 'axllent/silverstripe-meta-editor: images/MetaEditor.svg';

    /**
     * CMS managed modals
     *
     * @var array
     */
    private static $managed_models = [
        SiteTree::class,
    ];

    /**
     * Init
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        Requirements::css('axllent/silverstripe-meta-editor: css/meta-editor.css');
        Requirements::javascript('axllent/silverstripe-meta-editor: javascript/meta-editor.js');
    }

    /**
     * Get edit form
     *
     * @param int   $id     ID
     * @param array $fields Array
     *
     * @return mixed
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridFieldName = $this->sanitiseClassName($this->modelClass);

        $grid = $form->Fields()->dataFieldByName($gridFieldName);
        if ($grid) {
            $config = $grid->getConfig();
            $config->removeComponentsByType(GridFieldAddNewButton::class);
            $config->removeComponentsByType(GridFieldPrintButton::class);
            $config->removeComponentsByType(GridFieldEditButton::class);
            $config->removeComponentsByType(GridFieldExportButton::class);
            $config->removeComponentsByType(GridFieldImportButton::class);
            $config->removeComponentsByType(GridFieldDeleteAction::class);
            $config->removeComponentsByType(GridField_ActionMenu::class);
            $config->removeComponentsByType(GridField_ActionMenuItem::class);

            $parent_id = $this->request->requestVar('ParentID') ?: 0;

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

            $config->getComponentByType(GridFieldDataColumns::class)
                ->setDisplayFields(
                    [
                        'MetaEditorPageColumn'        => 'Page',
                        'MetaEditorTitleColumn'       => 'Meta Title',
                        'MetaEditorDescriptionColumn' => 'Meta Description',
                        'MetaEditorPageLinkColumn'    => '',
                    ]
                );

            $config->addComponent(new MetaEditorPageColumn());
            $config->addComponent(new MetaEditorTitleColumn());
            $config->addComponent(new MetaEditorDescriptionColumn());
            $config->addComponent(new MetaEditorPageLinkColumn());
        }

        return $form;
    }

    /**
     * Get the list for the GridField
     *
     * @return SS_List
     */
    public function getList()
    {
        $list = parent::getList();

        $parent_id = $this->request->requestVar('ParentID') ?: 0;

        $search_filter = false;

        $conf = $this->config();

        if (!empty($this->request->requestVar('Search'))) {
            $search        = $this->request->requestVar('Search');
            $search_filter = true;
            $list          = $list->filterAny(
                [
                    'MenuTitle:PartialMatch'                        => $search,
                    $conf->meta_title_field . ':PartialMatch'       => $search,
                    $conf->meta_description_field . ':PartialMatch' => $search,
                ]
            );
        }
        if (!empty($this->request->requestVar('EmptyMetaDescriptions'))) {
            $search_filter = true;
            $list          = $list->where(
                $this->config()->meta_description_field . ' IS NULL'
            );
        }
        if (!empty($this->request->requestVar('PagesWithWarnings'))) {
            $search_filter = true;
            $list          = $list->filterByCallback(
                function ($item) {
                    if (!empty(MetaEditorTitleColumn::getErrors($item))
                        || !empty(MetaEditorDescriptionColumn::getErrors($item))
                    ) {
                        return true;
                    }

                    return false;
                }
            );
        }

        if ($this->config()->hidden_page_types) {
            $ignore = [];
            foreach ($this->config()->hidden_page_types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);
                $ignore     = array_merge(array_keys($subclasses), $ignore);
            }
            if (!empty($ignore)) {
                // remove error pages etc
                $list = $list->exclude('ClassName', $ignore);
            }
        }

        if (!$search_filter) {
            $list = $list->filter('ParentID', $parent_id);
        }

        $fluent = Injector::inst()->get(SiteTree::class)
            ->hasExtension(FluentSiteTreeExtension::class) && Locale::get()->count();

        if ($fluent) {
            $list = $list->filterByCallBack(
                function ($page) {
                    return $page->existsInLocale();
                }
            );
        }

        return $list->count() ? $list : SiteTree::get()->filter('ID', 0);
    }
}
