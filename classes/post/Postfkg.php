<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2019 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\conf\wfsprocessors\fkg_check\classes\post;

use app\conf\App;
use app\conf\wfsprocessors\PostInterface;

class Postfkg implements PostInterface
{
    private $logFile;
    private $serializer;
    private $unserializer;
    private $db;
    private $gc2User;
    private $service;

    function __construct($db)
    {
        $this->db = $db;
        $this->gc2User = \app\inc\Input::getPath()->part(2);
        $this->logFile = fopen(dirname(__FILE__) . "/../../../../../public/logs/fkg_" . $this->gc2User . ".log", "w");
    }

    function __destruct()
    {
        fclose($this->logFile);
    }

    private function log($txt)
    {
        fwrite($this->logFile, $txt);
    }

    public function process(): array
    {
        $response["success"] = true;
        return $response;

        global $rowIdsChanged;
        $response = [];
        $komnr = "0" . substr($_SESSION["screen_name"], -3);
        foreach ($rowIdsChanged as $objekt_id) {
            $sql = "SELECT objekt_id FROM fkg.t_5710_born_skole_dis, dagi.kommune " .
                "WHERE fkg.t_5710_born_skole_dis.objekt_id='{$objekt_id}' AND dagi.kommune.komkode='{$komnr}'" .
                " AND st_within(fkg.t_5710_born_skole_dis.geometri, ST_buffer(dagi.kommune.the_geom, 1000))";

            try {
                $res = $this->db->prepare($sql);
                $res->execute();
                $row = $this->db->fetchRow($res, "assoc");
                if (!$row) {
                    $response["message"] = "Et eller flere objekter ligger uden for kommunegrænsen.";
                    $response["success"] = false;
                    return $response;
                }
            } catch (\PDOException $e) {
                $response["message"] = $e->getMessage();
                $response["success"] = false;
                return $response;
            }
        }
        $response["success"] = true;
        return $response;
    }
}