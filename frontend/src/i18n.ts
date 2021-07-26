import { createI18n } from 'vue3-i18n';
import de from '@/locales/de.json';
import en from '@/locales/en.json';
import { writeJsonSync } from './file-system';

const i18n = createI18n({
  locale: process.env.VUE_APP_I18N_LOCALE || 'en',
  fallbackLocale: process.env.VUE_APP_I18N_FALLBACK_LOCALE || 'en',
  messages: { de: de, en: en },
});

i18n.t2 = (key: string, fallback_message: string): string => {
  const translation = i18n.t(key);
  if (translation.length == 0) {
    const json: { [name: string]: string } = {};
    json[key] = fallback_message;
    writeJsonSync('./spacehuddle-test.json', json);
    if (fallback_message) return fallback_message;
    const keyParts = key.split('.');
    if (keyParts.length > 0) return keyParts[keyParts.length - 1];
  }
  return translation;
};

export default i18n;
