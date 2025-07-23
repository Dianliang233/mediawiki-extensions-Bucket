<?php

namespace MediaWiki\Extension\Bucket;

use FormlessAction;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser as ParserParser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\ParserOutputFlags;
use MediaWiki\Title\TitleValue;
use Wikimedia\Parsoid\Core\SectionMetadata;
use Wikimedia\Parsoid\Core\TOCData;

class BucketAction extends FormlessAction {
	public function getName() {
		return 'bucket';
	}

	public function requiresWrite() {
		return false;
	}

	public function requiresUnblock() {
		return false;
	}

	public function getDescription() {
		return wfMessage( 'bucket-action-description' )->text();
	}

	public function onView() {
		$this->getOutput()->enableOOUI(); // We want to use OOUI for consistent styling

		$out = $this->getOutput();
		$title = $this->getArticle()->getTitle();
		$pageId = $this->getArticle()->getPage()->getId();
		$out->setPageTitleMsg( wfMessage( 'bucket-action-title', $title ) );
		$out->addModuleStyles( [
			'mediawiki.codex.messagebox.styles'
		] );

		$dbw = BucketDatabase::getDB();

		$res = $dbw->newSelectQueryBuilder()
			->from( 'bucket_pages' )
			->select( [ 'bucket_name' ] )
			->where( [ '_page_id' => $pageId ] )
			->groupBy( 'bucket_name' )
			->caller( __METHOD__ )
			->fetchResultSet();
		$buckets = [];
		foreach ( $res as $row ) {
			$buckets[] = $row->bucket_name;
		}

		if ( count( $buckets ) === 0 ) {
			$out->addHTML( Html::noticeBox( $out->msg( 'bucket-action-writes-empty' )->parse(), '' ) );
			return;
		}

		if ( count( $buckets ) > 1 ) {
			$tocData = new TOCData();
			$tocLength = 0;
			foreach ( $buckets as $bucketName ) {
				++$tocLength;
				$tocData->addSection( new SectionMetadata(
					1,
					2,
					'Bucket:' . $bucketName,
					$this->getLanguage()->formatNum( $tocLength ),
					(string) $tocLength,
					null,
					null,
					'bucket-' . $bucketName,
				) );
			}
	}

		$pout = new ParserOutput;
		$pout->setTOCData( $tocData );
		$pout->setOutputFlag( ParserOutputFlags::SHOW_TOC );
		$pout->setRawText( ParserParser::TOC_PLACEHOLDER );

		$res = $dbw->newSelectQueryBuilder()
			->from( 'bucket_schemas' )
			->select( [ 'bucket_name', 'schema_json' ] )
			->where( [ 'bucket_name' => $buckets ] )
			->caller( __METHOD__ )
			->fetchResultSet();
		$schemas = [];
		foreach ( $res as $row ) {
			$schemas[$row->bucket_name] = json_decode( $row->schema_json, true );
		}

		$title = $dbw->addQuotes( $title );
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		foreach ( $buckets as $bucketName ) {
			$bucket_page_name = str_replace( '_', ' ', $bucketName );

			$out->addHTML( '<h2>' . $linkRenderer->makePreloadedLink( new TitleValue( NS_BUCKET, $bucket_page_name ) ) . '</h2>' );

			$fullResult = BucketPageHelper::runQuery( $this->getRequest(), $bucketName, '*', "{'page_name', $title}", 500, 0 );

			$out->addWikiTextAsContent( BucketPageHelper::getResultTable( $schemas[$bucketName], $fullResult['fields'], $fullResult['bucket'] ) );
		}
	}

}
