export interface EntityTypeInfo {
  id: string
  label: string
  keys: Record<string, string>
  group?: string | null
}

type NonEmptyArray<T> = [T, ...T[]]

export interface ResolvedNavGroup {
  key: string
  labelKey: string
  entityTypes: NonEmptyArray<EntityTypeInfo>
}

const groupOrder: string[] = [
  'people',
  'content',
  'taxonomy',
  'media',
  'structure',
  'workflows',
  'ai',
]

export function groupEntityTypes(entityTypes: EntityTypeInfo[]): ResolvedNavGroup[] {
  const grouped = new Map<string, EntityTypeInfo[]>()
  const ungrouped: EntityTypeInfo[] = []

  for (const et of entityTypes) {
    if (et.group) {
      const list = grouped.get(et.group) ?? []
      list.push(et)
      grouped.set(et.group, list)
    } else {
      ungrouped.push(et)
    }
  }

  // Sort groups: known order first, then unknown groups alphabetically
  const sortedKeys = [...grouped.keys()].sort((a, b) => {
    const ai = groupOrder.indexOf(a)
    const bi = groupOrder.indexOf(b)
    if (ai !== -1 && bi !== -1) return ai - bi
    if (ai !== -1) return -1
    if (bi !== -1) return 1
    return a.localeCompare(b)
  })

  const groups: ResolvedNavGroup[] = []

  for (const key of sortedKeys) {
    const types = grouped.get(key)!
    groups.push({
      key,
      labelKey: `nav_group_${key}`,
      entityTypes: types as NonEmptyArray<EntityTypeInfo>,
    })
  }

  if (ungrouped.length > 0) {
    groups.push({
      key: 'other',
      labelKey: 'nav_group_other',
      entityTypes: ungrouped as NonEmptyArray<EntityTypeInfo>,
    })
  }

  return groups
}
