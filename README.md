<!-- statamic:hide -->

![Statamic](https://flat.badgen.net/badge/Statamic/3.4+/FF269E)
![Packagist version](https://flat.badgen.net/packagist/v/jacksleight/statamic-distill)
![License](https://flat.badgen.net/github/license/jacksleight/statamic-distill)

# Distill 

<!-- /statamic:hide -->

This Statamic addon allows you to query or index the individual values, sets and relations within your entries, from both root and deeply nested fields.

This is useful for things like:

* Extracting all the text from multiple nested Bard fields
* Adding individual sections of a page to your search index
* Finding every asset referenced in a Replicator, or just the first image
* Filtering, sorting and paginating a Grid field, just like you can with a collection

## Installation

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

```bash
composer require jacksleight/statamic-distill
```

## Examples

## Usage

### Optimisation

By default Distill will walk through the entire value and into many fieldtypes. Whenever possible you should use the `limit`, `depth`, `length` and `expand` parameters to restrict how far it goes. For example, the `limit` parameter doesn’t just slice the final result set, it tells Distill to stop when enough results have been found.
