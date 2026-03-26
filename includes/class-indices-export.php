<?php
/**
 * Exportación de índices a Excel (.xlsx)
 *
 * Estructura real de tablas (prefijo tm_):
 *   tm_estar_index_groups  → id, name, slug
 *   tm_estar_indices       → id, group_id, year, number, index_date, url
 *   tm_estar_index_items   → id, index_id, section, title, item_url, author, sort_order
 *
 * @package IndicesEstar
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Indices_Estar_Export {

    const ACTION = 'indices_estar_export_excel';

    // ------------------------------------------------------------------
    // Inicialización
    // ------------------------------------------------------------------

    public function init(): void {
        add_action( 'admin_init',    [ $this, 'maybe_export' ] );
        add_action( 'admin_notices', [ $this, 'maybe_render_button' ] );
    }

    // ------------------------------------------------------------------
    // Botón — aparece en todas las páginas admin del plugin
    // ------------------------------------------------------------------

    public function maybe_render_button(): void {
        $page = $_GET['page'] ?? '';
        if ( strpos( $page, 'indices-estar' ) === false ) return;

        // En la página de issues usamos el group_id de la URL
        $group_id = absint( $_GET['group_id'] ?? 0 );
        self::button( $group_id );
    }

    public static function button( int $group_id = 0 ): void {
        $url_group = self::export_url( $group_id );
        $url_all   = self::export_url( 0 );
        ?>
        <div class="notice notice-info inline"
             style="padding:6px 12px;display:flex;align-items:center;gap:8px;
                    width:fit-content;margin:8px 0;">
            <span style="font-size:18px;">📥</span>
            <?php if ( $group_id > 0 ) : ?>
                <a href="<?php echo esc_url( $url_group ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Exportar este índice a Excel', 'indices-estar' ); ?>
                </a>
                <a href="<?php echo esc_url( $url_all ); ?>" class="button">
                    <?php esc_html_e( 'Exportar todos los índices', 'indices-estar' ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $url_all ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Exportar todos los índices a Excel', 'indices-estar' ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    // ------------------------------------------------------------------
    // Interceptar petición de descarga
    // ------------------------------------------------------------------

    public function maybe_export(): void {
        if ( ( $_GET['action'] ?? '' ) !== self::ACTION ) return;

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permisos insuficientes.', 'indices-estar' ) );
        }
        if ( ! check_admin_referer( self::ACTION ) ) {
            wp_die( esc_html__( 'Fallo de seguridad. Recarga la página e inténtalo de nuevo.', 'indices-estar' ) );
        }

        $group_id = absint( $_GET['group_id'] ?? 0 );
        $this->export( $group_id );
    }

    // ------------------------------------------------------------------
    // Generación del Excel
    // ------------------------------------------------------------------

    private function export( int $group_id ): void {

        $rows_db = $this->fetch_data( $group_id );

        if ( empty( $rows_db ) ) {
            wp_die(
                '<p><strong>' . esc_html__( 'No hay datos que exportar.', 'indices-estar' ) . '</strong></p>',
                'Sin datos',
                [ 'back_link' => true ]
            );
        }

        // Fila de cabecera
        $data = [ [ 'Titulo', 'Sección', 'Autor', 'Número', 'Fecha' ] ];

        foreach ( $rows_db as $row ) {
            $titulo   = trim( $row->title     ?? '' );
            $item_url = trim( $row->item_url  ?? '' );

            // Si el artículo tiene URL propia, el título es un hipervínculo
            $cell_titulo = ( $item_url !== '' && filter_var( $item_url, FILTER_VALIDATE_URL ) )
                ? [ $titulo, $item_url ]
                : $titulo;

            // index_date es DATE en BD (ej. "1974-06-01") → DateTime → DD/MM/YYYY
            $fecha = null;
            if ( ! empty( $row->index_date ) && $row->index_date !== '0000-00-00' ) {
                try   { $fecha = new DateTime( $row->index_date ); }
                catch ( Exception $e ) { $fecha = $row->index_date; }
            }

            $data[] = [
                $cell_titulo,
                $row->section ?? '',
                $row->author  ?? '',
                (int) ( $row->number ?? 0 ),
                $fecha ?? '',
            ];
        }

        // Nombre del fichero: índices_estar_NombreGrupo_YYYYMMDD.xlsx
        $suffix = '';
        if ( $group_id > 0 ) {
            $tg     = Indices_Estar_DB::table_groups();
            global $wpdb;
            $nombre = $wpdb->get_var( $wpdb->prepare(
                "SELECT name FROM $tg WHERE id = %d LIMIT 1", $group_id
            ) );
            if ( $nombre ) $suffix = '_' . sanitize_title( $nombre );
        }

        $filename = 'indices_estar' . $suffix . '_' . date( 'Ymd' ) . '.xlsx';
        SimpleXLSXGen::fromArray( $data, 'Índices' )->download_as( $filename );
    }

    // ------------------------------------------------------------------
    // Consulta a la base de datos usando los métodos de Indices_Estar_DB
    // ------------------------------------------------------------------

    private function fetch_data( int $group_id ): array {
        global $wpdb;

        $tg = Indices_Estar_DB::table_groups();   // tm_estar_index_groups
        $ti = Indices_Estar_DB::table_issues();   // tm_estar_indices
        $tt = Indices_Estar_DB::table_items();    // tm_estar_index_items

        if ( $group_id > 0 ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT
                     t.title,
                     t.section,
                     t.author,
                     i.number,
                     i.index_date,
                     t.item_url
                 FROM      `$tt` t
                 JOIN      `$ti` i ON i.id       = t.index_id
                 JOIN      `$tg` g ON g.id       = i.group_id
                 WHERE     g.id = %d
                 ORDER BY  i.year ASC, i.number ASC, t.sort_order ASC, t.id ASC",
                $group_id
            ) );
        }

        // Todos los grupos
        return $wpdb->get_results(
            "SELECT
                 t.title,
                 t.section,
                 t.author,
                 i.number,
                 i.index_date,
                 t.item_url
             FROM      `$tt` t
             JOIN      `$ti` i ON i.id = t.index_id
             ORDER BY  i.group_id ASC, i.year ASC, i.number ASC, t.sort_order ASC, t.id ASC"
        );
    }

    // ------------------------------------------------------------------
    // Helper: construye la URL de exportación con nonce
    // ------------------------------------------------------------------

    private static function export_url( int $group_id = 0 ): string {
        $args = [ 'action' => self::ACTION ];
        if ( $group_id > 0 ) $args['group_id'] = $group_id;
        return wp_nonce_url(
            add_query_arg( $args, admin_url( 'admin.php' ) ),
            self::ACTION
        );
    }
}
