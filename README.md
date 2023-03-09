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
* Loads more!

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
{{ distill:skus depth="1" paginate="10" :category:is="get:category" :sort="get:sort" }}
    {{ items }}
        {{ name }} {{ price }}
    {{ /items }}
    {{ paginate }}
        <a href="{{ prev_page }}">⬅</a>
        <a href="{{ next_page }}">➡</a>
    {{ /paginate }}
{{ /distill:skus }}
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
<a href="{{ drop:source:url }}#{{ id }}">
    {{ title }}
</a>
```
```antlers
{{# resources/views/pages/show.antlers.html #}}
<div id="{{ id }}">
    <h2>{{ title }}</h2>
    {{ content }}
</div>
```

## Usage

Distill works by walking through the value you provide looking for items that match your criteria. It can find individual paragraphs and field values through to entire sets and references to other content.

For optimal performance you should use the `from`, `path`, `expand`, `limit` and `max_depth` parameters to restrict where it goes based on what you're looking for. These options don't just filter the final result, they tell Distill where to look and when to stop.

Distill can find references to other entries, terms, assets and users, but it will not recursively walk into those objects.

### Distill Tag

The `{{ distill:* }}` tag accepts the following parameters:

* **from (mixed)**  
  The source value.
* **type (string, array)**  
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
* **path (string, array)**  
  The path to match, asterisks can be used as a wildcard and multiple paths can be pipe delimited, paths themselves are dot delimited.
* **depth (integer)**  
  Sets both `max_depth` and `min_depth`.
* **min_depth (integer)**  
  The minimim depth to find items from.
* **max_depth (integer)**  
  The maximum depth to find items from.
* **expand (string, array)**  
  Which types to expand and walk into, defaults to all, options are:
  * `set:*`
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
  The number of items per page.
* **sort (string)**  
  The sort order.
* **include_root (string)**  
  Whether to include the source value, defaults to false.
* **still (string)**  
  Which stills to apply, multiple stills can be pipe delimited.
* **[conditions] (mixed)**  
  Any [where conditions](https://statamic.dev/conditions).

The `from` parameter must be the name of the source variable passed as a string, this wont work: `:from="builder"`.

The query builder class has camel cased method names that match the parameters above.

### Distill Bard Tag

The `distill:bard` tag returns Bard data only and in a format that is compatible with the `bard_*` modifiers.

### Distill Count Tag

The `distill:count` tag returns the number of results from a query.

### Stills

Stills are exactly the same as [query scopes](https://statamic.dev/extending/query-scopes-and-filters), but for Distill queries. You can create them by adding a new class in `app/Stills/*.php`. They have an `apply` method that receives the query builder object and an array of additional tag parameters.

### Search

Distill allows you to add any individual page items to a search index, so they appear as their own search results. You can then use hash/fragment URLs to link to those pages using set IDs, slugs generated from your content, or some other method. Check out the example above for further details.