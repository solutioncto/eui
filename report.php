/* Copyright Eric Baller and other contributors; Licensed MIT */

<?php
// <link href="font-awesome-4.1.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
require_once 'bpd.php';

error_reporting(E_ALL);
ini_set('display_errors', 'On');

	$lead = getLead($codename);
	fprintf($log,"\n-- lead --\n");
	fwrite($log, print_r($lead, TRUE));
/** sample lead objects:
btu: 50708000
codename: "kgn5y"
electric: 9000
eui: "20"
fuel: "Gas"
gas: 200
id: "4371"
source: "null"
sqft: 2500
step: "1"
zip: "12345"
zone: "5"

btu: 50708000
codename: "kgn5y"
elecRate: "0.13"
electric: 9000
estimated: "1"
eui: "20"
finRate: "4.0"
finTerm: "30"
fuel: "Gas"
fuelRate: "1.151"
gas: 200
hers: ""
id: "4374"
savings: "0"
source: "null"
sqft: 2500
step: "5"
zip: "12345"
zone: "5"
***/
	if(isset($lead['zip'])) {
		$zip = $lead['zip'];
		$place = getCity($zip);
		} else {
		$zip = "N/A";
		$place = "Unspecified";
		}

	// defaults used if user skips intermediate steps and jumps right to report
	$fuel = isset($lead['fuel']) ? $lead['fuel'] : 'N/A';
	$electric = isset($lead['electric']) ? $lead['electric'] : 0;
	$gas = isset($lead['gas']) ? $lead['gas'] : 0;
	$sqft = isset($lead['sqft']) ? $lead['sqft'] : 0;
	$elecRate = isset($lead['elecRate']) ? $lead['elecRate'] : 0.3;	// default rate from js/savings.js
	$estimated = isset($lead['estimated']) ? ($lead['estimated']?"Estimated":"based on Actuals") : "Estimated";
	$finRate = isset($lead['finRate']) ? $lead['finRate'] : 4;		// default from quick.html
	if(isset($lead['finTerm'])) {
		$finTerm = min(max($lead['finTerm'], 1), 100);
		} else {
		$finTerm = 30;		// default from quick.html
		}
	$fuelRate = isset($lead['fuelRate']) ? $lead['fuelRate'] : 2;
	$hers = isset($lead['hers']) ? ($lead['hers'] ? $lead['hers']:"Not Rated") : "Not Rated";
	$savings = isset($lead['savings']) ? $lead['savings'] : "N/A";
	$zone = getLeadZone($lead);		// e.g. 5
	$czd = getDoeZone($lead);		// e.g. "5A Cool - Humid (Chicago-IL)"
	$euiArray = getEuiArray($lead);
	$eui = number_format($euiArray['eui'],1);
	$elecCost = ($electric * $elecRate)/12;
	$fuelCost = ($gas * $fuelRate)/12;
	$yourCost = 12 * ($elecCost + $fuelCost);

	// call BPD
	global $environment;
	if ($environment == "production") {
		// $result=bpd($state,$sqft,$log);		// actual
		$result=bpdZone($czd,$sqft,$log);		// actual
		} else {
		$result=bpdStub($czd,$sqft,$log);		// placeholder
		}

	if(!$result) {
		echo "<html>Sorry, the Building Performance Database is temporarily unavailable.</html>";
		exit();
		}

	$scatterplot=$result["scatterplot"];
	$totals=$result["totals"];
	$metadata=$result["metadata"];

	$total = (int)$totals["number_of_buildings_in_bpd"];
	$found = (int)$totals["number_of_matching_buildings"];
	if( $found < 1 ) {
		$statement="Unable to find any homes in your geography with a similar square footage in the database of ".$totals['number_of_buildings_in_bpd'].".";
		}
	if( $found >= 1000 ) {
		$statement="Using 1000 random entries of the ".$totals['number_of_matching_buildings']." matching buildings found in the database of ".$totals['number_of_buildings_in_bpd'].".";
		$found=1000;
		} else {
		$statement="Using the {$found} matching buildings from the database of {$total}.";
		}
	$buckets=array();
	for($i=0;$i<=15;$i++) {
		$buckets[$i]=0;
		}
	$sortEui=array(); $sortElec=array(); $sortFuel=array();
	$response = "[";
	for($i=0;$i<$found;$i++) {
		$x=$scatterplot[$i][0];
		$elecY=$scatterplot[$i][1];
		$fuelY=$scatterplot[$i][2];
		$y=$elecY+$fuelY;

		$y = min($y,120);	// limit chart to max just under chart y-axis max, below
		$sortEui[]=$y;
		$elecY = min($elecY,120);
		$sortElec[]=$elecY;
		$fuelY = min($fuelY,120);
		$sortFuel[]=$fuelY;

		$bucket=floor($y/10);

		$buckets[$bucket]++;
		if ($i==0) {
			$response.= "[{$x},{$y}]";
			} else {
			$response.= ",[{$x},{$y}]";
			}
		}
	$response.= "]";
	$yvalues=array();
	$ycolors=array();
	for($i=0;$i<=12;$i++) {
		$yvalues[]=$buckets[$i];
		$ycolors[]='"blue"';
		}
// placeholder
	$ycolors[floor($eui/10)]='"red"';
	$yvaules[floor($eui/10)]=$yvalues[floor($eui/10)]+1;

	// find quartiles
	sort($sortEui);
	$q=array();
    $q[]=$sortEui[floor($found/4)];	// EUI of the 25th percentile home
    $q[]=$sortEui[floor($found/2)];
    $q[]=$sortEui[3*floor($found/4)];
	$avgEui=$q[1];	// mid-point

	// find quartiles
	sort($sortElec);
	$qElec=array();
    $qElec[]=$sortElec[floor($found/4)];	// EUI of the 25th percentile home
    $qElec[]=$sortElec[floor($found/2)];
    $qElec[]=$sortElec[3*floor($found/4)];

	// find quartiles
	sort($sortFuel);
	$qFuel=array();
    $qFuel[]=$sortFuel[floor($found/4)];	// EUI of the 25th percentile home
    $qFuel[]=$sortFuel[floor($found/2)];
    $qFuel[]=$sortFuel[3*floor($found/4)];

	// select appropriate emoji for comparison table. "Anguished Emoji Png Transparent Icon" and others by Clipart.info are licensed under CC BY 4.0
	// if($pain>0){
	$debug="eui=".$eui.",q:";
	fwrite($log, $debug);
	fwrite($log, print_r($q, TRUE));
	if ($eui>$q[2]) {
		$painImg = "images/anguish.png";
		} else if ($eui>$q[1]) {
		$painImg = "images/grimace.png";
		} else if ($eui>$q[0]) {
		$painImg = "images/slightly.png";
		} else {
		$painImg = "images/smiling.png";
		}

	// find the index of closest entry so we can find %age
	$low=0; $high=$found;
	while ($low != $high) {
    	$mid = floor(($low + $high) / 2); // binary sort
    	if ($sortEui[$mid] <= $eui) {
        	/* This index, and everything below it, must not be 
         	* greater than what we're looking for because this element is
			* no greater than the element.
         	*/
        	$low = $mid + 1;
    	}
    	else {
        	/* This element is at least as large as the element, so anything
			* after it can't be the first element that's at least as large.
         	*/
        	$high = $mid;
    	}
	}
	$lower=round(100*$high/$found);		// found the index; where is it?
	$higher=100-$lower;
	$positionEui = round(5*$eui+80-35);	// position + offset for start of bar - half width of image
	$positionElec = round(5*$euiArray['elec']+80-35);	// position + offset for start of bar - half width of image
	$positionFuel = round(5*$euiArray['fuel']+80-35);	// position + offset for start of bar - half width of image

	// prep analytic values
	$debug="zone=".$zone.",zoneEUI=".$avgEui.", EUI=".$eui."\n";
	fwrite($log, $debug);
	$avgCost = $yourCost / ($eui/$avgEui);		// EUI 80/50 is 1.6; EUI 30/50 is 0.6 

	// Straight line cost over the term
	$avgTerm = $avgCost * $finTerm;
	$yourTerm = $yourCost * $finTerm;
	$pain = $yourCost - $avgCost;
	$painTerm = $pain * $finTerm;

	// Net Present Value
	// $pain = $yourCost * ($eui/$zones[$zone][1]-1) / 12;		// EUI 80/50-1 is 0.6; EUI 30/50-1 is -.4 (negtive pain is pleasure)
	$debug="avgCost=".$avgCost.",yourCost=".$yourCost.", avgNPV=".$avgNPV."\n";
	fwrite($log, $debug);
	$avgNPV = 0;
	$yourNPV = 0;
	for ($i = 1; $i <= $finTerm; $i++) {
			$avgNPV += $avgCost / (1+$finRate/100)**($i-1);
			$yourNPV += $yourCost / (1+$finRate/100)**($i-1);
			}
	// $avgNPV = $yourNPV - $painNPV;
	$painNPV = $yourNPV - $avgNPV;
	$debug="avgNPV=".$avgNPV.",yourNPV=".$yourNPV.", painNPV=".$painNPV."\n";
	fwrite($log, $debug);

	switch($fuel) {
		case 'Oil':
        	$oldFuel = 'Oil';
        	$oldUnit = 'gallon';
			break;
		case 'Propane':
        	$oldFuel = 'Propane';
        	$oldUnit= 'gallon';
			break;
		case 'Gas':
        	$oldFuel = 'Gas';
        	$oldUnit = 'therm';
			break;
		case 'CCF':
        	$oldFuel = 'Gas';
        	$oldUnit = 'CCF';
			break;
		case 'MCF':
        	$oldFuel = 'Gas';
        	$oldUnit = 'MCF';
			break;
		case 'Other':
        	$oldFuel = 'Other';
        	$oldUnit = 'BTU';
			break;
		default:
			break;
		}

	// heredoc can't run functions, so format as comma thousand strings first
	$TXTelectric=number_format($electric);
	$TXTgas=number_format($gas);
	$TXTsqft=number_format($sqft);
	$TXTyourTerm=number_format($yourTerm);
	$TXTavgTerm=number_format($avgTerm);
	$TXTpainTerm=number_format(abs($painTerm));
	$TXTyourNPV=number_format($yourNPV);
	$TXTavgNPV=number_format($avgNPV);
	$TXTpainNPV=number_format(abs($painNPV));
	$TXTyourCost=number_format($yourCost);
	$TXTavgCost=number_format($avgCost);
	$TXTyourCostMonthly=number_format($yourCost/12);
	$TXTavgCostMonthly=number_format($avgCost/12);
	$TXTdifCost=number_format(abs($avgCost-$yourCost));
	$TXTdifCostMonthly=number_format(abs($avgCost-$yourCost)/12);
	$TXTdifEui=number_format(abs($avgEui-$eui),1);
	$TXTavgEui=number_format($avgEui,1);
	$TXTfinTerm=number_format($finTerm);
	if ($finTerm>1) {
		$TXTfinTerm .= " Years";
		} else {
		$TXTfinTerm = " a Year";
		}
	$TXTfinRate=number_format($finRate,2);

if ($eui < $avgEui) {
	$comment = "top " . $higher . "% of most";
	$difference = "Your Gain";
	} else {
	$comment = "bottom " . (int)(100-$lower) . "% of least";
	$difference = "Your Loss";
	}
	$comment = "
			The EUI of your home is {$eui}, which puts your home in the {$comment} efficient homes in the Department of Energy Building Performance Database for residentail properties in climate zone " . $zone . ". The Energy Use Intensity (EUI) of a building provides a standard comparison calculated from energy use and size.
			";
	if(strlen($fuel)>2) {
		$comment2 = "The Building Performance Database includes both electricity and fuel usage. " . $fuel . " isn't broken out from other fuels, but the following charts separate out electric and non-electric EUI, so you can see how you compare in each.";
		} else {
		$comment2 = "The Building Performance Database includes both electricity and fuel usage. Since you indicated you only use electricity, the following chart shows only homes in the database that also had no additional fuel use.";
		}

?>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/highcharts.js"></script>
<script type="text/javascript" src="js/data.js"></script>
<script type="text/javascript" src="js/exporting.js"></script>
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/bootstrap.min.js"></script>
<!-- Custom CSS -->
<link href="css/modern-business.css" rel="stylesheet">
<link href="css/cto.css" rel="stylesheet">

<script>
$(document).ready(function() {
var scatterdata= <?php echo $response;?>;
// scatterdata[] = [x: 5000, y: 100];
// scatterdata[] = {x: 5000, y: 100, marker:{ fillColor: 'red'} };
console.log("starting");
    var chart = new Highcharts.Chart({
                chart: {
					animation: false,
                    renderTo: 'mychart1',
                    type: 'scatter',
					width: 730,
					height: 260
                },
				plotOptions: {
					series: { enableMouseTracking: false, shadow: false, animation: false },
					scatter: { turboThreshold: 1001}		// prevents assumption that all points look the same
					 },
                title: {
                    text: 'Energy Use Intensity (EUI) of <?php echo $found;?> similar homes in climate zone <?php echo $zone;?>'
                },
                yAxis: {
					min: 0,
					max: 120,
					maxPadding: 0,
					tickInterval: 20,
                    title: {
                        text: 'Energy Use Intensity'
                    }
                },
  				credits: {
      				enabled: false
  				},
				exporting: {
         			enabled: false
				},
                legend: {
                    enabled: false
                },
                xAxis: {
					min: 0,
                    title: {
                        text: 'Square Feet of Conditioned Space'
                    }
                },
                series: [{
					name: 'series 1',
					data: scatterdata
					}]
            });
chart.series[0].addPoint({marker:{fillColor:'red',radius: 6}, x: <?php echo $sqft;?>, y: <?php echo $eui;?>}, true, true);
    var categories = [
                    '0-9',
                    '10-19',
                    '20-29',
                    '30-39',
                    '40-49',
                    '50-59',
                    '60-69',
                    '70-79',
                    '80-89',
                    '90-99',
                    '100-109',
                    '110-119',
                    '120+'
                ];
   	var yvalues = [<?php echo join($yvalues,',');?>];
   	var ycolors = [<?php echo join($ycolors,',');?>];
	var data = [];
console.log("next");
	for(var i=0;i<=12;i++) {
        data.push({y:yvalues[i], color: ycolors[i]});
		}
console.log("values, colors, data");
console.log(yvalues);
console.log(ycolors);
console.log(data);
    var chart2 = new Highcharts.Chart({

                chart: {
					animation: false,
                    renderTo: 'mychart0',
                    type: 'column',
					width: 730,
					height: 260
                },
				plotOptions: { series: { enableMouseTracking: false, shadow: false, animation: false } },
                title: {
                    text: 'Energy Use Intensity (EUI) of <?php echo $found;?> similar homes in climate zone <?php echo $zone;?>'
                },
                yAxis: {
					min: 0,
                    title: {
                        text: 'Number of Homes'
                    }
                },
  				credits: {
      				enabled: false
  				},
				exporting: {
         			enabled: false
				},
                legend: {
                    enabled: false
                },
                xAxis: {
					type: 'category',
                	categories: categories
                },
                series: [{
					name: 'series 1',
					data: data
					}]
            });


	var pos = <?php echo $positionEui;?>;
	var q=[<?php echo $q[0];?>,<?php echo $q[1];?>,<?php echo $q[2];?>];
	var c  = document.getElementById('myCanvas');
	var ct = c.getContext("2d");
	drawSpectrum(q,ct,pos);
console.log(pos);
console.log(q);

	var posE = <?php echo $positionElec;?>;
	var qElec=[<?php echo $qElec[0];?>,<?php echo $qElec[1];?>,<?php echo $qElec[2];?>];
	var c  = document.getElementById('myElecCanvas');
	var cte = c.getContext("2d");
	drawSpectrum(qElec,cte,posE);
console.log(posE);
console.log(qElec);

<?php
	if (strlen($fuel)>2) {
?>
		var posF = <?php echo $positionFuel;?>;
		var qFuel=[<?php echo $qFuel[0];?>,<?php echo $qFuel[1];?>,<?php echo $qFuel[2];?>];
		var c  = document.getElementById('myFuelCanvas');
		var ctf = c.getContext("2d");
		drawSpectrum(qFuel,ctf,posF);
console.log(posF);
console.log(qFuel);
<?php
	}
?>

function drawSpectrum(q,ctx,position)
{
	var xBase = 80;
	var xPoint = xBase;
	var yPoint = 90;
	var colors=['#00ff00','#ccff66','#ffcc66','#ff0000'];

	make_base(ctx,position);

	ctx.font = "12px Arial";
	ctx.fillText("0",82,123);
	if(q[0]>1) {		// don't squeeze in the bottom number if not necessary
		ctx.fillText(Math.floor(q[0]),Math.floor(5*q[0]+80-6),123);
		}
	ctx.fillText(Math.floor(q[1]),Math.floor(5*q[1]+80-7),123);
	ctx.fillText(Math.floor(q[2]),Math.floor(5*q[2]+80-7),123);
	if(q[2]<112) {		// dont squeeze in the top number if not necessary
		ctx.fillText("120",658,123);
		}

	var colorcode = 0;
	for(var i =0; i<4;i++)
	{
      colorcode = colors[i];
	  zPoint = (i==3) ? 680 : xBase + 5*q[i];
	  colorsize = zPoint - xPoint;

      ctx.beginPath();
      ctx.fillStyle = colorcode ;      
      ctx.rect(xPoint, yPoint, colorsize, 20);
      xPoint = zPoint;
      ctx.fill();
      ctx.stroke();
      ctx.closePath();
	}
}

function make_base(ctx,position)
{
  image1 = new Image();
  image1.src = 'images/badHouse.png';
  image1.onload = function(){
    ctx.drawImage(image1, 680, 50, 75, 73);
  }
  image2 = new Image();
  image2.src = 'images/goodHouse.png';
  image2.onload = function(){
    ctx.drawImage(image2, 0, 50, 75, 73);
  }
  image3 = new Image();
  image3.src = 'images/yourHouse.png';
  image3.onload = function(){
    ctx.drawImage(image3, position, 8, 70, 80);
  }
}


});
</script>
<div id="page-wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12">
					<h1>Your Home Comparison Report
                    <small>EUI = <?php echo $eui;?></small>
					</h1>
<?php
echo $comment;
?>
<p>
						<canvas id="myCanvas" width="755" height="125">
							Your browser does not support the HTML5 canvas tag.
						</canvas>
<p>Each colored bar represents one quarter of the homes. The best quarter of homes fall in the dark green section of the bar. The next best in the light green section, and so on.
			</div> <!-- /.col -->
		</div> <!-- /.row -->
		<div class="row">
			<div class="col-xs-12">
			<h3>Details from the Building Performance Database</h3>
			</div> <!-- /.col -->
		</div> <!-- /.row -->
		<div class="row">
			<div class="col-xs-12">
					The next two charts show where you are against homes up to <?php echo (int)(1.5*$sqft); ?> square feet. The <span class="redText">red</span> column includes your
					home; the <span class="redText">red</span> dot on the next chart is your home against all the comparision homes.
					<div id="mychart0"></div>
			</div> <!-- /.col -->
		</div> <!-- /.row -->
		<div class="row">
			<div class="col-xs-12">
					<div id="mychart1"></div>
			</div> <!-- /.col -->
		</div> <!-- /.row -->

		<div style="page-break-before:always;">
<?php
echo <<< EOF
                <h3 class="page-header">Your Inputs
                </h3>
        <!-- Content Row -->
        <div class="row">
            <div class="col-xs-4">
                <div class="panel panel-primary text-center">
                    <div class="panel-heading">
                        <h3 class="panel-title">Building</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item-narrow">{$TXTsqft} Square Feet</li>
                        <li class="list-group-item-narrow">Climate Zone {$zone}</li>
                        <li class="list-group-item-narrow">HERS Rated: {$hers}</li>
                        <li class="list-group-item-narrow">Usage is {$estimated}</li>
                        <li class="list-group-item-narrow">{$place} ({$zip})</li>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-xs-4">
                <div class="panel panel-primary text-center">
                    <div class="panel-heading">
                        <h3 class="panel-title">Energy</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item-narrow">Electric: $TXTelectric kWh/year </li>
                        <li class="list-group-item-narrow period">\${$elecRate}/kWh</li>
                        <li class="list-group-item-narrow">{$oldFuel}: $TXTgas {$oldUnit}s/year</li>
                        <li class="list-group-item-narrow period">\${$fuelRate} per {$oldUnit}</li>
                        <li class="list-group-item-narrow">Annual Cost: \${$TXTyourCost}</li>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-xs-4">
                <div class="panel panel-primary text-center">
                    <div class="panel-heading">
                        <h3 class="panel-title">Present Value</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item-narrow">Term: {$TXTfinTerm}</li>
                        <li class="list-group-item-narrow">Rate: {$TXTfinRate}%</li>
                        </li>
                    </ul>
                </div>
            </div>
        	<small>If you want to make any changes, follow the link in your email to update and rerun the report.</small>
        </div>
        <!-- /.row -->

        <h3 class="page-header">The Results</h3>
        <!-- Content Row -->
        <div class="row">
            <div class="col-xs-4">
                <div class="panel panel-primary text-center">
                    <div class="panel-heading">
                        <h3 class="panel-title">You</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">Monthly: \${$TXTyourCostMonthly}</li>
                        <li class="list-group-item">Over {$TXTfinTerm}: \${$TXTyourTerm}</li>
                        <li class="list-group-item">Building EUI: {$eui}</li>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-xs-4">
                <div class="panel panel-primary text-center">
                    <div class="panel-heading">
                        <h3 class="panel-title">Zone Average</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">Monthly: \${$TXTavgCostMonthly}</li>
                        <li class="list-group-item">Over {$TXTfinTerm}: \${$TXTavgTerm}</li>
                        <li class="list-group-item">Building EUI: {$TXTavgEui}</li>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-xs-4">
                <div class="panel panel-primary text-center">
                    <div class="panel-heading">
                        <h3 class="panel-title">{$difference}</h3>
                    </div>
                    <ul id="difGroup" class="list-group">
                        <li class="list-group-item"><img src='{$painImg}' height='18'/> Monthly: \${$TXTdifCostMonthly}</li>
                        <li class="list-group-item"><img src='{$painImg}' height='18' /> Over {$TXTfinTerm}: \${$TXTpainTerm}</li>
                        <li class="list-group-item"><img src='{$painImg}' height='18' /> Present Value: \${$TXTpainNPV}</li>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- /.row -->
EOF;
?>
		</div>

		<div class="row">
			<div class="col-xs-12">
					<h3>Detailed Comparisons</h3>
<?php
echo $comment2;
?>
<p>
					<canvas id="myElecCanvas" width="755" height="125">
						Your browser does not support the HTML5 canvas tag.
					</canvas>
					<h4 class="ctr">Your Electric Use</h4>
<?php
if (strlen($fuel)>2) {
echo <<<EOF
					<canvas id="myFuelCanvas" width="755" height="125">
						Your browser does not support the HTML5 canvas tag.
					</canvas>
EOF;
					echo "<h4 class='ctr'>Your " . $oldFuel . " Use</h4>";
	}
?>
			</div> <!-- /.col -->
		</div> <!-- /.row -->
	</div> <!-- /.container -->
</div> <!-- /.wrapper -->
