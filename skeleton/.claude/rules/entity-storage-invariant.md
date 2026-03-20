# Entity Storage Invariant

This rule is always active. Follow it silently. This is the canonical persistence pipeline for Waaseyaa applications.

---

## The Pipeline

All entity persistence MUST follow:

```
Entity (extends EntityBase or ContentEntityBase)
  → EntityType registered via EntityTypeManager
  → EntityStorageDriverInterface (SqlStorageDriver for SQL)
  → EntityRepository (hydration, events, language fallback)
  → DatabaseInterface (Doctrine DBAL, NOT raw PDO)
```

## Forbidden Patterns

| Forbidden | Use Instead |
|-----------|-------------|
| `new \PDO(...)` | `DBALDatabase` + `DriverManager::getConnection()` |
| `$pdo->prepare(...)` | `EntityRepository::findBy()` or `DatabaseInterface::select()` |
| `\PDO::FETCH_ASSOC` arrays | Entity objects via `EntityRepository::find()` |
| `use Illuminate\*` | Waaseyaa/Symfony equivalents |
| Laravel facades | DI-injected services |

## When DatabaseInterface Without EntityRepository Is OK

Non-entity tables (join tables, counters, audit logs) may use `DatabaseInterface` directly. The test: if it has an identity (ID/UUID) and lifecycle, it must be an entity.

## ContentEntityBase vs EntityBase

- `ContentEntityBase` — has `set()` for field mutations (most entities)
- `EntityBase` — immutable value-like entities (rare)
