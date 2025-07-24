<?php

namespace MediaWiki\Extension\Bucket\Content;

use MediaWiki\Content\JsonContent;

class BucketContent extends JsonContent {
	/**
	 * @param string $text
	 */
	public function __construct( $text ) {
		parent::__construct( $text, 'bucket' );
	}
}
