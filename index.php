<?php
    require("MatchManager.php");
    echo("yolo");

    $matchManager = new MatchManager();
    $matchManager->runMatchJob();

    // This code executes the process for adding new content to the matching database and discovering potential ad targets

    // STEP 1: Get a list of newly added corpus content from the archive

    // $maxfin = trim(file_get_contents(self::MAXFIN));
    // DB::connect();
    // $qry = "SELECT identifier,finished,cmd,args FROM catalog_done WHERE finished>$maxfin AND ".TV::ID_SQL_REGEXP." AND cmd IN ('bup.php','fixer.php','delete.php','make_dark.php','make_undark.php','rename.php') ORDER BY finished LIMIT ".self::CHUNK_SIZE;
    // error_log($qry);
    // $rows = DB::qrows($qry);
    // foreach ($rows as $row)
    // {
    //   $maxfin = max($maxfin, $row['finished']);

    //   $id = $row['identifier'];
    //   if ($row['cmd']=='rename.php'){
    //     // renames are unusual -- and since cat_done identifier *changes* during rename task,
    //     // find the *old* identifier name now a different way (using the "dir" arg of task)
    //     // and make sure we add it to our list to remove from SE later on...
    //     // NOTE: the "new identifier" will come to us w/ later "bup" task, so we're cool there.
    //     $args = Util::cgi_args_to_array($row['args']);
    //     $id = basename($args['dir']);
    //   }

    //   $ids[$id]=1;
    // }

    // echo "(Now ~".(join("",DB::qvals("SELECT max(finished) FROM catalog_done")) - $maxfin)." tasks behind last finished task)\n";

    // file_put_contents(self::MAXFIN, $maxfin);

    // echo "\nNow considering ".count($ids)." shows -- will determine if news or not now...\n";


    // if (!count($rows))
    //   return false;


    // return $this->filter_ids(array_keys($ids));
    // }



    // STEP 2: For each item in the list, add it to the fingerprinting API

    // STEP 3: For each item in the list, run a "Match" within the fingerprinting API.  After the match is run, add it to the corpus.

?>
