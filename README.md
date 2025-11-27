<!-- statamic:hide -->

# Distill 

<!-- /statamic:hide -->

This Statamic addon allows you to filter, fetch or index the individual values, sets and relations within your entries, from both root and deeply nested fields. It's useful for things like:

* Extracting the text from every Bard field within a replicator
* Finding every asset in the page, or just the first image
* Filtering, sorting and paginating a Grid field or raw array just like a collection
* Adding individual sections of a page to a search index
* Refactoring nested data and sets

## Installation

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

```bash
composer require jacksleight/statamic-distill
```

## Examples

### Find every image in the page

```antlers
{{ distill:page type="asset:*" is_image:is="true" unique="true" }}
    {{ url }}
{{ /distill:page }}
```

### Extract all Bard text from a page builder

```antlers
{{ { distill:bard from="builder" } | bard_text }}
```

### Filter, sort and paginate a Grid field

```antlers
{{ distill:parts_list depth="1" paginate="10" :category:is="get:category" :sort="get:sort" }}
    {{ items }}
        {{ name }} {{ price }}
    {{ /items }}
    {{ paginate }}
        <a href="{{ prev_page }}">←</a>
        <a href="{{ next_page }}">→</a>
    {{ /paginate }}
{{ /distill:parts_list }}
```

## Usage

Distill recursively walks through the value you provide looking for items that match your criteria. It can find individual paragraphs and field values through to entire sets and references to other content.

For optimal performance you should use the `from`, `path`, `expand`, `limit` and `max_depth` parameters to restrict where it goes based on what you're looking for. These options don't just filter the final result, they tell Distill where to look and when to stop.

Distill can find references to other entries, terms, assets and users, but it will not walk into those objects. Additionally Distill will only walk raw values if you provide one, it will not walk into a field's raw value.

### Distill Tag

The `{{ distill:* }}` tag accepts the following parameters:

* **from (string)**  
  The name of the source variable (_not the variable itself_), can be a single field or raw value or an entry, term, asset or user.
* **type (string|array)**  
  The type to match, asterisks can be used as a wildcard and multiple types can be pipe delimited, options are:
  * `value:[fieldtype]` - A field value
  * `set:[handle]` - A Replicator or Bard set
  * `row:[distill-type]` - A Grid row (type can be defined in the field config)
  * `node:[type]` - A Bard node
  * `mark:[type]` - A Bard mark
  * `entry:[collection]` - An entry
  * `term:[taxonomy]` - A term
  * `asset:[container]` - An asset
  * `user` - A user
  * `raw:[php-type]` - A raw value (inc. stdClass)
  * `class:[php-class]` - Any other object (exc. stdClass)
* **path (string|array)**  
  The path to match, asterisks can be used as a wildcard and multiple paths can be pipe delimited, paths themselves are dot delimited.
* **depth (integer)**  
  Sets both `max_depth` and `min_depth`.
* **min_depth (integer)**  
  The minimim depth to find items from.
* **max_depth (integer)**  
  The maximum depth to find items from.
* **unique (boolean, false)**  
  Filter out duplicate items. Filtering is only applied to these types:
  * `entry`
  * `term`
  * `asset`
  * `user`
* **expand (string|array, all)**  
  Which types to expand and walk into, asterisks can be used as a wildcard and multiple types can be pipe delimited, options are:
  * `set:[handle]`
  * `row:[distill-type]`
  * `value:replicator`
  * `value:bard`
  * `value:grid`
  * `value:entries`
  * `value:terms`
  * `value:assets`
  * `value:users`
  * `raw:array`
  * `raw:object`
* **limit (integer)**  
  The maximum number of items.
* **offset (integer)**  
  The starting item offset.
* **paginate (integer)**  
  Enables pagination and sets the number of items per page.
* **sort (string)**  
  The sort order.
* **include_source (boolean, false)**  
  Whether to include the source value.
* **still (string)**  
  Which still to apply, multiple stills can be pipe delimited.
* **[conditions] (mixed)**  
  Any [where conditions](https://statamic.dev/conditions).

Each item returned includes an `info` object that contains the following values:

* **type** - Type of the item.
* **path** - Path to the item from the source.
* **name** - Field handle/value index.
<!-- * **index** - Index of the item, applies to non `value:*` types. -->
* **source** - Original source value.
* **parent** - Parent item in the hierachy.
<!-- * **prev** - Previous sibling item in the hierachy.
* **next** - Next sibling item in the hierachy. -->

### Distill Text Tag & Modifier

The `{{ distill:text }}` tag returns all plain text from `text`, `textarea`, `bard` and  `markdown` fields.

The `distill_text` modifier does the same thing, but must be passed the *name* of the field (as a string), not the field value itself.

### Distill Bard Tag & Modifier

The `{{ distill:bard }}` tag returns all Bard data in a format that is compatible with the `bard_*` modifiers.

The `distill_bard` modifier does the same thing, but must be passed the *name* of the field (as a string), not the field value itself.

### Distill Count Tag

The `{{ distill:count }}` tag returns the number of results from a query.

### Stills

Stills are exactly the same as [query scopes](https://statamic.dev/extending/query-scopes-and-filters), but for Distill queries. You can create them by adding a new class in `app/Stills/*.php`. They have an `apply` method that receives the query builder object and array of tag parameters.

### Usage in PHP

#### Query Builder

You can query a value manually in PHP using the `Distill::query()` method. The query builder class has camel cased method names that match the tag parameters above, plus all the usual `where` methods:

```php
use JackSleight\StatamicDistill\Facades\Distill;

$youtubeVideoSets = Distill::query($value)
    ->type('set:video')
    ->where('url', 'like', '%youtube.com%')
    ->get();
```

#### Bard & Text Values

You can extract Bard data and plain text manually in PHP using the `Distill::bard()` and `Distill::text()` methods. For example to create a plain text value of a page builder you could do one of these: 

```php
use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Facades\Collection;
use Statamic\Statamic;

$value = $entry->augmentedValue('content');
$data = Distill::bard($value);

$value = $entry->augmentedValue('builder');
$text = Distill::text($value);
```

### Search Integration

Distill can add the results of a query to a search index, so they appear as their own individual search results. You can then use hash/fragment URLs to link to those items within the source page. Items from entries and terms are supported. 

```php
// config/statamic/search.php
'searchables' => [
    'distill:collection:pages:sections',
],
'fields' => ['heading'],
```
```php
// app/Stills/Sections.php
namespace App\Stills;

use JackSleight\StatamicDistill\Stills\Still;

class Sections extends Still
{
    public function apply($query, $values)
    {
        $query->path('builder.*')->type('set:section');
    }
}
```
```antlers
{{# resources/views/search.antlers.html #}}
{{ search:results }}
    {{ if result_type === 'distill:set:section' }}
        <a href="{{ info:source:url }}#{{ id }}">
            {{ title }}
        </a>
    {{ else }}
        ...
    {{ /if }}
{{ /search:results }}
```

Here's a brief explanation of what that's doing:

1. `distill:collection:articles:sections` - Use the Sections still to extract items from entries within the articles collection
2. `$query->path('builder.*')` - Extract all the items that are direct children of the "builder" field (the sets)
3. `->type('set:section')` - Only extract the sets of type "section"
4. `'fields' => ['heading']` - Index the "heading" field from those sets

When a search is run the section headings will be searched, and any matching sets will be returned as results.

<!-- statamic:hide -->

## Sponsoring 

This addon is completely free to use. However fixing bugs, adding features and helping users takes time and effort. If you find this addon useful and would like to support its development any [contribution](https://github.com/sponsors/jacksleight) would be greatly appreciated. Thanks! 🙂

<!-- /statamic:hide -->
