<?php
/**
 * Pierre's projects catalog - he catalogs WordPress projects! 🪨
 * 
 * This class manages a catalog of WordPress plugins and themes
 * from WordPress.org for discovery and monitoring.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Discovery;

use Pierre\Traits\StatusTrait;

/**
 * Projects Catalog class - Pierre's project discovery system! 🪨
 * 
 * @since 1.0.0
 */
class ProjectsCatalog {
    use StatusTrait;
    
    /**
     * Fetch projects from catalog with pagination and filters.
     * 
     * @since 1.0.0
     * @param array $args Arguments: type, source, search, page, per_page, sort, tags
     * @return array Array with 'items', 'total', 'page', 'per_page', 'pages'
     */
    public function fetch( array $args ): array {
        $type     = (array) ( $args['type'] ?? array( 'plugin' ) );
        $source   = sanitize_key( $args['source'] ?? 'popular' );
        $search   = sanitize_text_field( $args['search'] ?? '' );
        $page     = max( 1, (int) ( $args['page'] ?? 1 ) );
        $per_page = min( 100, max( 1, (int) ( $args['per_page'] ?? 24 ) ) );
        $sort     = sanitize_key( $args['sort'] ?? '' );
        $tags     = (array) ( $args['tags'] ?? array() );
        
        // Build cache key
        $cache_key = 'pierre_catalog_fetch_' . md5( wp_json_encode( array( $type, $source, $search, $page, $per_page, $sort, $tags ) ) );
        
        // Check cache first
        $cached = get_option( $cache_key, null );
        if ( $cached !== null && is_array( $cached ) ) {
            return $cached;
        }
        
        $items = array();
        $meta  = get_option( 'pierre_projects_catalog_meta', [] );
        $index = is_array( $meta['index'] ?? null ) ? $meta['index'] : array();
        
        // Fetch from stored catalog pages
        foreach ( $type as $t ) {
            $t = sanitize_key( $t );
            if ( ! in_array( $t, array( 'plugin', 'theme' ), true ) ) {
                continue;
            }
            
            $last_page = (int) ( $index[ $t ]['last_page'] ?? 1 );
            $catalog_pages = array();
            
            // Collect items from catalog pages
            for ( $p = 1; $p <= $last_page; $p++ ) {
                $chunk_key = 'pierre_projects_catalog_' . $t . '_' . $p;
                $chunk = get_option( $chunk_key, [] );
                if ( is_array( $chunk ) ) {
                    $catalog_pages = array_merge( $catalog_pages, $chunk );
                }
            }
            
            // Filter by source if needed
            if ( $source === 'popular' || $source === 'featured' ) {
                // Filter logic would go here based on stored metadata
            }
            
            // Apply search filter
            if ( ! empty( $search ) ) {
                $catalog_pages = array_filter( $catalog_pages, function( $item ) use ( $search ) {
                    $name = (string) ( $item['name'] ?? '' );
                    $slug = (string) ( $item['slug'] ?? '' );
                    $desc = (string) ( $item['description'] ?? '' );
                    return stripos( $name, $search ) !== false 
                        || stripos( $slug, $search ) !== false
                        || stripos( $desc, $search ) !== false;
                } );
            }
            
            // Apply tags filter
            if ( ! empty( $tags ) ) {
                $catalog_pages = array_filter( $catalog_pages, function( $item ) use ( $tags ) {
                    $item_tags = (array) ( $item['tags'] ?? array() );
                    return ! empty( array_intersect( $tags, $item_tags ) );
                } );
            }
            
            $items = array_merge( $items, $catalog_pages );
        }
        
        // Apply sorting
        if ( ! empty( $sort ) ) {
            usort( $items, function( $a, $b ) use ( $sort ) {
                $a_val = $a[ $sort ] ?? 0;
                $b_val = $b[ $sort ] ?? 0;
                if ( $sort === 'name' || $sort === 'slug' ) {
                    return strcmp( (string) $a_val, (string) $b_val );
                }
                return (int) $b_val - (int) $a_val;
            } );
        }
        
        $total = count( $items );
        $pages = (int) ceil( $total / $per_page );
        $offset = ( $page - 1 ) * $per_page;
        $items = array_slice( $items, $offset, $per_page );
        
        $result = array(
            'items'    => array_values( $items ),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => $pages,
        );
        
        // Cache for 15 minutes
        update_option( $cache_key, $result );
        set_transient( $cache_key . '_timeout', time(), 15 * MINUTE_IN_SECONDS );
        
        return $result;
    }
    
    /**
     * Rebuild the projects catalog.
     * 
     * @since 1.0.0
     * @return array Array with 'success' => bool, 'errors' => array
     */
    public function rebuild(): array {
        $errors = array();
        
        try {
            $meta = get_option( 'pierre_projects_catalog_meta', [] );
            if ( ! is_array( $meta ) ) {
                $meta = array();
            }
            
            $sources = (array) ( $meta['sources'] ?? array() );
            $schedule = (array) ( $meta['schedule'] ?? array() );
            $max_per_run = (int) ( $schedule['max_per_run'] ?? 200 );
            
            // Schedule rebuild via cron if not already running
            if ( ! get_transient( 'pierre_catalog_progress' ) ) {
                set_transient( 'pierre_catalog_progress', array( 'started' => time() ), 2 * HOUR_IN_SECONDS );
                wp_schedule_single_event( time() + 60, 'pierre_build_projects_catalog' );
                
                return array(
                    'success' => true,
                    'message' => __( 'Catalog rebuild scheduled.', 'wp-pierre' ),
                    'errors'  => array(),
                );
            }
            
            return array(
                'success' => false,
                'message' => __( 'Catalog rebuild already in progress.', 'wp-pierre' ),
                'errors'  => array(),
            );
            
        } catch ( \Throwable $e ) {
            $errors[] = array(
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            );
            
            return array(
                'success' => false,
                'errors'  => $errors,
            );
        }
    }
    
    /**
     * Mark a project as known for prioritization.
     * 
     * @since 1.0.0
     * @param string $type Project type (plugin, theme, meta, etc.)
     * @param string $slug Project slug
     * @return void
     */
    public function mark_known( string $type, string $slug ): void {
        $type = sanitize_key( $type );
        $slug = sanitize_key( $slug );
        
        if ( empty( $slug ) ) {
            return;
        }
        
        $meta = get_option( 'pierre_projects_catalog_meta', [] );
        if ( ! is_array( $meta ) ) {
            $meta = array();
        }
        
        if ( ! isset( $meta['known'] ) || ! is_array( $meta['known'] ) ) {
            $meta['known'] = array();
        }
        
        $key = $type . '_' . $slug;
        if ( ! isset( $meta['known'][ $key ] ) ) {
            $meta['known'][ $key ] = time();
            update_option( 'pierre_projects_catalog_meta', $meta, false );
        }
    }
    
    /**
     * Get status message.
     *
     * @since 1.0.0
     * @return string Status message
     */
    protected function get_status_message(): string {
        return 'Projects catalog ready';
    }

    /**
     * Get status details.
     *
     * @since 1.0.0
     * @return array Status details
     */
    protected function get_status_details(): array {
        $meta = get_option( 'pierre_projects_catalog_meta', [] );
        if ( ! is_array( $meta ) ) {
            $meta = array();
        }
        
        $index = is_array( $meta['index'] ?? null ) ? $meta['index'] : array();
        $known = is_array( $meta['known'] ?? null ) ? $meta['known'] : array();
        $progress = get_transient( 'pierre_catalog_progress' );
        $errors = get_option( 'pierre_projects_catalog_errors', [] );
        
        return array(
            'meta'         => $meta,
            'index'        => $index,
            'known_count'  => count( $known ),
            'progress'     => is_array( $progress ) ? $progress : null,
            'errors_count' => is_array( $errors ) ? count( $errors ) : 0,
            'last_build'   => $meta['last_build'] ?? null,
            'next_build'   => $meta['next_build'] ?? null,
        );
    }
}

