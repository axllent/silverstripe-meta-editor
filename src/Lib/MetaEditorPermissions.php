<?php

namespace Axllent\MetaEditor\Lib;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\ClassInfo;

/**
 * Basic permissions class to determine whether a page's meta tags are editable
 */
class MetaEditorPermissions
{
    private static $non_editable_page_types;

    public static function canEdit($page)
    {
        if (!self::$non_editable_page_types) {
            $types = Config::inst()->get('Axllent\\MetaEditor\\MetaEditor', 'non_editable_page_types');
            self::$non_editable_page_types = [];
            foreach ($types as $class) {
                $subclasses = ClassInfo::getValidSubClasses($class);
                self::$non_editable_page_types = array_merge(array_keys($subclasses), self::$non_editable_page_types);
            }
        }

        return !in_array($page->ClassName, self::$non_editable_page_types);
    }
}
