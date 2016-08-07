<?php

/**
 * User: linzh<784855684@qq.com>
 * Datetime: 8/4/16 8:58 AM
 */
namespace Application\Home\Controller;

use PLite\Extension\Sphinx\SphinxClient;
use PLite\Library\Session;

class Index {

    public function index(){
        $href = __PUBLIC__.'index.php/Admin/Index/index';
        echo "<a href='{$href}'>Click to background</a>";
    }

    public function memcache(){
        $memacache = new \Memcached();
        $memacache->addServer('localhost','11211');
        $memacache->set('key','value');
        \PLite\dump($memacache->get('key'));
    }

    public function session(){
        $val = Session::get('name');
        if(!$val){
            echo 'none ,goto set';
            Session::set('name',$val = 'this is an session value');
        }
        echo "session value is :'$val'";
    }

    public function sphinxSinX(){
        $client = new SphinxClient();
        $args = [
            'doc',
        ];
        $query = "";
        $mode = SPH_MATCH_ALL;
        $host = "localhost";
        $port = 9312;
        $index = "*";
        $groupby = "";
        $groupsort = "@group desc";
        $filter = "group_id";
        $filtervals = array();
        $distinct = "";
        $sortby = "";
        $sortexpr = "";
        $limit = 20;
        $ranker = SPH_RANK_PROXIMITY_BM25;
        $select = "";
        for ( $i=0; $i<count($args); $i++ )
        {
            $arg = $args[$i];

            if ( $arg=="-h" || $arg=="--host" )				$host = $args[++$i];
            else if ( $arg=="-p" || $arg=="--port" )		$port = (int)$args[++$i];
            else if ( $arg=="-i" || $arg=="--index" )		$index = $args[++$i];
            else if ( $arg=="-s" || $arg=="--sortby" )		{ $sortby = $args[++$i]; $sortexpr = ""; }
            else if ( $arg=="-S" || $arg=="--sortexpr" )	{ $sortexpr = $args[++$i]; $sortby = ""; }
            else if ( $arg=="-a" || $arg=="--any" )			$mode = SPH_MATCH_ANY;
            else if ( $arg=="-b" || $arg=="--boolean" )		$mode = SPH_MATCH_BOOLEAN;
            else if ( $arg=="-e" || $arg=="--extended" )	$mode = SPH_MATCH_EXTENDED;
            else if ( $arg=="-e2" )							$mode = SPH_MATCH_EXTENDED2;
            else if ( $arg=="-ph"|| $arg=="--phrase" )		$mode = SPH_MATCH_PHRASE;
            else if ( $arg=="-f" || $arg=="--filter" )		$filter = $args[++$i];
            else if ( $arg=="-v" || $arg=="--value" )		$filtervals[] = $args[++$i];
            else if ( $arg=="-g" || $arg=="--groupby" )		$groupby = $args[++$i];
            else if ( $arg=="-gs"|| $arg=="--groupsort" )	$groupsort = $args[++$i];
            else if ( $arg=="-d" || $arg=="--distinct" )	$distinct = $args[++$i];
            else if ( $arg=="-l" || $arg=="--limit" )		$limit = (int)$args[++$i];
            else if ( $arg=="--select" )					$select = $args[++$i];
            else if ( $arg=="-fr"|| $arg=="--filterrange" )	$client->SetFilterRange ( $args[++$i], $args[++$i], $args[++$i] );
            else if ( $arg=="-r" )
            {
                $arg = strtolower($args[++$i]);
                if ( $arg=="bm25" )		$ranker = SPH_RANK_BM25;
                if ( $arg=="none" )		$ranker = SPH_RANK_NONE;
                if ( $arg=="wordcount" )$ranker = SPH_RANK_WORDCOUNT;
                if ( $arg=="fieldmask" )$ranker = SPH_RANK_FIELDMASK;
                if ( $arg=="sph04" )	$ranker = SPH_RANK_SPH04;
            }
            else
                $query .= $args[$i] . " ";
        }

        ////////////
        // do query
        ////////////

        $client->SetServer ( $host, $port )->SetConnectTimeout ( 1 )->SetArrayResult ( true )->SetMatchMode ( $mode )->SetRankingMode ( $ranker );
        if ( count($filtervals) )	$client->SetFilter ( $filter, $filtervals );
        if ( $groupby )				$client->SetGroupBy ( $groupby, SPH_GROUPBY_ATTR, $groupsort );
        if ( $sortby )				$client->SetSortMode ( SPH_SORT_EXTENDED, $sortby );
        if ( $sortexpr )			$client->SetSortMode ( SPH_SORT_EXPR, $sortexpr );
        if ( $distinct )			$client->SetGroupDistinct ( $distinct );
        if ( $select )				$client->SetSelect ( $select );
        if ( $limit )				$client->SetLimits ( 0, $limit, ( $limit>1000 ) ? $limit : 1000 );
        $res = $client->Query ( $query, $index );

        ////////////////
        // echo me out
        ////////////////

        $echo = '';

        if ( $res===false ) $echo .=  "Query failed: " . $client->GetLastError() . ".\n";
        else{
            if ( $client->GetLastWarning() ) $echo .=  "WARNING: " . $client->GetLastWarning() . "\n\n";

            $echo .=  "Query '$query' retrieved $res[total] of $res[total_found] matches in $res[time] sec.\n";
            $echo .=  "Query stats:\n";
            if ( is_array($res["words"]) )
                foreach ( $res["words"] as $word => $info )
                    $echo .=  "    '$word' found $info[hits] times in $info[docs] documents\n";

            if ( is_array($res["matches"]) )
            {
                $n = 1;
                $echo .=  "Matches:\n";
                foreach ( $res["matches"] as $docinfo )
                {
                    $echo .=  "$n. doc_id=$docinfo[id], weight=$docinfo[weight]";
                    foreach ( $res["attrs"] as $attrname => $attrtype )
                    {
                        $value = $docinfo["attrs"][$attrname];
                        if ( $attrtype==SPH_ATTR_MULTI || $attrtype==SPH_ATTR_MULTI64 )
                        {
                            $value = "(" . implode( ",", $value ) .")";
                        } else
                        {
                            if ( $attrtype==SPH_ATTR_TIMESTAMP )
                                $value = date ( "Y-m-d H:i:s", $value );
                        }
                        $echo .=  ", $attrname=$value";
                    }
                    $echo .=  "\n";
                    $n++;
                }
            }
        }
        echo nl2br($echo);
    }

    public function sphinx(){
        $client = new SphinxClient();
        $client->SetServer('127.0.0.1',9312);
        $result = $client->Query('doc');
        \PLite\dumpout($result);
    }

}