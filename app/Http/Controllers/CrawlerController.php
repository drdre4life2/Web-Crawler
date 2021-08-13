<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Service\CrawlerService;

class CrawlerController extends Controller
{




    public  function getAnalytics()
    {

        $crawled = 1;
        while($crawled <=5){
        $crawl = new CrawlerService; //crawler class
        $url  = $crawl->setUrl();
        $html = $crawl->getHtml($url);
        $links = $crawl->getLinks($html,$url);
        $images = $crawl->getImages($html,$url);
        $words = $crawl->countWords($html,$url);
        $crawled ++;
        }
        
        if($crawled >=5){
        return redirect()->route('crawler');
        }
    }

    public function analyse(){

        $crawled_pages =  DB::table('crawled')->get(); //
        $unique_images =  DB::table('images')->select('link')->distinct()->get(); //
        $avg_title=    DB::table('crawled')->select('title_length')->distinct()->avg('title_length'); //
        $avg_page_load=    DB::table('crawled')->select('load_time')->distinct()->avg('load_time'); //
        $word_count = DB::table('words')->select('count')->sum('count');

        //internal and external link
        $internal_link  =DB::table('links')->select('link')->distinct()->get(); //
        foreach($internal_link as $link){
          if(substr($link->link, 0,1) == '/'){
          $internal[] = $link;
          }else{
              $external[] = $link;
          }
        }
        $image_count = count($unique_images);
        $crawled_count = count($crawled_pages);
        $internal_link = count($internal);
        $external_link = count($external);
        $avg_word_count = $word_count/$crawled_count;

        //dd($crawled_pages);
        return view('crawler',compact('crawled_pages', 'image_count', 'avg_title', 'avg_page_load', 'avg_word_count', 'internal_link', 'external_link'));


    }


}
