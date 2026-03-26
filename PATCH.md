# Parche exacto para integrar la exportación Excel
# Plugin: Índices ESTAR v2.0.15
# =====================================================================

## 1. indices-estar.php  (fichero raíz del plugin)
## ─────────────────────────────────────────────────────────────────────
## Busca el bloque de require_once existente (líneas 20-25 aprox.):
##
##   require_once INDICES_ESTAR_PATH . 'includes/helpers.php';
##   require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar-db.php';
##   require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar-ajax.php';
##   require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar-shortcode.php';
##   require_once INDICES_ESTAR_PATH . 'admin/class-indices-estar-admin.php';
##   require_once INDICES_ESTAR_PATH . 'includes/class-indices-estar.php';
##
## Añade estas DOS líneas justo DESPUÉS del último require_once:

require_once INDICES_ESTAR_PATH . 'includes/SimpleXLSXGen.php';
require_once INDICES_ESTAR_PATH . 'includes/class-indices-export.php';

## =====================================================================

## 2. includes/class-indices-estar.php  (clase principal del plugin)
## ─────────────────────────────────────────────────────────────────────
## Busca el método init() de la clase Indices_Estar.
## Debería tener un aspecto similar a:
##
##   public function init(): void {
##       ( new Indices_Estar_DB() )->...
##       ( new Indices_Estar_Admin() )->init();
##       ( new Indices_Estar_Ajax() )->init();
##       ( new Indices_Estar_Shortcode() )->init();
##   }
##
## Añade UNA línea al final del método init(), antes del cierre }:

        ( new Indices_Estar_Export() )->init();

## =====================================================================
##
## Con esto queda completamente integrado:
##   - El botón aparece automáticamente en todas las páginas del plugin
##     (page=indices-estar y page=indices-estar-numeros).
##   - La descarga se intercepta en admin_init antes de que WordPress
##     envíe cabeceras HTML.
##   - No es necesario tocar ningún otro archivo.
##
## =====================================================================


## ALTERNATIVA si no quieres tocar class-indices-estar.php
## ─────────────────────────────────────────────────────────────────────
## En indices-estar.php, dentro del callback de plugins_loaded,
## justo después de ( new Indices_Estar() )->init(); añade:

    if ( is_admin() ) {
        ( new Indices_Estar_Export() )->init();
    }

## Quedaría así:
##
##   add_action( 'plugins_loaded', static function (): void {
##       load_plugin_textdomain( ... );
##       ( new Indices_Estar() )->init();
##       if ( is_admin() ) {
##           ( new Indices_Estar_Export() )->init();
##       }
##   } );
