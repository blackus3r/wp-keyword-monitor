<?php
/**
 *
 * @copyright Patrick Hausmann
 * @author Patrick Hausmann <privat@patrck-designs.de>
 */


namespace WpKeywordMonitor\Page;
use WpKeywordMonitor\KeywordQuery;
use WpKeywordMonitor\Model\KeywordResult;
use WpKeywordMonitor\Query\KeywordResultQuery;
use WpKeywordMonitor\RankChecker;
use WpKeywordMonitor\WpKeywordMonitor;

/**
 *
 */
class Statistics
{
    function __construct()
    {
        add_action('admin_action_doRankCheck', array($this, "doRankCheck"));
        add_action('admin_action_addKeyword', array($this, "addKeyword"));
        add_action('admin_action_deleteKeyword', array($this, "deleteKeyword"));
        add_action('admin_action_addKeywordToChart', array($this, "addKeywordToChart"));
        add_action('admin_action_removeKeywordFromChart', array($this, "removeKeywordFromChart"));
    }

    function addKeyword()
    {
        global $wpdb;
        $keywordQuery = new KeywordQuery($wpdb);

        if (isset($_POST["wp-keyword-monitor-add-keyword"]))
        {
            $keywordQuery->addKeyword($_POST["wp-keyword-monitor-add-keyword"]);
        }

        wp_redirect($_SERVER['HTTP_REFERER']);
        exit();
    }

    function deleteKeyword()
    {
        global $wpdb;
        $keywordQuery = new KeywordQuery($wpdb);

        if (isset($_POST["wp-keyword-monitor-keyword-id"]))
        {
            $keywordIds = $_POST["wp-keyword-monitor-keyword-id"];

            foreach ($keywordIds as $keywordId)
            {
                if (is_int((int)$keywordId))
                {
                    $keywordQuery->deleteKeyword($keywordId);
                }
            }

        }

        wp_redirect($_SERVER['HTTP_REFERER']);
        exit();
    }

    function addKeywordToChart()
    {
        $keywordsInCharts = get_option(WP_KEYWORD_MONITOR_KEYWORD_CHARTS);
        if (isset($_POST["wp-keyword-monitor-keyword-id"]))
        {
            $keywordIds = $_POST["wp-keyword-monitor-keyword-id"];

            if (!$keywordsInCharts) $keywordsInCharts = array();

            foreach ($keywordIds as $keywordId)
            {

                if (!in_array($keywordId, $keywordsInCharts))
                {
                    $keywordsInCharts = array_merge($keywordIds, $keywordsInCharts);
                }
            }

            update_option(WP_KEYWORD_MONITOR_KEYWORD_CHARTS, $keywordsInCharts);

        }
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit();
    }

    function removeKeywordFromChart()
    {
        $keywordsInCharts = get_option(WP_KEYWORD_MONITOR_KEYWORD_CHARTS);
        if (isset($_POST["wp-keyword-monitor-keyword-id"]))
        {
            $keywordIds = $_POST["wp-keyword-monitor-keyword-id"];

            foreach ($keywordsInCharts as $index => $keywordId)
            {

                if (in_array($keywordId, $keywordIds))
                {
                    unset($keywordsInCharts[$index]);
                }
            }

            update_option(WP_KEYWORD_MONITOR_KEYWORD_CHARTS, $keywordsInCharts);

        }
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit();
    }

    function doRankCheck()
    {
        global $wpdb;
        WpKeywordMonitor::checkRanks($wpdb, true);
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit();
    }

    function createPage()
    {
        ?>
        <?php
        global $wpdb;
        $keywordQuery = new KeywordQuery($wpdb);
        $keywords = $keywordQuery->getAllKeywords();
        $options = get_option(WP_KEYWORD_MONITOR_OPTIONS);
        $errors = get_option(WP_KEYWORD_MONITOR_ERROR);
        ?>
        <div class="wrap">
            <?php if (isset($errors["error"])) {?>
            <div class="notice error">
                <?php echo __("Error while checking ranks: ", WP_KEYWORD_MONITOR_TEXT_DOMAIN).$errors["error"]; ?>
            </div>
            <?php } ?>
            <h1><?php _e("Statistics", WP_KEYWORD_MONITOR_TEXT_DOMAIN);?></h1>
            <canvas style="margin:5px" id="keywordStatistics" width="300" height="400"></canvas>
            <p class="description"><?php _e("Statistics of the last 30 days.", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></p>
            <?php
            $dates = array();
            for ($i=0; $i<=30; $i++)
            {
                $dates[] = date("Y-m-d", strtotime((30-$i)." days ago"));
            }

            $ranks = array();
            $count=0;
            $keywordsInCharts = get_option(WP_KEYWORD_MONITOR_KEYWORD_CHARTS);
            foreach ($keywords as $keyword)
            {
                if ((!$keywordsInCharts && $count<10)|| ($keywordsInCharts && in_array($keyword->id, $keywordsInCharts)))
                {
                    $positions = array();

                    foreach ($dates as $date)
                    {
                        $result = $keyword->getResultForDate($date);
                        if ($result) $positions[] = $result->rank;
                        else if (count($positions)>1) $positions[] = $positions[count($positions)-1];
			            else $positions[] = 0;
                    }

                    $range = 0 - 256;
                    $factor = $range / 256;
                    $offset = 0;

                    $base_hash = substr(md5($keyword->keyword), 0, 6);
                    $b_R = hexdec(substr($base_hash,0,2));
                    $b_G = hexdec(substr($base_hash,2,2));
                    $b_B = hexdec(substr($base_hash,4,2));

                    $f_R = -1 * floor((floor($b_R * $factor) + $offset) / 16) * 16;
                    $f_G = -1 * floor((floor($b_G * $factor) + $offset) / 16) * 16;
                    $f_B = -1 * floor((floor($b_B * $factor) + $offset) / 16) * 16;


                    $color = "rgba(".$f_R.", ".$f_G.", ".$f_B;
                    if (count($positions))
                    {
                        $ranks[] = (object) array(
                            "label" => $keyword->keyword,
                            "data" => $positions,
                            "borderWidth" => 1,
                            "borderColor" => $color.", 1)",
                            "backgroundColor" => $color.", 0.1)"
                        );
                    }
                }
                $count++;
            }

            echo "<script type='text/javascript'>
				jQuery(document).ready(function () {
                    var ctx = document.getElementById('keywordStatistics');
                    new Chart(ctx, {

                        type: 'line',
                        data: {
                            labels: " . (json_encode(($dates),JSON_HEX_QUOT)) . ",
                            datasets: ".json_encode(($ranks), JSON_HEX_QUOT)."
                        },
                        options: {

                reverse: true, // will reverse the scale
         
  scaleOverride: true,
  scaleSteps: 19,
  scaleStepWidth: -1,
  scaleStartValue: 20,
                            responsive: true,
                            display: true,
                            tooltip: true,
                            ticks: {
                                reverse: true
                            },
                            maintainAspectRatio: false
                        }
                    });
                });
			</script>";
            ?>
        </div>
        <form method="post" action="<?php echo admin_url( 'admin.php' ); ?>">

        <div class="wrap">
            <div style="float: left">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row"><?php _e("Keyword", WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?></th>
                            <td>
                                <input type="text" name="wp-keyword-monitor-add-keyword">
                                <button name="action" value="addKeyword" class="button button-primary"> <?php _e("Add", WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?></button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
            </div>
            <div style="float: left; margin: 22px"  class="">
                <?php if (!isset($options["autoMode"]) || (isset($options["autoMode"]) && !$options["autoMode"])) { ?>
                    <button name="action" value="doRankCheck" class="button"><?php _e("Check Ranks"); ?></button>
                <?php } ?>
            </div>
            <div style="float: right; margin: 22px 0px 22px 22px"  class="">
                <button name="action" value="deleteKeyword" class="button danger"><?php _e("Delete", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></button>
                <button name="action" value="addKeywordToChart" class="button success"><?php _e("Add to chart", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></button>
                <button name="action" value="removeKeywordFromChart" class="button warning"><?php _e("Remove from chart", WP_KEYWORD_MONITOR_TEXT_DOMAIN); ?></button>
            </div>
        </div>
        <div class="wrap">
            <table class="widefat fixed striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th><?php _e("Keyword", WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?></th>
                    <th><?php _e("Google Ranking (last ranking)", WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?></th>
                    <th><?php _e("Last checked", WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?></th>
                    <th><?php _e("Action", WP_KEYWORD_MONITOR_TEXT_DOMAIN) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($keywords as $keyword)
                {
                    ?>
                    <tr>
                        <td><?php echo $keyword->id ?></td>
                        <td><?php echo $keyword->keyword ?></td>
                        <td>
                            <?php
                            $lastRank = $keyword->getLastResultRank();
                            $previousRank = $keyword->getPreviousResultRank();
                            echo $lastRank;
                            if (is_int($previousRank) && $previousRank!==0)
                            {
                                if ($previousRank!=$lastRank) echo " <span class=\"keyword-result-".($previousRank>$keyword->getLastResultRank() && is_int($lastRank)? "up" : "down")."\"></span>";
                                echo " ($previousRank)";
                            }
                            ?>
                        </td>
                        <td><?php  echo $keyword->getLastResultDateTime(); ?></td>
                        <td>
                            <input type="checkbox" value="<?php echo $keyword->id ?>" name="wp-keyword-monitor-keyword-id[]">
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <p class="description"><?php printf(__( 'Total Keywords: %s', WP_KEYWORD_MONITOR_TEXT_DOMAIN ),count($keywords));?></p>
        </div>
        </form>

        <?php
    }
}