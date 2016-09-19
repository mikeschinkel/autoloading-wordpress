<?php

/**
 * Deprecated HTTP Transport method which used fsockopen.
 *
 * This class is not used, and is included for backward compatibility only.
 * All code should make use of WP_Http directly through its API.
 *
 * @see WP_HTTP::request
 *
 * @since 2.7.0
 * @deprecated 3.7.0 Please use WP_HTTP::request() directly
 */
class WP_HTTP_Fsockopen extends WP_HTTP_Streams {
	// For backward compatibility for users who are using the class directly.
}
