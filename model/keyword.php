<?php
/**
 *
 * @copyright 2017 DerPade.de
 * @author Patrick Hausmann <privat@patrick-designs.de>
 */


namespace WpKeywordMonitor\Model;


use WpKeywordMonitor\Query\KeywordResultQuery;

class Keyword
{
    public $keyword;
    /**
     * @var KeywordResult[]
     */
    public $keywordResults;
    public $createdOn;
    public $id;

    function __construct($_keyword, $_id = null, $_createdOn = null, $_keywordResults = array())
    {
        $this->keyword = $_keyword;
        $this->keywordResults = $_keywordResults;
        $this->createdOn = $_createdOn;
        $this->id = $_id;
    }

    function getLastResultDateTime()
    {
        if (count($this->keywordResults)!=0)
        {
            return $this->keywordResults[count($this->keywordResults)-1]->dateTime;
        }
        return __("Not checked yet", WP_KEYWORD_MONITOR_TEXT_DOMAIN);
    }

    function getLastResultDate()
    {
        if (count($this->keywordResults)!=0)
        {
            return date("Y-m-d", strtotime($this->keywordResults[count($this->keywordResults)-1]->dateTime));
        }
        else return null;
    }

    function getPreviousResultDateTime()
    {
        if (count($this->keywordResults)>1)
        {
            return $this->keywordResults[count($this->keywordResults)-2]->dateTime;
        }
        return __("Not checked yet", WP_KEYWORD_MONITOR_TEXT_DOMAIN);
    }

    function getResultForDate( $_date)
    {
        foreach ($this->keywordResults as $keywordResult)
        {
            if ($_date<=$keywordResult->getDate() && $keywordResult->getDate()<=$_date)
            {
                return $keywordResult;
            }
        }

        return null;
    }

    function getLastResultRank()
    {
        if (count($this->keywordResults)!=0)
        {
            $lastRank = $this->keywordResults[count($this->keywordResults)-1]->rank;
            if ($lastRank==0) return __("No rank found", WP_KEYWORD_MONITOR_TEXT_DOMAIN);
            else return (int)$this->keywordResults[count($this->keywordResults)-1]->rank;
        }
        else return __("No rank available", WP_KEYWORD_MONITOR_TEXT_DOMAIN);
    }


    function getPreviousResultRank()
    {
        if (count($this->keywordResults)>1)
        {
            return (int)$this->keywordResults[count($this->keywordResults)-2]->rank;
        }
        else return null;
    }
}