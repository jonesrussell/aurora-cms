import { ref, onUnmounted, type Ref } from 'vue'

export interface BroadcastMessage {
  channel: string
  event: string
  data: Record<string, any>
  timestamp: number
}

export function useRealtime(channels: string[] = ['admin']) {
  const messages: Ref<BroadcastMessage[]> = ref([])
  const connected = ref(false)

  let eventSource: EventSource | null = null
  let reconnectTimer: ReturnType<typeof setTimeout> | null = null

  function connect() {
    if (typeof window === 'undefined') return

    const channelParam = channels.join(',')
    eventSource = new EventSource(`/api/broadcast?channels=${channelParam}`)

    eventSource.onopen = () => {
      connected.value = true
    }

    eventSource.onmessage = (event) => {
      try {
        const msg: BroadcastMessage = JSON.parse(event.data)
        messages.value = [...messages.value.slice(-99), msg]
      } catch {
        // Ignore unparseable messages (e.g. keepalives).
      }
    }

    eventSource.onerror = () => {
      connected.value = false
      eventSource?.close()
      eventSource = null
      // Auto-reconnect after 3 seconds.
      reconnectTimer = setTimeout(connect, 3000)
    }
  }

  function disconnect() {
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
    eventSource?.close()
    eventSource = null
    connected.value = false
  }

  connect()
  onUnmounted(disconnect)

  return { messages, connected, disconnect }
}
