<?php

/**
 *
 * @file
 * @version
 * @copyright 2017 CN-Consult GmbH
 * @author Patrick Hausmann <privat@patrick-designs.de>
 */



namespace WpKeywordMonitor\Model;


class KeywordResult
{
    public $id;
    public $keywordId;
    public $rank;
    public $dateTime;

    function __construct($_keywordId, $_rank, $_dateTime = null, $_id = null)
    {
        if (!$_dateTime) $this->dateTime = date("Y-m-d H:i:s");
        else $this->dateTime = $_dateTime;

        $this->id = $_id;
        $this->keywordId = $_keywordId;
        $this->rank = $_rank;
    }

    function getDate()
    {
        return date("Y-m-d", strtotime($this->dateTime));
    }
}