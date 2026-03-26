<?php
/**
 * SimpleXLSXGen - Generador ligero de archivos .xlsx
 * Requiere únicamente la extensión ZipArchive de PHP (activa por defecto en Raiola).
 *
 * Uso básico:
 *   $xlsx = SimpleXLSXGen::fromArray( $filas, 'Nombre hoja' );
 *   $xlsx->download_as( 'fichero.xlsx' );
 *
 * @package IndicesEstar
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SimpleXLSXGen {

    /** @var array  [ ['name'=>string, 'rows'=>array] ] */
    private array $sheets = [];

    /** @var array  cadenas compartidas → índice */
    private array $shared_map = [];

    /** @var array  cadenas compartidas en orden */
    private array $shared = [];

    // ------------------------------------------------------------------
    // API pública
    // ------------------------------------------------------------------

    public static function fromArray( array $rows, string $sheet_name = 'Hoja1' ): self {
        $inst = new self();
        $inst->add_sheet( $rows, $sheet_name );
        return $inst;
    }

    public function add_sheet( array $rows, string $sheet_name = 'Hoja1' ): self {
        $this->sheets[] = [ 'name' => $sheet_name, 'rows' => $rows ];
        return $this;
    }

    /**
     * Envía el archivo al navegador y termina la ejecución.
     */
    public function download_as( string $filename ): void {
        $data = $this->to_string();
        if ( ob_get_length() ) ob_clean();
        header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        header( 'Content-Disposition: attachment; filename="' . rawurlencode( $filename ) . '"' );
        header( 'Content-Length: ' . strlen( $data ) );
        header( 'Cache-Control: max-age=0' );
        echo $data;
        exit;
    }

    /**
     * Devuelve el contenido binario del .xlsx.
     */
    public function to_string(): string {
        $this->shared_map = [];
        $this->shared     = [];

        // Construir hojas primero (para poblar shared strings)
        $sheets_xml = [];
        foreach ( $this->sheets as $i => $sheet ) {
            $sheets_xml[ $i ] = $this->build_sheet( $sheet['rows'] );
        }

        $tmp = tempnam( sys_get_temp_dir(), 'xlsx_' );
        $zip = new ZipArchive();
        $zip->open( $tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE );

        $zip->addFromString( '[Content_Types].xml',       $this->build_content_types() );
        $zip->addFromString( '_rels/.rels',                $this->build_root_rels() );
        $zip->addFromString( 'xl/workbook.xml',            $this->build_workbook() );
        $zip->addFromString( 'xl/_rels/workbook.xml.rels', $this->build_workbook_rels() );
        $zip->addFromString( 'xl/styles.xml',              $this->build_styles() );
        $zip->addFromString( 'xl/sharedStrings.xml',       $this->build_shared_strings() );

        foreach ( $sheets_xml as $i => $xml ) {
            $zip->addFromString( 'xl/worksheets/sheet' . ( $i + 1 ) . '.xml', $xml );
        }

        $zip->close();
        $data = file_get_contents( $tmp );
        unlink( $tmp );
        return $data;
    }

    // ------------------------------------------------------------------
    // Construcción de piezas XML
    // ------------------------------------------------------------------

    private function build_content_types(): string {
        $overrides = '';
        foreach ( $this->sheets as $i => $_ ) {
            $n = $i + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml"'
                        . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
             . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
             . '<Default Extension="xml"  ContentType="application/xml"/>'
             . '<Override PartName="/xl/workbook.xml"      ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
             . '<Override PartName="/xl/styles.xml"        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
             . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
             . $overrides
             . '</Types>';
    }

    private function build_root_rels(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
             . '</Relationships>';
    }

    private function build_workbook(): string {
        $sheets = '';
        foreach ( $this->sheets as $i => $s ) {
            $n      = $i + 1;
            $name   = htmlspecialchars( $s['name'], ENT_XML1 );
            $sheets .= '<sheet name="' . $name . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
             . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
             . '<sheets>' . $sheets . '</sheets>'
             . '</workbook>';
    }

    private function build_workbook_rels(): string {
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
              . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
              . '<Relationship Id="rId0"  Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"        Target="styles.xml"/>'
              . '<Relationship Id="rId00" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        foreach ( $this->sheets as $i => $_ ) {
            $n    = $i + 1;
            $rels .= '<Relationship Id="rId' . $n . '"'
                   . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                   . ' Target="worksheets/sheet' . $n . '.xml"/>';
        }
        return $rels . '</Relationships>';
    }

    private function build_styles(): string {
        // Estilos:
        //   s=0  normal   (Arial 11)
        //   s=1  cabecera (Arial 11 negrita + fondo azul claro #D9E1F2)
        //   s=2  fecha    DD/MM/YYYY
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . '<numFmts count="1">'
             .   '<numFmt numFmtId="164" formatCode="DD/MM/YYYY"/>'
             . '</numFmts>'
             . '<fonts count="2">'
             .   '<font><sz val="11"/><name val="Arial"/></font>'
             .   '<font><b/><sz val="11"/><name val="Arial"/></font>'
             . '</fonts>'
             . '<fills count="3">'
             .   '<fill><patternFill patternType="none"/></fill>'
             .   '<fill><patternFill patternType="gray125"/></fill>'
             .   '<fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/></patternFill></fill>'
             . '</fills>'
             . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
             . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
             . '<cellXfs count="3">'
             .   '<xf numFmtId="0"   fontId="0" fillId="0" borderId="0" xfId="0"/>'
             .   '<xf numFmtId="0"   fontId="1" fillId="2" borderId="0" xfId="0"/>'
             .   '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
             . '</cellXfs>'
             . '</styleSheet>';
    }

    private function build_shared_strings(): string {
        $count = count( $this->shared );
        $xml   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
               . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
               . ' count="' . $count . '" uniqueCount="' . $count . '">';
        foreach ( $this->shared as $s ) {
            $xml .= '<si><t xml:space="preserve">' . htmlspecialchars( $s, ENT_XML1 ) . '</t></si>';
        }
        return $xml . '</sst>';
    }

    private function build_sheet( array $rows ): string {
        // Pre-calcular anchos de columna
        $col_widths = [];
        foreach ( $rows as $row ) {
            foreach ( $row as $ci => $cell ) {
                $val  = is_array( $cell ) ? ( $cell[0] ?? '' ) : ( $cell instanceof DateTime ? $cell->format( 'd/m/Y' ) : (string) $cell );
                $len  = mb_strlen( $val );
                $col_widths[ $ci ] = max( $col_widths[ $ci ] ?? 10, min( $len + 2, 80 ) );
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
             . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        if ( ! empty( $col_widths ) ) {
            $xml .= '<cols>';
            foreach ( $col_widths as $ci => $w ) {
                $col  = $ci + 1;
                $xml .= '<col min="' . $col . '" max="' . $col . '" width="' . $w . '" bestFit="1" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ( $rows as $ri => $row ) {
            $row_num   = $ri + 1;
            $is_header = ( $ri === 0 );
            $xml      .= '<row r="' . $row_num . '">';

            foreach ( $row as $ci => $cell ) {
                $col_letter = $this->col_letter( $ci + 1 );
                $cell_ref   = $col_letter . $row_num;
                $s          = $is_header ? 1 : 0;

                // Hipervínculo: array [ texto, url ] → fórmula HYPERLINK()
                if ( is_array( $cell ) ) {
                    $text = (string) ( $cell[0] ?? '' );
                    $url  = (string) ( $cell[1] ?? '' );
                    $cell = $url !== ''
                        ? '=HYPERLINK("' . str_replace( '"', '""', $url ) . '","' . str_replace( '"', '""', $text ) . '")'
                        : $text;
                }

                if ( $cell instanceof DateTime ) {
                    $serial = (int) ( new DateTime( '1899-12-30' ) )->diff( $cell )->days;
                    $xml   .= '<c r="' . $cell_ref . '" s="2"><v>' . $serial . '</v></c>';

                } elseif ( is_string( $cell ) && str_starts_with( $cell, '=' ) ) {
                    $xml .= '<c r="' . $cell_ref . '" s="' . $s . '">'
                          .   '<f>' . htmlspecialchars( substr( $cell, 1 ), ENT_XML1 ) . '</f>'
                          . '</c>';

                } elseif ( is_int( $cell ) || is_float( $cell ) ) {
                    $xml .= '<c r="' . $cell_ref . '" s="' . $s . '"><v>' . $cell . '</v></c>';

                } elseif ( is_string( $cell ) && $cell !== '' ) {
                    $si   = $this->shared_index( $cell );
                    $xml .= '<c r="' . $cell_ref . '" t="s" s="' . $s . '"><v>' . $si . '</v></c>';

                } else {
                    $xml .= '<c r="' . $cell_ref . '" s="' . $s . '"/>';
                }
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData><pageSetup orientation="landscape"/></worksheet>';
    }

    // ------------------------------------------------------------------
    // Utilidades internas
    // ------------------------------------------------------------------

    private function shared_index( string $str ): int {
        if ( ! isset( $this->shared_map[ $str ] ) ) {
            $this->shared_map[ $str ] = count( $this->shared );
            $this->shared[]           = $str;
        }
        return $this->shared_map[ $str ];
    }

    private function col_letter( int $n ): string {
        $col = '';
        while ( $n > 0 ) {
            $n--;
            $col = chr( 65 + ( $n % 26 ) ) . $col;
            $n   = (int) floor( $n / 26 );
        }
        return $col;
    }
}
