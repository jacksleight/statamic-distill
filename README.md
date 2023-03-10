<!-- statamic:hide -->

![Statamic](https://flat.badgen.net/badge/Statamic/3.4+/FF269E)
![Packagist version](https://flat.badgen.net/packagist/v/jacksleight/statamic-distill)
![License](https://flat.badgen.net/github/license/jacksleight/statamic-distill)

# Distill 

<!-- /statamic:hide -->

This Statamic addon allows you to query or index the individual values, sets and relations within your entries, from both root and deeply nested fields. It's useful for things like:

* Extracting all the text from multiple nested Bard fields
* Finding every asset referenced in a Replicator, or just the first image
* Filtering, sorting and paginating a Grid field just like a collection
* Adding individual sections of a page to your search index
* Plenty more!

## Installation

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

```bash
composer require jacksleight/statamic-distill
```

## Examples

### Find every image in the page

```antlers
{{ distill:page type="asset" is_image:is="true" }}
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
        <a href="{{ prev_page }}">⬅</a>
        <a href="{{ next_page }}">➡</a>
    {{ /paginate }}
{{ /distill:parts_list }}
```

### Add sections of a page to a search index

```php
// config/statamic/search.php
'searchables' => [
    'distill:collection:page:sections',
],
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
    {{ if is_distilled }}
        <a href="{{ item:source:url }}#{{ id }}">
            {{ title }}
        </a>
    {{ else }}
        ...
    {{ /if }}
{{ /search:results }}
```

## Usage

Distill recursively walks through the value you provide looking for items that match your criteria. It can find individual paragraphs and field values through to entire sets and references to other content.

For optimal performance you should use the `from`, `path`, `expand`, `limit` and `max_depth` parameters to restrict where it goes based on what you're looking for. These options don't just filter the final result, they tell Distill where to look and when to stop.

Distill can find references to other entries, terms, assets and users, but it will not walk into those objects.

### Distill Tag

The `{{ distill:* }}` tag accepts the following parameters:

* **from (string)**  
  The name of the source variable, _not the variable itself_.
* **type (string|array)**  
  The type to match, asterisks can be used as a wildcard and multiple types can be pipe delimited, options are:
  * `value:[fieldtype]` A field value
  * `set:[handle]` A Replicator or Bard set
  * `row` A Grid row
  * `node:[type]` A Bard node
  * `mark:[type]` A Bard mark
  * `entry` An entry
  * `term` A term
  * `asset` An asset
  * `user` A user
* **path (string|array)**  
  The path to match, asterisks can be used as a wildcard and multiple paths can be pipe delimited, paths themselves are dot delimited.
* **depth (integer)**  
  Sets both `max_depth` and `min_depth`.
* **min_depth (integer)**  
  The minimim depth to find items from.
* **max_depth (integer)**  
  The maximum depth to find items from.
* **expand (string|array, all)**  
  Which types to expand and walk into, asterisks can be used as a wildcard and multiple types can be pipe delimited, options are:
  * `set:[handle]`
  * `row`
  * `value:replicator`
  * `value:bard`
  * `value:grid`
  * `value:entries`
  * `value:terms`
  * `value:assets`
  * `value:users`
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
  Which stills to apply, multiple stills can be pipe delimited.
* **[conditions] (mixed)**  
  Any [where conditions](https://statamic.dev/conditions).

Each item returned includes an `info` object that contains the following values:

* **type** - Type of the item.
* **path** - Path to the item from the source.
* **name** - Field name/handle, applies to `value:*` types.
* **index** - Index of the item, applies to non `value:*` types.
* **source** - Original source value.
* **parent** - Parent item in the hierachy.
* **prev** - Previous sibling item in the hierachy.
* **next** - Next sibling item in the hierachy.

### Distill Bard Tag

The `distill:bard` tag returns Bard data only and in a format that is compatible with the `bard_*` modifiers.

### Distill Count Tag

The `distill:count` tag returns the number of results from a query.

### Stills

Stills are exactly the same as [query scopes](https://statamic.dev/extending/query-scopes-and-filters), but for Distill queries. You can create them by adding a new class in `app/Stills/*.php`. They have an `apply` method that receives the query builder object and an array of additional tag parameters.

### Search

Distill can add the results of a query to a search index, so they appear as their own individual search results. You can then use hash/fragment URLs to link to those items within the source page. Check out the example above. Search indexing queries use the whole entry as their source value, you can use the path parameter to target specific fields within the entry.

### Queries

You can query a value manually in PHP using the Distill facade. The query builder class has camel cased method names that match the tag parameters above, plus all the usual `where` methods:

```php
use JackSleight\StatamicDistill\Facades\Distill;

$youTubeVideos = Distill::from($value)
  ->type('set:video')
  ->where('url', 'like', '%youtube.com%')
  ->get();
```