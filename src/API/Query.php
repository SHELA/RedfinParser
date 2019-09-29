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
        $content_page = $this->request->request("https://www.redfin.com/stingray/do/query-location?al=1&location=" . urlencode($query) . "&market=connecticut&num_homes=1000&ooa=true&v=2");
        if ($content_page) {
            if ($content_page['payload'] && $content_page['payload']['sections'][0] && $content_page['payload']['sections'][0]['rows']) {
                return $content_page['payload']['sections'][0]['rows'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function details($string, $limit = 10, $offset = 0)
    {
        if (!$string && !$limit) die();
        $string = str_replace('&amp;', '&', $string);
        $content_page = $this->request->request("https://www.redfin.com" . $string, 'GET', [], false);
        if ($content_page) {
            $content_page = str_replace('&amp;', '&', $content_page);
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($content_page);
            $xpath = new \DOMXPath($doc);
            $t = $xpath->query('//div[@class="remarks-container"]');
            $image_num = 0;
            if ($t->length) {
                $image_n = $t->item(0)->getAttribute('style');
                if($image_n){
                    $image_num = explode('/',explode('.com/photo/', $image_n)[1])[0];
                }
            }

            $model = $doc->getElementById("download-and-save");
            $url = "https://www.redfin.com" . $model->getAttribute('href');
            $url_path = $this->request->request($url, 'GET', [], false);
            $file_data = $this->processCsv($url_path);
            if($file_data) {
                $index = $num = $is_has_offset = 0;
                $return_data = [];
                foreach ($file_data as $k => $data) {
                    $index++;
                    if ($offset && ($index < $offset + 1)) continue;
                    $num++;
                    if ($limit && ($num > $limit + 1)) {
                        if(array_key_exists($k+1, $file_data)){
                            $is_has_offset = 1;
                        }
                        break;
                    }

                    if ($index == 1) continue; //[0] => SALE TYPE, [1] => SOLD DATE, [2] => PROPERTY TYPE, [3] => ADDRESS, [4] => CITY, [5] => STATE OR PROVINCE, [6] => ZIP OR POSTAL CODE, [7] => PRICE, [8] => BEDS, [9] => BATHS, [10] => LOCATION, [11] => SQUARE FEET, [12] => LOT SIZE, [13] => YEAR BUILT, [14] => DAYS ON MARKET, [15] => $/SQUARE FEET, [16] => HOA/MONTH, [17] => STATUS, [18] => NEXT OPEN HOUSE START TIME, [19] => NEXT OPEN HOUSE END TIME, [20] => URL (SEE http://www.redfin.com/buy-a-home/comparative-market-analysis FOR INFO ON PRICING), [21] => SOURCE, [22] => MLS#, [23] => FAVORITE, [24] => INTERESTED, [25] => LATITUDE, [26] => LONGITUDE

                    if(isset($data[1])) {
                        $data_array = [
                            'sale_type' => $data[0],
                            'sold_date' => $data[1],
                            'property_type' => $data[2],
                            'address' => $data[3],
                            'city' => $data[4],
                            'state_province' => $data[5],
                            'zip_postal' => $data[6],
                            'price' => $data[7],
                            'beds' => $data[8],
                            'baths' => $data[9],
                            'location' => $data[10],
                            'square_feet' => $data[11],
                            'lot_size' => $data[12],
                            'year_built' => $data[13],
                            'days_on_market' => $data[14],
                            '$_square_feet' => $data[15],
                            'hoa_month' => $data[16],
                            'status' => $data[17],
                            'next_open_house_start_time' => $data[18],
                            'next_open_house_end_time' => $data[19],
                            'url' => $data[20],
                            'source' => $data[21],
                            'mlc' => $data[22],
                            'favorite' => $data[23],
                            'interested' => $data[24],
                            'latitude' => $data[25],
                            'longitude' => $data[26]
                        ];
                        $make_url = "https://ssl.cdn-redfin.com/photo/". $image_num ."/islphoto/" . substr($data['22'], -3) . "/genIslnoResize." . $data['22'] . "_0.jpg";

                        $a = $this->request->exists($make_url);
                        if ($a) {
                            $data_array['url_image'] = $make_url;
                        } else {
                            $data_array['url_image'] = "https://ssl.cdn-redfin.com/v280.3.0/images/homecard/ghosttown-640x460.png";
                        }
                        $return_data[] = $data_array;
                    }else{
                        $num--;
                        $index--;
                    }
                }
                if ($return_data) {
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
        } else {
            return false;
        }
    }

    public function item($string = false, $mls = false)
    {
        if (!$string && !$mls) die();
        $string = str_replace('&amp;', '&', $string);
        $content_page = $this->request->request($string, 'GET', [], false);
        if ($content_page) {
            $content_page = str_replace('&amp;', '&', $content_page);
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($content_page);
            $xpath = new \DOMXPath($doc);
            $return_data = [];
            $data_url = $this->get_image_list($content_page);
            if ($data_url) {
                $return_data['images'] = $data_url;
                $description = $xpath->query('//div[@class="remarks"]/p/span');
                if ($description->length) {
                    $return_data['description'] = $description->item(0)->nodeValue;
                }
                $details = $xpath->query('//div[@class="keyDetailsList"]/div');
                if ($details->length) {
                    $details_arr = [];
                    for ($i = 1; $i <= $details->length; $i++) {
                        $header = $xpath->query('//div[@class="keyDetailsList"]/div[' . $i . ']/span[1]');
                        $content = $xpath->query('//div[@class="keyDetailsList"]/div[' . $i . ']/span[2]');
                        $details_arr[] = [$header->item(0)->nodeValue, $content->item(0)->nodeValue];
                    }
                    if ($details_arr) {
                        $return_data['details'] = $details_arr;
                    }
                }
                if ($return_data) {
                    return $return_data;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function processCsv($string)
    {
        try {
            $csv = new \Jabran\CSV_Parser();
            $csv->fromString($string);
            return $csv->parse(false);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function get_image_list($html){
        $re = '/\"fullScreenPhotoUrl\":\"(.*?)\"\}/m';
        $html = str_replace('\"','"',$html);
        preg_match_all($re, $html, $matches, PREG_SET_ORDER, 0);
        $return_images = [];
        if($matches){
            foreach ($matches as $match){
                $return_images[] = $this->unicode_decode($match[1]);
            }
        }
        return $return_images;
    }

    function b($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }
    function unicode_decode($str)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', array(&$this, 'b'), $str);
    }
}



