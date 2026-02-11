
Projekt wtyczki WordPress do tłumaczeń AI z naciskiem na SEO i SEM
Zakres funkcjonalny i wymagania jakości
Twoim celem jest wtyczka, która:

Z polskiej wersji serwisu tworzy i utrzymuje wersję angielską.
Dba o SEO techniczne: osobne URL-e dla języków, poprawne hreflang, kanonikalizacja, mapy witryny, przekierowania. 
Umożliwia tryb automatyczny (AI) i kontrolę jakości oraz korekty przez człowieka (human-in-the-loop).
Ma architekturę „provider-agnostic”, czyli możesz podmienić dostawcę AI bez przebudowy logiki biznesowej.
W tym raporcie przyjmuję (bo nie podałeś inaczej):

Język źródłowy: pl-PL.
Język docelowy: en (wariant nieokreślony - warto rozstrzygnąć en-GB vs en-US). 
Strategia URL dla języków: nieokreślona (subkatalog, subdomena, domena krajowa). Google zaleca osobne URL-e dla każdej wersji językowej. 
Definicje jakości, które warto wdrożyć jako twarde kryteria:

„Semantyczna równoważność”: sens i intencja zachowane, brak omijania ważnych fragmentów.
„Spójność terminologiczna”: marka, produkty, nazwy własne i słownik firmowy zawsze tak samo (glossary/term base).
„SEO-aware tłumaczenie”: zachowanie struktury nagłówków, linków wewnętrznych, alt text, danych strukturalnych, a także wariantów tytułów i opisów meta per język.
„Bezpieczne formatowanie”: brak uszkodzeń HTML, bloków Gutenberga, shortcodów i parametrów trackingowych.
Architektura aplikacji i model danych
Architektura logiczna
Proponowany podział na moduły:

Core
Rejestracja hooków, bootstrapping, konfiguracja.
Language & Routing
Określenie języka bieżącej strony.
Mapowanie obiektu źródłowego na obiekt tłumaczenia.
Generowanie URL-i per język (w zależności od strategii URL - nieokreślone).
Content Extractor
Ekstrakcja treści do tłumaczenia (Gutenberg, Classic, shortcody, pola niestandardowe).
Translation Orchestrator
Tworzenie zleceń, batchowanie, retry, idempotencja.
Providers
Adaptery do API (OpenAI, DeepL, Google, AWS, Azure i inne).
SEO Layer
hreflang, canonical, meta, sitemap, przekierowania, integracje z wtyczkami SEO.
Admin UI
Ustawienia, kolejka, podgląd różnic, edycja tłumaczeń, QA.
Observability
Logi zdarzeń, metryki użycia, audyt.
Security & Privacy
Zgody na połączenia zewnętrzne, anonimizacja, retencja, eksport/usuwanie danych.
Hooki i punkty integracji w WordPress
Minimalny zestaw hooków:

REST API
rest_api_init do rejestracji endpointów. 
Ustawienia
admin_init dla Settings API. 
admin_menu dla stron w panelu admin.
Zdarzenia treści
save_post (oraz save_post_{post_type}) do wykrywania zmian i oznaczania tłumaczeń jako „stale”. 
Mapy witryny
wp_sitemaps_post_types, wp_sitemaps_posts_query_args, wp_sitemaps_posts_entry, wp_sitemaps_add_provider i/lub własny provider przez wp_register_sitemap_provider. 
Canonical
get_canonical_url do kontroli kanonikalizacji per język. 
Cron i zadania
wp_schedule_event i WP-Cron do cyklicznych zadań (np. walidacja hreflang, przebudowa sitemap, retry jobów). 
Alternatywnie kolejka zadań przez Action Scheduler (rekomendowane dla masowych tłumaczeń). 
Struktura plików
Wymagany output: file structure.

text
Copy
ai-translation-seo/
  ai-translation-seo.php
  readme.txt
  uninstall.php
  assets/
    admin.js
    admin.css
  includes/
    Core/
      Plugin.php
      Activator.php
      Deactivator.php
      Capabilities.php
      Settings.php
    REST/
      Routes.php
      Controllers/
        JobsController.php
        GlossaryController.php
        MemoryController.php
        ProvidersController.php
        SeoController.php
    Content/
      Extractor.php
      GutenbergParser.php
      HtmlDomTranslator.php
      ShortcodeGuard.php
    Translation/
      Orchestrator.php
      Segmenter.php
      QualityGate.php
      TranslationMemory.php
      Glossary.php
    Providers/
      ProviderInterface.php
      OpenAIProvider.php
      DeepLProvider.php
      GoogleProvider.php
      AwsProvider.php
      AzureProvider.php
    SEO/
      Hreflang.php
      Canonical.php
      Meta.php
      Sitemaps.php
      Redirects.php
      Integrations/
        YoastIntegration.php
        RankMathIntegration.php
        WooCommerceIntegration.php
        WpmlIntegration.php
        PolylangIntegration.php
    Jobs/
      Queue.php
      ActionSchedulerQueue.php
      WpCronQueue.php
      Workers/
        TranslatePostWorker.php
        TranslateMenuWorker.php
        RebuildSitemapWorker.php
    Data/
      Schema.php
      Migrations.php
      Repositories/
        JobsRepository.php
        MemoryRepository.php
        GlossaryRepository.php
        LogsRepository.php
        UsageRepository.php
  tests/
    phpunit.xml
    Unit/
    Integration/
Schemat bazy danych
Wymagany output: SQL schema.

Założenie: używasz własnych tabel, bo:

masz kolejkę i statusy jobów,
masz translation memory i term base,
potrzebujesz audytu i usage.
WordPress zaleca tworzenie tabel wtyczek przez mechanizmy instalacyjne i dbDelta(). 

sql
Copy
-- Prefix: {wp_prefix}_

CREATE TABLE {wp_prefix}ait_translation_map (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_object_type VARCHAR(32) NOT NULL,        -- post|term|menu|attachment|option
  source_object_id BIGINT UNSIGNED NOT NULL,
  target_lang VARCHAR(16) NOT NULL,               -- en, en-GB, en-US
  target_object_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(16) NOT NULL,                    -- draft|published|stale|disabled
  last_synced_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_map (source_object_type, source_object_id, target_lang),
  KEY idx_target (target_lang, target_object_id)
);

CREATE TABLE {wp_prefix}ait_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_type VARCHAR(32) NOT NULL,                  -- translate_post|translate_menu|rebuild_sitemap
  source_lang VARCHAR(16) NOT NULL,
  target_lang VARCHAR(16) NOT NULL,
  mode VARCHAR(16) NOT NULL,                      -- realtime|batch
  status VARCHAR(16) NOT NULL,                    -- queued|running|done|failed|canceled
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  provider VARCHAR(32) NOT NULL,                  -- openai|deepl|google|aws|azure
  provider_request_id VARCHAR(128) NULL,
  error_code VARCHAR(64) NULL,
  error_message TEXT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_status (status, created_at),
  KEY idx_mode (mode, created_at)
);

CREATE TABLE {wp_prefix}ait_job_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id BIGINT UNSIGNED NOT NULL,
  object_type VARCHAR(32) NOT NULL,               -- post|term|menu_item|attachment
  object_id BIGINT UNSIGNED NOT NULL,
  payload_hash CHAR(64) NOT NULL,                 -- idempotency
  status VARCHAR(16) NOT NULL,                    -- queued|done|failed|skipped
  error_message TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_item (job_id, object_type, object_id),
  KEY idx_job (job_id)
);

CREATE TABLE {wp_prefix}ait_glossary_terms (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_lang VARCHAR(16) NOT NULL,
  target_lang VARCHAR(16) NOT NULL,
  source_term VARCHAR(255) NOT NULL,
  target_term VARCHAR(255) NOT NULL,
  match_type VARCHAR(16) NOT NULL,                -- exact|case_insensitive|regex (regex: optional)
  notes TEXT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pair (source_lang, target_lang),
  KEY idx_source (source_term(191))
);

CREATE TABLE {wp_prefix}ait_translation_memory (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_lang VARCHAR(16) NOT NULL,
  target_lang VARCHAR(16) NOT NULL,
  source_text_hash CHAR(64) NOT NULL,
  source_text LONGTEXT NOT NULL,
  target_text LONGTEXT NOT NULL,
  context VARCHAR(128) NULL,                      -- np. post:{id}|product:{id}|menu:{id}
  quality_score DECIMAL(5,2) NULL,
  approved TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tm (source_lang, target_lang, source_text_hash),
  KEY idx_pair (source_lang, target_lang),
  KEY idx_approved (approved)
);

CREATE TABLE {wp_prefix}ait_usage (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_ym VARCHAR(7) NOT NULL,                  -- YYYY-MM
  provider VARCHAR(32) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  chars_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
  chars_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
  tokens_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
  tokens_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
  cost_usd DECIMAL(12,4) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usage (period_ym, provider, user_id)
);

CREATE TABLE {wp_prefix}ait_audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_type VARCHAR(64) NOT NULL,                -- consent_granted|job_created|translation_published|seo_updated
  user_id BIGINT UNSIGNED NULL,
  object_type VARCHAR(32) NULL,
  object_id BIGINT UNSIGNED NULL,
  details_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_event (event_type, created_at)
);
Kluczowe decyzje, które są nieokreślone i muszą zostać doprecyzowane:

Jak przechowujesz tłumaczenia: osobne posty per język vs dane w meta vs własny CPT (rekomendowane: osobne posty per język dla SEO i edycji).
Strategia URL: subkatalog /en/ vs subdomena vs ccTLD.
Czy trzymasz tłumaczenia w statusie draft do akceptacji, czy publikujesz automatycznie.
Codex prompt: bootstrap i szkielet wtyczki
Wymagany output: code snippets, file structure.

text
Copy
PROMPT (Codex) - Plugin bootstrap

Zbuduj wtyczkę WordPress o nazwie "ai-translation-seo" (text-domain: ai-translation-seo).
Wymagania:
- PHP 8+ (jeśli nie da się założyć wersji, oznacz jako nieokreślone w komentarzu).
- OOP, PSR-4-like autoload (prosty autoloader bez composera).
- Plik główny: ai-translation-seo.php rejestruje aktywację/dezaktywację, inicjuje Plugin.php.
- Dodaj Activator.php z instalacją DB (dbDelta), zapis wersji schematu w opcji.
- Dodaj Deactivator.php z czyszczeniem cron i (opcjonalnie) flush rewrite rules tylko przy dezaktywacji.
- Dodaj uninstall.php: usuń opcje i (opcjonalnie) tabele zależnie od ustawienia "hard delete".
Zwróć:
- drzewo plików
- komplet kodu dla tych plików
- komentarze bezpieczeństwa (capability checks, nonces dla admin)
Workflow tłumaczeń i obsługa SEO technicznego
Strategia językowa i URL
Google wskazuje, że dla wersji językowych należy używać różnych URL-i (zamiast przełączania treści przez cookies lub ustawienia przeglądarki). 

Masz 3 typowe strategie (u Ciebie: nieokreślone):

Subkatalogi: / i /en/
Subdomeny: pl. i en.
Oddzielne domeny
Wtyczka powinna umieć:

Wygenerować URL docelowy dla tłumaczenia.
Utrzymać stabilność URL (zmiana slugu = strategia redirectów).
Nie mieszać canonical i hreflang w sposób, który grozi deindeksacją.
Tłumaczenie postów i stron
Proces rekomendowany:

Ekstrakcja
Gutenberg: parsuj bloki i tłumacz tylko pola tekstowe.
Classic: tłumacz węzły tekstowe HTML i atrybuty (np. alt), nie dotykaj shortcodów.
Segmentacja
Segmentuj na „bezpieczne fragmenty”: akapity, nagłówki, listy, opisy produktów, meta.
Prefilter
Zastosuj term base (glossary) zanim wyślesz do AI.
Tłumaczenie
Real-time: przy zapisie save_post twórz job.
Batch: UI do zaznaczania wielu postów i wrzucania do kolejki.
Postfilter
Walidacja HTML, walidacja Gutenberg, walidacja linków.
Kontrola reguł SEO (np. brak pustego title, brak duplikatów hreflang).
Publikacja
Opcja: publikuj zawsze jako draft do akceptacji (rekomendowane dla jakości i ryzyka SEO).
Opcja: auto publish (ryzykowne, ale możliwe).
Hook save_post wyzwala się przy tworzeniu i aktualizacji treści. 

Tłumaczenie menu, nawigacji i elementów globalnych
WordPress core sitemaps dla post types wykluczają m.in. attachment, a menu items to oddzielny typ, więc tłumaczenia menu nie „załatwią się” same przez sitemap. 

Wtyczka powinna mieć moduł:

„Menu duplicator”: tworzy menu EN jako kopię menu PL.
„Menu mapper”: mapuje elementy menu do przetłumaczonych stron (na podstawie ait_translation_map).
„String catalog”: tłumaczy etykiety pozycji menu.
URL-e, slugi i przekierowania
Ryzyko SEO powstaje, gdy:

zmieniasz slug EN po publikacji,
zmieniasz strukturę językową,
masz duplikaty w indeksie.
Google traktuje redirecty jako sygnały kanonikalizacji (siła sygnału zależy od typu redirectu).

Wtyczka powinna:

Przy zmianie slugu EN dodać redirect 301 ze starego URL na nowy (konfigurowalne).
Trzymać tabelę redirectów per język (lub integracja z istniejącą wtyczką do redirectów - nieokreślone).
Nie flushować rewrite rules często, bo to kosztowna operacja. 
hreflang
Google opisuje 3 równoważne metody deklarowania hreflang: HTML, HTTP headers, sitemap. Nie ma korzyści z używania wszystkich naraz. 

Wtyczka powinna wdrożyć 1 metodę jako „primary” (nieokreślone), a pozostałe jako opcjonalne.

Minimalna implementacja HTML (rekomendowana na start):

wp_head: wstawia zestaw link rel="alternate" hreflang="..." dla każdej wersji językowej.
Każda wersja musi wskazywać siebie i inne warianty (return links). 
Dodaj x-default dla strony wyboru języka lub fallback. 
Sitemap hreflang (gdy chcesz pełną kontrolę crawlingu):

Robisz własny sitemap provider przez wp_register_sitemap_provider. 
Dodajesz wpisy xhtml:link w sitemap dla każdej wersji URL. 
Canonical tagi
Google wyjaśnia canonicalization jako wybór reprezentatywnego URL dla duplikatów. 

Dla wersji językowych:

Google traktuje wersje językowe jako warianty lokalizacyjne, a nie „duplikaty”, gdy główna treść jest przetłumaczona. 
Praktyczna reguła wdrożeniowa: canonical powinien wskazywać bieżący URL danej wersji językowej, chyba że masz faktyczne duplikaty w obrębie tego samego języka (np. parametry filtrowania). To jest wniosek na bazie rozdzielenia mechanizmów hreflang i canonicalization w dokumentacji Google. 
W WordPress możesz kontrolować canonical per post przez filtr get_canonical_url. 

SEO meta, robots, sitemapy wtyczek SEO
Integracje z wtyczkami SEO powinny działać „warstwowo”:

Jeżeli aktywna jest wtyczka SEO, pozwól jej generować meta.
Twoja wtyczka dostarcza językowo poprawne wartości przez ich filtry.
Dla Yoast SEO:

Tytuł: filtr wpseo_title. 
Opis: filtr wpseo_metadesc. 
Canonical: filtr wpseo_canonical. 
Dla Rank Math:

Canonical: filtr rank_math/frontend/canonical. 
Robots: filtr rank_math/frontend/robots. 
Mapy witryny:

WordPress ma własne sitemapy od wersji 5.5 i są one rozszerzalne. 
Jeśli aktywna jest wtyczka SEO, często generuje własne sitemapy (mechanizm zależny od wtyczki - nieokreślone).
WooCommerce
Kompatybilność z WooCommerce oznacza tłumaczenie:

Produktów (product) i wariantów (często osobny typ lub meta).
Atrybutów i taksonomii produktów.
Tekstów systemowych (np. nazwy metod wysyłki) - to trudniejsze, bo bywa przechowywane w opcjach lub generowane dynamicznie (nieokreślone).
Rekomendacja:

Zacznij od produktów i kategorii, potem dopiero checkout-related strings.
Sekwencje zdarzeń
Wymagany output: sequence diagrams.

SEO Layer
AI Provider API
Queue (Action Scheduler or WP-Cron)
AI Translation Plugin
WordPress Core
Editor WP
SEO Layer
AI Provider API
Queue (Action Scheduler or WP-Cron)
AI Translation Plugin
WordPress Core
Editor WP
Save post (PL)
save_post hook
Detect deltas + mark EN as stale
Enqueue translate_post job
Run job worker
Extract segments + apply glossary
Translate batch
Translated payload + usage
Store EN post + TM + audit log
Update hreflang/canonical/meta mappings
Clear caches / flush derived data


Show code
Warstwa AI i porównanie dostawców
Provider-agnostic API wtyczki
Wtyczka powinna mieć interfejs:

translateTextBatch(sourceLang, targetLang, segments, options)
supportsGlossary() oraz pushGlossary(termBase)
getUsageMetrics() (chars/tokens/cost)
healthCheck() (klucz API, limity, błąd 401/429)
estimateCost() (heurystyka)
W praktyce:

Dostawcy „machine translation” liczą znaki.
Dostawcy „LLM” liczą tokeny.
Wtyczka musi umieć rozróżnić tryby:

Real-time: małe porcje, niska latencja, wysoka kontrola retry.
Batch: duże porcje, preferowany asynchroniczny processing.
Batch i rate limits dla OpenAI
Jeśli idziesz w LLM:

Batch API daje asynchroniczne joby z niższym kosztem (w dokumentacji: 50% taniej) i oknem realizacji do 24 godzin. 
Musisz uwzględnić rate limits i retry na 429. 
Tabela porównawcza dostawców AI
Wymagany output: request tables comparing AI providers.

Stan cen i polityk: luty 2026 (to zmienne w czasie, więc wtyczka powinna mieć moduł „pricing profiles” aktualizowany niezależnie od kodu).

Dostawca	Model rozliczeń	Cena bazowa (oficjalna)	Glossary / term base	Dane i prywatność	Języki	Pozycjonowanie użycia
OpenAI	Tokeny (input, output, cached input)	Przykład: GPT-5 mini ma stawki per 1M tokenów (input, cached input, output), widoczne w dokumentacji modelu; Batch ma cenę Batch API. 	Term base po stronie wtyczki (w promptach). Brak natywnego „glossary endpoint” - nieokreślone.	Domyślnie retencja stanu aplikacji 30 dni, ZDR możliwe dla organizacji. 	Brak zamkniętej listy; praktycznie wielojęzyczne. (Nieokreślone jako specyfikacja)	Najlepsze gdy potrzebujesz SEO-aware parafrazy, nie tylko „literal translation”.
Google (Cloud Translation)	Znaki	NMT: 20 USD / 1M znaków; pierwsze 500k znaków miesięcznie jako darmowy kredyt. 	Tak, glossaries w edycji Advanced. 	Google deklaruje, że nie używa treści z API do trenowania i ulepszania funkcji tłumaczeń. 	Lista wspieranych języków dostępna przez endpoint /supportedLanguages.	Stabilny wybór dla dużych wolumenów i prostych treści.
Microsoft Azure (Translator)	Znaki	Darmowy limit 2M znaków miesięcznie (F0). Cena per 1M znaków w tabeli dynamiczna (nieokreślona w danych statycznych).	Azure wspiera glossaries w Document Translation. 	Azure FAQ: dane przesłane do tłumaczenia nie są trwale przechowywane.	Endpoint languages dostępny publicznie.	Dobry wybór gdy i tak jesteś w ekosystemie Azure i chcesz ograniczyć ryzyka integracyjne.
DeepL	Znaki	API Pro: 25 USD / 1M znaków (pay-as-you-go) + mechanizmy kontroli kosztów. 	Tak, glossaries (v2, v3; v3 rozszerza funkcje). 	Komunikacja B2B: deklaracja, że teksty nie są przechowywane ani używane do trenowania bez zgody; logi dostępu mogą zawierać metadane żądań, ale nie treść. 	DeepL publikuje listę wspieranych języków i kody (np. EN-GB, EN-US). 	Często wybierany, gdy jakość stylistyczna EN ma być wysoka i spójna.
Amazon Web Services (Translate)	Znaki	15 USD / 1M znaków (standard text). 	Tak, custom terminology (CSV/TSV/TMX, także TMX).	Dokumentacja opisuje model odpowiedzialności i ochrony danych.	FAQ podaje wsparcie dla 75 języków. 	Dobry kosztowo przy dużych wolumenach, szczególnie w AWS.

Mini-wykres kosztów (tylko dostawcy „per znak”)
Wymagany output: chart.

Skala: USD za 1M znaków (luty 2026, wartości oficjalne tam gdzie dostępne).

AWS: 15
Google: 20
DeepL: 25
Azure: nieokreślone (dynamiczny cennik w statycznym podglądzie)
ASCII bar (niżej = taniej):

AWS [###############]
Google[####################]
DeepL [#########################]
Prompty do tłumaczeń „SEO-aware” i QC
Wymagany output: sample prompts.

Prompt: SEO-aware tłumaczenie postu z zachowaniem struktury
text
Copy
PROMPT - SEO-aware translation (PL -> EN)

Rola: jesteś tłumaczem i redaktorem SEO. Tłumacz z pl-PL na en-GB (jeśli wariant EN jest inny, oznacz jako nieokreślone).
Cele:
- zachowaj znaczenie i intencję
- utrzymaj strukturę HTML/Gutenberg, nie usuwaj tagów ani atrybutów
- nie tłumacz: shortcodów, nazw klas CSS, identyfikatorów, fragmentów w nawiasach {LIKE_THIS}
- zachowaj linki i parametry trackingowe
- zastosuj słownik (glossary) bez wyjątków

Wejście:
- title_pl: ...
- slug_pl: ...
- html_or_blocks_pl: ...
- meta_title_pl: ...
- meta_desc_pl: ...
- glossary_json: [{source:"...", target:"..."}]
Wyjście w JSON zgodnym ze schematem:
{
  "title_en": "...",
  "slug_en": "...",
  "html_or_blocks_en": "...",
  "meta_title_en": "...",
  "meta_desc_en": "...",
  "notes": [{ "type":"warning|info", "message":"..." }]
}

Dodaj ostrzeżenia gdy:
- widzisz terminy niejednoznaczne
- brakuje kontekstu dla nazwy własnej
- meta opis wygląda na zbyt ogólny lub niezgodny z treścią
Prompt: Glossary enforcement jako walidator
text
Copy
PROMPT - Glossary enforcement validator

Wejście:
- source_lang: pl-PL
- target_lang: en-GB
- glossary_json: [...]
- translated_text_en: ...

Zadanie:
1) Sprawdź, czy każdy termin ze słownika występujący w tekście źródłowym ma w tekście EN dokładnie wskazaną formę.
2) Jeśli nie, zwróć listę naruszeń i poprawioną wersję tekstu.

Wyjście JSON:
{
  "violations": [
    {
      "source_term": "...",
      "expected_target": "...",
      "found_target": "...",
      "locations": ["..."]
    }
  ],
  "fixed_text_en": "...",
  "confidence": 0-1
}
Prompt: Kontrola jakości tłumaczenia pod SEO i SEM
text
Copy
PROMPT - Translation QC (SEO + SEM)

Oceń tłumaczenie (EN) względem:
- zgodności znaczeniowej z PL
- spójności terminologicznej
- naturalności języka EN (bez kalk)
- zachowania intencji CTA (pod SEM)
- zachowania nazw produktów i parametrów trackingowych
- ryzyk SEO: duplikaty, brak unikalności, dziwne tytuły sekcji, keyword stuffing

Zwróć:
- score_total 0-100
- lista problemów (severity: low|medium|high)
- rekomendowane poprawki (diff-like, ale bez niszczenia HTML)
Codex prompt: adapter dla OpenAI + structured outputs
Structured outputs dają gwarancję formatu JSON zgodnego ze schematem. 

text
Copy
PROMPT (Codex) - OpenAI provider client

Zaimplementuj klasę Providers/OpenAIProvider.php używając WP HTTP API (wp_remote_post).
Wymagania:
- Konfiguracja w Settings: api_key, model, use_batch (bool), store (bool), max_tokens, timeout.
- Obsłuż structured outputs: wysyłasz JSON schema i wymuszasz odpowiedź zgodną ze schematem.
- Zbieraj usage: input_tokens, output_tokens, cached_input_tokens jeśli dostępne.
- Retries na 429 z exponential backoff i jitter (max 5 prób).
- Idempotency: hash payload i cache wyników w transientach.
- Nigdy nie loguj tajnych kluczy.
Zwróć:
- kod klasy
- helper do budowania request body
- testy jednostkowe z mockami odpowiedzi HTTP (sukces, 401, 429, timeout)
Panel administracyjny, role i UX dla human-in-the-loop
Wymagane role i uprawnienia
WordPress ma role i capabilities, które możesz rozszerzać i kontrolować. 

Proponowane capabilities (custom):

ait_manage_settings - konfiguracja providerów, zgody na dane, billing.
ait_translate_content - uruchamianie tłumaczeń i batch jobów.
ait_review_translations - akceptacja, publikacja, edycje i QA.
ait_view_logs - logi i audyt.
ait_manage_glossary - term base i translation memory.
Mapowanie na role (domyślnie):

Administrator: wszystko.
Editor: translate + review.
Author: translate własne treści (opcjonalnie - nieokreślone).
Settings UI
Settings API pozwala rejestrować ustawienia i pola przez register_setting, add_settings_section, add_settings_field. 

Wymagany output: admin screens mockups.

text
Copy
MOCKUP - WP Admin: AI Translation SEO

[Top tabs]
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

General
- Default source language: pl-PL (readonly)
- Default target language: [en-GB|en-US|en] (nieokreślone)
- URL strategy: [subdir|subdomain|domain] (nieokreślone)
- Consent to contact external AI services: [checkbox + timestamp]
- Hard delete on uninstall: [checkbox]

Providers
- Provider: [OpenAI|DeepL|Google|AWS|Azure]
- API credentials: [masked fields]
- Test connection button
- Rate limit mode: [conservative|balanced|aggressive]
- Batch mode: [on/off]

Workflow
- Auto-translate on publish: [on/off]
- Translate on update: [on/off]
- Always create as draft: [on/off]
- Human QA required thresholds:
  - score_total minimum: [0-100]
  - glossary violations allowed: [0/1/..]

Jobs
- Queue overview (filters: queued/running/failed)
- Bulk actions: retry, cancel, export report
UI edycji tłumaczeń
Human-in-the-loop ma sens, gdy:

treści są sprzedażowe,
masz brand voice,
masz regulacje prawne,
chcesz minimalizować ryzyko SEO.
Rekomendowany ekran „Translation Review” per obiekt:

Widok równoległy PL vs EN (diff).
Sekcja „glossary compliance”.
Przyciski: Approve, Publish, Send back to AI (re-translate), Lock segment (do TM jako approved).
Codex prompt: admin UI + REST
text
Copy
PROMPT (Codex) - Admin UI + REST

Zaimplementuj:
- Admin page w WP (submenu pod Settings lub osobne menu).
- UI w JS (wp-admin) pobiera dane przez REST API.
- REST endpointy wymagają uprawnień i nonce (X-WP-Nonce).
Wymagania:
- użyj register_rest_route w rest_api_init
- permission_callback sprawdza capability
- autoryzacja cookie+nonce dla zapytań z panelu admin
Zwróć:
- Routes.php + kontrolery
- admin.js (fetch z credentials include)
- przykładowe komponenty UI: Jobs table, Provider test, Glossary editor
Uwierzytelnianie REST w WP admin zwykle opiera się o cookie i nonce wp_rest, przesyłane np. w nagłówku X-WP-Nonce. 

Bezpieczeństwo, prywatność, logowanie i zgodność
Zasady bezpieczeństwa wtyczki
WordPress ma oficjalne wytyczne dot. sanitizacji i nonce. 

Wymagania minimalne:

Sanitizuj input, waliduj gdy możliwe, escape output.
W REST API każda mutacja:
permission_callback z capability.
Walidacja payload (typy, długości, whitelisty).
Klucze API:
przechowuj w option: zaszyfrowane (nieokreślone: metoda), lub alternatywnie w stałej / env (lepsze).
Logi:
nie loguj treści stron ani pełnych promptów, jeśli nie musisz.
loguj tylko metadane, hash, ID joba.
Zgoda na połączenia zewnętrzne
Jeśli publikujesz w katalogu wtyczek, WordPress wymaga, aby wtyczki nie kontaktowały serwerów zewnętrznych bez wyraźnej zgody użytkownika (opt-in) oraz wymaga opisania zbierania i użycia danych. 

To ma bezpośredni wpływ na UX:

Pierwsze uruchomienie: ekran zgody na wysyłanie treści do dostawcy tłumaczeń.
Zapisz zgodę w audycie.
Pozwól wycofać zgodę i zatrzymać kolejkę.
GDPR i retencja danych
W kontekście GDPR (Twoja strona, języki, tłumaczenia) krytyczne są:

Minimalizacja danych, ograniczenie celu i retencji (zasady przetwarzania). 
Zabezpieczenia przetwarzania (środki techniczne i organizacyjne). 
Umowy powierzenia z podmiotami przetwarzającymi (jeśli wysyłasz dane do zewnętrznych dostawców). 
To oznacza w praktyce (dla wtyczki):

Tryb „PII guard”:
wykrywa dane osobowe (nieokreślone: heurystyki) i blokuje wysyłkę do AI, albo maskuje (np. email -> placeholder).
Retencja:
translation memory i logi powinny mieć politykę TTL.
Eksport i usuwanie:
narzędzie „Delete translation logs” i „Delete TM” per okres.
Prywatność po stronie dostawców
Oficjalne deklaracje pomocne dla Twoich polityk:

OpenAI: dane z API nie są używane do trenowania, domyślne zasady retencji i kontrola „store” (oraz opcja ZDR zależna od organizacji). 
Google Cloud Translation: deklaracja, że treści wysyłane do API nie są używane do trenowania i ulepszania funkcji tłumaczeń. 
Azure Translator: FAQ deklaruje brak trwałego przechowywania danych przesłanych do tłumaczenia. 
DeepL: deklaracje o braku przechowywania i braku trenowania bez zgody oraz o logach dostępu bez treści. 
AWS Translate: dokumentacja omawia ochronę danych w modelu shared responsibility.
Testy, wdrożenie, migracje, rollback i utrzymanie
Testy
Wymagany output: test cases.

Testy jednostkowe (przykłady przypadków):

Segmenter:
nie tłumaczy shortcodów
nie niszczy HTML
poprawnie wykrywa alt i „do-not-translate tags”
Glossary:
wymusza zamiany
wykrywa konflikty (ten sam termin ma 2 tłumaczenia)
Translation memory:
idempotency: ten sam segment -> ten sam hash -> reuse
Provider client:
retry na 429
błąd 401 blokuje kolejkę i zgłasza alert
SEO layer:
hreflang: komplet return links
canonical: self dla wersji językowych
sitemap: poprawne wpisy dla EN
Testy integracyjne:

save_post tworzy job i tłumaczy draft EN.
Publikacja EN czyści cache i aktualizuje meta.
Batch mode tłumaczy 100 postów bez timeoutu (kolejka).
Asynchroniczne joby i kolejka
WP-Cron to mechanizm „best effort” i zależy od ruchu na stronie. 

Dlatego dla wolumenów rekomendowane jest użycie Action Scheduler:

jest zaprojektowany jako kolejka jobów dla wtyczek. 
Migracja z innych wtyczek i współpraca
Wymaganie: kompatybilność i migracje.

Dla WPML:

WPML trzyma mapowanie tłumaczeń w swoich tabelach (np. icl_translations). 
Możesz zrobić migrator:
odczytuje relacje z WPML,
tworzy wpisy w ait_translation_map,
mapuje języki i statusy.
Dla Polylang:

Polylang ma funkcje do wiązania tłumaczeń postów i terminów (pll_save_post_translations, pll_save_term_translations). 
Wtyczka może działać w trybie „adapter”:
jeśli Polylang aktywny, używaj jego API do relacji, a swoje tabele traktuj jako cache/observability.
Jeżeli wykryjesz jednocześnie WPML i Polylang:

To stan konfliktowy (nieokreślone jak obsłużyć).
Rekomendacja: zablokuj aktywację trybu standalone i pokaż błąd konfiguracji.
Rollback i upgrade path
Rollback:

Wyłączanie wtyczki powinno:
zatrzymać kolejkę,
przestać emitować hreflang i modyfikować canonical,
pozostawić treści EN (opcjonalnie) jako zwykłe posty.
Upgrade path:

Wersjonuj schemat DB w opcji.
Przy aktywacji i przy plugins_loaded sprawdzaj wersję i uruchamiaj migracje przez dbDelta(). 
Codex prompt: migracje DB + dbDelta
text
Copy
PROMPT (Codex) - DB migrations with dbDelta

Zaimplementuj includes/Data/Schema.php i includes/Data/Migrations.php.
Wymagania:
- Schema version w opcji: ait_db_version
- Install: tworzy tabele przez dbDelta
- Upgrade: jeśli wersja schematu < aktualna, wykonaj migracje (np. dodanie indexu, nowe kolumny)
- Wszystkie nazwy tabel z prefixem $wpdb->prefix
- Użyj $wpdb->get_charset_collate()
Zwróć:
- kod
- testy integracyjne (symulacja starej wersji)
Źródła
WordPress Developer Resources - REST API: Routes & Endpoints; Authentication; save_post; Settings API; wp_remote_post; dbDelta; WP Sitemaps hooks i provider API; get_canonical_url. 
Google Search Central - hreflang (localized versions), multi-regional guidance, canonicalization, redirects, sitemap extensions. 
OpenAI - model docs (GPT-5 mini), Structured Outputs, Batch API, Data controls, Pricing, Deprecations. 
DeepL - API Pro pricing, supported languages, translate endpoint, glossaries, data security, terms dot. logów dostępu. 
Google Cloud Translation - pricing, supported languages list, glossaries, data usage FAQ.
AWS Translate - pricing, supported languages, custom terminology, data protection. 
Azure Translator - security/FAQ deklaracje, glossaries dla document translation, REST languages endpoint. 
WordPress Plugin Directory Guidelines - zgoda na kontakt z zewnętrznymi serwerami i polityka prywatności dla pluginów. 
GDPR (teksty artykułów w wersji publikowanej w serwisie legislation.gov.uk) - zasady przetwarzania, procesor, bezpieczeństwo przetwarzania. 
