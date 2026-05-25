<?php

$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__).'wp-load.php';
$result = NULL;


if(! file_exists($path)) {
    $result = "wordpress main path not found";
} else if(empty($_GET["id"])) {
    $result = "no event id";
} else {

    include_once($path);

    if(!get_option("ev_kirchen_termine_show_feedback_count")) {
        $result = "feedback count disabled";
    } else {
        $ev_kirchen_termine_webpage = get_option("ev_kirchen_termine_webpage");

        if(empty($ev_kirchen_termine_webpage)) {

            $result = "wrong configuration";

        } else {

            $url = $ev_kirchen_termine_webpage.'/Veranstalter/xml.php?ID='.$_GET["id"];

            $xml_content = file_get_contents($url);

            if(substr($xml_content, 0, 5) == "<?xml") {

                $xml = simplexml_load_string($xml_content);

                if(!empty( $xml->Export->meta->avail)) {
                    $result = (int) $xml->Export->meta->avail;
                } else {
                    $result = "no counter";
                }

                // Check if event is not in future
                if(!empty( $xml->Export->Veranstaltung->START_RFC) && date("Y-m-d H:i:s", strtotime($xml->Export->Veranstaltung->START_RFC)) < date("Y-m-d H:i:s") ) {
                    $result = -10;
                }

            } else {
                $result = "event not found";
            }

        }

    }

}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
