<?php

/**
 *
 * @file
 * @version
 * @copyright 2017 phausmann.de
 * @author Patrick Hausmann <privat@patrick-designs.de>
 */


namespace WpKeywordMonitor;


use WpKeywordMonitor\model\Keyword;
use WpKeywordMonitor\Model\KeywordResult;

class RankChecker
{
    private $apiKey;
    private $cx;
    private $domain;
    public $usedApiCalls = 0;

    function __construct($_apiKey, $_cx, $_domain)
    {
        $this->apiKey = $_apiKey;
        $this->cx = $_cx;
        $this->domain = $_domain;
    }

    public function calculateKeywordResultOfKeyword(Keyword $_keyword, $_searchDepth=1)
    {
        if (!$_searchDepth) $_searchDepth = 1;
        $position = 0;
        $found = false;

        for ($i=1; $i<=$_searchDepth; $i++)
        {
	        $start = ($i * 10) + 1 - 10;

            if ($found===false)
            {
                $curl = curl_init();
                $keyword  = urlencode($_keyword->keyword);
                $url = "https://www.googleapis.com/customsearch/v1?key=$this->apiKey&cx=$this->cx&q=$keyword&filter=1&start=$start&num=10&alt=json";
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

                $response = curl_exec($curl);
                $jsonDecoded = json_decode($response);

                if ($jsonDecoded)
                {
                    if (isset($jsonDecoded->items))
                    {
                        $this->usedApiCalls++;
                        $position = 1;
                        foreach ($jsonDecoded->items as $entry)
                        {
                            if ($found===false && strpos($entry->formattedUrl, $this->domain) !== false)
                            {
                                $found = true;
                                break;
                            }
                            else if ($found===false) $position++;
                        }
                    }
                    else if (isset($jsonDecoded->error) && $jsonDecoded->error->errors[0]->reason == "dailyLimitExceeded")
                    {

                        return __("Daily limit exceeded (".date("Y-m-d H:i:s", current_time("timestamp")).")", WP_KEYWORD_MONITOR_TEXT_DOMAIN);
                    }
                    else return __("Unknown error (".date("Y-m-d H:i:s", current_time("timestamp")).")", WP_KEYWORD_MONITOR_TEXT_DOMAIN);
                }
                else return __("Unknown error (".date("Y-m-d H:i:s", current_time("timestamp")).")", WP_KEYWORD_MONITOR_TEXT_DOMAIN);

                curl_close($curl);
            }
            else break;
        }

        if ($found) return new KeywordResult($_keyword->id, $position);
        else return new KeywordResult($_keyword->id, 0);
    }
}