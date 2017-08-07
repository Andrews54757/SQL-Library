<?php
/*
MIT License

Copyright (c) 2017 Andrew S

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

/*
 Author: Andrews54757
 License: MIT
 Source: https://github.com/ThreeLetters/SQL-Library
 Build: v2.5.0
 Built on: 07/08/2017
*/

// lib/connector/index.php
class Response{public$result;public$affected;public$ind;public$error;public$errorData;function __construct($a,$b){$this->error=!$b;if(!$b){$this->errorData=$a->errorInfo();}else{$this->result=$a->fetchAll();$this->affected=$a->rowCount();}$this->ind=0;$a->closeCursor();}function error(){return$this->error ?$this->errorData : false;}function getData(){return$this->result;}function getAffected(){return$this->affected;}function next(){return$this->result[$this->ind++];}function reset(){$this->ind=0;}}class Connector{public$queries=array();public$db;public$log=array();public$dev=false;function __construct($c,$d,$e){$this->db=new \PDO($c,$d,$e);$this->log=array();}function query($f,$g=null){$h=$this->db->prepare($f);if($g)$i=$h->execute($g);else$i=$h->execute();if($this->dev)array_push($this->log,array($f,$g));return new Response($h,$i);}function _query($j,$k,$l,$m){if(isset($this->queries[$j."|".$m])){$n=$this->queries[$j."|".$m];$h=$n[1];$o=&$n[0];foreach($k as$p=>$q){$o[$p][0]=$q[0];}if($this->dev)array_push($this->log,array("fromcache",$j,$m,$k,$l));}else{$h=$this->db->prepare($j);$o=$k;foreach($o as$p=>&$r){$h->bindParam($p + 1,$r[0],$r[1]);}$this->queries[$j."|".$m]=array(&$o,$h);if($this->dev)array_push($this->log,array($j,$m,$k,$l));}if(count($l)==0){$i=$h->execute();return new Response($h,$i);}else{$s=array();$i=$h->execute();array_push($s,new Response($h,$i));foreach($l as$p=>$t){foreach($t as$u=>$v){$o[$u][0]=$v;}$i=$h->execute();array_push($s,new Response($h,$i));}return$s;}}function close(){$this->db=null;$this->queries=null;}function clearCache(){$this->queries=array();}}
// lib/parser/Simple.php
class SimpleParser{public static function WHERE($a,&$b,&$c){if(count($a)!=0){$b.=" WHERE ";$d=0;foreach($a as$e=>$f){if($d!=0){$b.=" AND ";}$b.="`".$e."` = ?";array_push($c,$f);$d++;}}}public static function SELECT($g,$h,$a,$i){$b="SELECT ";$c=array();$j=count($h);if($j==0){$b.="*";}else{for($d=0;$d<$j;$d++){if($d!=0){$b.=", ";}$b.="`".$h[$d]."`";}}$b.="FROM `".$g."`";self::WHERE($a,$b);$b.=" ".$i;return array($b,$c);}public static function INSERT($g,$k){$b="INSERT INTO `".$g."` (";$l=") VALUES (";$c=array();$d=0;foreach($k as$e=>$f){if($d!=0){$b.=", ";$l.=", ";}$b.="`".$e."`";$l.="?";array_push($c,$f);$d++;}$b.=$l;return array($b,$c);}public static function UPDATE($g,$k,$a){$b="UPDATE `".$g."` SET ";$c=array();$d=0;foreach($k as$e=>$f){if($d!=0){$b.=", ";}$b.="`".$e."` = ?";array_push($c,$f);$d++;}self::WHERE($a,$b,$c);return array($b,$c);}public static function DELETE($g,$a){$b="DELETE FROM `".$g."`";$c=array();self::WHERE($a,$b,$c);return array($b,$c);}}
// lib/parser/Advanced.php
class AdvancedParser{private static function parseArg(&$a){if(substr($a,0,1)=="[" && substr($a,3,1)=="]"){$b=substr($a,1,2);$a=substr($a,4);return$b;}else{return false;}}private static function append(&$c,$d,$e){if(gettype($d)=="array"){$f=count($d);for($g=1;$g<$f;$g++){if(!isset($c[$g]))$c[$g]=array();$c[$g][$e]=$d[$g];}}}private static function append2(&$h,$i,$j){function stripArgs(&$k){if(substr($k,-1)=="]"){$l=strrpos($k,"[",-1);$k=substr($k,0,$l);}$l=strrpos($k,"]",-1);if($l!==false)$k=substr($k,$l + 1);}function recurse(&$m,$d,$i,$n){foreach($d as$g=>$o){if(gettype($o)=="array"){$p=substr($g,0,4);stripArgs($g);if($p!="[||]" &&$p!="[&&]"){if(isset($i[$g."#".$n."*"]))$q=$i[$g."#".$n."*"];else$q=$i[$g."*"];foreach($o as$r=>$s){$m[$q +$r]=$s;}}else{recurse($m,$o,$i,$n."/".$g);}}else{stripArgs($g);if(isset($i[$g."#".$n]))$q=$i[$g."#".$n];else$q=$i[$g];$m[$q]=$o;}}}$f=count($j);for($k=1;$k<$f;$k++){$d=$j[$k];if(!isset($h[$k]))$h[$k]=array();recurse($h[$k],$d,$i,"");}}private static function quote($a){$a=explode(".",$a);$b="";for($r=0;$r<count($a);$r++){if($r!=0)$b.=".";$b.="`".$a[$r]."`";}return$b;}private static function table($t){if(gettype($t)=="array"){$u="";for($r=0;$r<count($t);$r++){if($r!=0)$u.=", ";$u.=self::quote($t[$r]);}return$u;}else{return self::quote($t);}}private static function value($v,$w,&$x){$y=strtolower($v);if(!$y)$y=strtolower(gettype($w));$v=\PDO::PARAM_INT;if($y=="boolean" ||$y=="bool"){$v=\PDO::PARAM_BOOL;$w=$w ? "1" : "0";$x.="b";}else if($y=="integer" ||$y=="int"){$x.="i";}else if($y=="string" ||$y=="str"){$v=\PDO::PARAM_STR;$x.="s";}else if($y=="double" ||$y=="doub"){$w=(int)$w;$x.="i";}else if($y=="resource" ||$y=="lob"){$v=\PDO::PARAM_LOB;$x.="l";}else if($y=="null"){$v=\PDO::PARAM_NULL;$w=null;$x.="n";}return array($w,$v);}private static function getType(&$a){if(substr($a,-1)=="]"){$z=strpos($a,"[");if($z===false){return "";}$b=substr($a,$z + 1);$a=substr($a,0,$z);return$b;}else return "";}private static function conditions($j,&$aa=false,&$ba=false,&$x="",&$e=0){$ca=function(&$ca,$j,&$ba,&$e,&$aa,&$x,$da=" AND ",$ea=" = ",$fa=""){$ga=0;$u="";foreach($j as$k=>$d){if(substr($k,0,1)==="#"){$ha=true;$k=substr($k,1);}else{$ha=false;}$ia=self::parseArg($k);$ja=$ia ? self::parseArg($k): false;$ka=gettype($d);$la=!isset($d[0]);$ma=$da;$na=$ea;switch($ia){case "||":$ia=$ja;$ma=" OR ";break;case "&&":$ia=$ja;$ma=" AND ";break;}switch($ia){case ">>":$na=" > ";break;case "<<":$na=" < ";break;case ">=":$na=" >= ";break;case "<=":$na=" <= ";break;default: if(!$la)$na=" = ";break;}if($ga!=0)$u.=$da;if($ka=="array"){if($la){$u.="(".$ca($ca,$d,$ba,$e,$aa,$ma,$na,$fa."/".$k).")";}else{$v=self::getType($k);if($ba!==false &&!$ha){$ba[$k."*"]=$e;$ba[$k."#".$fa."*"]=$e++;}foreach($w as$g=>$o){if($g!=0)$u.=$ma;$u.="`".$k."`".$na;$e++;if($ha){$u.=$o;}else if($aa!==false){$u.="?";array_push($aa,self::value($v,$o,$x));}else{if(gettype($o)=="integer"){$u.=$o;}else{$u.=self::quote($o);}}}}}else{if($ha){$u.=$d;}else{if($aa!==false){$u.="`".$k."`".$na."?";array_push($aa,self::value(self::getType($k),$d,$x));}else{$u.=self::quote($k).$na;if(gettype($d)=="integer"){$u.=$d;}else{$u.=self::quote($d);}}if($ba!==false){$ba[$k]=$e;$ba[$k."#".$fa]=$e++;}}}return$u;}$ga++;};return$ca($ca,$j,$ba,$e,$aa,$x);}static function SELECT($t,$oa,$pa,$da,$qa){$u="SELECT ";$f=count($oa);$aa=array();$h=array();if($f==0){$u.="*";}else{$r=0;$ra=0;$sa="";if($oa[0]=="DISTINCT"){$r=1;$ra=1;$u.="DISTINCT ";}else if(substr($oa[0],0,11)=="INSERT INTO"){$r=1;$ra=1;$u=$oa[0]." ".$u;}else if(substr($oa[0],0,4)=="INTO"){$r=1;$ra=1;$sa=" ".$oa[0]." ";}if($f>$ra){for(;$r<$f;$r++){if($r>$ra){$u.=", ";}$u.=self::quote($oa[$r]);}}else$u.="*";$u.=$sa;}$u.=" FROM ".self::table($t);if($da){foreach($da as$k=>$d){if(substr($k,0,1)==="#"){$ha=true;$k=substr($k,1);}else{$ha=false;}$ia=self::parseArg($k);switch($ia){case "<<":$u.=" RIGHT JOIN ";break;case ">>":$u.=" LEFT JOIN ";break;case "<>":$u.=" FULL JOIN ";break;default:$u.=" JOIN ";break;}$u.=self::quote($k)." ON ";if($ha){$u.="val";}else{$u.=self::conditions($d);}}}$x="";if(count($pa)!=0){$u.=" WHERE ";$e=array();if(isset($pa[0])){$u.=self::conditions($pa[0],$aa,$e,$x);self::append2($h,$e,$pa);}else{$u.=self::conditions($pa,$aa,$e,$x);}}if($qa)$u.=" LIMIT ".$qa;return array($u,$aa,$h,$x);}static function INSERT($t,$ta){$u="INSERT INTO ".self::table($t)." (";$aa=array();$h=array();$x="";$ua="";$r=0;$l=0;if(isset($ta[0])){$i=array();foreach($ta[0]as$k=>$d){if(substr($k,0,1)==="#"){$ha=true;$k=substr($k,1);}else{$ha=false;}if($l!=0){$u.=", ";$ua.=", ";}$v=self::getType($k);$u.="`".$k."`";if($ha){$ua.=$d;}else{$ua.="?";array_push($aa,self::value($v,$d,$x));$i[$k]=$r++;}$l++;}self::append2($h,$i,$ta);}else{foreach($ta as$k=>$d){if(substr($k,0,1)==="#"){$ha=true;$k=substr($k,1);}else{$ha=false;}if($l!=0){$u.=", ";$ua.=", ";}$v=self::getType($k);$u.="`".$k."`";if($ha){$ua.=$d;}else{array_push($aa,self::value($v,$d,$x));$ua.="?";self::append($h,$d,$r++);}$l++;}}$u.=") VALUES (".$ua.")";return array($u,$aa,$h,$x);}static function UPDATE($t,$ta,$pa){$u="UPDATE ".self::table($t)." SET ";$aa=array();$h=array();$x="";$r=0;$l=0;if(isset($ta[0])){$i=array();foreach($ta[0]as$k=>$d){if(substr($k,0,1)==="#"){$ha=true;$k=substr($k,1);}else{$ha=false;}if($l!=0){$u.=", ";}$u.="`".$k."` = ";if($ha){$u.=$d;}else{$v=self::getType($k);array_push($aa,self::value($v,$d,$x));$i[$k]=$r++;$u.="?";}$l++;}self::append2($h,$i,$ta);}else{foreach($ta as$k=>$d){if(substr($k,0,1)==="#"){$ha=true;$k=substr($k,1);}else{$ha=false;}if($r!=0){$u.=", ";}$u.="`".$k."` = ";if($ha){$u.=$d;}else{$v=self::getType($k);array_push($aa,self::value($v,$d,$x));$u.="?";self::append($h,$d,$r++);}}}if(count($pa)!=0){$u.=" WHERE ";$e=array();if(isset($pa[0])){$u.=self::conditions($pa[0],$aa,$e,$x,$r);self::append2($h,$e,$pa);}else{$u.=self::conditions($pa,$aa,$e,$x,$r);}}return array($u,$aa,$h,$x);}static function DELETE($t,$pa){$u="DELETE FROM ".self::table($t);$aa=array();$h=array();$x="";if(count($pa)!=0){$u.=" WHERE ";$e=array();if(isset($pa[0])){$u.=self::conditions($pa[0],$aa,$e,$x);self::append2($h,$e,$pa);}else{$u.=self::conditions($pa,$aa,$e,$x);}}return array($u,$aa,$h,$x);}}
// index.php
class SuperSQL{public$connector;function __construct($a,$b,$c){$this->connector=new Connector($a,$b,$c);}function SELECT($d,$e,$f,$g=null,$h=false){if(gettype($g)=="integer"){$h=$g;$g=null;}$i=AdvancedParser::SELECT($d,$e,$f,$g,$h);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function INSERT($d,$j){$i=AdvancedParser::INSERT($d,$j);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function UPDATE($d,$j,$f){$i=AdvancedParser::UPDATE($d,$j,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function DELETE($d,$f){$i=AdvancedParser::DELETE($d,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sSELECT($d,$e,$f,$k=""){$i=SimpleParser::SELECT($d,$e,$f,$k);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sINSERT($d,$j){$i=SimpleParser::INSERT($d,$j);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sUPDATE($d,$j,$f){$i=SimpleParser::UPDATE($d,$j,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sDELETE($d,$f){$i=SimpleParser::DELETE($d,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function query($l,$m=null){return$this->connector->query($l,$m);}function close(){$this->connector->close();}function dev(){$this->connector->dev=true;}function getLog(){return$this->connector->log;}function clearCache(){$this->connector->clearCache();}}
?>