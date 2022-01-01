<?php

namespace Framelix\Framelix;

use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;

use function array_unique;
use function basename;
use function explode;
use function file_exists;
use function in_array;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function str_starts_with;
use function substr;

/**
 * Language and translations
 */
class Lang
{
    /**
     * All official 2-char ISO lang codes
     */
    public const ISO_LANG_CODES = [
        "ab" => "Abkhazian",
        "aa" => "Afar",
        "af" => "Afrikaans",
        "ak" => "Akan",
        "sq" => "Albanian",
        "am" => "Amharic",
        "ar" => "Arabic",
        "an" => "Aragonese",
        "hy" => "Armenian",
        "as" => "Assamese",
        "av" => "Avaric",
        "ae" => "Avestan",
        "ay" => "Aymara",
        "az" => "Azerbaijani",
        "bm" => "Bambara",
        "ba" => "Bashkir",
        "eu" => "Basque",
        "be" => "Belarusian",
        "bn" => "Bengali",
        "bh" => "Bihari languages",
        "bi" => "Bislama",
        "nb" => "Bokmål, Norwegian; Norwegian Bokmål",
        "bs" => "Bosnian",
        "br" => "Breton",
        "bg" => "Bulgarian",
        "my" => "Burmese",
        "ca" => "Catalan; Valencian",
        "km" => "Central Khmer",
        "ch" => "Chamorro",
        "ce" => "Chechen",
        "ny" => "Chichewa; Chewa; Nyanja",
        "zh" => "Chinese",
        "cu" => "Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic",
        "cv" => "Chuvash",
        "kw" => "Cornish",
        "co" => "Corsican",
        "cr" => "Cree",
        "hr" => "Croatian",
        "cs" => "Czech",
        "da" => "Danish",
        "dv" => "Divehi; Dhivehi; Maldivian",
        "nl" => "Dutch; Flemish",
        "dz" => "Dzongkha",
        "en" => "English",
        "eo" => "Esperanto",
        "et" => "Estonian",
        "ee" => "Ewe",
        "fo" => "Faroese",
        "fj" => "Fijian",
        "fi" => "Finnish",
        "fr" => "French",
        "ff" => "Fulah",
        "gd" => "Gaelic; Scottish Gaelic",
        "gl" => "Galician",
        "lg" => "Ganda",
        "ka" => "Georgian",
        "de" => "German",
        "el" => "Greek, Modern (1453-)",
        "gn" => "Guarani",
        "gu" => "Gujarati",
        "ht" => "Haitian; Haitian Creole",
        "ha" => "Hausa",
        "he" => "Hebrew",
        "hz" => "Herero",
        "hi" => "Hindi",
        "ho" => "Hiri Motu",
        "hu" => "Hungarian",
        "is" => "Icelandic",
        "io" => "Ido",
        "ig" => "Igbo",
        "id" => "Indonesian",
        "ia" => "Interlingua (International Auxiliary Language Association)",
        "ie" => "Interlingue; Occidental",
        "iu" => "Inuktitut",
        "ik" => "Inupiaq",
        "ga" => "Irish",
        "it" => "Italian",
        "ja" => "Japanese",
        "jv" => "Javanese",
        "kl" => "Kalaallisut; Greenlandic",
        "kn" => "Kannada",
        "kr" => "Kanuri",
        "ks" => "Kashmiri",
        "kk" => "Kazakh",
        "ki" => "Kikuyu; Gikuyu",
        "rw" => "Kinyarwanda",
        "ky" => "Kirghiz; Kyrgyz",
        "kv" => "Komi",
        "kg" => "Kongo",
        "ko" => "Korean",
        "kj" => "Kuanyama; Kwanyama",
        "ku" => "Kurdish",
        "lo" => "Lao",
        "la" => "Latin",
        "lv" => "Latvian",
        "li" => "Limburgan; Limburger; Limburgish",
        "ln" => "Lingala",
        "lt" => "Lithuanian",
        "lu" => "Luba-Katanga",
        "lb" => "Luxembourgish; Letzeburgesch",
        "mk" => "Macedonian",
        "mg" => "Malagasy",
        "ms" => "Malay",
        "ml" => "Malayalam",
        "mt" => "Maltese",
        "gv" => "Manx",
        "mi" => "Maori",
        "mr" => "Marathi",
        "mh" => "Marshallese",
        "mn" => "Mongolian",
        "na" => "Nauru",
        "nv" => "Navajo; Navaho",
        "nd" => "Ndebele, North; North Ndebele",
        "nr" => "Ndebele, South; South Ndebele",
        "ng" => "Ndonga",
        "ne" => "Nepali",
        "se" => "Northern Sami",
        "no" => "Norwegian",
        "nn" => "Norwegian Nynorsk; Nynorsk, Norwegian",
        "oc" => "Occitan (post 1500)",
        "oj" => "Ojibwa",
        "or" => "Oriya",
        "om" => "Oromo",
        "os" => "Ossetian; Ossetic",
        "pi" => "Pali",
        "pa" => "Panjabi; Punjabi",
        "fa" => "Persian",
        "pl" => "Polish",
        "pt" => "Portuguese",
        "ps" => "Pushto; Pashto",
        "qu" => "Quechua",
        "ro" => "Romanian; Moldavian; Moldovan",
        "rm" => "Romansh",
        "rn" => "Rundi",
        "ru" => "Russian",
        "sm" => "Samoan",
        "sg" => "Sango",
        "sa" => "Sanskrit",
        "sc" => "Sardinian",
        "sr" => "Serbian",
        "sn" => "Shona",
        "ii" => "Sichuan Yi; Nuosu",
        "sd" => "Sindhi",
        "si" => "Sinhala; Sinhalese",
        "sk" => "Slovak",
        "sl" => "Slovenian",
        "so" => "Somali",
        "st" => "Sotho, Southern",
        "es" => "Spanish; Castilian",
        "su" => "Sundanese",
        "sw" => "Swahili",
        "ss" => "Swati",
        "sv" => "Swedish",
        "tl" => "Tagalog",
        "ty" => "Tahitian",
        "tg" => "Tajik",
        "ta" => "Tamil",
        "tt" => "Tatar",
        "te" => "Telugu",
        "th" => "Thai",
        "bo" => "Tibetan",
        "ti" => "Tigrinya",
        "to" => "Tonga (Tonga Islands)",
        "ts" => "Tsonga",
        "tn" => "Tswana",
        "tr" => "Turkish",
        "tk" => "Turkmen",
        "tw" => "Twi",
        "ug" => "Uighur; Uyghur",
        "uk" => "Ukrainian",
        "ur" => "Urdu",
        "uz" => "Uzbek",
        "ve" => "Venda",
        "vi" => "Vietnamese",
        "vo" => "Volapük",
        "wa" => "Walloon",
        "cy" => "Welsh",
        "fy" => "Western Frisian",
        "wo" => "Wolof",
        "xh" => "Xhosa",
        "yi" => "Yiddish",
        "yo" => "Yoruba",
        "za" => "Zhuang; Chuang",
        "zu" => "Zulu"
    ];

    /**
     * All languages that are natively supported by the framelix core itself
     * @var string[]]
     */
    public static array $coreSupportedLanguages = ['en', 'de'];

    /**
     * The active language
     * @var string|null
     */
    public static ?string $lang = null;

    /**
     * All lang values
     * @var string[][]
     */
    public static array $values = [];

    /**
     * All loaded modules and languages for them
     * @var array
     */
    private static array $loadedModules = [];

    /**
     * Supported languages
     * @var array|null
     */
    private static ?array $supportedLanguages = null;

    /**
     * Get all languages that are supported by at least one module
     * @return string[]
     */
    public static function getAllModuleLanguages(): array
    {
        if (self::$supportedLanguages === null) {
            self::$supportedLanguages = [];
            foreach (Config::$loadedModules as $module) {
                $files = FileUtils::getFiles(FileUtils::getModuleRootPath($module) . "/lang", "~\.json$~");
                foreach ($files as $file) {
                    $lang = substr(basename($file), 0, -5);
                    self::$supportedLanguages[] = $lang;
                }
            }
            self::$supportedLanguages = array_unique(self::$supportedLanguages);
        }
        return self::$supportedLanguages;
    }

    /**
     * Get all supported languages that are enabled in the config
     * @return string[]
     */
    public static function getSupportedLanguages(): array
    {
        $languages = [self::$lang];
        if (Config::get('languageMultiple') && Config::get('languagesSupported')) {
            $languages = Config::get('languagesSupported');
        }
        return $languages;
    }

    /**
     * Check if a key exist
     * @param string $key
     * @param string|null $lang
     * @return bool
     */
    public static function keyExist(string $key, ?string $lang = null): bool
    {
        $langDefault = $lang ?? self::$lang ?? Config::get('languageDefault');
        $langFallback = Config::get('languageFallback') ?? 'en';
        if (!isset(self::$values[$langDefault])) {
            self::$values[$langDefault] = [];
            self::loadValues($langDefault);
        }
        if (!isset(self::$values[$langFallback])) {
            self::$values[$langFallback] = [];
            self::loadValues($langFallback);
        }
        $value = self::$values[$langDefault][$key] ?? self::$values[$langFallback][$key] ?? null;
        return $value !== null;
    }

    /**
     * Set a language key at runtime
     * @param string $key
     * @param string $value
     * @param string|null $lang
     * @return void
     */
    public static function set(string $key, string $value, string $lang = null): void
    {
        self::$values[$lang ?? self::$lang][$key] = $value;
    }

    /**
     * Get translated language key
     * @param string $key
     * @param array|null $parameters
     * @param string|null $lang
     * @return string
     */
    public static function get(string $key, ?array $parameters = null, ?string $lang = null): string
    {
        if (!$key) {
            return $key;
        }
        if (!str_starts_with($key, "__")) {
            return $key;
        }
        if (!self::keyExist($key, $lang)) {
            return $key;
        }
        $langDefault = $lang ?? self::$lang ?? Config::get('languageDefault');
        $langFallback = Config::get('languageFallback') ?? 'en';
        $value = self::$values[$langDefault][$key] ?? self::$values[$langFallback][$key] ?? null;
        // if value is not set, get fallback language
        if ($value === '' && $langDefault !== $langFallback) {
            return self::get($key, $parameters, $langFallback);
        }
        if ($parameters) {
            preg_match_all("~\{\{(.*?)\}\}~i", $value, $conditionParameters);
            // replace conditions parameters
            foreach ($conditionParameters[0] as $key => $match) {
                $replaceWith = null;
                $conditions = explode("|", $conditionParameters[1][$key]);
                foreach ($conditions as $condition) {
                    preg_match("~^([a-z0-9-_]+)([!=<>]+)([0-9*]+):(.*)~i", $condition, $conditionSplit);
                    if ($conditionSplit) {
                        $parameterName = $conditionSplit[1];
                        $compareOperator = $conditionSplit[2];
                        $compareNumber = (int)$conditionSplit[3];
                        $outputValue = $conditionSplit[4];
                        $parameterValue = $parameters[$parameterName];
                        if ($conditionSplit[3] === "*") {
                            $replaceWith = $outputValue;
                        } elseif ($compareOperator === "=" && $compareNumber === (int)$parameterValue) {
                            $replaceWith = $outputValue;
                        } elseif ($compareOperator === "<" && $parameterValue < $compareNumber) {
                            $replaceWith = $outputValue;
                        } elseif ($compareOperator === ">" && $parameterValue > $compareNumber) {
                            $replaceWith = $outputValue;
                        } elseif ($compareOperator === "<=" && $parameterValue <= $compareNumber) {
                            $replaceWith = $outputValue;
                        } elseif ($compareOperator === ">=" && $parameterValue >= $compareNumber) {
                            $replaceWith = $outputValue;
                        }
                        if ($replaceWith !== null) {
                            $replaceWith = $parameterValue . " " . $replaceWith;
                            break;
                        }
                    }
                }
                $value = str_replace($match, (string)$replaceWith, $value);
            }
            foreach ($parameters as $search => $replace) {
                // replace default parameters
                $value = str_replace('{' . $search . '}', $replace, $value);
            }
        }
        return $value;
    }

    /**
     * Get all values for all supported languages
     * @return string[][]
     */
    public static function getValuesForSupportedLanguages(): array
    {
        foreach (self::getSupportedLanguages() as $language) {
            self::loadValues($language);
        }
        return self::$values;
    }

    /**
     * Get active language by browser settings
     * @return string|null
     */
    public static function getLanguageByBrowserSettings(): ?string
    {
        $supportedLanguages = Config::get('languagesSupported');
        if (!$supportedLanguages) {
            return null;
        }
        $langOrder = [
            (string)Request::getHeader('http_x_frontend_language'),
            substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? "", 0, 2),
            (string)($_GET['_lang'] ?? null)
        ];
        foreach ($langOrder as $lang) {
            if (in_array($lang, $supportedLanguages)) {
                return $lang;
            }
        }
        return null;
    }

    /**
     * Load values from json files for given language
     * @param string $language
     */
    public static function loadValues(string $language): void
    {
        foreach (Config::$loadedModules as $module) {
            $cacheKey = $language . "-" . $module;
            if (self::$loadedModules[$cacheKey] ?? null) {
                continue;
            }
            self::$loadedModules[$cacheKey] = true;
            $file = FileUtils::getModuleRootPath($module) . "/lang/$language.json";
            if (file_exists($file)) {
                $values = JsonUtils::readFromFile($file);
                foreach ($values as $key => $value) {
                    self::set($key, $value[0], $language);
                }
            }
        }
    }
}