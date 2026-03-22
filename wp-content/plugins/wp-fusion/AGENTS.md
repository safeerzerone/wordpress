# WP Fusion - Agent Guide

Use this file as the single source of truth for AI agent behavior in this repo. It consolidates `CLAUDE.md` and `.cursor/rules/*`.

## Priority Order

1. All inline comments must end in a period.
2. Follow code-generation patterns in relevant rule files for the area you are editing.
3. For general inquiries, reference `CLAUDE.md`.

## Project Context

WP Fusion is a WordPress plugin that connects sites to 50+ CRMs and marketing platforms, syncing user data, tags, and custom fields. It uses an OOP architecture and many plugin integrations.

## Critical Notes

- Version is defined only in `readme.txt` (Stable tag). Gulp syncs it to plugin header and constants.
- Always use `@since x.x.x` for new methods/classes/properties (placeholder replaced at release time).
- Text domain is always `wp-fusion`.
- Maintain PHP 7.4+ and WordPress 4.6+ compatibility.
- Never log sensitive data (API keys, passwords).

## WordPress Coding Standards

- Tabs for indentation.
- Keep lines under 100 chars.
- Use Yoda conditions.
- Sanitize inputs and escape outputs.
- Use WP hooks (`add_action`, `add_filter`) and follow WP naming conventions.
- File header required for PHP files.
- Public methods/properties should not use `@access`.
- Inline comments must end with a period.

## Documentation Requirements

- PHPDoc for all public classes, methods, and properties.
- Document filters and actions.
- New `@since` tags must use `x.x.x`.

## Architecture Principles

- Keep classes focused and single-purpose.
- Maintain backward compatibility.
- Use dependency injection where possible.
- Cache expensive operations and minimize database queries.
- Use WP transients and object cache appropriately.

## Integration Patterns

### CRM Integrations (`includes/crms/*`)

- Directory: `includes/crms/{crm-slug}/`.
- Main class: `class-{crm-slug}.php`.
- Admin class: `class-{crm-slug}-admin.php`.
- Extend `WPF_CRM_Base` and implement required methods.
- Implement `handle_http_response()` for HTTP status errors.
- Transient cURL errors and duplicate/not_found recovery are handled automatically by the base class.

### Plugin Integrations (`includes/integrations/*`)

- Directory: `includes/integrations/{plugin-slug}/`.
- Main class: `class-{plugin-slug}.php` extending `WPF_Integrations_Base`.
- Larger integrations can split into admin/batch/courses/etc sub-classes.
- Use integration points like checkout, membership changes, form submissions, and CPT events.

## Error Handling

- Use `WP_Error` for failures.
- Log meaningful messages with `wpf_log()`.
- Handle API errors gracefully and provide safe fallbacks.

## Testing Expectations

### What to run (and when)

- Always run `phpcs` + `phpstan` for any new or modified PHP code.
- Use `phpcbf` to auto-fix PHPCS issues, then re-run `phpcs` to confirm clean.
- Run `composer test` (PHPUnit) only when CRM integrations are built or modified.

### Scoping (faster feedback)

PHPCS, PHPCBF, and PHPStan can all be run against specific files / directories.
Prefer scoping them to the PHP files you changed when possible.

- PHPCBF: `./vendor/bin/phpcbf --standard=phpcs.xml <files...>`
- PHPCS: `./vendor/bin/phpcs --standard=phpcs.xml <files...>`
- PHPStan: `./vendor/bin/phpstan analyse --memory-limit=2G <paths...>`

### What counts as “CRM integrations changed”

Run `composer test` when changes touch:

- Anything under `includes/crms/`
- CRM base classes (for example: `includes/crms/class-base.php`)
- CRM test coverage under `tests/includes/crms/`

## Tooling Guidance

### Codanna (code intelligence)

Use Codanna for semantic search, impact analysis, and relationships:

- Start with `semantic_search_with_context`.
- Use `analyze_impact` before modifying core functions.
- Use `find_symbol`, `find_callers`, `get_calls` for known symbols.
- Use `rg` for exact string matches only.
- Do not use `codebase_search` for WP Fusion.

### AutoMem (persistent memory)

Use memory MCP tools for project context, decisions, patterns, and key insights.
Follow the 3-phase pattern from `.cursor/rules/automem.mdc`.

#### 1) Conversation start: recall before acting

- Recall for project context, architecture, decisions, preferences, debugging,
  performance, integrations, refactors, and any "why" questions.
- Adapt recall to open files, error signatures, and related components.
- Skip memory only for trivial edits, pure syntax questions, or direct file reads.

#### 2) During work: store durable outcomes

- Decisions (importance 0.9): architecture, libraries, strategy.
- Insights (importance 0.8): root cause, fixes, learnings.
- Patterns (importance 0.7): reusable approaches or best practices.
- Preferences (importance 0.6-0.8): style, tooling, constraints.
- Context (importance 0.5-0.7): general changes worth remembering.

Content format: "Brief title. Context and details. Impact/outcome."

Tags (always include):

- `wp-fusion`
- A component tag (for example: `auth`, `crm`, `admin`, `integrations`)

Type and confidence:

- Provide `type` and `confidence` when you are sure about the classification.
- Avoid storing trivial or noisy changes (typos, formatting, comments).

#### 3) Conversation end: summarize if needed

- Store a summary when multiple files change, refactors land, or new features ship.
- Use update instead of duplicate memories when refining prior context.
- Associate related memories to keep the graph connected.
- If memory tools fail or return empty, continue without blocking work.

## File Map (Quick References)

- `wp-fusion.php` - Main plugin singleton.
- `includes/crms/class-base.php` - CRM base class.
- `includes/integrations/class-base.php` - Integration base class.
- `includes/class-user.php` - User/CRM sync.
- `includes/class-access-control.php` - Tag-based restriction.
- `includes/admin/class-batch.php` - Batch processing.
- `includes/admin/class-settings.php` - Settings management.
