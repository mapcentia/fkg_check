<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2018 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\conf\wfsprocessors\fkg_check\classes\pre;


use app\conf\wfsprocessors\PreInterface;

class Prefkg implements PreInterface
{
    static public $typeName;
    static public $isDelete;
    private $db;
    private $gc2User;
    private $logFile;

    function __construct($db)
    {
        $this->db = $db;
        $this->gc2User = \app\inc\Input::getPath()->part(2);
        $this->logFile = fopen(dirname(__FILE__) . "/../../../../../public/logs/fkg_" . $this->gc2User . ".log", "w");
        self::$isDelete = false;
    }

    /**
     * The main function called by the WFS prior to the single UPDATE transaction.
     * @param $arr
     * @return array
     */
    public function processUpdate($arr, $typeName) : array {
        self::$typeName = $typeName;
        $res["arr"] = $arr;
        $res["success"] = true;
        $res["message"] = $arr;
        return $res;    }

    /**
     * The main function called by the WFS prior to the single INSERT transaction.
     * @param $arr
     * @return array
     */
    public function processInsert($arr, $typeName) : array {
        self::$typeName = $typeName;
        $res["arr"] = $arr;
        $res["success"] = true;
        $res["message"] = $arr;
        return $res;    }

    /**
     * The main function called by the WFS prior to the single DELETE transaction.
     * @param $arr
     * @return array
     */
    public function processDelete($arr, $typeName) : array {
        self::$typeName = $typeName;
        self::$isDelete = true;
        $komnr = "0" . substr($this->gc2User, -3);
        $objekt_id = explode(".", $arr["Filter"]["FeatureId"]["fid"])[1];

        $sql = "SELECT objekt_id FROM fkg.{$typeName}, dagi.kommune " .
            "WHERE fkg.{$typeName}.objekt_id='{$objekt_id}' AND dagi.kommune.komkode='{$komnr}'" .
            " AND st_within(fkg.{$typeName}.geometri, ST_buffer(dagi.kommune.the_geom, 1000))";

        try {
            $res = $this->db->prepare($sql);
            $res->execute();
            $row = $this->db->fetchRow($res, "assoc");
            if (!$row) {
                $response["message"] = "Et eller flere objekter ligger uden for kommunegrænsen (Operation: DELETE)";
                $response["success"] = false;
                return $response;
            }
        } catch (\PDOException $e) {
            $response["message"] = $e->getMessage();
            $response["success"] = false;
            return $response;
        }

        $response["arr"] = $arr;
        $response["success"] = true;
        $response["message"] = $arr;
        return $response;
    }
}
