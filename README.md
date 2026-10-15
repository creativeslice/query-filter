# Query Loop Filters

![image](https://github.com/user-attachments/assets/85358de8-0929-47fe-85f5-b53a59fb522e)

This plugin allows you to easily add filters to any query loop block.

Provides 2 new blocks that can be added within a query loop block to allow filtering by either post type or a taxonomy. Also supports using the core search block to allow you to search.

Compatible with both the core query loop block and the [Advanced query loop plugin](https://wordpress.org/plugins/advanced-query-loop/) (In fact, in order to use post type filters, use of the Advanced Query Loop plugin is required). 

Easy to use and lightweight, built using the WordPress Interactivity API.

## Creative Slice fork

Fork of [humanmade/query-filter](https://github.com/humanmade/query-filter), merged with upstream **v0.5.0** (source commit 6455782). Upstream's files stay as upstream ships them, apart from a few hook points marked `CS:`. Creative Slice code lives in `inc/creative-slice/`, `src/cs-style.scss`, `src/taxonomy/cs-*`, `src/sort/`, and `src/clear/`.

Fork additions: taxonomy filters can render as buttons with multiple selections, a single-select mode, and a custom dropdown style. Taxonomy filter options are scoped to the posts the query loop actually covers.

Versions use the Creative Slice `YY.MM.DD` scheme and are independent of upstream's numbering, which is recorded above. Earlier releases used a `0.2.x-cslice.<date>` form; that is a semver pre-release, so it sorted below the upstream version it was already ahead of.

Upstream's Installation, Local Development, Testing, and Release Process sections below describe humanmade/query-filter. This fork commits `build/`, runs no GitHub workflows, and releases as described under Building a release.

### Updating a site

No site deploys this plugin automatically. That is deliberate: an earlier update broke things, so each site is updated by hand.

1. A site hosted on WordPress.com connects this repository under Hosting > Repositories with Auto Deploy off, and deploys `main` by hand from that screen. Every other site installs the `query-filter-{version}.zip` attached to a tagged `vYY.MM.DD` GitHub release, never a branch checkout or a folder copied from another site, so the version on its Plugins screen names the code it runs. `npm run release` builds the zip.
2. Read every changelog entry between the site's current version and the new one, since a site can skip several releases, and follow each entry's upgrade notes.
3. Update staging or a local copy first and check every page with a filter, sort, or search, then update production.
4. Purge the site's page cache after updating production. A page cached before the update keeps the old markup, which the new script may not drive.

### Building a release

1. Never rename or remove a block, attribute, CSS class, CSS custom property, Interactivity action, or URL parameter, and never change what a saved attribute means or what its default is. New behavior goes behind a new attribute that defaults to the old behavior, so one release is safe on every site.
2. Before tagging, test one page with every mode: select, checkbox, radio, buttons, single-select and multi-select dropdowns, sort, clear filters, and search, in both an inherited and a non-inherited query loop. Run `composer phpcs` and `npm run test:e2e`.
3. Give each changelog entry upgrade notes: what to re-check after updating, and which fixes every site should get. A release carrying fixes every site should get is installed on every site, rather than waiting for a site that needs a feature.
4. Set the Version header to the release date in the release PR, with `build/` rebuilt and committed, because WordPress.com sites deploy `main` as it stands. Merge the PR with a merge commit, then tag `vYY.MM.DD` on `main` and attach the zip from `npm run release`.

### Tracking upstream

1. Take each upstream release with `git merge` on a branch from `main`. Upstream's release tag points at a build commit on its `release` branch, so merge that commit's parent, the source commit on upstream's `main`.
2. Conflicts land on the lines marked `CS:` inside upstream files: keep upstream's change and the CS hook. Upstream deletes `build/` and edits `.github/workflows/`; keep the fork's side of both, `build/` committed and the workflows removed.
3. Never squash or rebase a pull request that merges upstream. The merge commit is what lets the next `git merge` start from upstream's history instead of conflicting everywhere.
4. Ported upstream code keeps Human Made's comments verbatim, British spelling included. A comment Creative Slice adds starts with `CS:` and uses US English.
5. Keep Creative Slice code in its own files, and mark each edit inside an upstream file with a `CS:` comment, so each merge meets fewer conflicts.
6. Offer generic features and extension hooks upstream first, so the fork shrinks over time.

## Usage

* Add a query block. This can anyhere that the query block is supported e.g. page, template, or pattern.
* Add one of the filter blocks and configure as required:
    * Taxonomy filter. Select which taxonomy to to use, customise the label (and whether it's shown), and customise the text used when none is selected. For a hierarchical taxonomy, choose how the tree is shown: a flat list (the default), nested beneath parents, or nested with each parent's children collapsed behind a toggle. Selecting a parent also matches posts in its children.
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

Built assets are not committed to `main`. Manual or Composer installs should track the `release` branch (or a tagged release), which contains the compiled `build` directory.

## Local Development

This project uses [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to run a lightweight, containerized WordPress instance at [localhost:3031](http://localhost:3031) for testing purposes. The default username for the localhost environment is `admin`, with the password `password`.

These commands can be used to interact with the environment:

Command | Purpose
---- | ----
`npm run env:start` | Start the local environment at http://localhost:3031
`npm run env:stop` | Turn off the local environment
`npm run env:cli -- wp ...` | Run WP-CLI commands within the environment
`npm run env:logs` | Open (and tail) the error logs for the application<sup>&ddagger;</sup>
`npm run env:db` | Open the database in the mysql command line
`npm run env:destroy` | Fully destroy the local environment (deletes container database)

<sup>&ddagger;</sup> This command deliberately filters out GET/OPTIONS/HEAD/POST/PUT access log entries

## Testing

End-to-end tests run against [WordPress Playground](https://wordpress.org/playground/), which boots in process — there is no environment to start or stop:

```bash
npm ci
npm run build   # tests run against build/, not src/
npm run test:e2e
```

Command | Purpose
---- | ----
`npm run test:e2e` | Run the Playwright suite
`npm run test:e2e:debug` | Step through tests in the Playwright inspector
`npm run test:e2e:watch` | Open the Playwright UI, rerunning on change
`npm run playground:start` | Boot the same environment for manual testing, without running tests

The environment is described by [blueprint.json](./blueprint.json) and is shared between the test run and manual use. See [tests/e2e/README.md](./tests/e2e/README.md) for the fixture content and how to write tests.

Every pull request also gets a **Preview in WordPress Playground** button added to its description, which boots that PR's build with demo content already in place — no local checkout needed to try a change.

## Release Process

Releases are cut by a manually-dispatched ["Release" GH Actions workflow](https://github.com/humanmade/query-filter/actions/workflows/release.yml), which builds the plugin, stamps the version into `query-filter.php`, and creates an immutable `vX.Y.Z` tag pointing at the built, versioned code. That tag is published to [Packagist](https://packagist.org/packages/humanmade/query-filter) and attached to a GitHub release as a downloadable ZIP.

To cut a new release:

1. Choose the target version number using [semantic versioning](https://semver.org/).
2. On the ["Release" GH Action page](https://github.com/humanmade/query-filter/actions/workflows/release.yml), click "Run workflow".
3. Fill out the "Version" field **without** a leading `v`, e.g. `1.2.3`.
4. Click "Run workflow".

There is no "prepare release" pull request — the version number is supplied to the workflow and never lives on `main`, where the `Version` header is always the literal placeholder `__VERSION__`.

Separately, merges to `main` automatically [build](https://github.com/humanmade/query-filter/actions/workflows/build-release-branch.yml) to the `release` branch. A project may track the `release` branch using [Composer](https://getcomposer.org/) to pull in the latest built beta version. The `release` branch is not versioned and is not involved in cutting a tagged release.

See [RELEASE.md](./RELEASE.md) for the full process, including what ships in the ZIP and how to cut a release by hand.

## CHANGELOG

Noteworthy changes only. Releases before this changelog are in the git log.

### 2026-10-08

Fixes ported from upstream v0.5.0. Each commit names the upstream commit it came from.

* An array value in a filter parameter, such as `?query-foo[]=x`, no longer throws a TypeError. The main query maps `query-*` parameters on every request, so any URL on a site running the plugin could return a 500.
* Filter parameters now accept only publicly queryable taxonomies and post types, so a URL can no longer point a loop at a private taxonomy or post type. A filter block for a taxonomy or post type that is not publicly queryable no longer renders.
* Search: the form drops the loop's page parameter, so a search started from page 2 no longer renders an empty loop. The field value is no longer decoded twice, which had turned a searched "C++" into "C". On the search template, an inherited loop's search field is named `s`; archives keep `query-s`, because `?s=` would switch an archive to the search template.
* All filter actions are wrapped in `withSyncEvent()`, and router navigation waits 30 seconds before falling back to a full page load. Requires WordPress 6.8 or newer.
* The post type filter no longer warns when the query block's context has no `inherit` key, and its editor preview no longer logs a React warning about `inert`.
* Built with `@wordpress/scripts` 36.1.0.

### 2026-07-16

* Taxonomy filter options are now scoped to the posts the query loop covers. `get_terms()`'s `hide_empty` is site-global, so it only hides a term with zero posts anywhere and an archive offered every term on the site, including ones matching nothing in the loop. Inherit mode only; non-inherit loops are unchanged. The scope query is capped at 5000 posts, filterable via `query_filter_scope_post_limit`.
* Fixed an archive losing its identity when filtered on its own taxonomy. Core back-fills `cat`/`category_name` from the first `tax_query` clause, so a category filter on a category archive retargeted `get_queried_object()` to the filtered term and the template hierarchy resolved a different template.
* The archive's own term is no longer offered as a filter option. It narrows nothing and duplicates the empty ("All") choice.
* The WP 7.0 `taxQuery` block attribute shape (`{"include":{…},"exclude":{…}}`) is now read alongside the pre-7.0 shape (`{"category":[4]}`). The block's term narrowing had been silently inert on blocks saved on WP 7.0+. Blocks do not need re-saving, and `exclude` is honoured.
* Adopted the Creative Slice `YY.MM.DD` version scheme.
