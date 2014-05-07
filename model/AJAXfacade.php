<?php

namespace piGallery\model;

require_once __DIR__ ."./../db/DB.php";
require_once __DIR__ ."./Helper.php";

use piGallery\db\DB;

/* empty string set as null*/
foreach ($_REQUEST as $key => $value){
    if($value == ""){
        unset($_REQUEST[$key]);
    }
}



switch (Helper::require_REQUEST('method')){

    case 'getContent':
        $dir = Helper::require_REQUEST('dir');

        die(Helper::contentArrayToJSON(DB::getDirectoryContent($dir)));

        break;
    case 'autoComplete':
        $count= intval(Helper::get_REQUEST('count',5));
        $searchText= Helper::require_REQUEST('searchText');

        die(json_encode(DB::getAutoComplete($searchText,$count,"/")));



        break;
    case 'search':
        $searchString = Helper::require_REQUEST('searchString');


        die(Helper::contentArrayToJSON(DB::getSearchResult($searchString)));
        break;

}
