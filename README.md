# ARK Identifier Resolver

ARK Identifier Resolver is a WordPress plugin for resolving ARK-style identifiers in the form:

- `/ark:/12345/example-name`
- `/ark:/12345/example-name/qualifier`

It supports:

- preserving the ARK URL in-browser using an embedded page
- HTTP 302/303 redirects to the final destination
- a dashboard for creating and managing ARK mappings

## Installation

1. Upload the plugin files to the `/wp-content/plugins/ark-id/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the WordPress admin panel.
3. Navigate to Settings → ARK Resolver.
4. Add one or more ARK mappings.

## Example

If you create a record like:

- NAAN: `12345`
- Name: `example-document`
- Target URL: `https://example.com/docs/report.pdf`

Then the resolver will handle requests to:

- `https://your-site.example/ark:/12345/example-document`

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Repository

- GitHub: https://github.com/jkunze/ark-id
