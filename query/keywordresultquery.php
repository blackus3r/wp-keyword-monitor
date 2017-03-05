<?php

/**
 *
 * @file
 * @version
 * @copyright 2017 phausmann.de
 * @author Patrick Hausmann <privat@patrick-designs.de>
 */


namespace WpKeywordMonitor\Query;

use WpKeywordMonitor\Model\Keyword;
use WpKeywordMonitor\Model\KeywordResult;

class KeywordResultQuery
{
    const TABLE_NAME = "wp_key_mon_keyword_results";

    public $fullQualifiedTableName;
    private $wpdb;
    function __construct($_wpDb)
    {
        $this->wpdb = $_wpDb;
        $this->fullQualifiedTableName = $this->wpdb->prefix.self::TABLE_NAME;
    }

    function addKeywordResultToKeyword(KeywordResult $_keywordResult)
    {
        $this->wpdb->query( $this->wpdb->prepare( "
				INSERT INTO ".$this->fullQualifiedTableName."
				( rank, keyword_id, date_time )
				VALUES ( %s, %s, %s)",
            $_keywordResult->rank, $_keywordResult->keywordId, $_keywordResult->dateTime) );
    }
}