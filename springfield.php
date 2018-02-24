<!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://code.jquery.com/jquery-3.1.1.slim.js" integrity="sha256-5i/mQ300M779N2OVDrl16lbohwXNUdzL/R2aVUXyXWA=" crossorigin="anonymous"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <!-- 行番号 -->
    <link rel="stylesheet"
    href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/default.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.1.0/highlightjs-line-numbers.min.js"></script>

    <style type="text/css">
    .container,
    .panel-body {
        padding: 0;
    }
    /* for block of numbers */
    td.hljs-ln-numbers {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;

        text-align: center;
        color: #ccc;
        border-right: 1px solid #CCC;
            vertical-align: top;
        padding-right: 5px;

        /* your custom style here */
    }

    /* for block of code */
    td.hljs-ln-code {
        padding-left: 10px;
    }
    </style>

    </head>
    <body>

<?php

error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED | E_STRICT));
date_default_timezone_set("Asia/Tokyo");

// simplehtmldomでコンテンツ解析
include_once "./simple_html_dom.php";
include_once "./http_build_url.php";

$res = [];

if ($_GET["url"] != "") {
    // get url
    $url = filter_input(INPUT_GET, "url");
    $url = trim($url);
    $url = preg_replace("/\r|\n/", "", $url);
    // echo $url;
    // exit;
    $urlp = "http://" . $url ;
    $urls = "https://" .  $url ;

    if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
        $url = $url;
    } elseif (!filter_var($urlp, FILTER_VALIDATE_URL) === false) {
        $url = $urlp;
    } elseif (!filter_var($urls, FILTER_VALIDATE_URL) === false) {
        $url = $urls;
    } else {
        echo "your url is invalid. $url";
    }

    $res = get_divided_contents($url) ;
    // print_r($res);
    // exit;

    // 数字を +1 -1 したlinkを作るためのurl
    $links = new Links($url);
    $prevurl = $links->get_new_url("-1");
    $nexturl = $links->get_new_url("+1");
    $listurl = $links->get_new_url("empty");
}

// 前後のエピソードを見れるurlを生成する
// ※生成するurlはspringfield.comのものではなくこのphpベースのもの
class Links
{
    // springfiledのurl
    private $url;
    // このphpのurl
    public $localurl = "./springfield.php?url=";

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * urlから +1/-1 したurlを生成する。&episodeを空にしてリストページにとぼうとする "empty"にも対応
     * @param (string) "+1" などの加減算か、"empty"
     * @return (string) url [自分ベースURL: "./~.php?url=相手url"の形]
     */
    public function get_new_url($addition)
    {
        // echo "|".$this->url."|";
        // // echo "|localurl=".$this->localurl."|";
        // query以外分解
        // http, example.com, /path/ とか
        $parse = parse_url($this->url);
        // print_r($parse);
        // queryを分解
        // ?a=1&b=2 → [a]=1, [b]=2
        parse_str($parse["query"], $query_string);
        // print_r($query_string);

        if ($addition == "empty") {
            // "episode" query stringを消したい場合
            unset($query_string['episode']);
            // echo "episode query stringを消したい場合";
            // print_r($query_string);
        } else {
            ////// urlを加算減算する場合
            // echo "urlを加算減算する場合";
            //// queryの中の後ろの数字だけを操作する準備
            // s02e16 → s, 02, e, 16 とアルファベット・数字に分解する
            preg_match_all("/([0-9]+|[a-zA-Z]+)/", $query_string['episode'], $forward);
            // print_r($forward);
            // 一番後ろの数字を操作したいので、逆から操作する
            $reversed = array_reverse($forward[0]);
            foreach ($reversed as $k => $v) {
                if (preg_match("/\d+/", $v)) {
                    // 桁数を数えておく
                    $length = strlen($v);
                    // 数字のみなら新しい値を生成
                    $v = $v + intval($addition);
                    $reversed[$k] = sprintf("%0${length}d", $v);
                    break;
                }
            }
            // print_r($reversed);
            // 元の順番に戻す
            $forward = array_reverse($reversed);
            // print_r($forward);
            // ひとつながりのquery stringsにする
            // s, 02, e, 16 → s02e16
            $query_string['episode'] = implode("", $forward);
        }

        // あたらしいurl生成
        $rebuilt_url = $this->rebuilt_url($parse, $query_string);
        // echo "<br>this->localurl=".$this->localurl;
        // この変換phpベースのurlを生成。springfieldに飛ぶわけじゃないurl。
        return $this->localurl . urlencode($rebuilt_url) ;
    }

    /**
     * 加工されたurlの parsed arrayとquery stringのarrayからurl(str)を作る
     * @param  parse_url(array)
     * @param  query_strings(array)
     * @return  url(strings)
     */
    private function rebuilt_url($parse, $query_string)
    {
        // 新しいquery生成
        $parse["query"] = http_build_query($query_string);
        // print_r($rdr_str);
        // 新しいqueryを使ったurl生成
        // print_r($rebuilt_url);
        return http_build_url($parse);
    }
}

function get_divided_contents($url)
{
    try {
        $html = getUrlContent($url);

        if ($html === false) {
            // Handle the error
            // echo 1;
            // echo " $url";
            // echo 1;
            // echo " $html";
            // exit;
            return false;
        }
    } catch (Exception $e) {
        // Handle exception
        echo 21;
        exit;
    }
    //$url = "https://www.brookings.edu/testimonies/ten-years-later-the-status-of-the-u-n-human-rights-council/";
    // echo $html;
    // exit;
    if (strlen($html)>0) {

        // simplehtmldomを使う
        $dom = new SimpleHtmlDom($html);

        // quantico を取る
        $out["program"] = $dom->getElement("h1");

        // その回のエピソード名 MKTOPAZ を取る
        $out["episode"] = $dom->getElement("h3");

        // transcriptそのものを取る
        $tra =  $dom->getElement(".scrolling-script-container");
        // 見やすくする
        $out["transcript"] = make_easy_to_read($tra);

        // プログラムのエピソード一覧の場合は一覧を取る
        $list =  $dom->getElement(".main-content-left");
        // print_r($list);

        //// aタグのリンク先が相対パス→このphp経由のものに変換する
        // href="view_episode_scripts.php?tv~" → href="./local.php?url=~~~"
        $out["list"] = preg_replace_callback(
            '|(href=")(.*?)"|',
            function ($matches) {
            // print_r($matches);
            $replaced_url = "https://www.springfieldspringfield.co.uk/" . $matches[2];
            return 'href="./springfield.php?url=' . urlencode($replaced_url). '"';
        },
            $list
        );

        // print_r($out["list"]);

        // print_r($out);
        // exit;
        return $out;
    }
}

// 読みやすいtranscriptにする
function make_easy_to_read($rep){
    // <br> をなくす
    $rep = br2nl($rep);
    // !, ? に改行を付けて長い文章を読みやすくする
    $rep = preg_replace('/!\s?/', "!\n", $rep);
    $rep = preg_replace('/\?\s?/', "?\n", $rep);
    // 変なとこで区切れるperiod系名詞を救済する
    $rep = preg_replace('/(Dr|Mr|Ms|Mrs|Miss|I)\.\n/', "$1. ", $rep);
    return $rep;
}
// <br> を \n にする
// https://stackoverflow.com/questions/6004343/converting-br-into-a-new-line-for-use-in-a-text-area
function br2nl($input)
{
    // replace line brakes
    return preg_replace('/(<br\s?\/?>)/ius', "\n", str_replace("\n", "", str_replace("\r", "", htmlspecialchars_decode($input))));
}

class SimpleHtmlDom
{
    private $html;

    public function __construct($str)
    {
        $this->html = str_get_html($str);
    }
    public function getElement($selecter)
    {
        $out = "";
        foreach ($this->html->find($selecter) as $element) {
            $out .= $element->innertext;
        }
        return $out;
    }
}

function getUrlContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if (getenv('kc_proxy_domain')) {
        define('PROXY_SERVER', getenv('kc_proxy_domain'));
        define('PROXY_PORT', getenv('kc_proxy_port'));

        if (getenv('kc_proxy_user')) {
            //PROXY USER/PASS
            define('PROXY_USER', getenv('kc_proxy_user'));
            define('PROXY_PASS', getenv('kc_proxy_password'));
        } else {
            define('PROXY_USER', '');
            define('PROXY_PASS', '');
        }
        //proxy setting
        if (PROXY_SERVER && PROXY_PORT) {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($ch, CURLOPT_PROXY, PROXY_SERVER.':'.PROXY_PORT);
            if (PROXY_USER && PROXY_PASS) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USER.':'.PROXY_PASS);
            }
        }
    }

    $data = curl_exec($ch);
    // $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_exec($ch) === false) {
        echo 'Curl error: ' . curl_error($ch);
        print_r($data);
        curl_close($ch);
        return false;
    } else {
        // echo 'Operation completed without any errors';
        curl_close($ch);
        return $data;
    }

}


?>

    <!-- Begin page content -->
    <div class="container">
        <div class="page-header">
            <h3>Transcript no stress reader</h3>
        </div>
        <p class="lead"> </p>
        <form action="./springfield.php" method="get" >

        <label for="basic-url"> Paste a springfield url. :</label>
            <div class="form-group form-group-lg">

            <!-- URL入力 -->
            <div class="input-group">
            <span class="input-group-addon">http://</span>
            <textarea class="form-control" id="url" name="url"  placeholder="https://www.springfieldspringfield.co.uk/view_episode_scripts.php?tv-show=quantico-2015&episode=s01e07">
                <?php echo $url; ?>
            </textarea>
        </div>
        <br />

        <button type="submit" class="btn btn-default btn-lg btn-block">Get Transcript</button>

        </div>

        </form>

        <!-- 前後に行くボタン -->
        <p>
        <a class="btn btn-default" href="<?php echo $prevurl; ?>">Previous</a>
        <a class="btn btn-default" href="<?php echo $listurl; ?>">Episode List</a>
        <a class="btn btn-default" href="<?php echo $nexturl; ?>">Next</a>

        <button class="btn btn-info" onclick="window.scrollTo(0,document.body.scrollHeight);">History</button>
        </p>
        <br>

        <div class="panel panel-success hidden">
            <div class="panel-heading">
                <a href="<?php echo $url; ?>" target="_blank">
                    <!-- タイトル -->
                    <h4><?php echo $res["program"]; ?></h4>
                    <h5><?php echo $res["episode"]; ?></h5>
                </a>
            </div>
            <div class="panel-body" id="ans">
                <!-- transcript -->
                <code class="hljs nohighlight"><?php echo $res["transcript"]; ?></code>

                    <!-- 一覧ページの場合のエピソードリスト -->
                    <?php
                        // トランスクリプトがないときだけ表示
                        if (!$res["transcript"]) {
                            echo $res["list"];
                        }
                    ?>
            </div>
        </div>

        <div class="panel panel-danger hidden">
            <div class="panel-heading">
                Error
            </div>
            <div class="panel-body" id="ans">
                Your url is invalid. Please check the url is correct and alive.
            </div>
        </div>

        <!-- 履歴 -->
        <h4> History </h4>
        <div id="lastResults"></div>
    </div>



    </body>
    </html>

    <script>
    $(function() {
        console.log( "ready!" );

        $("#url").focus().select();
        <?php
            if ($url && !$res) {
                echo <<<TTT
        hideError();
TTT;
            }
        if ($res) {
            echo <<<TTT
        showSuccess();

        // 行番号表示
        $('code.hljs').each(function(i, block) {
            hljs.lineNumbersBlock(block);
        });
TTT;
            // GET APAした後のアクセスだったらコピーしやすいようにfocusを当てる
            echo <<<TTT
$("#bar").focus().select();
TTT;
        }
        ?>

            // click then clear input
            //     $("input[type=text]").click(function() {
            //    $(this).closest('form').find("input[type=text], textarea").select();
            //   });

    });

function hideError() {
    $(".panel-danger").removeClass("hidden");
}

function showSuccess() {

    $(".panel-danger").addClass("hidden");
    $(".panel-success").removeClass("hidden");
}


// 履歴保持
// TODO: fix and utilize
if (typeof(Storage) !== "undefined") {
    // Code for localStorage/sessionStorage.

    function addHistory(url, apa) {
        console.log("addHistory");
        //Storing New result in previous History localstorage
        if (localStorage.getItem("history") != null)
        {
            var historyTmp = localStorage.getItem("history");
            historyTmp += url + "|";
            localStorage.setItem("history",historyTmp);
        }
        else
        {
            var historyTmp = url + "|";
            localStorage.setItem("history",historyTmp);
        }
    }
    <?php

        // 履歴を追加する
        if ($url) {
            echo "addHistory('$url');";
        }
    ?>

        //To Check and show previous results in **lastResults** div
        if (localStorage.getItem("history") != null)
        {
            var historyTmp = localStorage.getItem("history");
            var oldhistoryarrayDuplicates = historyTmp.split('|').reverse();

            var oldhistoryarray = [];
            $.each(oldhistoryarrayDuplicates, function(i, el){
                if($.inArray(el, oldhistoryarray) === -1) oldhistoryarray.push(el);
            });


            $('#lastResults').empty();
            for(var i =0; i<oldhistoryarray.length; i++)
            {
                $('#lastResults').before('<a href="./springfield.php?url=' + encodeURIComponent(oldhistoryarray[i]) + '">'+oldhistoryarray[i]+'</p>');
            }
        }


} else {
    // No Web Storage support..
}


</script>