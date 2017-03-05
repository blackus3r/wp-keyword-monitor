<?php

/**
 *
 * @file
 * @version
 * @copyright 2017 phausmann.de
 * @author Patrick Hausmann <privat@patrick-designs.de>
 */


namespace WpKeywordMonitor;
use WpKeywordMonitor\Model\Keyword;
use WpKeywordMonitor\Model\KeywordResult;
use WpKeywordMonitor\Query\KeywordResultQuery;


class KeywordQuery
{
    const TABLE_NAME = "wp_key_mon_keywords";
    private $wpDb;
    public $fullQualifiedTableName;

    function __construct(\wpdb $_wpDb)
    {
        $this->wpDb = $_wpDb;
        $this->fullQualifiedTableName = $this->wpDb->prefix.KeywordQuery::TABLE_NAME;
    }

    function getAllKeywords($_orderById = "DESC")
    {
        /** @var Keyword[] $keywords */
        $keywords = array();

        $results = $this->wpDb->get_results("
          SELECT 
            *,
            ".$this->fullQualifiedTableName.".id,
            ".$this->wpDb->prefix.KeywordResultQuery::TABLE_NAME.".id  AS keyword_result_id
          FROM ".$this->fullQualifiedTableName." 
          LEFT JOIN ".$this->wpDb->prefix.KeywordResultQuery::TABLE_NAME." 
            ON ".$this->wpDb->prefix.KeywordResultQuery::TABLE_NAME.".keyword_id = ".$this->fullQualifiedTableName.".id OR 
                ".$this->wpDb->prefix.KeywordResultQuery::TABLE_NAME.".id = NULL 
          ORDER BY ".$this->fullQualifiedTableName.".id $_orderById", ARRAY_A );

        foreach ($results as $result)
        {
            if (!isset($keywords[$result["id"]]))
            {
                $keywords[$result["id"]] = new Keyword($result["keyword"], $result["id"], $result["created_on"], array());
            }

            if ($result["rank"]!==null)
            {
                $keywords[$result["id"]]->keywordResults[] = new KeywordResult( $result["id"],  $result["rank"],  $result["date_time"],  $result["keyword_result_id"]);
            }
        }

        return $keywords;
    }

    function getOnlyKeywordsWithResults($_orderById = "DESC")
    {
        $keywords = array();

        foreach ($this->getAllKeywords($_orderById = "DESC") as $allKeyword)
        {
            if (count($allKeyword->keywordResults)!==0)
            {
                $keywords[] = $allKeyword;
            }
        }

        return $keywords;
    }

    function getKeywordsWhichNeedACheck($_checkInterval, $_limit)
    {
        $keywords = array();
        $count = 0;
        foreach ($this->getAllKeywords() as $keyword)
        {
            if ($keyword->getLastResultDate() <= date("Y-m-d", current_time("timestamp")-60*60*24*$_checkInterval) ||
                count($keyword->keywordResults)===0)
            {
                $count++;
                $keywords[] = $keyword;
            }

            if ($count>=$_limit) break;
        }

        return $keywords;
    }

    /**
     *
     */
    function deleteKeyword ($_keywordId)
    {

        $this->wpDb->query( $this->wpDb->prepare("DELETE FROM `". $this->wpDb->prefix.KeywordResultQuery::TABLE_NAME."` WHERE `keyword_id`=$_keywordId;"));
        $this->wpDb->query( $this->wpDb->prepare("DELETE FROM `".$this->fullQualifiedTableName."` WHERE `id`=$_keywordId;"));
    }

    function addKeyword($_keyword)
    {
        if (is_admin())
        {
            $this->wpDb->query( $this->wpDb->prepare( "
				INSERT INTO ".$this->fullQualifiedTableName."
				( keyword, created_on )
				VALUES ( %s, %s)",
                $_keyword, date("Y-m-d", current_time("timestamp"))) );
        }
    }
}