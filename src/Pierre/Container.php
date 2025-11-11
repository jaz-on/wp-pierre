<?php
/**
 * Pierre's dependency injection container - he manages dependencies! 🪨
 *
 * This class provides a simple dependency injection container
 * for Pierre's components.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre;

/**
 * Container class - Pierre's dependency injection container! 🪨
 *
 * @since 1.0.0
 */
class Container {
	/**
	 * Registered services.
	 *
	 * @var array<string, mixed>
	 */
	private array $services = array();

	/**
	 * Shared instances.
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = array();

	/**
	 * Get a service from the container.
	 *
	 * @since 1.0.0
	 * @param string $class_name Class name to resolve.
	 * @return mixed Service instance.
	 */
	public function get( string $class_name ) {
		// Return cached instance if available.
		if ( isset( $this->instances[ $class_name ] ) ) {
			return $this->instances[ $class_name ];
		}

		// Return registered service if available.
		if ( isset( $this->services[ $class_name ] ) ) {
			$service = $this->services[ $class_name ];
			if ( is_callable( $service ) ) {
				$instance = $service( $this );
			} else {
				$instance = $service;
			}
			$this->instances[ $class_name ] = $instance;
			return $instance;
		}

		// Auto-resolve class if it exists.
		if ( class_exists( $class_name ) ) {
			$instance = new $class_name();
			$this->instances[ $class_name ] = $instance;
			return $instance;
		}

		// Fallback: try to get from Plugin instance if available.
		if ( function_exists( 'pierre' ) ) {
			$plugin = pierre();
			if ( method_exists( $plugin, 'get_' . strtolower( str_replace( '\\', '_', $class_name ) ) ) ) {
				$method = 'get_' . strtolower( str_replace( '\\', '_', $class_name ) );
				return $plugin->$method();
			}
		}

		// Last resort: throw exception.
		throw new \RuntimeException( sprintf( 'Service %s not found in container.', $class_name ) );
	}

	/**
	 * Register a service in the container.
	 *
	 * @since 1.0.0
	 * @param string   $class_name Class name.
	 * @param callable|object $service Service factory or instance.
	 * @return void
	 */
	public function set( string $class_name, $service ): void {
		$this->services[ $class_name ] = $service;
		unset( $this->instances[ $class_name ] );
	}
}

