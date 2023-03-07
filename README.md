<!-- statamic:hide -->

![Statamic](https://flat.badgen.net/badge/Statamic/3.4+/FF269E)
![Packagist version](https://flat.badgen.net/packagist/v/jacksleight/statamic-distill)
![License](https://flat.badgen.net/github/license/jacksleight/statamic-distill)

# Distill 

<!-- /statamic:hide -->

This Statamic addon allows you to query or index the individual values, sets and relations within your entries, from both root and deeply nested fields. This is useful for things like:

* Extracting all the text from multiple nested Bard fields
* Adding individual sections of a page to your search index
* Finding every asset referenced in a Replicator, or just the first image
* Filtering, sorting and paginating a Grid field just like a collection

## Installation

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

```bash
composer require jacksleight/statamic-distill
```

## Examples

## Usage

### Optimisation

By default Distill will walk through the entire value looking for items that match your criteria. You should use the `from`, `expand`, `limit` and `max_depth` parameters to restrict how far it goes based on what you're looking for. These options don't just filter the final result, they tell Distill when to stop looking.

Distill can find references to other entries, terms, assets and users, but it will not recursively walk into those objects.
