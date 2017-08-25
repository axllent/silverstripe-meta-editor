# SilverStripe Meta Editor Configuration

To access the Meta Editor users should be granted CMS access to the 'Meta Editor' or have 'full
administrative rights'. They do not require read or write access to the pages themselves.

```yaml
Axllent\MetaEditor\MetaEditor:
  meta_title_field: "Title"
  meta_title_min_length: 20
  meta_title_max_length: 70
  meta_description_field: "MetaDescription"
  meta_description_min_length: 100
  meta_description_max_length: 200
  non_editable_page_types:
    - SilverStripe\CMS\Model\RedirectorPage
    - SilverStripe\CMS\Model\VirtualPage
  hidden_page_types:
    - SilverStripe\ErrorPage\ErrorPage
```

## meta_title_field

The database field used for the meta title

## meta_title_min_length / meta_title_max_length

The minimum and maximum length the meta title should be

## meta_description_field

The database field used for the meta description

## meta_description_min_length / meta_description_max_length

The minimum and maximum length the meta description should be

## non_editable_page_types

Array of page classes to prevent meta title/description editing. These will still be shown, but will not
be editiable. **Note**: Classes are inherited, so any class extending these will also not be editable.

## hidden_page_types

Array of page classes to hide from the editor.
**Note**: Classes are inherited, so any class extending these will also not be shown. This includes children,
even if they themselves are editable.
