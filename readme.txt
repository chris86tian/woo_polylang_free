=== WooCommerce Polylang Integration ===
Contributors: yourname
Tags: woocommerce, polylang, multilingual, translation, ecommerce
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Vollständige WooCommerce-Mehrsprachigkeit mit Polylang-Integration. Macht alle WooCommerce-Inhalte übersetzbar und bietet SEO-optimierte Sprachversionen.

== Description ==

Das WooCommerce Polylang Integration Plugin macht Ihren WooCommerce-Shop vollständig mehrsprachig durch die Integration mit Polylang. Es ermöglicht die manuelle Übersetzung aller WooCommerce-Inhalte und bietet SEO-optimierte Sprachversionen.

= Hauptfunktionen =

* **Vollständige Produktübersetzung**: Titel, Beschreibungen, Attribute, Kategorien, Schlagwörter
* **Widget-Übersetzung**: Warenkorb, Checkout, Buttons wie "Add to Cart", "Proceed to Checkout"
* **E-Mail-Übersetzung**: WooCommerce-E-Mails und Benachrichtigungen in der Kundensprache
* **Custom Fields**: RankMath SEO Felder, ACF Felder und andere Custom Fields
* **SEO-Optimierung**: Canonical URLs, hreflang-Tags, strukturierte Daten
* **Plugin-Kompatibilität**: Hooks für eigene Plugins und Erweiterungen

= Unterstützte Plugins =

* Polylang / Polylang Pro
* RankMath SEO
* Yoast SEO
* Advanced Custom Fields (ACF)
* WooCommerce Subscriptions
* WooCommerce Bookings
* Custom Plugins über API-Hooks

= SEO-Features =

* Automatische hreflang-Tags für alle Sprachversionen
* Canonical URLs für jede Sprache
* Sprachspezifische URLs (z.B. /en/shop/)
* Strukturierte Daten mit Sprachinformationen
* Sitemap-Integration

= Entwickler-API =

Das Plugin bietet umfangreiche Hooks für Entwickler:

```php
// String für Übersetzung registrieren
wc_polylang_register_string($string, $name, $group);

// String übersetzen
$translated = wc_polylang_translate_string($string, $name);

// Aktuelle Sprache abrufen
$language = wc_polylang_get_current_language();

// Produktübersetzungen abrufen
$translations = wc_polylang_get_product_translations($product_id);
```

== Installation ==

1. Laden Sie das Plugin hoch und aktivieren Sie es
2. Stellen Sie sicher, dass WooCommerce und Polylang installiert und aktiviert sind
3. Gehen Sie zu WooCommerce > Polylang Integration
4. Konfigurieren Sie die gewünschten Übersetzungsfeatures
5. Beginnen Sie mit der manuellen Übersetzung Ihrer Inhalte in Polylang

= Systemanforderungen =

* WordPress 5.0+
* WooCommerce 7.0+
* Polylang (kostenlos oder Pro)
* PHP 7.4+

== Frequently Asked Questions ==

= Funktioniert das Plugin mit automatischen Übersetzungen? =

Nein, das Plugin ist für manuelle Übersetzungen über Polylang konzipiert. Dies gewährleistet höchste Qualität und Kontrolle über Ihre Übersetzungen.

= Ist das Plugin mit WooCommerce HPOS kompatibel? =

Ja, das Plugin ist vollständig kompatibel mit WooCommerce High-Performance Order Storage (HPOS).

= Kann ich eigene Custom Fields übersetzen? =

Ja, das Plugin unterstützt die Übersetzung von Custom Fields. Sie können auch eigene Felder über die bereitgestellten Hooks registrieren.

= Funktioniert das Plugin mit anderen SEO-Plugins? =

Ja, das Plugin ist kompatibel mit RankMath, Yoast SEO und anderen SEO-Plugins.

== Screenshots ==

1. Admin-Dashboard mit Übersetzungsstatistiken
2. Einstellungsseite für Konfiguration
3. Produktseite mit Sprachlinks
4. E-Mail-Übersetzung in Aktion

== Changelog ==

= 1.0.0 =
* Erste Veröffentlichung
* Vollständige WooCommerce-Polylang-Integration
* SEO-Optimierung mit hreflang und Canonical URLs
* E-Mail-Übersetzungen
* Custom Fields Unterstützung
* Entwickler-API mit Hooks
* Admin-Dashboard mit Statistiken

== Upgrade Notice ==

= 1.0.0 =
Erste Veröffentlichung des Plugins.

== Support ==

Für Support und Fragen besuchen Sie bitte:
* Plugin-Support-Forum
* Dokumentation: [Link zur Dokumentation]
* GitHub Repository: [Link zum Repository]

== Entwicklung ==

Dieses Plugin wurde entwickelt, um die Lücke zwischen WooCommerce und Polylang zu schließen und eine vollständige mehrsprachige E-Commerce-Lösung zu bieten.

= Mitwirken =

Entwickler sind eingeladen, zum Plugin beizutragen:
* GitHub Repository: [Link]
* Issue Tracker: [Link]
* Pull Requests willkommen

== Lizenz ==

Dieses Plugin ist unter der GPL v2 oder höher lizenziert.
