<?php

namespace App\Service;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;


class CrawlerService
{

    private $base_url = 'https://agencyanalytics.com/';

    public static function getHtml($url)
    {

        $ch = curl_init();  // Initialising cURL session
        // Setting cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);  // Returning transfer as a string
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);  // Follow location
        curl_setopt($ch, CURLOPT_URL, $url);  // Setting URL
        $results = curl_exec($ch);  // Executing cURL sessionn the results
        $info = curl_getinfo($ch);

        $dom = new DomDocument();  // Instantiating a new DomDocument object
        @$dom->loadHTML($results);  // Loading the HTML fromdownloaded page

        $collection = collect(['info' => $info, 'dom' => $dom]);

        return $collection;
    }

    public function getLinks($html, $url)
    {

        $links = $html['dom']->getElementsByTagName('a');
        $title = $html['dom']->getElementsByTagName('title');

        if ($title->length > 0) {

            $title = $title->item(0)->textContent;
            $title_length = strlen($title);

            DB::table('crawled')->updateOrInsert([
                'url' => $url,
                'load_time' => $html['info']['total_time'],
                "title_length" => $title_length,
                "http_code" => $html['info']['http_code']

            ]);
        }

        if ($links->length > 0) {

            foreach ($links as $link) {

                $pages[] =  $link->getAttribute('href');
            }

            $cleaned_links = array_unique($pages);

            foreach ($cleaned_links as $filtered_link) {
                DB::table('links')->updateOrInsert([
                    'url' => $url,
                    'link' => $filtered_link,
                ]);
            }



            if ($url == $this->base_url) {

                $update = DB::table('links')
                    ->where('link', '/')
                    ->update(['crawled' => true]);
            }
        }
        if (isset($cleaned_links)) {
            return true;
        } else {
            return false;
        }
    }

    public function getImages($html, $url)
    {

        //dd($html);
        if (isset($html['dom'])) {
            $imageTags = $html['dom']->getElementsByTagName('img');

            $images = [];
            foreach ($imageTags as $tag) {
                $img =  $tag->getAttribute('src');
                $images[] = $img;
            }
        }


        // dd($images);
        $cleaned_images = array_unique($images); //remove repeated images
        foreach ($cleaned_images as $image) {
            //dd($image);
            DB::table('images')->updateOrInsert([
                'url' => $url,
                'link' => $image,
            ]);
        }
        return $cleaned_images;
    }

    public function countWords($html, $url)
    {

        $xpath = new DOMXPath($html['dom']);
        $title = $html['dom']->getElementsByTagName('title');
        $titleContent = '';

        foreach ($title as $node) {
            $titleContent .= " $node->nodeValue";
        }

        $nodes = $xpath->query('//*[not(count(.|//script|/*/*/style)=count(//script|/*/*/style))]/text()'); //text excluding syle and script

        $textNodeContent = '';
        foreach ($nodes as $node) {
            $textNodeContent .= " $node->nodeValue";
        }

        $words =  array_count_values(str_word_count($textNodeContent, 1));

        foreach ($words as $word => $word_count) {
            DB::table('words')->updateOrInsert([
                'url' => $url,
                'word' => $word,
                'count' => $word_count,
            ]);
        }
    }

    public function setUrl()
    {

        if (DB::table('links')->where('url', 'https://agencyanalytics.com/')->exists()) {

            $dblinks =  DB::table('links')->where('url', 'https://agencyanalytics.com/')->get();
            foreach ($dblinks as $dblink) {
                if ($dblink->crawled != true && $dblink->id < 6) {
                    $page = ltrim($dblink->link, $dblink->link[0]); // trim url
                    $url = $this->base_url . $page; // new page to crawl

                    $update = DB::table('links')
                        ->where('link', $dblink->link)
                        ->update(['crawled' => true]);
                    return $url;
                }
            }
        } else {

            return  $url = $this->base_url;  //crawl the first page

        }
    }
}
