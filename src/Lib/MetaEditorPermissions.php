<?php

namespace Axllent\MetaEditor\Lib;

use Axllent\MetaEditor\MetaEditor;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;

/**
 * Basic permissions class to determine whether a page's meta tags are editable
 */
class MetaEditorPermissions
{
    /**
     * Non-editable page types
     *
     * @var array
     */
    private static $non_editable_page_types;

    /**
     * Can Edit
     *
     * @param SiteTree $page page
     *
     * @return bool
     */
    public static function canEdit($page)
    {
        if (!self::$non_editable_page_types) {
            $types = Config::inst()
                ->get(MetaEditor::class, 'non_editable_page_types');

            self::$non_editable_page_types = [];

            foreach ($types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);

                self::$non_editable_page_types = array_merge(
                    array_keys($subclasses),
                    self::$non_editable_page_types,
                );
            }
        }

        return !in_array(
            strtolower($page->ClassName),
            self::$non_editable_page_types,
        );
    }
}
