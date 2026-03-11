<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();
$root = realpath(__DIR__ . '/..');

echo "=====================================\n";
echo "PROJECT AUDIT\n";
echo "ROOT: {$root}\n";
echo "=====================================\n\n";

function rel($root,$path){
    return ltrim(str_replace($root,'',$path),'/');
}

function listPhp($dir){

    $files=[];

    if(!is_dir($dir)) return $files;

    $it=new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach($it as $f){

        if($f->isDir()) continue;

        if(strtolower($f->getExtension())==="php"){
            $files[]=$f->getPathname();
        }

    }

    sort($files);

    return $files;
}

#########################################################
# LOAD FILES
#########################################################

$dirs=[
$root."/app",
$root."/core",
$root."/config",
$root."/routes",
$root."/resources",
$root."/scripts",
$root."/public"
];

$php=[];

foreach($dirs as $d){
    $php=array_merge($php,listPhp($d));
}

$php=array_unique($php);
sort($php);

$code=[];

foreach($php as $f){
    $code[$f]=file_get_contents($f);
}

$all=implode("\n",$code);

echo "PHP files scanned: ".count($php)."\n\n";

#########################################################
# ROUTES
#########################################################

echo "-------------------------------------\n";
echo "ROUTES\n";
echo "-------------------------------------\n";

$routeFile=$root."/routes/web.php";

$routes=[];
$handlers=[];

if(file_exists($routeFile)){

$c=file_get_contents($routeFile);

preg_match_all(
'/\$router->(get|post)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/',
$c,
$m,
PREG_SET_ORDER
);

foreach($m as $r){

$method=strtoupper($r[1]);
$path=$r[2];
$handler=$r[3];

$routes[]=[
'method'=>$method,
'path'=>$path,
'handler'=>$handler
];

$handlers[]=$handler;

}

}

echo "Routes found: ".count($routes)."\n";

foreach($routes as $r){
echo $r['method']." ".$r['path']." -> ".$r['handler']."\n";
}

#########################################################
# CONTROLLERS
#########################################################

echo "\n-------------------------------------\n";
echo "CONTROLLERS\n";
echo "-------------------------------------\n";

$ctrl=listPhp($root."/app/Controllers");

$controllers=[];
$methodsWithout=[];
$controllersWithout=[];

foreach($ctrl as $f){

$name=basename($f,'.php');

$controllers[]=$name;

$c=$code[$f];

preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/',$c,$mm);

$has=false;

foreach($handlers as $h){

if(strpos($h,$name.'@')===0){
$has=true;
break;
}

}

if(!$has){
$controllersWithout[]=$name;
}

foreach($mm[1] as $method){

if($method=="__construct") continue;

$found=false;

foreach($handlers as $h){

if($h==$name.'@'.$method){
$found=true;
break;
}

}

if(!$found){
$methodsWithout[]=$name."::".$method;
}

}

}

echo "Controllers found: ".count($controllers)."\n";

echo "\nControllers without route:\n";

foreach($controllersWithout as $c){
echo "  - ".$c."\n";
}

echo "\nController methods without route:\n";

foreach($methodsWithout as $m){
echo "  - ".$m."\n";
}

#########################################################
# VIEWS
#########################################################

echo "\n-------------------------------------\n";
echo "VIEWS\n";
echo "-------------------------------------\n";

$views=listPhp($root."/resources/views");

echo "Views found: ".count($views)."\n";

$unusedViews=[];

foreach($views as $v){

$b=basename($v);

if(strpos($all,$b)===false){
$unusedViews[]=rel($root,$v);
}

}

echo "\nUnused views:\n";

foreach($unusedViews as $v){
echo "  - ".$v."\n";
}

#########################################################
# BROKEN INCLUDES
#########################################################

echo "\n-------------------------------------\n";
echo "BROKEN INCLUDES\n";
echo "-------------------------------------\n";

$broken=[];

foreach($php as $f){

$c=$code[$f];

preg_match_all(
'/require|include|require_once|include_once\s*\(?\s*[\'"]([^\'"]+)[\'"]/',
$c,
$m
);

foreach($m[1] as $t){

$full=realpath(dirname($f)."/".$t);

if(!$full){
$broken[]=rel($root,$f)." -> ".$t;
}

}

}

foreach($broken as $b){
echo $b."\n";
}

#########################################################
# LINK TARGETS
#########################################################

echo "\n-------------------------------------\n";
echo "LINK TARGETS\n";
echo "-------------------------------------\n";

$targets=[];

foreach($php as $f){

$c=$code[$f];

preg_match_all('/href=[\'"]([^\'"]+)/',$c,$m);

foreach($m[1] as $t){
$targets[$t]=true;
}

}

echo "Targets found: ".count($targets)."\n";

#########################################################
# DATABASE TABLES
#########################################################

echo "\n-------------------------------------\n";
echo "DATABASE TABLES (_v2)\n";
echo "-------------------------------------\n";

$res=$db->query("SHOW TABLES LIKE '%_v2'");

$tables=[];

while($r=$res->fetch_array()){
$tables[]=$r[0];
}

echo "Tables found: ".count($tables)."\n";

foreach($tables as $t){

echo "\nTABLE: ".$t."\n";

$used=false;

foreach($code as $file=>$c){

if(stripos($c,$t)!==false){

echo "  USED IN: ".rel($root,$file)."\n";
$used=true;

}

}

if(!$used){
echo "  NOT USED\n";
}

}

#########################################################
# COLUMNS
#########################################################

echo "\n-------------------------------------\n";
echo "DATABASE COLUMNS\n";
echo "-------------------------------------\n";

foreach($tables as $t){

echo "\nTABLE: ".$t."\n";

$res=$db->query("SHOW COLUMNS FROM `$t`");

while($c=$res->fetch_assoc()){

$col=$c['Field'];

if(in_array($col,['id','created_at','updated_at'])){
echo "  COLUMN: ".$col." skipped\n";
continue;
}

$found=false;

foreach($code as $f=>$cc){

if(preg_match('/\b'.$col.'\b/i',$cc)){
$found=true;
break;
}

}

if($found){
echo "  COLUMN: ".$col." used\n";
}else{
echo "  COLUMN: ".$col." POSSIBLY UNUSED\n";
}

}

}

#########################################################
# SQL DUPLICATES
#########################################################

echo "\n-------------------------------------\n";
echo "DUPLICATE SQL QUERIES\n";
echo "-------------------------------------\n";

$queries=[];

foreach($code as $f=>$c){

preg_match_all('/SELECT .*?;/is',$c,$m);

foreach($m[0] as $q){

$q=trim($q);

if(strlen($q)<20) continue;

$queries[$q][]=rel($root,$f);

}

}

foreach($queries as $q=>$files){

if(count($files)>2){

echo "\nQuery repeated ".count($files)." times\n";
echo $q."\n";

}

}

#########################################################
# SECRETS
#########################################################

echo "\n-------------------------------------\n";
echo "HARDCODED SECRETS\n";
echo "-------------------------------------\n";

$hits=[];

foreach($php as $f){

$c=$code[$f];

if(preg_match('/apiToken|X-Auth-Token|password\s*=\s*[\'"]/i',$c)){
$hits[]=rel($root,$f);
}

}

foreach(array_unique($hits) as $h){
echo "  - ".$h."\n";
}

#########################################################
# SUMMARY
#########################################################

echo "\n=====================================\n";
echo "SUMMARY\n";
echo "=====================================\n";

echo "Unused views: ".count($unusedViews)."\n";
echo "Controllers without route: ".count($controllersWithout)."\n";
echo "Methods without route: ".count($methodsWithout)."\n";
echo "Broken includes: ".count($broken)."\n";
echo "Tables: ".count($tables)."\n";
echo "Secret hits: ".count($hits)."\n";

echo "\n=====================================\n";
echo "AUDIT COMPLETE\n";
echo "=====================================\n";