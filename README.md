<!-- statamic:hide -->

![Statamic](https://flat.badgen.net/badge/Statamic/3.4+/FF269E)
![Packagist version](https://flat.badgen.net/packagist/v/jacksleight/statamic-distill)
![License](https://flat.badgen.net/github/license/jacksleight/statamic-distill)

# Distill 

<!-- /statamic:hide -->

This Statamic addon allows you to run queries against the flat and nested values within your entries, like the data a page builder might generate. Some examples of what’s possible:

* Extract the text from all Bard fields within a page builder, or just the headings
* Find every asset in a replicator, or just the first image
* Merge similar field values from different sets together 
* Filter, sort and paginate a grid field just like you can with a collection

## Installation

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

```bash
composer require jacksleight/statamic-distill
```