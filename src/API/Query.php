<?php

namespace Shela\RedfinParser\API;

class Query
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function search($query = '')
    {
        $content_page = $this->request->request("https://www.redfin.com/stingray/do/query-location?al=1&location=".urlencode($query)."&market=connecticut&num_homes=1000&ooa=true&v=2");
        if($content_page){
            if($content_page['payload'] && $content_page['payload']['sections'][0] && $content_page['payload']['sections'][0]['rows']){
                return $content_page['payload']['sections'][0]['rows'];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function details($string, $limit=10, $offset = 0){
        if(!$string && !$limit) die();
        $content_page = $this->request->request("https://www.redfin.com/".$string,'GET',[],false);
        if($content_page){
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($content_page);
            $model = $doc->getElementById("download-and-save");
            $url = "https://www.redfin.com".$model->getAttribute('href');
            $h = fopen($url, "r");
            if($h) {
                $index = $num = 0;
                $return_data = [];
                while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {
                    $index++;
                    if ($index < $offset + 1) continue;
                    $num++;
                    if ($num > $limit + 1) break;

                    if ($index == 1) continue; //[0] => SALE TYPE, [1] => SOLD DATE, [2] => PROPERTY TYPE, [3] => ADDRESS, [4] => CITY, [5] => STATE OR PROVINCE, [6] => ZIP OR POSTAL CODE, [7] => PRICE, [8] => BEDS, [9] => BATHS, [10] => LOCATION, [11] => SQUARE FEET, [12] => LOT SIZE, [13] => YEAR BUILT, [14] => DAYS ON MARKET, [15] => $/SQUARE FEET, [16] => HOA/MONTH, [17] => STATUS, [18] => NEXT OPEN HOUSE START TIME, [19] => NEXT OPEN HOUSE END TIME, [20] => URL (SEE http://www.redfin.com/buy-a-home/comparative-market-analysis FOR INFO ON PRICING), [21] => SOURCE, [22] => MLS#, [23] => FAVORITE, [24] => INTERESTED, [25] => LATITUDE, [26] => LONGITUDE
                    $make_url = "https://ssl.cdn-redfin.com/photo/234/islphoto/" . substr($data[22], -3) . "/genIslnoResize." . $data[22] . "_0.jpg";
                    $a = $this->request->request($make_url);
                    if ($a['status'] == 'success') {
                        $data[] = $make_url;
                    } else {
                        $data[] = "https://ssl.cdn-redfin.com/v280.3.0/images/homecard/ghosttown-640x460.png";
                    }
                    $return_data[] = $data;
                }
                fclose($h);
                if ($return_data) {
                    $is_has_offset = 0;
                    if ((fgetcsv($h, 1000, ",")) !== FALSE) {
                        $is_has_offset = 1;
                    }
                    $arr = [
                        'data' => $return_data,
                        'is_has_offset' => $is_has_offset
                    ];
                    return $arr;
                } else {
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
