import { ref, computed } from 'vue'
import en from '~/i18n/en.json'

type Messages = Record<string, string>

const currentLocale = ref('en')
const messages: Record<string, Messages> = { en }

export function useLanguage() {
  function t(key: string, replacements: Record<string, string> = {}): string {
    const msg = messages[currentLocale.value]?.[key] ?? key
    return Object.entries(replacements).reduce(
      (result, [token, value]) => result.replace(`{${token}}`, value),
      msg,
    )
  }

  function setLocale(locale: string) {
    currentLocale.value = locale
  }

  const locale = computed(() => currentLocale.value)

  return { t, locale, setLocale }
}
