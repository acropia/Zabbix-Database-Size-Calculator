<?php
/**
 * Zabbix-Database-Size-Calculator
 *
 * @version 0.1
 * @author Jan Bouma | SoHosted
 */
define('ZBXCALC_VERSION', '0.1');

$historyStoragePeriodDays = 7;
$historyBytesPerRecord = 50; // DO NOT CHANGE
$trendStoragePeriodDays = 365;
$trendBytesPerRecord = 60; // DO NOT CHANGE

function formatBytes($size, $precision = 0) {
	$base = log($size, 1024);
	$suffixes = array('', 'KB', 'MB', 'GB', 'TB');   
	return round(pow(1024, $base - floor($base)), $precision) ." ".$suffixes[floor($base)];
}

if (!file_exists('/etc/zabbix/web/zabbix.conf.php')) {
	echo "Config file <tt>/etc/zabbix/web/zabbix.conf.php</tt> not found!";
	die();
}
include_once('/etc/zabbix/web/zabbix.conf.php');
$dbh = new PDO('mysql:host=localhost;dbname=zabbix', $DB['USER'], $DB['PASSWORD']);
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='UTF-8' />
	<title>Zabbix Database Size Calculator <?=ZBXCALC_VERSION ?></title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap-theme.min.css">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
</head>
<body>
<div class='container'>
	<h1>Zabbix Database Size Calculator <?=ZBXCALC_VERSION ?></h1>
	<div class='row'>
		<div class='col-lg-8'>
		<h2>Required storage <small>for history &amp; trends</small></h2>
		<p>Each monitored item is saved in de database per refresh rate. The table below lists the required storage for history and trend records.</p>
		<p>The firsti two columns show the actual amount of items and there refresh rate in Zabbix. The formula used to calculate the required storage is: <tt>days * (items / delay) * 24 * 3600 * bytes</tt></p>
		<table class='table table-condensed'>
			<tr>
				<th>Items</th>
				<th>Refresh rate</th>
				<th>Formula</th>
				<th>Records</th>
				<th>Bytes</th>
				<th>Storage</th>
			</tr>
			<tr>
				<th colspan='6'><em>History</em></th>
			</tr>
<?php
$historyTotalBytes = 0;
$historyTotalRecords = 0;
$historyTotalItems = 0;

foreach ($dbh->query('SELECT COUNT(itemid) AS count, delay FROM items GROUP BY delay ORDER BY delay') as $row) {
	$itemCount = $row['count'];
	$itemDelay = $row['delay'];

	$historyTotalItems += $row['count'];
	$historyRecordsRequired = $historyStoragePeriodDays * ($itemCount / $itemDelay) * 24 * 3600;
	$historyTotalRecords += $historyRecordsRequired;
	$historyBytesRequired = $historyRecordsRequired * $historyBytesPerRecord;
	$historyTotalBytes += $historyBytesRequired;
	echo "\t<tr>\n"
		."\t\t<td class='text-right'>".$itemCount."</td>\n"
		."\t\t<td>".$itemDelay."</td>\n"
		."\t\t<td>".$historyStoragePeriodDays." &times; (".$itemCount." / ".$itemDelay.") &times; 24 &times; 3600 &times; ".$historyBytesPerRecord."</td>\n"
		."\t\t<td class='text-right'>".$historyRecordsRequired."</td>\n"
		."\t\t<td class='text-right'>".$historyBytesRequired."</td>\n"
		."\t\t<td>".formatBytes($historyBytesRequired)."</td>\n"
		."\t</tr>\n";
}
?>
			<tr class='active'>
				<td colspan='3' class='text-right'>Total history</td>
				<td class='text-right'><?php echo $historyTotalRecords ?></td>
				<td class='text-right'><?php echo $historyTotalBytes ?></td>
				<td><?php echo formatBytes($historyTotalBytes) ?></td>
			</tr>
<?php
$trendRecordsRequired = $trendStoragePeriodDays * ($historyTotalItems / 3600) * 24 * 3600;
$trendBytesRequired = $trendRecordsRequired * 60;
$trendGigabytesCount = $trendBytesRequired / 1024 / 1024 / 1024;
?>
			<tr>
				<th colspan='6'><em>Trends</em></th>
			</tr>
<?php
echo "\t<tr>\n"
	."\t\t<td class='text-right'>".$historyTotalItems."</td>\n"
	."\t\t<td>3600</td>\n"
	."\t\t<td>".$trendStoragePeriodDays." &times; (".$historyTotalItems." / 3600) &times; 24 &times; 3600 &times; ".$trendBytesPerRecord."</td>\n"
	."\t\t<td class='text-right'>".$trendRecordsRequired."</td>\n"
	."\t\t<td class='text-right'>".$trendBytesRequired."</td>\n"
	."\t\t<td>".formatBytes($trendBytesRequired)."</td>\n"
	."\t</tr>\n";
?>
			<tr class='active'>
				<td colspan='3' class='text-right'>Total history + trends</td>
				<td class='text-right'><?php echo $historyTotalRecords + $trendRecordsRequired ?></td>
				<td class='text-right'><?php echo $historyTotalBytes + $trendBytesRequired ?></td>
				<td><?php echo formatBytes(($historyTotalBytes + $trendBytesRequired)) ?></td>
			</tr>
		</table>


<h2>Database table size <small>actual table status</small></h2>
<p>This table lists the actual table size of the Zabbix database tables</p>
<table class='table table-condensed'>
	<tr>
		<th>Table</th>
		<th>Data length</th>
		<th>Index length</th>
		<th>Data free</th>
		<th>Total</th>
		<th>Total GB</th>
	</tr>
<?php
$tables = array(
	'events',
	'history',
	'history_log',
	'history_str',
	'history_text',
	'history_uint',
	'trends',
	'trends_uint',
);

foreach ($dbh->query('SHOW TABLE STATUS') as $row) {
	if (!in_array($row['Name'],$tables)) {
		continue;
	}

	$tableData = $row['Data_length'];
	$tableIndex = $row['Index_length'];
	$tableFree = $row['Data_free'];
	$tableTotal = $tableData + $tableIndex + $tableFree;
	$tableTotalGB = $tableTotal / 1024 / 1024 / 1024;
	
	echo "\t<tr>\n"
		."\t\t<td>".$row['Name']."</td>\n"
		."\t\t<td class='text-right'>".$tableData."</td>\n"
		."\t\t<td class='text-right'>".$tableIndex."</td>\n"
		."\t\t<td class='text-right'>".$tableFree."</td>\n"
		."\t\t<td class='text-right'>".$tableTotal."</td>\n"
		."\t\t<td class='text-right'>".formatBytes($tableTotal)."</td>\n"
		."\t</tr>\n";
}
?>
			</table>	
		</div><!--col-lg-8-->
		<div class='col-lg-4'>
			<h2>Variables</h2>
			<p>Variables used to calculate the required storage</p>
			<div class='panel panel-default'>
				<table class='table table-condensed'>
					<tr>
						<td>History storage period:</td>
						<td><?php echo $historyStoragePeriodDays; ?></td>
					</tr>
					<tr>
						<td>Trend storage period:</td>
						<td><?php echo $trendStoragePeriodDays; ?></td>
					</tr>
					<tr>
						<td>History bytes per record:</td>
						<td><?php echo $historyBytesPerRecord; ?></td>
					</tr>
					<tr>
						<td>Trend bytes per record:</td>
						<td><?php echo $trendBytesPerRecord; ?></td>
					</tr>
				</table>
			</div>
		</div><!--col-lg-4-->
	</div><!--row-->
	<footer>
		<hr />
		<address>
			<strong>Author: Jan Bouma (acropia)</strong><br />
			GitHub repo: <a href='https://github.com/acropia/Zabbix-Database-Size-Calculator' target='_blank'>https://github.com/acropia/Zabbix-Database-Size-Calculator</a>
		</address>
	</footer>
</div><!--container-->
</body>
</html>
