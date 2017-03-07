<?php

/**
 *
 * @file
 * @version
 * @copyright 2017 phausmann.de
 * @author Patrick Hausmann <privat@patrick-designs.de>
 */

namespace WpKeywordMonitor;
use WpKeywordMonitor\Model\KeywordResult;
use WpKeywordMonitor\Query\KeywordResultQuery;

class WpKeywordMonitor
{

    public static function install(\wpdb $_wpdb)
    {

        $keywordsTableName= $_wpdb->prefix.KeywordQuery::TABLE_NAME;
        $sql = "SELECT * FROM ".$keywordsTableName.";";
        if($_wpdb->query($sql) === false)
        {
            $sql = "CREATE TABLE `$keywordsTableName` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `keyword` varchar(255) NOT NULL,
              `created_on` date NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            $_wpdb->query($sql);
        }

        $tableName= $_wpdb->prefix.KeywordResultQuery::TABLE_NAME;
        $sql = "SELECT * FROM ".$tableName.";";
        if($_wpdb->query($sql) === false)
        {
            $sql = "CREATE TABLE `$tableName` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `rank` int(3) NOT NULL,
              `keyword_id` int(11) NOT NULL,
              `date_time` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `keyword_id` (`keyword_id`),
              CONSTRAINT `keyword_id` FOREIGN KEY (`keyword_id`) REFERENCES `$keywordsTableName` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            $_wpdb->query($sql);
        }
    }


    public function unIstall()
    {

    }


    public static function enhanceCronSchedule($schedules)
    {
        if(!isset($schedules["10min"])){
            $schedules["10min"] = array(
                'interval' => 10*60,
                'display' => __('Once every 10 minutes'));
        }
        return $schedules;
    }


    public static function checkRanks($_wpdb, $_direct = false)
    {
        $keywordQuery = new \WpKeywordMonitor\KeywordQuery($_wpdb);
        $keywordResultQuery = new \WpKeywordMonitor\Query\KeywordResultQuery($_wpdb);
        $options = get_option(WP_KEYWORD_MONITOR_OPTIONS);
        $rankChecker = new RankChecker($options["apiKey"], $options["cx"], $options["domain"]);

        if (isset($options["maxApiCallsPerDay"])) $maxApiCallsPerDay = $options["maxApiCallsPerDay"];
        else $maxApiCallsPerDay = 100;

        if ((isset($options["autoMode"]) && $options["autoMode"]) || $_direct)
        {
            if (isset($options["checkInterval"])) $checkInterval = $options["checkInterval"];
            else $checkInterval = 1;

            $usedApiCallsWithDate = get_option(WP_KEYWORD_MONITOR_USED_CALLS, 0);

            $today = date("Y-m-d", current_time("timestamp"));
            if (isset($usedApiCallsWithDate[$today])) $usedApiCalls = (int)$usedApiCallsWithDate[$today];
            else $usedApiCalls = 0;

            foreach ($keywordQuery->getKeywordsWhichNeedACheck($checkInterval, 10) as $keyword)
            {
                if (($usedApiCalls+$rankChecker->usedApiCalls)<$maxApiCallsPerDay)
                {
                    $keywordResult = $rankChecker->calculateKeywordResultOfKeyword($keyword, $options["searchDepth"]);
                    if ($keywordResult instanceof KeywordResult)
                    {
                        $keywordResultQuery->addKeywordResultToKeyword($keywordResult);
                        update_option(WP_KEYWORD_MONITOR_ERROR, null);
                    }
                    else update_option(WP_KEYWORD_MONITOR_ERROR, array("error" => $keywordResult));

                }
                else break;
            }

            update_option(WP_KEYWORD_MONITOR_USED_CALLS, array(date("Y-m-d", current_time("timestamp"))=>(string)($usedApiCalls+$rankChecker->usedApiCalls)));
        }
    }
}