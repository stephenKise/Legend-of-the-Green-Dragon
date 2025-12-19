<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/**
 * Global cache for loaded translation namespaces.
 * Format: "language.namespace" => array
 *
 * @var array
 */
global $TRANSLATION_CACHE;
$TRANSLATION_CACHE = [];

/**
 * Tracks currently resolving translation keys to prevent infinite recursion
 * during {{key}} placeholder resolution.
 *
 * @var array
 */
global $TRANSLATION_RESOLVING;
$TRANSLATION_RESOLVING = [];

/**
 * Resolve {{namespace.key}} or {{key}} placeholders within a translated string.
 *
 * Supports:
 * - Relative keys: {{title}} (uses current namespace)
 * - Namespaced keys: {{common.site_name}}
 * - Fallback defaults: {{common.gold|gold coins}}
 *
 * @param string $string           The string containing placeholders
 * @param string $currentNamespace Current namespace for relative key resolution
 *
 * @return string String with all placeholders resolved
 */
function handleSubtranslations(string $string, string $currentNamespace): string
{
    global $TRANSLATION_RESOLVING;

    return preg_replace_callback(
        '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/',
        function (array $matches) use ($currentNamespace): string {
            $fullKey = $matches[1];
            $default = $matches[3] ?? null;

            // Split into namespace and key
            $parts = explode('.', $fullKey, 2);
            if (count($parts) === 2) {
                [$namespace, $key] = $parts;
            } else {
                $namespace = $currentNamespace;
                $key = $fullKey;
            }

            if ($namespace === '' || $key === '') {
                return $default ?? $matches[0];
            }

            $namespacedKey = "{$namespace}.{$key}";

            // Prevent infinite recursion
            if (isset($TRANSLATION_RESOLVING[$namespacedKey])) {
                return $default ?? $matches[0];
            }

            $TRANSLATION_RESOLVING[$namespacedKey] = true;

            $resolved = loadTranslation($namespacedKey);

            unset($TRANSLATION_RESOLVING[$namespacedKey]);

            return $resolved !== '' ? $resolved : ($default ?? $matches[0]);
        },
        $string
    );
}

/**
 * Get the current language code.
 *
 * Priority:
 * 1. Logged-in user's preference
 * 2. Server default setting
 * 3. Fallback to 'en'
 *
 * @return string Language code (e.g. 'en', 'es', 'de')
 */
function getLanguage(): string
{
    global $session;

    return $session['user']['language'] ?? getsetting('defaultlanguage', 'en');
}

/**
 * Load all translations for a given namespace.
 *
 * Supports module-specific paths:
 * - If direct 'namespace.yaml' missing, tries 'modules/namespace.yaml'
 * - Falls back to English for both direct and module paths if needed.
 *
 * @param string $namespace Namespace (e.g. 'core', 'home', 'mymodule')
 *
 * @return array Associative array of translation keys and values
 */
function loadNamespace(string $namespace): array
{
    global $TRANSLATION_CACHE;

    $language = getLanguage();
    $cacheKey = "{$language}.{$namespace}";

    if (isset($TRANSLATION_CACHE[$cacheKey])) {
        return $TRANSLATION_CACHE[$cacheKey];
    }

    $filePath = "translations/{$language}/{$namespace}.yaml";

    // If direct path missing, try module path
    if (!file_exists($filePath)) {
        $filePath = "translations/{$language}/modules/{$namespace}.yaml";
    }

    // Fallback to English if still missing
    if (!file_exists($filePath)) {
        $filePath = "translations/en/{$namespace}.yaml";

        // English module fallback
        if (!file_exists($filePath)) {
            $filePath = "translations/en/modules/{$namespace}.yaml";
        }

        if (!file_exists($filePath)) {
            $TRANSLATION_CACHE[$cacheKey] = [];
            return [];
        }
    }

    $yamlContent = file_get_contents($filePath);
    $translations = Yaml::parse($yamlContent) ?: [];

    if (!is_array($translations)) {
        $translations = [];
    }

    $TRANSLATION_CACHE[$cacheKey] = $translations;

    return $translations;
}

/**
 * Retrieve a single translation string from a namespace (flat key only).
 *
 * Used internally for performance when namespace is already known.
 *
 * @param string $key        Translation key within the namespace
 * @param array  $replace    Optional sprintf-style replacements
 * @param string $namespace  Namespace to load from (default: 'core')
 *
 * @return string Translated string
 */
function getTranslation(string $key, array $replace = [], string $namespace = 'core'): string
{
    static $currentNamespace = null;
    static $currentTranslations = [];

    if ($namespace !== $currentNamespace) {
        $currentTranslations = loadNamespace($namespace);
        $currentNamespace = $namespace;
    }

    $translatedString = $currentTranslations[$key] ?? $key;

    // Apply vsprintf replacements first
    if (!empty($replace) && is_string($translatedString)) {
        $translatedString = vsprintf($translatedString, $replace);
    }

    // Then resolve nested {{}} references
    if (is_string($translatedString)) {
        $translatedString = handleSubtranslations($translatedString, $namespace);
    }

    return $translatedString;
}

/**
 * Traverse a nested array using dot-separated key parts.
 *
 * @param array  $array    The array to traverse
 * @param array  $keyParts The exploded key parts (e.g. ['settings', 'save'])
 * @param string $fallback Fallback value if key not found
 *
 * @return mixed The resolved value (string or fallback)
 */
function traverseDeepKey(array $array, array $keyParts, string $fallback): mixed
{
    $value = $array;

    foreach ($keyParts as $part) {
        if (is_array($value) && isset($value[$part])) {
            $value = $value[$part];
        } else {
            return $fallback;
        }
    }

    return $value;
}

/**
 * Main translation function used throughout the application.
 *
 * Supports:
 * - Classic namespaces: 'home.title', 'common.gold'
 * - Scalar module translations:
 *     'mymodule.welcome' → translations/{lang}/modules/mymodule.yaml → welcome
 *     'mymodule.settings.save' → deep key traversal
 * - Deep keys: 'home.navs.create'
 * - sprintf replacements: loadTranslation('mymodule.gold_found', [250])
 * - Nested references: {{common.site_name}}
 *
 * @param string $namespacedKey Full key: namespace.key(.subkey...) or module.key(.subkey...)
 * @param array  $replace       Optional values for %s, %d placeholders
 *
 * @return string Translated and processed string
 */
function loadTranslation(string $namespacedKey, array $replace = []): string
{
    $parts = explode('.', $namespacedKey);

    $currentNamespace = '';
    $translatedString = $namespacedKey; // Default fallback

    if (count($parts) < 2) {
        return 'Warning: Invalid translation key!';
    }

    // First part is namespace/module name
    $namespace = $parts[0];
    $keyParts = array_slice($parts, 1);

    $translations = loadNamespace($namespace);
    $currentNamespace = $namespace;

    // Simplified deep traversal
    $value = traverseDeepKey($translations, $keyParts, $namespacedKey);

    $translatedString = is_string($value) ? $value : $namespacedKey;

    // Apply sprintf replacements first
    if (!empty($replace) && is_string($translatedString)) {
        $translatedString = vsprintf($translatedString, $replace);
    }

    // Then resolve {{namespace.key}} placeholders
    if (is_string($translatedString)) {
        $translatedString = handleSubtranslations($translatedString, $currentNamespace);
    }

    return $translatedString;
}

/**
 * Clear the translation cache.
 *
 * Useful after editing YAML files via the admin editor or during updates.
 */
function invalidateTranslationCache(): void
{
    global $TRANSLATION_CACHE;
    global $TRANSLATION_RESOLVING;

    $TRANSLATION_CACHE = [];
    $TRANSLATION_RESOLVING = [];
}