# MOCKUP - WP Admin: AI Translation SEO

## [Top tabs]
- General
- Languages
- Providers
- Workflow
- Glossary
- Translation Memory
- Jobs
- SEO
- Logs
- Billing

---

## General
- Default source language: `pl-PL` (readonly)
- Default target language: `[en-GB|en-US|en]` *(nieokreślone)*
- URL strategy: `[subdir|subdomain|domain]` *(nieokreślone)*
- Consent to contact external AI services: `[checkbox + timestamp]`
- Hard delete on uninstall: `[checkbox]`

## Providers
- Provider: `[OpenAI|DeepL|Google|AWS|Azure]`
- API credentials: `[masked fields]`
- Test connection button
- Rate limit mode: `[conservative|balanced|aggressive]`
- Batch mode: `[on/off]`

## Workflow
- Auto-translate on publish: `[on/off]`
- Translate on update: `[on/off]`
- Always create as draft: `[on/off]`
- Human QA required thresholds:
  - score_total minimum: `[0-100]`
  - glossary violations allowed: `[0/1/..]`

## Roles and Capabilities (default mapping)
- Administrator: wszystko.
- Editor: `ait_translate_content` + `ait_review_translations`.
- Author: `ait_translate_content` własne treści *(opcjonalne / nieokreślone, domyślnie wyłączone i sterowane filtrem).* 
