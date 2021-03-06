<?php
/***************************************************************************
 *  For license information see doc/license.txt
 *
 *  Unicode Reminder メモ
 ***************************************************************************/

class ChildWp_Handler
{
  private $childWpTypes = array();
  private $translator;

  public function __construct()
  {
    global $opt;

    $this->translator = new Language_Translator();

		// read available types from DB
		$rs = sql("SELECT `coordinates_type`.`id`, IFNULL(`trans`.`text`, `coordinates_type`.`name`) AS `name`, `coordinates_type`.`image`, IFNULL(`trans_pp`.`text`, `coordinates_type`.`preposition`) AS `preposition`
		             FROM `coordinates_type`
						LEFT JOIN `sys_trans_text` `trans` ON `coordinates_type`.`trans_id`=`trans`.`trans_id` AND `trans`.`lang`='&1'
						LEFT JOIN `sys_trans_text` `trans_pp` ON `coordinates_type`.`pp_trans_id`=`trans_pp`.`trans_id` AND `trans_pp`.`lang`='&1'",
						          $opt['template']['locale']);
		while ($r = sql_fetch_assoc($rs))
		{
			$type = new ChildWp_Type($r['id'], $r['name'], $r['preposition'], $r['image']);
			$this->childWpTypes[$type->getId()] = $type;
		}
		sql_free_result($rs);
  }

  public function add($cacheid, $type, $lat, $lon, $desc)
  {
    sql("INSERT INTO coordinates(type, subtype, latitude, longitude, cache_id, description) VALUES(&1, &2, &3, &4, &5, '&6')", Coordinate_Type::ChildWaypoint, $type, $lat, $lon, $cacheid, $desc);
  }

  public function update($childid, $type, $lat, $lon, $desc)
  {
    sql("UPDATE coordinates SET subtype = &1, latitude = &2, longitude = &3, description = '&4' WHERE id = &5", $type, $lat, $lon, $desc, $childid);
  }

  public function getChildWp($childid)
  {
    $rs = sql("SELECT id, cache_id, type, subtype, latitude, longitude, description FROM coordinates WHERE id = &1", $childid);
    $ret = $this->recordToArray(sql_fetch_array($rs));
    mysql_free_result($rs);

    return $ret;
  }

  public function getChildWps($cacheid, $include_usernote=false)
  {
    global $login;

		if ($include_usernote)
			$type2 = Coordinate_Type::UserNote;
		else
			$type2 = 0;
    $rs = sql("SELECT id, cache_id, type, subtype, latitude, longitude, description FROM coordinates WHERE cache_id = &1 AND type IN (&2,&3) AND (type='&2' OR (user_id='&4' and latitude!=0 and longitude!=0)) ORDER BY id", $cacheid, Coordinate_Type::ChildWaypoint, $type2, $login->userid);
    $ret = array();

    while ($r = sql_fetch_array($rs))
    {
      $ret[] = $this->recordToArray($r);
    }

    mysql_free_result($rs);

    return $ret;
  }

  public function getChildWpIdAndNames()
  {
    $idAndNames = array();

    foreach ($this->childWpTypes as $type)
      $idAndNames[$type->getId()] = $type->getName();

    return $idAndNames;
  }

  public function getChildNamesAndImages()
  {
    $nameAndTypes= array();

    foreach ($this->childWpTypes as $type)
      $nameAndTypes[$type->getName()] = $type->getImage();

    return $nameAndTypes;
  }
  
  private function recordToArray($r)
  {
    $ret = array();

    $ret['cacheid'] = $r['cache_id'];
    $ret['childid'] = $r['id'];
    $ret['latitude'] = $r['latitude'];
    $ret['longitude'] = $r['longitude'];
    $ret['coordinate'] = new Coordinate_Coordinate($ret['latitude'], $ret['longitude']);
    $ret['description'] = $r['description'];

    if ($r['type'] == Coordinate_Type::ChildWaypoint)
    {
      $ret['type'] = $r['subtype'];
      $type = $this->childWpTypes[$ret['type']];

      if ($type)
      {
        $ret['name'] = $type->getName();
        $ret['preposition'] = $type->getPreposition();
        $ret['image'] = $type->getImage();
      }
    }
    else
    {
      $ret['type'] = 0;
      $ret['name'] = $this->translator->translate('Personal cache note');
      $ret['image'] = CacheNote_Presenter::image;
    }

    return $ret;
  }

  public function delete($childid)
  {
    sql("DELETE FROM coordinates WHERE id = &1", $childid);
  }
}

?>
