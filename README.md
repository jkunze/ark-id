# WordPress plugin for local ARK identifier resolution

The `ark-id` plugin lets your WordPress site act become a local Archival Resource Key ([ARK](https://arks.org)) identifier resolver. Your local resolver can serve ARKs (as URLs) based at your domain name as well as ARKs received (redirected) from the global ARK resolver at [N2T.net](https://n2t.net). For example, if your [NAAN](https://arks.org/about/ark-naans-and-systems/) is 12345, it could resolve these ARKs:

- `https://mydomain.example.com/ark:12345/ExampleName`
- `https://n2t.net/ark:12345/ExampleName`
- `/ark:/12345/example-name/qualifier`

It supports ExampleNames with up to two levels of hierarchy (at most one slash (/)). (_xxx can this be generalized to deeper levels and query strings?_) It also supports modern-form and classic-form ARKs (_xxx currently only classic-form_)

- preserving the ARK URL in-browser using an embedded page
- HTTP 302/303 redirects to the final destination
- a dashboard for creating and managing ARK mappings


## Installation

1. Upload the plugin files to the `/wp-content/plugins/ark-id/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the WordPress admin panel.
3. Navigate to Settings → ARK Resolver.
4. Add one or more ARK mappings.

## Example

If your NAAN is 12345 and you create a record with,

- Name: `b261201`
- Target URL: `https://mydomain.example.com/docs/report.pdf`

Then the resolver will deal with any of these,

- `https://mydomain.example.com/ark:12345/b261201`
- `https://n2t.net/ark:12345/b261201`
- [ark:12345/b261201](https://n2t.net/ark:12345/b261201) (hyperlinked to `https://n2t.net/ark:12345/b261201`)

by redirecting (resolving) them to `https://mydomain.example.com/docs/report.pdf`.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Repository

- GitHub: https://github.com/jkunze/ark-id
