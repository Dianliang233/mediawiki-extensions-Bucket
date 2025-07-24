<?php

namespace MediaWiki\Extension\Bucket\Content;

use MediaWiki\Content\JsonContentHandler;
use MediaWiki\Title\Title;

class BucketContentHandler extends JsonContentHandler {

	public function __construct( $modelId = 'bucket' ) {
		parent::__construct( $modelId );
	}

	/** @inheritDoc */
	public function canBeUsedOn( Title $title ) {
		return $title->getNamespace() === NS_BUCKET;
	}

	/** @inheritDoc */
	protected function getContentClass() {
		return BucketContent::class;
	}
}
