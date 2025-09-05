<?php

namespace Axllent\MetaEditor\Forms\GridField;

use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\HTML;
use SilverStripe\View\SSViewer;

/**
 * Adds a "level up" link to a GridField table
 */
class GridFieldLevelup implements GridField_HTMLProvider
{
    /**
     * LinkSpec
     *
     * @var string
     */
    protected $linkSpec = '';

    /**
     * Attributes
     *
     * @var array Extra attributes for the link
     */
    protected $attributes = [];

    /**
     * Content
     *
     * @var Link content (inside html)
     */
    protected $content;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Get HTML Fragment
     *
     * @param GridField $gridField GridField
     *
     * @return null|array
     */
    public function getHTMLFragments($gridField)
    {
        // Attributes
        $attrs = array_merge(
            $this->attributes,
            [
                'href'  => $this->linkSpec,
                'class' => 'cms-panel-link ss-ui-button font-icon-level-up grid-levelup',
            ]
        );

        $linkTag = HTML::createTag('a', $attrs, $this->content);

        if (class_exists('\SilverStripe\View\ArrayData')) {
            // Silverstripe <= 5
            $forTemplate = \SilverStripe\View\ArrayData::create(
                [
                    'UpLink' => DBField::create_field('HTMLFragment', $linkTag),
                ]
            );
        } else {
            // Silverstripe 6
            $forTemplate = ArrayData::create(
                [
                    'UpLink' => DBField::create_field('HTMLFragment', $linkTag),
                ]
            );
        }

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);

        return [
            'before' => $forTemplate->renderWith($template),
        ];
    }

    /**
     * Set Attributes
     *
     * @param array $attrs array
     *
     * @return self
     */
    public function setAttributes($attrs)
    {
        $this->attributes = $attrs;

        return $this;
    }

    /**
     * Return attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set Link Spec
     *
     * @param string $link Link
     *
     * @return self
     */
    public function setLinkSpec($link)
    {
        $this->linkSpec = $link;

        return $this;
    }

    /**
     * Get Link Spec
     *
     * @return string
     */
    public function getLinkSpec()
    {
        return $this->linkSpec;
    }

    /**
     * Set content
     *
     * @param string $string Content
     *
     * @return self
     */
    public function setContent($string)
    {
        $this->content = $string;

        return $this;
    }

    /**
     * Get Content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
