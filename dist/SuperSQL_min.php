<?php
/*
 Author: Andrews54757
 License: MIT (https://github.com/ThreeLetters/SuperSQL/blob/master/LICENSE)
 Source: https://github.com/ThreeLetters/SQL-Library
 Build: v1.1.5
 Built on: 16/09/2017
*/

namespace SuperSQL;

// lib/connector.php
class Response implements \ArrayAccess,\Iterator{public$result;public$affected;public$ind=0;public$error;public$errorData;public$outTypes;public$complete=true;function __construct($a,$b,&$c,$d){$this->error=!$b;if(!$b){$this->errorData=$a->errorInfo();}else{$this->outTypes=$c;if($d===0){$e=$a->fetchAll(\PDO::FETCH_ASSOC);if($c){foreach($e as$f=>&$g){$this->map($g,$c);}}$this->result=$e;}else if($d===1){$this->stmt=$a;$this->complete=false;$this->result=array();}$this->affected=$a->rowCount();}}function close(){$this->complete=true;if($this->stmt){$this->stmt->closeCursor();$this->stmt=null;}}private function fetchNextRow(){$g=$this->stmt->fetch(\PDO::FETCH_ASSOC);if($g){if($this->outTypes){$this->map($g,$this->outTypes);}array_push($this->result,$g);return$g;}else{$this->close();return false;}}private function fetchAll(){while($this->fetchNextRow()){}}function map(&$g,&$c){foreach($c as$h=>$i){if(isset($g[$h])){switch($i){case 'int':$g[$h]=(int)$g[$h];break;case 'double':$g[$h]=(double)$g[$h];break;case 'string':$g[$h]=(string)$g[$h];break;case 'bool':$g[$h]=$g[$h]?true:false;break;case 'json':$g[$h]=json_decode($g[$h]);break;case 'object':$g[$h]=unserialize($g[$h]);break;}}}}function error(){return$this->error?$this->errorData:false;}function getData($j=false){if(!$this->complete&&!$j)$this->fetchAll();return$this->result;}function getAffected(){return$this->affected;}function countRows(){return count($this->result);}function offsetSet($k,$l){}function offsetExists($k){return$this->offsetGet($k)===null?false:true;}function offsetUnset($k){}function offsetGet($k){if(is_int($k)){if(isset($this->result[$k])){return$this->result[$k];}else if(!$this->complete){while($this->fetchNextRow()){if(isset($this->result[$k]))return$this->result[$k];}}}return null;}function next(){if(isset($this->result[$this->ind])){return$this->result[$this->ind++];}else if(!$this->complete){$g=$this->fetchNextRow();$this->ind++;return$g;}else{return false;}}function rewind(){$this->ind=0;}function current(){return$this->result[$this->ind];}function key(){return$this->ind;}function valid(){return$this->offsetExists($this->ind);}}class Connector{public$db;public$log=array();public$dev=false;function __construct($m,$n,$o){try{$this->db=new \PDO($m,$n,$o);}catch(\PDOException$p){throw new \Exception($p->getMessage());}}function query($q,$r=null,$c=null,$d=0){$s=$this->db->prepare($q);if($r)$p=$s->execute($r);else$p=$s->execute();if($this->dev)array_push($this->log,array($q,$r));if($d!==3){return new Response($s,$p,$c,$d);}else{return$s;}}function _query(&$t,$u,&$v,&$c=null,$d=0){$s=$this->db->prepare($t);if($this->dev)array_push($this->log,array($t,$u,$v));foreach($u as$w=>&$x){$s->bindParam($w+1,$x[0],$x[1]);}$p=$s->execute();if(!isset($v[0])){return new Response($s,$p,$c,$d);}else{$y=array();array_push($y,new Response($s,$p,$c,0));foreach($v as$w=>$l){foreach($l as$z=>&$aa){$u[$z][0]=$aa;}$p=$s->execute();array_push($y,new Response($s,$p,$c,0));}return$y;}}function close(){$this->db=null;}}
// lib/parser.php
class Parser{static function getArg(&$a){preg_match('/^(?:\[(.{2})\])(.*)/',$a,$b);if(isset($b[1])){$a=$b[2];return$b[1];}else{return false;}}static function isRaw(&$c){if($c[0]==='#'){$c=substr($c,1);return true;}return false;}static function isSpecial($d){return$d==='json'||$d==='object';}static function append(&$e,$f,$g,$h){if(is_array($f)&&$h[$g][2]<5){$i=count($f);for($j=1;$j<$i;$j++){if(!isset($e[$j-1]))$e[$j-1]=array();$e[$j-1][$g]=$f[$j];}}}static function stripArgs(&$c){preg_match('/(?:\[.{2}\]){0,2}([^\[]*)/',$c,$k);return$k[1];}static function append2(&$l,$m,$n,&$h,$b=false){$i=count($n);if($b){self::recurse($h,$n[0],$m,'',$h,0);}for($c=1;$c<$i;$c++){if(!isset($l[$c-1]))$l[$c-1]=array();self::recurse($l[$c-1],$n[$c],$m,'',$h,1);}}private static function recurse(&$o,$f,$m,$p,$h,$b){foreach($f as$j=>&$q){if($j[0]==='#')continue;self::stripArgs($j);$r=$j.'#'.$p;if(isset($m[$r]))$s=$m[$r];else$s=$m[$j];if(is_array($q)&&!self::isSpecial($h[$s][2])){if(isset($q[0])){foreach($q as$t=>&$u){$v=$s+$t;if($b&&isset($o[$v]))trigger_error('Key collision: '.$j,E_USER_WARNING);$o[$v]=self::value($h[$v][2],$u);if($b)$o[$v]=$o[$v][0];}}else{self::recurse($o,$q,$m,$p.'/'.$j,$h,$b);}}else{if($b&&isset($o[$s]))trigger_error('Key collision: '.$j,E_USER_WARNING);$o[$s]=self::value($h[$s][2],$q);if($b)$o[$s]=$o[$s][0];}}}static function quote($a){preg_match('/([a-zA-Z0-9_]*)\.?([a-zA-Z0-9_]*)?/',$a,$k);if($k[2]!==''){return '`'.$k[1].'`.`'.$k[2].'`';}else{return '`'.$k[1].'`';}}static function quoteArray(&$w){foreach($w as&$q){$q=self::quote($q);}}static function table($x){if(is_array($x)){$y='';foreach($x as$t=>&$f){$z=self::getType($f);if($t!==0)$y.=', ';$y.='`'.$f.'`';if($z)$y.=' AS `'.$z.'`';}return$y;}else{return '`'.$x.'`';}}static function value($d,$aa){if(!$d)$d=gettype($aa);$ba=\PDO::PARAM_STR;if($d==='integer'||$d==='int'){$ba=\PDO::PARAM_INT;$aa=(int)$aa;}else if($d==='string'||$d==='str'||$d==='double'){$aa=(string)$aa;}else if($d==='boolean'||$d==='bool'){$ba=\PDO::PARAM_BOOL;$aa=$aa?'1':'0';}else if($d==='null'||$d==='NULL'){$ba=\PDO::PARAM_NULL;$aa=null;}else if($d==='resource'||$d==='lob'){$ba=\PDO::PARAM_LOB;}else if($d==='json'){$aa=json_encode($aa);}else if($d==='object'){$aa=serialize($aa);}else{$aa=(string)$aa;trigger_error('Invalid type '.$d.' Assumed STRING',E_USER_WARNING);}return array($aa,$ba,$d);}static function getType(&$a){preg_match('/([^\[]*)(?:\[([^\]]*)\])?/',$a,$b);$a=$b[1];return isset($b[2])?$b[2]:false;}static function rmComments($a){preg_match('/([^#]*)/',$a,$k);return$k[1];}static function conditions($n,&$h,&$ca=false,&$g=0,$da=' AND ',$ea=' = ',$fa=''){$ga=0;$y='';foreach($n as$c=>$f){if(is_int($c)){$c=$f;$f='';}preg_match('/^(?<r>\#)?(?:(?:\[(?<a>.{2})\])(?:(?:\[(?<b>.{2})\])(?:\[(?<c>.{2})\])?)?)?(?<out>.*)/',$c,$k);$ha=($k['r']==='#');$ia=$k['a'];$c=$k['out'];$ja=$da;$ka=$ea;$d=$ha?false:self::getType($c);$w=is_array($f)&&!self::isSpecial($d);$la=$w&&!isset($f[0]);if($ia&&($ia==='||'||$ia==='&&')){$ja=($ia==='||')?' OR ':' AND ';$ia=$k['b'];if($w&&$ia&&($ia==='||'||$ia==='&&')){$da=$ja;$ja=($ia==='||')?' OR ':' AND ';$ia=$k['c'];}}$ma=false;if($ia&&$ia!=='=='){if($ia==='!='||$ia==='>='||$ia==='<='){$ka=' '.$ia.' ';}else if($ia==='>>'){$ka=' > ';}else if($ia==='<<'){$ka=' < ';}else if($ia==='~~'){$ka=' LIKE ';}else if($ia==='!~'){$ka=' NOT LIKE ';}else if($ia==='><'||$ia==='<>'){$ma=true;}else{throw new \Exception('Invalid operator '.$ia.' Available: ==,!=,>>,<<,>=,<=,~~,!~,<>,><');}}else if($la||$ia==='==')$ka=' = ';if(!$w)$da=$ja;if($ga!==0)$y.=$da;$na=self::rmComments($c);if(!$ha)$na=self::quote($na);if($w){$y.='(';if($la){$y.=self::conditions($f,$h,$ca,$g,$ja,$ka,$fa.'/'.$c);}else{if($ca!==false&&!$ha){$ca[$c]=$g;$ca[$c.'#'.$fa]=$g++;}if($ma){$g+=2;$y.=$na.($ia==='<>'?'NOT':'').' BETWEEN ';if($ha){$y.=$f[0].' AND '.$f[1];}else{$y.='? AND ?';array_push($h,self::value($d,$f[0]));array_push($h,self::value($d,$f[1]));}}else{$oa=isset($f[1]);$i=$oa?count($f):$f[0];for($j=0;$j<$i;$j++){$q=$oa?$f[$j]:'';if($j!==0)$y.=$ja;++$g;$y.=$na.$ka;if($ha){$y.=$q;}else{$y.='?';array_push($h,self::value($d,$q));}}}}$y.=')';}else{$y.=$na.$ka;if($ha){$y.=$f;}else{$y.='?';array_push($h,self::value($d,$f));if($ca!==false){$ca[$c]=$g;$ca[$c.'#'.$fa]=$g++;}}}++$ga;}return$y;}static function JOIN($da,&$y,&$h,&$t){foreach($da as$c=>&$f){$ha=self::isRaw($c);$ia=self::getArg($c);switch($ia){case '<<':$y.=' RIGHT';break;case '>>':$y.=' LEFT';break;case '<>':$y.=' FULL';break;case '>~':$y.=' LEFT OUTER';break;}$y.=' JOIN `'.$c.'` ON ';if($ha){$y.=$f;}else{$y.=self::conditions($f,$h,$pa,$t);}}}static function WHERE(&$y,$qa,&$h,&$l,$t=0){$y.=' WHERE ';if(isset($qa[0])){$g=array();$y.=self::conditions($qa[0],$h,$g,$t);$b=isset($qa[0][0])&&isset($qa[1][0]);self::append2($l,$g,$b?$qa[1]:$qa,$h,$b);}else{$y.=self::conditions($qa,$h);}}static function columns($ra,&$y,&$sa){$ta='';$pa=$ra[0][0];if($pa==='D'||$pa==='I'){if($ra[0]==='DISTINCT'){$y.='DISTINCT ';array_splice($ra,0,1);}else if(substr($ra[0],0,11)==='INSERT INTO'){$y=$ra[0].' '.$y;array_splice($ra,0,1);}else if(substr($ra[0],0,4)==='INTO'){$ta=' '.$ra[0].' ';array_splice($ra,0,1);}}if(isset($ra[0])){if($ra[0]==='*'){array_splice($ra,0,1);$y.='*';foreach($ra as$t=>$f){$z=self::getType($f);$sa[$f]=$z;}}else{foreach($ra as$t=>$f){$v=self::getType($f);$ua=false;if($v){$ua=$v;$va=self::getType($f);if($va){$d=$va;}else{if($ua==='json'||$ua==='object'||$ua==='int'||$ua==='string'||$ua==='bool'||$ua==='double'){$d=$ua;$ua=false;}else$d=false;}if($d){if(!$sa)$sa=array();$sa[$ua?$ua:$f]=$d;}}if($t!==0){$y.=', ';}$y.=self::quote($f);if($ua)$y.=' AS `'.$ua.'`';}}}else$y.='*';$y.=$ta;}static function SELECT($x,$ra,$qa,$da,$wa){$y='SELECT ';$h=$l=array();$sa=null;$t=0;if(!isset($ra[0])){$y.='*';}else{self::columns($ra,$y,$sa);}$y.=' FROM '.self::table($x);if($da){self::JOIN($da,$y,$h,$t);}if(!empty($qa)){self::WHERE($y,$qa,$h,$l,$t);}if($wa){if(is_int($wa)){$y.=' LIMIT '.$wa;}else if(is_string($wa)){$y.=' '.$wa;}else if(is_array($wa)){if(isset($wa[0])){$y.=' LIMIT '.(int)$wa[0].' OFFSET '.(int)$wa[1];}else{if(isset($wa['GROUP'])){$y.=' GROUP BY ';if(is_string($wa['GROUP'])){$y.=self::quote($wa['GROUP']);}else{self::quoteArray($wa['GROUP']);$y.=implode(', ',$wa['GROUP']);}if(isset($wa['HAVING'])){$y.=' HAVING '.(is_string($wa['HAVING'])?$wa['HAVING']:self::conditions($wa['HAVING'],$h,$pa,$t));}}if(isset($wa['ORDER'])){$y.=' ORDER BY '.self::quote($wa['ORDER']);}if(isset($wa['LIMIT'])){$y.=' LIMIT '.(int)$wa['LIMIT'];}if(isset($wa['OFFSET'])){$y.=' OFFSET '.(int)$wa['OFFSET'];}}}}return array($y,$h,$l,$sa);}static function INSERT($x,$xa,$ya){$y='INSERT INTO '.self::table($x).' (';$h=$l=$g=array();$za='';$va=0;$ab=isset($xa[0]);$n=$ab?$xa[0]:$xa;foreach($n as$c=>$f){$ha=self::isRaw($c);if($va){$y.=', ';$za.=', ';}else$va=1;if(!$ha)$d=self::getType($c);$y.='`'.$c.'`';if($ha){$za.=$f;}else{$za.='?';$bb=!$ab&&(!$d||!self::isSpecial($d))&&is_array($f);array_push($h,self::value($d,$bb?$f[0]:$f));if($ab){$g[$c]=array($f,$d);}else if($bb){self::append($l,$f,$t++,$h);}}}$y.=') VALUES ('.$za.')';if($ab){unset($xa[0]);foreach($xa as$cb){$y.=', ('.$za.')';foreach($g as$c=>$f){array_push($h,self::value($f[1],isset($cb[$c])?$cb[$c]:$f[0]));}}}if($ya)$y.=' '.$ya;return array($y,$h,$l);}static function UPDATE($x,$xa,$qa){$y='UPDATE '.self::table($x).' SET ';$h=$l=$m=array();$t=$va=0;$ab=isset($xa[0]);$n=$ab?$xa[0]:$xa;foreach($n as$c=>&$f){$ha=self::isRaw($c);if($va){$y.=', ';}else$va=1;if($ha){$y.='`'.$c.'` = '.$f;}else{$ia=self::getArg($c);$d=self::getType($c);$y.='`'.$c.'` = ';if($ia){$y.='`'.$c.'` ';switch($ia){case '+=':$y.='+ ?';break;case '-=':$y.='- ?';break;case '/=':$y.='/ ?';break;case '*=':$y.='* ?';break;}}else$y.='?';$bb=(!$d||!self::isSpecial($d))&&is_array($f);array_push($h,self::value($d,$bb?$f[0]:$f));if($ab){$m[$c]=$t++;}else if($bb){self::append($l,$f,$t++,$h);}}}if($ab)self::append2($l,$m,$xa,$h);if(!empty($qa)){self::WHERE($y,$qa,$h,$l,$t);}return array($y,$h,$l);}static function DELETE($x,$qa){$y='DELETE FROM '.self::table($x);$h=$l=array();if(!empty($qa)){self::WHERE($y,$qa,$h,$l);}return array($y,$h,$l);}}
// index.php
class SuperSQL{public$con;public$lockMode=false;function __construct($a,$b,$c){$this->con=new Connector($a,$b,$c);}function SELECT($d,$e=array(),$f=array(),$g=null,$h=false){if((is_int($g)||is_string($g)||isset($g[0]))&&!$h){$h=$g;$g=null;}$i=Parser::SELECT($d,$e,$f,$g,$h);return$this->con->_query($i[0],$i[1],$i[2],$i[3],$this->lockMode?0:1);}function INSERT($d,$j,$k=null){$i=Parser::INSERT($d,$j,$k);return$this->con->_query($i[0],$i[1],$i[2]);}function UPDATE($d,$j,$f=array()){$i=Parser::UPDATE($d,$j,$f);return$this->con->_query($i[0],$i[1],$i[2]);}function DELETE($d,$f=array()){$i=Parser::DELETE($d,$f);return$this->con->_query($i[0],$i[1],$i[2]);}function query($l,$m=null,$n=null,$o=0){return$this->con->query($l,$m,$n,$o);}function close(){$this->con->close();}function dev(){$this->con->dev=true;}function getLog(){return$this->con->log;}function transact($p){$this->con->db->beginTransaction();$q=$p($this);if($q===false)$this->con->db->rollBack();else$this->con->db->commit();return$q;}function modeLock($r){$this->lockMode=$r;}}
?>