<?php
/**
 * JsonSerializable interface.
 *
 * Compatibility shim for PHP <5.4
 *
 * @link https://secure.php.net/jsonserializable
 * @link https://core.trac.wordpress.org/changeset/34845
 *
 * @since 4.4.0
 */
interface JsonSerializable {
	public function jsonSerialize();
}
