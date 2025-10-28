<?php
/**
 * Pierre's surveillance interface - he defines how to watch! 🪨
 * 
 * This interface defines the contract for all surveillance components
 * that Pierre uses to monitor WordPress translations.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Surveillance;

/**
 * Watcher interface - Pierre's surveillance contract! 🪨
 * 
 * @since 1.0.0
 */
interface WatcherInterface {
    
    /**
     * Pierre starts his surveillance! 🪨
     * 
     * @since 1.0.0
     * @return bool True if surveillance started successfully, false otherwise
     */
    public function start_surveillance(): bool;
    
    /**
     * Pierre stops his surveillance! 🪨
     * 
     * @since 1.0.0
     * @return bool True if surveillance stopped successfully, false otherwise
     */
    public function stop_surveillance(): bool;
    
    /**
     * Pierre checks if he's currently watching! 🪨
     * 
     * @since 1.0.0
     * @return bool True if surveillance is active, false otherwise
     */
    public function is_surveillance_active(): bool;
    
    /**
     * Pierre gets his surveillance status! 🪨
     * 
     * @since 1.0.0
     * @return array Array containing surveillance status information
     */
    public function get_surveillance_status(): array;
    
    /**
     * Pierre watches a specific project! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug to watch
     * @param string $locale_code The locale code to monitor
     * @return bool True if project is now being watched, false otherwise
     */
    public function watch_project(string $project_slug, string $locale_code): bool;
    
    /**
     * Pierre stops watching a specific project! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug to stop watching
     * @param string $locale_code The locale code to stop monitoring
     * @return bool True if project is no longer being watched, false otherwise
     */
    public function unwatch_project(string $project_slug, string $locale_code): bool;
    
    /**
     * Pierre gets all projects he's currently watching! 🪨
     * 
     * @since 1.0.0
     * @return array Array of projects being watched
     */
    public function get_watched_projects(): array;
}