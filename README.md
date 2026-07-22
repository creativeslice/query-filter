# Query Loop Filters

![image](https://github.com/user-attachments/assets/85358de8-0929-47fe-85f5-b53a59fb522e)

This plugin allows you to easily add filters to any query loop block.

Provides 2 new blocks that can be added within a query loop block to allow filtering by either post type or a taxonomy. Also supports using the core search block to allow you to search.

Compatible with both the core query loop block and the [Advanced query loop plugin](https://wordpress.org/plugins/advanced-query-loop/) (In fact, in order to use post type filters, use of the Advanced Query Loop plugin is required). 

Easy to use and lightweight, built using the WordPress Interactivity API.

## Creative Slice fork

Fork of [humanmade/query-filter](https://github.com/humanmade/query-filter), tracking upstream **v0.2.4**.

Fork additions: taxonomy filters can render as buttons with multiple selections, a single-select mode, and a custom dropdown style. Taxonomy filter options are scoped to the posts the query loop actually covers.

Versions use the Creative Slice `YY.MM.DD` scheme and are independent of upstream's numbering, which is recorded above. Earlier releases used a `0.2.x-cslice.<date>` form; that is a semver pre-release, so it sorted below the upstream version it was already ahead of.

## Usage

* Add a query block. This can anyhere that the query block is supported e.g. page, template, or pattern.
* Add one of the filter blocks and configure as required:
    * Taxonomy filter. Select which taxonomy to to use, customise the label (and whether it's shown), and customise the text used when none is selected.
    * Post type filter. Customise the label (and whether it's shown), as well as the text used when no filter is applied.
    * Search block. No extra options.
 
![image](https://github.com/user-attachments/assets/e2f9b62d-91f7-4c22-87ac-078b4d031a60)

## Installation

### Using Composer

This plugin is available on packagist.

`composer require humanmade/query-filter`

### Manually from Github. 

1. Download the plugin from the [GitHub repository](https://github.com/humanmade/query-filter).
2. Upload the plugin to your site's `wp-content/plugins` directory.
3. Activate the plugin from the WordPress admin.

## CHANGELOG

Noteworthy changes only. Releases before this changelog are in the git log.

### 2026-07-16

* Taxonomy filter options are now scoped to the posts the query loop covers. `get_terms()`'s `hide_empty` is site-global, so it only hides a term with zero posts anywhere and an archive offered every term on the site, including ones matching nothing in the loop. Inherit mode only; non-inherit loops are unchanged. The scope query is capped at 5000 posts, filterable via `query_filter_scope_post_limit`.
* Fixed an archive losing its identity when filtered on its own taxonomy. Core back-fills `cat`/`category_name` from the first `tax_query` clause, so a category filter on a category archive retargeted `get_queried_object()` to the filtered term and the template hierarchy resolved a different template.
* The archive's own term is no longer offered as a filter option. It narrows nothing and duplicates the empty ("All") choice.
* The WP 7.0 `taxQuery` block attribute shape (`{"include":{…},"exclude":{…}}`) is now read alongside the pre-7.0 shape (`{"category":[4]}`). The block's term narrowing had been silently inert on blocks saved on WP 7.0+. Blocks do not need re-saving, and `exclude` is honoured.
* Adopted the Creative Slice `YY.MM.DD` version scheme.
