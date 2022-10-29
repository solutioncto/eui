/* Copyright Eric Baller and other contributors; Licensed MIT */

$(function(){
	quote_detail = "step=-1";
console.log("impression");
    $.ajax({
			type: "POST",
			url: "savings.php",
			data: quote_detail,
			dataType: "json",
			success: saveResult
     		});

	// static fuel prices, also copied in report.php
    var prices = {
            'Oil'  : '$3.00',
            'Gas'  : '$1.00',
            'MCF'  : '$10.00',
            'Propane'  : '$2.00',
            'Electric'  : '$0.20',
            'HeatPump'  : '$0.20',
            'Other'  : '$0.00001'
		};

    // static fuel BTU conversions
    var eff = {};
		eff['Oil']=140000;
		eff['Gas']=100000;
		eff['CCF']=100000;
		eff['MCF']=1000000;
		eff['Propane']=91600;
		eff['Electric']=3412;
		eff['HeatPump']=3412;
		eff['Other']=1;
		eff['None']=0;

	// hold fuel between submissions
	var fuel="";

	var source = "test";
	source = getURLParameter("source");

	var codename = "";
	var step = 0;

	// climate zone quartiles
	var zones = [
				[ 25, 50, 75 ],	// 0 place holder, used when lookup fails (outside US, for example)
				[ 25, 50, 75 ],	// 1
				[ 25, 50, 75 ],	// 2
				[ 25, 50, 75 ],	// 3
				[ 25, 50, 75 ],	// 4
				[ 25, 50, 75 ],	// 5
				[ 25, 50, 75 ],	// 6
				[ 25, 50, 75 ],	// 7
				[ 25, 50, 75 ]	// 8
			];

	var euiQuartileText={};
	euiQuartileText[1]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";
	euiQuartileText[2]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";
	euiQuartileText[3]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";
	euiQuartileText[4]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";
	euiQuartileText[5]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";
	euiQuartileText[6]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";
	euiQuartileText[7]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";
	euiQuartileText[8]="Below 25: the most efficient homes<br>25 - 50: better than average<br>51 - 75: below average<br>Above 75: the worst homes";

	function saveResult(data) {
 		if (data == 'save_failed') {
  			alert('Form save failed, please contact your administrator');
  			return false;
 		} else {
			updateBenefit(data);
			codename = data["codename"];
  			return false;
 		}
	}

	function saveCost(data) {
console.log("saveCost:");
var tmp = serialize(data);
console.log(tmp);
 		if (data == 'save_failed') {
  			alert('Form save failed, please contact your administrator');
  			return false;
 		} else {
			updateBenefit(data);
			codename = data["codename"];
    		$('#priceSection').hide();	// hide initial rate input section
    		$('#financeSection').show();	// now ask for finance inputs
    		$('#priceInputs').show();	// show recalculate section
  			return false;
 		}
	}

	function saveFinance(data) {
console.log("saveFinance:");
 		if (data == 'save_failed') {
  			alert('Form save failed, please contact your administrator');
  			return false;
 		} else {
			updateBenefit(data);
			codename = data["codename"];
    		$('#financeInputs').show();
    		$('#financeSection').hide();
    		$('#ratingSection').show();
  			return false;
 		}
	}

	function saveRating(data) {
console.log("saveRating:");
 		if (data == 'save_failed') {
  			alert('Form save failed, please contact your administrator');
  			return false;
 		} else {
			updateBenefit(data);
			codename = data["codename"];
    		$('#ratingInputs').show();
    		$('#ratingSection').hide();
  			return false;
 		}
	}

// update Benefit message based on current data
function updateBenefit(data) {

console.log("updateBenefit");
console.log(step);
console.log(data);
	var msg="";
	var xmsg="";
	var pain=0;

	if (step > 0) {
		msg = "Your Energy Use Intensity (EUI) is "+data["eui"];
		
		// instead of zone data, use nationwide residential average EUI from https://2030ddx.aia.org/helps/National%20Avg%20EUI
		// pain = data["eui"]/zones[data["zone"]][1]-1;
		pain = (data["eui"]/43.8)-1;
		if(pain>0) {
			// msg += ", which is worse than the average EUI of " + zones[data["zone"]][1] + " in climate zone " + data["zone"] + ".";
			msg += ", which is worse than the national average EUI of 43.8. Comparisions specific to your climate zone (zone " + data["zone"] + ") are available in a complete report at the end of this survey.";
			} else {
			msg += ", which is better than the national average EUI of 43.8. Comparisions specific to your climate zone (zone " + data["zone"] + ") are available in a complete report at the end of this survey.";
			}
    	$('#euiTop').html(msg);
		}

	if (step >= 3) {	// show cost section

		var elecCost = (data["electric"] * data["elecRate"])/12;
		xmsg="$"+addCommas(elecCost.toFixed(2));
    	$('#elecCostResult').html(xmsg);

		var fuelCost = (data["gas"] * data["fuelRate"])/12;
		xmsg="$"+addCommas(fuelCost.toFixed(2));
    	$('#fuelCostResult').html(xmsg);

		var yourCost = 12 * (elecCost + fuelCost);
		// pain = yourCost * (1-zones[data["zone"]][1]/data["eui"]) / 12;
		pain = yourCost * (data["eui"]/zones[data["zone"]][1]-1) / 12;
		if(pain>0) {
			msg = "At your average cost of energy, that's a waste of $" + addCommas(pain.toFixed(2)) + " every month.";
			} else {
			pain *= -1;
			msg = "At your average cost of energy, that's a savings of $" + addCommas(pain.toFixed(2)) + " every month.";
			}
    	$('#costTop').html(msg);
		}

	if (step >= 4) {	// show finance section as well
		var fin=0;
		var finMsg="";
		var rate = parseFloat(data["finRate"])/100.0;
		var term = parseInt(data["finTerm"]);
		var painNPV = 12*pain;
		var yourNPV = 12 * (elecCost + fuelCost);
		var avgNPV = yourNPV - painNPV;
finMsg="updateBenefits NPV calculation. Base pain=" + painNPV + ", your=" + yourNPV + ", avg=" + avgNPV + ".";
console.log(finMsg);
		for (var i = 1; i < term; i++) {
			painNPV += 12*pain / Math.pow(1+rate,i-1);
			yourNPV += 12*(elecCost+fuelCost) / Math.pow(1+rate,i-1);
			}
		avgNPV = yourNPV - painNPV;

		fin = elecCost + fuelCost;
		finMsg="$"+addCommas(fin.toFixed(2));
    	$('#myMonthCosts').html(finMsg);

		finMsg="$"+addCommas(pain.toFixed(2));
    	$('#avgMonthCosts').html(finMsg);

		finMsg="$"+addCommas(yourNPV.toFixed(2));
    	$('#myNPV').html(finMsg);

		finMsg="$"+addCommas(avgNPV.toFixed(2));
    	$('#avgNPV').html(finMsg);

		if (pain>0) {
			msg = "At " + data["finRate"] + "% cost of capital, that's a $" + addCommas(painNPV.toFixed(2)) + " loss over " + data["finTerm"] + " years!";
			} else {
			painNPV *= -1;
			msg = "At " + data["finRate"] + "% cost of capital, that's a $" + addCommas(painNPV.toFixed(2)) + " gain over " + data["finTerm"] + " years!";
			}

    	$('#finTop').html(msg);

		}

	if (step >= 5) {	// hers
		msg = "A more efficient home is a healthier, more controllable and more comfortable home.  Want to save your work and see a report?  Fill in your name and email below and we'll and get it sent over to you.";
		if (data["estimated"]) {
			msg += " You can come back later with your actual energy bills for a more accurate result.";
			}
		if (data["hers"]) {
			msg += " The report will also show how your home performance actually stacks up to your HERS rating.";
			}
    	$('#hersTop').html(msg);
		}
    // $('#benefit').html(msg);
}

	function addCommas(nStr) {
    	nStr += '';
    	x = nStr.split('.');
    	x1 = x[0];
    	x2 = x.length > 1 ? '.' + x[1] : '';
    	var rgx = /(\d+)(\d{3})/;
    	while (rgx.test(x1)) {
        	x1 = x1.replace(rgx, '$1' + ',' + '$2');
    	}
    	return x1 + x2;
	}


//
// http://stackoverflow.com/questions/1403888/get-url-parameter-with-jquery
function getURLParameter(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
}

function cap2() {	// capture content from all recalc fields
	var myData = [];
	var btu=0;

    units = $('#gas2').val().replace(/[^0-9]/g,"");
    fuel = $('#fuel2').val();	// not editable, still enumerated string
	energy= eff[fuel];
	btu += units * energy;
	myData["gas"]=units;
	myData["btu"]=btu;		// [btu] is fuel BTUs only

	// for EUI calc, add electric BTUs
   	var units = $('#electric2').val().replace(/[^0-9]/g,"");
	var energy= eff['Electric'];
	btu += units * energy;
	myData["electric"]=units;

    var sqft = $('#sqft2').val().replace(/[^0-9]/g,"");
	myData["sqft"]=sqft;

    units = $('#zip2').val().replace(/[^0-9]/g,"");
	myData["zip"]=units;

	// following not editable
	myData["fuel"]=fuel;

	var eui=Math.round(btu/sqft/1000);
	myData["eui"]=eui;

	// utility cost section
   	myData["elecRate"] = $('#elecRate2').val().replace(/[^0-9.]/g,"");
   	myData["fuelRate"] = $('#fuelRate2').val().replace(/[^0-9.]/g,"");

	// finance cost section
   	myData["finRate"] = $('#finRate2').val().replace(/[^0-9.]/g,"");
   	myData["finTerm"] = $('#finTerm2').val().replace(/[^0-9.]/g,"");

	// rating section
   	myData["hers"] = $('#hers2').val().replace(/[^0-9.]/g,"");
   	myData["estimated"] = $('#estEst2').prop('checked') ? 1 : 0;

	var savings=0;
	myData["savings"]=savings;

	myData["source"]=source;
	return myData;
}

// store content of page one in available fields
function sav1(fields) {
	var myData = [];
    var error = 0;
	var btu=0, energy=0, sqft=0;
	var units="", key2="";
    fields.each(function(){
            	var value = $(this).val();

                $(this).addClass('valid');
        		var vname = $(this).attr('id');
				switch(vname) {
						case 'electric':
       						units = $('#electric').val().replace(/[^0-9]/g,"");
							energy= eff['Electric'];
							btu += units * energy;
        					$('#electric2').val(value);
							myData["electric"]=units;
							break;
						case 'fuel':
       						units = $('#gas').val().replace(/[^0-9]/g,"");	// remove commas
       						fuel = value;	// one of our enumerated choices
							myData["fuel"]=fuel;
       						sqft = $('#sqft').val().replace(/[^0-9]/g,"");
							energy= eff[fuel];
							myData["btu"]= units * energy;		// myData[btu] is fuel BTUs only
							btu += units * energy;				// but add fuel + electric for calculating EUI
							// quote_detail += "&gas=" + units;
var msg = "Units="+units+", fuel="+fuel+", energy="+energy+", btu="+btu;
console.log(msg);
							switch(fuel) {
								case 'Oil':
        							$('#OldFuel').html('Fuel Oil');
        							$('#fueltype').html('Fuel Oil');
        							$('#OldUnit').html('gallons/year');
        							$('#fuelUnit').html(' / gallon');
        							$('#fuelRate').val(prices['Oil']);
									break;
								case 'Propane':
        							$('#OldFuel').html('Propane');
        							$('#fueltype').html('Propane');
        							$('#OldUnit').html('gallons/year');
        							$('#fuelUnit').html(' / gallon');
        							$('#fuelRate').val(prices['Propane']);
									break;
								case 'Gas':
        							$('#OldFuel').html('Natural Gas');
        							$('#fueltype').html('Natural Gas');
        							$('#OldUnit').html('therms/year');
        							$('#fuelUnit').html(' / therm');
        							$('#fuelRate').val(prices['Gas']);
									break;
								case 'CCF':
        							$('#OldFuel').html('Natural Gas');
        							$('#fueltype').html('Natural Gas');
        							$('#OldUnit').html('CCF/year');
        							$('#fuelUnit').html(' / CCF');
        							$('#fuelRate').val(prices['Gas']);
									break;
								case 'MCF':
        							$('#OldFuel').html('Natural Gas');
        							$('#fueltype').html('Natural Gas');
        							$('#OldUnit').html('MCF/year');
        							$('#fuelUnit').html(' / MCF');
        							$('#fuelRate').val(prices['MCF']);
									break;
								case 'Other':
        							$('#OldFuel').html('Other');
        							$('#fueltype').html('Other');
        							$('#OldUnit').html('BTUs/year');
        							$('#fuelUnit').html(' / BTU');
        							$('#fuelRate').val(prices['Other']);
									break;
								case 'None':
									$('#gas2').hide();
									$('#fuelRate').hide();
								default:
									break;
								}
        					$('#fuel2').val(fuel);
							break;
						case 'zip':
						case 'sqft':
            				if( value.length<3 ) {
                				$(this).addClass('error').removeClass('valid');
                				$(this).effect("shake", { times:3 }, 50);
                				error++;
								break;
								}
							// else: fall through and store
						default:
							// send value to PHP
							myData[vname]=value;
							// store value for use in step2
							key2="#"+vname+"2";
        					$(key2).val(value);
							key2+="="+value;
							// console.log(key2);
							break;
					}
        });        

        if(error) {
	        return false;
			}

		myData["source"]=source;

		var eui=Math.round(btu/sqft/1000);
		myData["eui"]=eui;
		
		// pre-fill contact form for later
		var myzip = $('#zip').val();
        $('#zip2').val(myzip);

		// pre-fill cost and benefit section
        $('#elecRate').val(prices['Electric']);

var msg = "sav1: myData=";
console.log(msg);
var myData_detail = serialize(myData);
console.log(myData_detail);
		return(myData);
}

function chartHouse(eui) {
	// for a 700 px image:
	var eui_margin=eui>90 ? 540 : eui<0 ? 100 : 100+5*eui;
	var eui_bottom=65;
	var msg = "EUI="+eui+",height="+eui_margin;
	console.log(msg);

	var newwidth = document.getElementById('euiscale').clientWidth;
	var newscale = newwidth/700;
	eui_margin=eui_margin*newscale;
	eui_bottom=eui_bottom*newscale;
	eui_width=80*newscale;
	var msg = "chartHouse-newwidth="+newwidth+",newscale="+newscale+",margin="+eui_margin+",eui_bottom="+eui_bottom+"eui_width="+eui_width;
	console.log(msg);
   	$('#eui').text('').css('margin-bottom',eui_bottom+'px').css('margin-left',eui_margin+'px');
	var euiImg = document.createElement("img");
	euiImg.src = 'images/yourHouse.png';
   	euiImg.style.width = eui_width+'px';
	return(euiImg);
}

serialize = function(obj) {
  var str = [];
  for(var p in obj)
    if (obj.hasOwnProperty(p)) {
      str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
    }
  return str.join("&");
}

    //first_step
    $('form').submit(function(){ return false; });
    $('#submit_first').click(function(){
     	console.log("first");
        //remove classes
        $('#first_step input').removeClass('error').removeClass('valid');

        //ckeck if inputs aren't empty
        var fields = $('#first_step input[type=text], #first_step select');
		var myData = sav1(fields);
        if(myData) {
                //slide steps
                $('#first_step').hide();
                $('#second_step').show();
        } else return false;
		var quote_detail = serialize(myData);
		step=1;
		quote_detail += "&step=1";

		var acc = document.getElementsByClassName("accordion");
		var accX = Math.min(3,Math.max(0,Math.floor(myData["eui"]/25)));
console.log(acc.length);
  		acc[accX].click();
var msg = "accX="+accX+", quote="+quote_detail;
console.log(msg);
        
		// pre-fill contact form for later
		var myzip = $('#zip').val();
        $('#zip2').val(myzip);

		// pre-fill cost and benefit section
        $('#elecRate').val(prices['Electric']);

		var euiImg=chartHouse(myData["eui"]);
		var euiDiv = document.getElementById('eui');
		euiDiv.appendChild(euiImg);

        $.ajax({
			type: "POST",
			url: "savings.php",
			data: quote_detail,
			dataType: "json",
			success: saveResult
     		});
    });

    $('#submit_again').click(function(){
     	console.log("resubmit");

	var myData = cap2();
	var quote_detail = serialize(myData);
	console.log(quote_detail);

	var euiImg=chartHouse(myData["eui"]);
	var euiDiv = document.getElementById('eui');
	euiDiv.appendChild(euiImg);

	quote_detail += "&step=1a&codename=" + codename;
	quote_detail += "&source=" + source;
        $.ajax({
		type: "POST",
		url: "savings.php",
		data: quote_detail,
		dataType: "json",
		success: saveResult
     		});

    });

    $('#submit_cost').click(function(){
     	console.log("cost calc");

	// move cost section to input2 form
   	var rate = 0;
	rate = $('#elecRate').val().replace(/[^0-9.]/g,"");
    $('#elecRate2').val(rate);
   	rate = $('#fuelRate').val().replace(/[^0-9.]/g,"");
    $('#fuelRate2').val(rate);

	// now use input2 section
	step=3;
	var quote_detail = "step=3&codename=" + codename + "&";
	var myData = cap2();
	quote_detail += serialize(myData);
	console.log(quote_detail);

	// quote_detail += "&source=" + source;
        $.ajax({
		type: "POST",
		url: "savings.php",
		data: quote_detail,
		dataType: "json",
		success: saveCost
     		});

    });

    $('#submit_finance').click(function(){
     	console.log("finance submit");

	// move finance section to input2 form
   	var rate = 0;
	rate = $('#finRate').val().replace(/[^0-9.]/g,"");
    $('#finRate2').val(rate);
   	rate = $('#finTerm').val().replace(/[^0-9.]/g,"");
    $('#finTerm2').val(rate);

	step=4;
	var quote_detail = "step=4&codename=" + codename + "&";

	var myData = cap2();
	quote_detail += serialize(myData);
	console.log(quote_detail);

	quote_detail += "&source=" + source;
        $.ajax({
		type: "POST",
		url: "savings.php",
		data: quote_detail,
		dataType: "json",
		success: saveFinance
     		});

    });

    $('#submit_rating').click(function(){
     	console.log("rating submit");

	// move rating section to input2 form
	var hers = $('#hers').val();
console.log(hers);
    $('#hers2').val(hers);
    hers = $('#estEst').prop('checked');
console.log(hers);
    $('#estEst2').prop('checked', hers);
hers = $('#estEst2').prop('checked');
console.log(hers);
    hers = $('#estAct').prop('checked');
console.log(hers);
    $('#estAct2').prop('checked', hers);
hers = $('#estAct2').prop('checked');
console.log(hers);

	step=5;
	var quote_detail = "step=5&codename=" + codename + "&";

	var myData = cap2();
	quote_detail += serialize(myData);
	console.log(quote_detail);

	quote_detail += "&source=" + source;
        $.ajax({
		type: "POST",
		url: "savings.php",
		data: quote_detail,
		dataType: "json",
		success: saveRating
     		});

    });

    $('#submit_email').click(function(){
     	console.log("email");

		var myData = cap2();
		console.log(myData);
		var quote_detail = serialize(myData);
		console.log(quote_detail);

		console.log("Done with EUI display");

		step=0;
		quote_detail += "&step=0&codename=" + codename;
		quote_detail += "&source=" + source;
        var value = $('#email').val();
        if( value.length<5 ) {
            $('#email').addClass('error');
            $('#email').effect("shake", { times:3 }, 50);
			return false;
			} else {
			quote_detail += "&email=" + value;
            }
        value = $('#first').val();
		quote_detail += "&first=" + value;

        console.log(quote_detail);
        $.ajax({
			type: "POST",
			url: "savings.php",
			data: quote_detail,
			success: function(data) {
    			if (data == "save_failed") {
					console.log("FAIL");
  					alert('Save failed, please contact your administrator');
  					return false;
    			} else {
					// var newurl = "http://myquote.net/site.php?id=" + contact_id + "#tabs-4";
        			// window.location.href = newurl;
    			}
}

     		});

            // display success
			$('#icontainer').html("<div id='message'></div>");
			$('#message').html("<strong>Your report is being prepared!</strong>")
			.append("Please look for an email from us.")
			.append("<p>To request a call back, please complete the form on the Contact Us tab, above.");

    });


    $('#calc').click(function(){
console.log("When is #calc clicked?");
       	var units = $('#OldUsage').val().replace(/[^0-9]/g,"");
		var fuel = $('#fuel').val();
		var energy= eff[fuel];
		var btu = units * energy;
    });

var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].onclick = function() {
    this.classList.toggle("active");
    var panel = this.nextElementSibling;
    if (panel.style.maxHeight){
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = panel.scrollHeight + "px";
    } 
  }
}

});
