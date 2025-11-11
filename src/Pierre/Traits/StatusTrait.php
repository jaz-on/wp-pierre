<?php
/**
 * Status trait - provides standardized get_status() method! 🪨
 *
 * This trait provides a common interface for status reporting across
 * Pierre's components.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Traits;

/**
 * StatusTrait - standardized status reporting! 🪨
 *
 * @since 1.0.0
 */
trait StatusTrait {
	/**
	 * Get component status.
	 *
	 * @since 1.0.0
	 * @return array Status information
	 */
	public function get_status(): array {
		$details = $this->get_status_details();
		$message = $this->get_status_message();

		// Merge message into details (message may override if present in details)
		return array_merge(
			$details,
			array(
				'message' => $message,
			)
		);
	}

	/**
	 * Get status message.
	 *
	 * @since 1.0.0
	 * @return string Status message
	 */
	abstract protected function get_status_message(): string;

	/**
	 * Get status details (all fields except message).
	 *
	 * @since 1.0.0
	 * @return array Status details
	 */
	abstract protected function get_status_details(): array;
}

