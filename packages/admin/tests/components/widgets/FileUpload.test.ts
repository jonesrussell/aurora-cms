import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import FileUpload from '~/components/widgets/FileUpload.vue'

class MockXMLHttpRequest {
  status = 201
  responseText = JSON.stringify({
    data: {
      attributes: {
        file_url: '/files/uploads/test.png',
      },
    },
  })

  upload = {
    addEventListener: vi.fn((event: string, cb: (e: ProgressEvent) => void) => {
      if (event === 'progress') {
        const progressEvent = { lengthComputable: true, loaded: 50, total: 100 } as ProgressEvent
        cb(progressEvent)
      }
    }),
  }

  private listeners: Record<string, (() => void)[]> = {}

  open = vi.fn()
  setRequestHeader = vi.fn()
  send = vi.fn(() => {
    this.dispatch('load')
  })

  withCredentials = false

  addEventListener(event: string, cb: () => void) {
    if (!this.listeners[event]) {
      this.listeners[event] = []
    }
    this.listeners[event].push(cb)
  }

  private dispatch(event: string) {
    for (const cb of this.listeners[event] ?? []) {
      cb()
    }
  }
}

describe('FileUpload widget', () => {
  const originalXhr = globalThis.XMLHttpRequest
  const originalCreateObjectUrl = URL.createObjectURL
  const originalRevokeObjectUrl = URL.revokeObjectURL

  beforeEach(() => {
    vi.stubGlobal('XMLHttpRequest', MockXMLHttpRequest as unknown as typeof XMLHttpRequest)
    URL.createObjectURL = vi.fn(() => 'blob:preview')
    URL.revokeObjectURL = vi.fn()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    globalThis.XMLHttpRequest = originalXhr
    URL.createObjectURL = originalCreateObjectUrl
    URL.revokeObjectURL = originalRevokeObjectUrl
  })

  it('uploads selected file and emits returned file URL', async () => {
    const wrapper = await mountSuspended(FileUpload, {
      props: {
        modelValue: '',
        label: 'Upload',
      },
    })

    const input = wrapper.find('input[type="file"]')
    const file = new File(['test'], 'hero.png', { type: 'image/png' })
    Object.defineProperty(input.element, 'files', {
      value: [file],
      writable: false,
    })
    await input.trigger('change')

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['/files/uploads/test.png'])
    expect(URL.createObjectURL).toHaveBeenCalled()
    expect(wrapper.find('img').exists()).toBe(true)
  })
})
