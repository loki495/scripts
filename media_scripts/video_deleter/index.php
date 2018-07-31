<?php
ini_set('display_errors',1);

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: POST,GET,OPTIONS,PUT,DELETE');
header('Access-Control-Allow-Headers: Content-Type,Accept');

$valid_extensions = ['mkv','mp4','avi','wmv'];
$base = realpath(__DIR__.'/../').'/';
$ytbase = realpath(__DIR__.'/../../dev/ytdownloader/downloads').'/';

if (isset($_POST['files'])) {
	foreach ($_POST['files'] as $file) {
		$type = str_replace($base, '', $file);
		$type = str_replace($ytbase, 'youtube/', $type);
		$pos = strpos($type,'/');
		$type = substr($type,0,$pos);
		if ($type == 'TV Shows' ||
			$type == 'Movies' ||
			$type == 'youtube' ||
			$type == 'torrents/downloading' ||
			$type == 'torrents' ||
			0) {
			unlink($file);
		}
	}
	header("Location: http://ac495.dynu.com:8001/");
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
    // $bytes /= pow(1024, $pow);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getDirContents($dir, &$results = array()){
    global $valid_extensions,$base,$ytbase;

    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(!is_dir($path)) {
            //$results[] = $path;
            if (strpos($dir,'ytdownloader')!==FALSE) {
                $shortname = str_replace("/",' - ',str_replace($ytbase,'',$path));
                if (strpos($shortname,'.')!==FALSE) $shortname = substr($shortname,0,strrpos($shortname,'.'));
                if (strpos($shortname,'.')!==FALSE) $shortname = substr($shortname,0,strrpos($shortname,'.'));
                if (strpos($shortname,'.')!==FALSE) $shortname = substr($shortname,0,strrpos($shortname,'.'));
                $type = 'youtube';
            } else
            if (strpos($dir,'torrents/completed')!==FALSE) {
                list($dummy, $type, $shortname) = explode('/',str_replace($base,'',$path));
            } else {
                list($type, $shortname) = explode('/',str_replace($base,'',$path));
            }
			$pathinfo = pathinfo($path);
			if (array_search($pathinfo['extension'],$valid_extensions) === FALSE) {
				continue;
			}
            $size = filesize($path);
			$date = date("Y-m-d H:i:s",filemtime($path));
			$result['name'] = $shortname;
			$result['type'] = $type;
			$result['filename'] = $path;
			$result['human_size'] = formatBytes($size);
			$result['size'] =($size);
			$result['date'] =($date);
			if ($type=='TV Shows') {
				preg_match('/S(\d+)E(.*?) - (.*?)\((.*?)\)/',$pathinfo['basename'],$matches);
				//list($dummy, $season, $ep, $ep_name) = $matches;
                $season = isset($matches[1])?$matches[1]:'';
                $ep = isset($matches[2])?$matches[2]:'';
                $ep_name = isset($matches[3])?$matches[3]:'';

				$result['season'] = trim($season,'0 ');
				$result['episode'] = ltrim($ep,'0 ');
				$result['episode_name'] = trim($ep_name);

                $fn = $pathinfo['filename'];
                preg_match('/\((.*?)\)$/',$fn,$matches,0,strrpos($fn,' '));

				$result['quality'] = isset($matches[1])?$matches[1]:'';
            } else
            if ($type == 'Movies') {
				$result['season'] = '';
				$result['episode'] = '';
				$result['episode_name'] = '';

                $fn = trim(preg_replace("/ Proper$/","",$pathinfo['filename']));
                $fn = trim(preg_replace("/ proper/","",$fn));
                preg_match('/\s(.*?)$/', $fn, $matches, 0, strrpos($fn,' '));
                if ($matches[1] == 'Proper') {
                    echo '<pre>'.__LINE__.' - '.__FILE__."\n";$e = new \Exception();print_r($e->getTraceAsString());print_r($pathinfo);exit;
                }
				$result['quality'] = $matches[1];

            } else
            if ($type == 'youtube') {
                $new_name = str_replace('_',' ',$result['name']);
                $parts = explode('.',$new_name);
                if (count($parts) > 1) {
                    list($channel, $date) = explode(' - ',$parts[0]);
                    $result['name'] = $channel;
                    $result['episode_name'] = $parts[1];
                } else {
                    $parts = explode(' - ',$new_name);
                    $result['name'] = $parts[0];
                    unset($parts[0]);
                    $result['episode_name'] = implode(' - ',$parts);
                }
				$result['quality'] = '';
			}
			preg_match('/\((\d\d\d\d)\)/',$path,$matches);
			if (isset($matches[1])) {
				$result['year'] = $matches[1];
				$result['name'] = trim(str_replace($matches[0],'',$result['name']));
			} else {
				$result['year'] = '';
			}
			$results[] = $result;
		} else if($value != "." && $value != "..") {
			getDirContents($path, $results);
			//$results[] = $path;
		}
	}

	return $results;
}

if (isset($_GET['names'])) {

    $names = [];

	$movies = (getDirContents($base.'Movies'));
	$tv = (getDirContents($base.'TV Shows'));
    $youtube = (getDirContents('/home/andres/dev/ytdownloader/downloads/'));
	$completed = (getDirContents($base.'torrents/completed'));

	$result = array_merge($movies,$tv,$youtube,$completed);
    foreach ($result as $r) {
        if ($r['type'] == 'TV Shows') {
            $names[$r['name']] = 1;
        }
    }
    $names = array_keys($names);

    $k = '0';
?>
        <div class="form-group type-name">
            <input class="form-radio-input" type="radio" name="type-name" value="" checked id="type-name-<?= $k ?>" /> <label class="form-radio-label"  for="type-name-<?= $k ?>">All Files</label>
        </div>
<?php
    foreach ($names as $k => $name) { ?>
        <div class="form-group type-name">
            <input class="form-radio-input" type="radio" name="type-name" value="<?= $name ?>" id="type-name-<?= $k+1 ?>" /> <label class="form-radio-label"  for="type-name-<?= $k+1 ?>"><?= $name ?></label>
        </div>
<?php }

    exit;

} else
if (isset($_GET['data'])) {

	$movies = (getDirContents($base.'Movies'));
	$tv = (getDirContents($base.'TV Shows'));
    $youtube = (getDirContents('/home/andres/dev/ytdownloader/downloads/'));
	$completed = (getDirContents($base.'torrents/completed'));
    $deluge_info = shell_exec('deluge-console -c /home/andres/.config/deluge/ info 2>&1');
    $deluge_info = explode("\n",$deluge_info);
    $trackers = [];
    $current_name = '';
    foreach ($deluge_info as $line) {
        if (stripos($line,'Name: ') === 0) {
            $current_name = str_replace("Name: ",'',$line);
        }
        if (stripos($line,'Tracker status: ') === 0) {
            list($trackers[strtolower($current_name)]) = explode(":",str_replace("Tracker status: ",'',$line));
        }
    }

    foreach ($completed as $k => $v) {
        $name = $v['name'];
        if (isset($trackers[$name])) {
            $completed[$k]['tracker'] = $trackers[$name];
        } else
        if (isset($trackers[strtolower($name)])) {
            $completed[$k]['tracker'] = $trackers[strtolower($name)];
        } else {
            $parts = pathinfo($name);
            $name = strtolower($parts['filename']);
            $completed[$k]['tracker'] = $trackers[$name];

        }
        chdir('/home/andres/media/scripts');
        $cmd = 'PYTHONPATH=/home/andres/.local/lib/python2.7/site-packages /usr/bin/python2 parse-filename.py "'.$name.'" 2>&1';
        $output = json_decode(exec($cmd));

        $completed[$k]['name'] = $output->title;
        $completed[$k]['quality'] = isset($output->resolution)?$output->resolution:'';
        $completed[$k]['year'] = isset($output->year)?$output->year:'';
        $completed[$k]['season'] = isset($output->season)?$output->season:'';
        $completed[$k]['episode'] = isset($output->episode)?$output->episode:'';
        if ($output->title == 'Preacher') {
            //echo '<pre>'.__LINE__.' - '.__FILE__."\n";$e = new \Exception();print_r($e->getTraceAsString());print_r($output);exit;
        }
    }

	$result = array_merge($movies,$tv,$youtube,$completed);
    foreach ($result as $k => $v) {
        if (!isset($v['tracker'])) {
            $result[$k]['tracker'] = '';
        }
        if (isset($v['quality'])) {
            $result[$k]['quality'] = preg_replace('/[^\d]/','',$v['quality']);
        }
    }

    echo json_encode($result);

	exit;
}

function printRows($files) { ?>
	<?php foreach ($files as $file) { ?>
	<tr>
		<td><input type="checkbox" name="files[]" value="<?php echo $file['filename']; ?>" /></td>
        <?php /* <td data-checkbox="true" ></td> */ ?>
		<td><?php echo $file['type']; ?></td>
		<td><?php echo $file['date']; ?></td>
		<td><?php echo $file['human_size']; ?></td>
<?php if ($file['type'] == 'Movies') { ?>
		<td class="name"><?php echo $file['name']; ?></td>
		<td colspan="3"><?php echo $file['year']; ?></td>
<?php } else { ?>
	  	<td class="name"><?php echo $file['name']; ?></td>
		<td><?php echo $file['season']; ?></td>
		<td><?php echo $file['episode']; ?></td>
		<td class="name"><?php echo $file['episode_name']; ?></td>
<?php } ?>
	</tr>
	<?php } ?>
<?php
}

list($total,$used,$free,$percent) = explode(" ",trim(`df -h | grep /dev/mapper | awk '{print $2 " " $3 " " $4 " " $5; }'`," \t\r\n\f"));
$free_space_str = "$free/$total ($percent)";
$progress_value = 100 - trim($percent,'%');
$progress_color = "0a0";
if ($progress_value < 90) {
    $progress_color = "4a0";
}
if ($progress_value < 76) {
    $progress_color = "8a0";
}
if ($progress_value < 51) {
    $progress_color = "dd0";
}
if ($progress_value < 37) {
    $progress_color = "aa0";
}
if ($progress_value < 25) {
    $progress_color = "c80";
}
if ($progress_value < 20) {
    $progress_color = "c40";
}
if ($progress_value < 11) {
    $progress_color = "f00";
}
$progress_value = 100 - $progress_value;
?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" />
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.css">

<style>
.selected,
.fixed-table-container tbody .selected td {
	background: rgb(50,180,180) !important;
	color: #fff !important;
	text-shadow: 1px 1px #000;
}
.name { color: rgb(250,220,100); }
tr { cursor: pointer; }
.form-group { margin-right: 10px; }
.progress-value { width: <?php echo ($progress_value*2); ?>px; border-radius: 7px 0 0 7px; border: none; background: #<?php echo $progress_color;?>; margin-top: 0px; height: 16px; position: relative; top:0}
.progress-value::after { content: " "; width: 200px; height:20px; margin:0; position: relative; border: 2px #888 solid; border-radius: 7px; margin-top: -2px; clear: both; display: block; background: transparent; position:absolute; top:0}
tr[data-type=tv-shows]  td, .type-check-tv-shows  { background: #074803 !important; }
tr[data-type=movies]    td, .type-check-movies    { background: #081067 !important; }
tr[data-type=youtube]   td, .type-check-youtube   { background: #501c16 !important; }
tr[data-type=completed] td, .type-check-completed { background: #564d2c !important; }
.type-check-button { padding: 10px; border-radius: 7px; cursor: pointer; display: inline-block;}
.type-check-button label { margin: 0; color: #fff; cursor: pointer; }
.type-check-all { background: #fff !important; }
.type-check-all label { color: #000; }
.form-group.type-name { display: inline-block; border: 1px solid #888; padding:5px; border-radius: 7px; cursor: pointer; }
.form-group.type-name label { margin: 0;  cursor: pointer; }
.form-group.type-name:hover { background: #ff8;}
.usage div { display: inline-block; }
.delete-button-wrapper { display: inline-block; font-weight: bold; }
.delete-button-wrapper input { font-weight: bold; }
</style>
</head>
<body>
<form method="post" />
<div class="container-fluid">
<div class="col-md-12">
    <div class="row">
        <div class="col-md-4 col-sm-12">
            <div class="col-md-12 usage" style="line-height: 40px;">
                <div>
                    HD Usage:
                </div>
                <div class="progress-value"></div>
            </div>
            Free Space: <?php echo $free_space_str; ?>
        </div>
        <div class="col-md-8 col-sm-12">
          <div class="form-group delete-button-wrapper">
            <input class="form-control btn btn-danger" type="button" value="Delete" onclick="checkFiles();" />
          </div>
          <div class="form-group type-check-button type-check-all">
            <input class="form-radio-input" type="radio" name="file-type" value="" id="all-type" checked /> <label class="form-radio-label"  for="all-type">All Files</label>
          </div>
          <div class="form-group type-check-button type-check-movies">
            <input class="form-radio-input" type="radio" name="file-type" value="Movies" id="movies-type" /> <label class="form-radio-label"  for="movies-type">Movies Only</label>
          </div>
          <div class="form-group type-check-button type-check-tv-shows">
            <input class="form-radio-input" type="radio" name="file-type" value="TV Shows" id="tv-type" /> <label class="form-radio-label" for="tv-type">TV Shows Only</label>
          </div>
          <div class="form-group type-check-button type-check-youtube">
            <input class="form-radio-input" type="radio" name="file-type" value="youtube" id="youtube-type" /> <label class="form-radio-label"  for="youtube-type">Youtube Only</label>
          </div>
          <div class="form-group type-check-button type-check-completed">
            <input class="form-radio-input" type="radio" name="file-type" value="completed" id="completed-type" /> <label class="form-radio-label"  for="completed-type">Completed Only</label>
          </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="row" style="margin-bottom: 30px">
    </div>
</div>
<div class="col-md-12">
    <div class="row" id="names-data">
    </div>
</div>
<div class="table-responsive-sm">
<table class="table table-dark table-striped table-bordered table-hover" id="files">
		<?php /*
		<th></th>
		<th data-field="type">Type</th>
		<th data-sortable="true" data-field="date">Date</th>
		<th data-sortable="true" data-field="human_size">Size</th>
		<th data-sortable="true" data-field="name">Name</th>
		<th data-sortable="true" data-field="year">Year</th>
		<th data-sortable="true" data-field="season">Season #</th>
		<th data-sortable="true" data-field="episode">Episode #</th>
		<th data-field="episode_name">Episode Name</th>
<tbody>
<?php

//printRows($tv,1);
//printRows($movies);
//printRows($completed);

?>
</tbody>
			*/ ?>
</table>
</div>
</div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js"></script>
<script src="js/bootstrap-table-multiple-sort.js"></script>

<script>
jQuery(function() {

    $('#names-data').load('/?names=1');
	$('table').bootstrapTable({
        autoWidth: false,
//		showMultiSort: true,
//		sortPriority: [{'sortName': 'date',"sortOrder":"desc"}, {'sortName': 'season',"sortOrder":"desc"},{'sortName': 'episode',"sortOrder":"desc"}],
		classes: 'table table-bordered table-hover table-dark',
        height:500,
		search:true,
		clickToSelect:true,
		maintainSelected:true,
		selectItemName:"files[]",
		checkboxHeader:true,
		url:"http://ac495.dynu.com:8001/?data=1",
		idField:"filename",
		uniqueId:"filename",
		sortName:"date",
		sortOrder:"desc",
        sortStable: true,
        rowAttributes: function(row, index) {
            return { 'data-type': row['type'].toLowerCase().replace(' ','-') } ;
        },
		rememberOrder:true,
		columns: [
			{
				checkbox: true,
				sortable: false,
				visible: true,
				title: '',
			},
			{
				sortable: true,
				title: 'Type',
				field: 'type',
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Date',
				field: 'date',
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Size',
				field: 'human_size',
				sorter: sortTable,
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Name',
				field: 'name',
                width: '300px',
                'max-width': '300px',
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Year',
				field: 'year',
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Season #',
				field: 'season',
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Episode #',
				field: 'episode',
			},
			{
				checkbox: false,
				sortable: false,
				visible: true,
				title: 'Episode name',
				field: 'episode_name',
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Quality',
				field: 'quality',
			},
			{
				checkbox: false,
				sortable: true,
				visible: true,
				title: 'Tracker',
				field: 'tracker',
			},
		],
	});
    $(document).on('change','input[name=file-type]',function() {
        var val = $('input[name=file-type]:checked').val();
        $('input[name=type-name]:checked').prop('checked',false);
        $('input[name=type-name]:first').prop('checked',true);

        var filter = {};
        if (val) {
            filter = { type: val };
        }
        //alert(filter.type);
        $('table').bootstrapTable('filterBy',filter);
    });
    $(document).on('change','input[name=type-name]',function() {
        var val = $('input[name=type-name]:checked').val();
        $('input[name=file-type]:checked').prop('checked',false);
        $('input[name=file-type]#tv-type').prop('checked',true);
        var filter = {};
        if (val) {
            filter = { name: val };
        }
        //alert(filter.type);
        $('table').bootstrapTable('filterBy',filter);
    });
});

function convertToBytes(from){
    var parts = from.split(' ');
	var number = parts[0];
	var type = parts[1]
    switch(type){
        case "KB":
            return number*1024;
        case "MB":
            return number*Math.pow(1024,2);
        case "GB":
            return number*Math.pow(1024,3);
        case "TB":
            return number*Math.pow(1024,4);
        case "PB":
            return number*Math.pow(1024,5);
        default:
            return from;
    }
}

function sortTable(a,b) {
	a = convertToBytes(a);
	b = convertToBytes(b);
	if (a < b) return -1;
	if (a > b) return 1;
	return 0;
}
function checkFiles() {
    var names = "";
	sels = $('table').bootstrapTable('getAllSelections');
	$(sels).each(function(key, item) {
		names += "* " + item.filename + "\n\n";
	});
	if (!names) return false;

    if (confirm("Delete: \n\n"+names)) {
        $('form').submit();
    }
}
</script>
</body>
