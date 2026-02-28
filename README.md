# Aurora CMS

A modern, entity-first, AI-native content management system built on PHP 8.3+ and Symfony 7.

Aurora replaces Drupal's legacy runtime with a clean, modular architecture organized as independent Composer packages. Every subsystem — entities, fields, config, caching, routing, access control — is a standalone package with explicit interfaces, no global state, and no hidden coupling.

## Architecture

Aurora is structured as 7 architectural layers with strict downward-only dependencies:

```
Layer 6  Interfaces     cli · ssr · admin
Layer 5  AI             ai-schema · ai-agent · ai-vector · ai-pipeline
Layer 4  API            api · graphql
Layer 3  Content Types  node · taxonomy · media · path · menu · workflows
Layer 2  Services       access · user · routing · queue · state · validation
Layer 1  Core Data      config · entity · field · entity-storage · database-legacy
Layer 0  Foundation     cache · plugin · typed-data
```

Three meta-packages provide convenient installation:

- **`aurora/core`** — Foundation + Core Data + Services (14 packages)
- **`aurora/cms`** — Core + Content Types + API + CLI (23 packages)
- **`aurora/full`** — CMS + AI + GraphQL + SSR (29 packages)

## Packages

| Layer | Package | Description |
|-------|---------|-------------|
| 0 | `aurora/cache` | Cache backends (Memory, Null) with tag-based invalidation |
| 0 | `aurora/plugin` | Attribute-based plugin discovery and management |
| 0 | `aurora/typed-data` | Typed data system with primitives, lists, and maps |
| 1 | `aurora/config` | Configuration management with import/export and events |
| 1 | `aurora/entity` | Entity type system with content and config entity bases |
| 1 | `aurora/field` | Field type definitions, items, and lists |
| 1 | `aurora/entity-storage` | SQL entity storage, queries, and schema management |
| 1 | `aurora/database-legacy` | PDO database abstraction with query builders |
| 2 | `aurora/access` | Permission-based access control with policy handlers |
| 2 | `aurora/user` | User entity, authentication, and session management |
| 2 | `aurora/routing` | Symfony-based routing with parameter upcasting |
| 2 | `aurora/queue` | Message queue with in-memory and sync backends |
| 2 | `aurora/state` | Key-value state storage |
| 2 | `aurora/validation` | Constraint-based entity validation |
| 3 | `aurora/node` | Node content type with access policies |
| 3 | `aurora/taxonomy` | Vocabulary and term hierarchies |
| 3 | `aurora/media` | Media entities with type-based handling |
| 3 | `aurora/path` | URL path aliases and resolution |
| 3 | `aurora/menu` | Menu links and tree building |
| 3 | `aurora/workflows` | Editorial workflow state machines |
| 4 | `aurora/api` | JSON:API resource layer with filtering, sorting, pagination |
| 4 | `aurora/graphql` | GraphQL schema generation from entity types |
| 5 | `aurora/ai-schema` | JSON Schema and MCP tool generation from entities |
| 5 | `aurora/ai-agent` | AI agent orchestration with tool execution and audit logging |
| 5 | `aurora/ai-vector` | Vector embedding storage and similarity search |
| 5 | `aurora/ai-pipeline` | AI processing pipelines with step orchestration |
| 6 | `aurora/cli` | Symfony Console commands for install, config, entities, scaffolding |
| 6 | `aurora/ssr` | Twig component renderer with server-side rendering |
| 6 | `aurora/admin` | React + Vite admin SPA scaffold |

## Requirements

- PHP 8.3 or later
- Composer 2.x

## Installation

```bash
composer create-project aurora/monorepo my-site
cd my-site
```

## Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Integration tests only
./vendor/bin/phpunit --testsuite Integration
```

## Project Stats

- **29** implementation packages + 3 meta-packages + 1 admin SPA
- **227** source files, ~15,000 lines of PHP
- **2,162** tests with **5,429** assertions
- **0** dependencies on Drupal core

## Key Design Principles

- **No global state.** Every service receives its dependencies through constructor injection.
- **Interface-first.** Public APIs are defined as interfaces. Implementations are swappable.
- **In-memory testable.** Every subsystem has in-memory implementations for fast, isolated testing.
- **Layered architecture.** Each layer only depends on layers below it. No circular dependencies.
- **AI-native.** Entity schemas automatically generate MCP tools, enabling AI agents to create, read, update, and query content through structured tool calls.

## License

GPL-2.0-or-later. See [LICENSE.txt](LICENSE.txt).
