<?php

namespace Axllent\MetaEditor\Forms\GridField;

use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;
use SilverStripe\View\HTML;
use SilverStripe\View\SSViewer;

/**
 * Adds a "level up" link to a GridField table
 */
class GridFieldLevelup implements GridField_HTMLProvider
{

    /**
     * @var string
     */
    protected $linkSpec = '';

    /**
     * @var array Extra attributes for the link
     */
    protected $attributes = [];

    /**
     * @var Link content (inside html)
     */
    protected $content;

    /**
     *
     * @param integer $currentID - The ID of the current item; this button will find that item's parent
     */
    public function __construct()
    {
    }

    /**
     * @param GridField $gridField
     * @return array|null
     */
    public function getHTMLFragments($gridField)
    {
        // Attributes
        $attrs = array_merge($this->attributes, array(
            'href' => $this->linkSpec,
            'class' => 'cms-panel-link ss-ui-button font-icon-level-up grid-levelup',
        ));

        $linkTag = HTML::createTag('a', $attrs, $this->content);

        $forTemplate = new ArrayData(array(
            'UpLink' => DBField::create_field('HTMLFragment', $linkTag)
        ));

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return array(
            'before' => $forTemplate->renderWith($template),
        );
    }

    public function setAttributes($attrs)
    {
        $this->attributes = $attrs;
        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setLinkSpec($link)
    {
        $this->linkSpec = $link;
        return $this;
    }

    public function getLinkSpec()
    {
        return $this->linkSpec;
    }

    public function setContent($string)
    {
        $this->content = $string;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }
}
