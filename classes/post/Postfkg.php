<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2019 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\conf\wfsprocessors\fkg_check\classes\post;

use \app\conf\wfsprocessors\PostInterface;
use \app\conf\wfsprocessors\fkg_check\classes\pre\Prefkg;

class Postfkg implements PostInterface
{
    private $logFile;
    private $db;
    private $gc2User;

    function __construct($db)
    {
        $this->db = $db;
        $this->gc2User = \app\inc\Input::getPath()->part(2);
        $this->logFile = fopen("/var/www/geocloud2/public/logs/fkg_" . $this->gc2User . ".log", "w");
        error_log($this->logFile);
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
//        $response["success"] = true;
//        return $response;

        global $rowIdsChanged;

        if (!Prefkg::$isDelete) {
            $response = [];
            $typeName = Prefkg::$typeName;
            $komnr = "0" . substr($this->gc2User, 3, 3);
            foreach ($rowIdsChanged as $objekt_id) {
                $sql = "SELECT objekt_id FROM fkg.{$typeName}, dagi.kommune " .
                    "WHERE fkg.{$typeName}.objekt_id='{$objekt_id}' AND dagi.kommune.komkode='{$komnr}'" .
                    " AND st_within(fkg.{$typeName}.geometri, ST_buffer(dagi.kommune.the_geom, 1000))";

                try {
                    $res = $this->db->prepare($sql);
                    $res->execute();
                    $row = $this->db->fetchRow($res, "assoc");
                    if (!$row) {
                        $response["message"] = "Et eller flere objekter ligger uden for kommunegrænsen (operation: UPDATE/INSERT)";
                        $response["success"] = false;
                        return $response;
                    }
                } catch (\PDOException $e) {
                    $response["message"] = $e->getMessage();
                    $response["success"] = false;
                    return $response;
                }
            }
        }
        $response["success"] = true;
        return $response;
    }
}