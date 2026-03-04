// packages/admin/tests/fixtures/entityTypes.ts
export const entityTypes = [
  { id: 'user', label: 'User', keys: { id: 'id', label: 'name' } },
  { id: 'node', label: 'Content', keys: { id: 'id', label: 'title' } },
  { id: 'node_type', label: 'Content Type', keys: { id: 'type', label: 'name' } },
  { id: 'taxonomy_term', label: 'Taxonomy Term', keys: { id: 'id', label: 'name' } },
  { id: 'taxonomy_vocabulary', label: 'Taxonomy Vocabulary', keys: { id: 'vid', label: 'name' } },
  { id: 'media', label: 'Media', keys: { id: 'id', label: 'name' } },
  { id: 'media_type', label: 'Media Type', keys: { id: 'type', label: 'name' } },
  { id: 'path_alias', label: 'Path Alias', keys: { id: 'id', label: 'alias' } },
  { id: 'menu', label: 'Menu', keys: { id: 'id', label: 'label' } },
  { id: 'menu_link', label: 'Menu Link', keys: { id: 'id', label: 'title' } },
  { id: 'workflow', label: 'Workflow', keys: { id: 'id', label: 'label' } },
  { id: 'pipeline', label: 'Pipeline', keys: { id: 'id', label: 'label' } },
]
