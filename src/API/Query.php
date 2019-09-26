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
        $string = str_replace('&amp;','&',$string);
        $content_page = $this->request->request("https://www.redfin.com/".$string,'GET',[],false);
        if($content_page){
            $content_page = str_replace('&amp;','&',$content_page);
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($content_page);
            $model = $doc->getElementById("download-and-save");
            $url = "https://www.redfin.com".$model->getAttribute('href');
            $h = file_get_contents($url);
            if($h) {
                $index = $num = 0;
                $return_data = [];
                $datas = $this->processCsv($h);
                unset($datas[0]);
                foreach($datas as $data){
                    if(!$data[0]) continue;
                    $index++;
                    if ($index < $offset + 1) continue;
                    $num++;
                    if ($num > $limit + 1) break;

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

                    $make_url = "https://ssl.cdn-redfin.com/photo/95/islphoto/" . substr($data[22], -3) . "/genIslnoResize." . $data[22] . "_0.jpg";
                    if ($this->request->exists($make_url)) {
                        $data_array['image'] = $make_url;
                    } else {
                        $data_array['image'] = "https://ssl.cdn-redfin.com/v280.3.0/images/homecard/ghosttown-640x460.png";
                    }
                    $return_data[] = $data_array;
                }
                if ($return_data) {
                    $is_has_offset = 0;
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

    public function item($string=false, $mls=false){
        if(!$string && !$mls) die();
        $string = str_replace('&amp;','&',$string);
        $content_page = $this->request->request($string,'GET',[],false);
        if($content_page){
            $content_page = str_replace('&amp;','&',$content_page);
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($content_page);
            $xpath = new \DOMXPath($doc);
            $model = $xpath->query('//div[@class="SpritedImageCard"]/img');
            $return_data = [];
            $template_url = $xpath->query('//span[@class="FadeItem visible"]/div[@class="ImageCard"]/img');
            $url_id = 0;
            if($template_url->length){
                $url = $template_url->item(0)->getAttribute('src');
                $return_data['images'][] = $url;
                $url_id = explode('_',explode('.jpg', $url)[0])[1];
            }
            for($i=1;$i<$model->length;$i++){
                $return_data['images'][] = "https://ssl.cdn-redfin.com/photo/234/mbpaddedwide/" . substr($mls, -3) . "/genMid." . $mls . "_" . $i ."_".$url_id.".jpg";
            }
            $description = $xpath->query('//div[@class="remarks"]/p/span');
            if($description->length){
                $return_data['description'] = $description->item(0)->nodeValue;
            }
            $details = $xpath->query('//div[@class="keyDetailsList"]/div');
            if($details->length){
                $details_arr = [];
                for($i=1;$i<=$details->length;$i++){
                    $header = $xpath->query('//div[@class="keyDetailsList"]/div['.$i.']/span[1]');
                    $content = $xpath->query('//div[@class="keyDetailsList"]/div['.$i.']/span[2]');
                    $details_arr[] = [$header->item(0)->nodeValue, $content->item(0)->nodeValue];
                }
                if($details_arr){
                    $return_data['details'] = $details_arr;
                }
            }
            if($return_data){
                return $return_data;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    function processCsv($string)
    {
        try{
            $csv = new \Jabran\CSV_Parser();
            $csv->fromString($string);
            return $csv->parse(false);
        }catch (\Exception $e){
            return [];
        }
    }
}
