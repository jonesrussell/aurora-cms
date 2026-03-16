import { BootstrapAuthAdapter } from '../adapters/BootstrapAuthAdapter'
import { JsonApiTransportAdapter } from '../adapters/JsonApiTransportAdapter'
import { ADMIN_CONTRACT_VERSION } from '../contracts/version'
import type { AdminBootstrap } from '../contracts/bootstrap'
import type { AdminRuntime } from '../contracts/runtime'

export default defineNuxtPlugin(async (): Promise<{ provide: { admin: AdminRuntime | null } }> => {
  const config = useRuntimeConfig()
  const baseUrl = (config.public.baseUrl as string) || ''

  // Step 1: Resolve bootstrap — inline first, endpoint fallback
  let bootstrap: AdminBootstrap

  if (import.meta.client && window.__WAASEYAA_ADMIN__) {
    bootstrap = window.__WAASEYAA_ADMIN__
  } else {
    let response: AdminBootstrap | null = null
    let fetchError: unknown = null

    try {
      response = await $fetch<AdminBootstrap>(`${baseUrl}/admin/bootstrap`, {
        ignoreResponseError: true,
        onResponseError({ response: res }) {
          if (res.status === 401 || res.status === 403) {
            // Auth failure — will redirect to login below
          }
        },
      })
    } catch (err) {
      fetchError = err
    }

    if (fetchError) {
      // Network, CORS, or timeout error — not an auth issue
      const message = fetchError instanceof Error ? fetchError.message : String(fetchError)
      console.error('[waaseyaa:admin] Bootstrap fetch failed (network/CORS/timeout):', message)
      throw createError({
        statusCode: 503,
        message: `Unable to reach the admin API: ${message}`,
        fatal: true,
      })
    }

    if (!response) {
      // HTTP error (401/403) — redirect to login
      if (import.meta.client) {
        window.location.href = `${baseUrl}/login`
      }
      return { provide: { admin: null } }
    }
    bootstrap = response
  }

  // Step 2: Validate contract version
  if (bootstrap.version !== ADMIN_CONTRACT_VERSION) {
    throw createError({
      statusCode: 500,
      message: `Admin contract version mismatch: expected ${ADMIN_CONTRACT_VERSION}, got ${bootstrap.version}`,
      fatal: true,
    })
  }

  // Step 3: Instantiate adapters
  const auth = new BootstrapAuthAdapter(bootstrap)
  const apiPath = bootstrap.transport.apiPath ?? '/api'
  const resolvedApiPath = `${baseUrl}${apiPath}`
  const transport = new JsonApiTransportAdapter(resolvedApiPath, bootstrap.tenant)

  // Step 4: Build runtime
  const runtime: AdminRuntime = {
    bootstrap,
    auth,
    transport,
    catalog: bootstrap.entities,
    tenant: bootstrap.tenant,
  }

  return { provide: { admin: runtime } }
})
